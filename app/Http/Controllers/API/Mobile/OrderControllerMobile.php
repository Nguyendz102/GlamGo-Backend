<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CardItemModel;
use App\Models\CardModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderProductAttributeValueItemModel;
use App\Models\ProductAttributeModel;
use App\Models\ProductAttributeValuesModel;
use App\Models\ProductImagesModel;
use App\Models\ProductVariantModel;
use App\Models\ProductVariantValueImage;
use App\Models\ProductsModel;
use App\Models\Rating;
use App\Models\User;
use App\Models\UserAddress;
use App\Notifications\OrderStatusChangedNotification;
use App\Services\ChatBotService;
use App\Services\CouponService;
use App\Services\OneSignalService;
use App\Services\VnpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderControllerMobile extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly ChatBotService $chatBot,
        private readonly OneSignalService $oneSignal,
        private readonly VnpayPaymentService $vnpayPayment
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['nullable', 'integer', 'in:1,2,3,4,5'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $orders = OrderModel::where('user_id', $request->user()->id)
            ->where('status', '>', 0)
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', (int) $request->status);
            })
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $orders->getCollection()->transform(fn (OrderModel $order) => $this->formatOrderSummary($order));

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $orders,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = OrderModel::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with([
                'coupon',
                'items.product:id,name,code,image,price,price_sale',
                'items.orderProductAttributeValueItemModel',
            ])
            ->first();

        if (! $order) {
            return response()->json([
                'status' => 404,
                'message' => 'Don hang khong ton tai.',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $this->formatOrderDetail($order),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address_id' => ['nullable', 'integer'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['required_without:address_id', 'nullable', 'regex:/^(?:\+?[1-9]\d{1,14}|0[1-9]\d{8})$/'],
            'address' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['nullable', 'integer', 'in:1,2,3'],
            'coupon_code' => ['nullable', 'string', 'max:255'],
            'code_discount' => ['nullable', 'string', 'max:255'],
            'item_ids' => ['nullable', 'array'],
            'item_ids.*' => ['integer'],
        ], [
            'phone_number.required' => 'Vui long nhap so dien thoai.',
            'phone_number.regex' => 'So dien thoai khong dung dinh dang.',
            'address.required' => 'Vui long nhap dia chi giao hang.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($request) {
                $paymentMethod = (int) ($request->payment_method ?? 1);
                $deliveryAddress = null;

                if ($request->filled('address_id')) {
                    $deliveryAddress = UserAddress::where('id', $request->address_id)
                        ->where('user_id', $request->user()->id)
                        ->first();

                    if (! $deliveryAddress) {
                        abort(response()->json([
                            'status' => 422,
                            'message' => 'Dia chi giao hang khong hop le.',
                        ], 422));
                    }
                }

                $cart = CardModel::where('user_id', $request->user()->id)
                    ->where('status', 1)
                    ->with(['items.product'])
                    ->lockForUpdate()
                    ->first();

                if (! $cart || $cart->items->isEmpty()) {
                    abort(response()->json([
                        'status' => 422,
                        'message' => 'Gio hang dang trong.',
                    ], 422));
                }

                $selectedItemIds = collect($request->input('item_ids', []))
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id) => $id > 0)
                    ->unique()
                    ->values();
                $selectedItems = $selectedItemIds->isEmpty()
                    ? $cart->items
                    : $cart->items->whereIn('id', $selectedItemIds->all())->values();

                if ($selectedItems->isEmpty()
                    || ($selectedItemIds->isNotEmpty() && $selectedItems->count() !== $selectedItemIds->count())) {
                    abort(response()->json([
                        'status' => 422,
                        'message' => 'San pham duoc chon khong hop le.',
                    ], 422));
                }

                $subtotal = 0;

                foreach ($selectedItems as $item) {
                    $product = ProductsModel::where('id', $item->product_id)
                        ->where('status', 1)
                        ->first();

                    if (! $product) {
                        abort(response()->json([
                            'status' => 422,
                            'message' => 'San pham trong gio hang khong con hoat dong.',
                        ], 422));
                    }

                    $variant = $this->resolveVariantForCartItem($product->id, $item);
                    $price = $this->getSellPrice($product, $variant);
                    $item->price = $price;
                    $item->total_price = $price * (int) $item->quantity;
                    $item->product_variant_id = $variant?->id;
                    $item->save();

                    if ($variant) {
                        if ((int) $variant->quantity < (int) $item->quantity) {
                            abort(response()->json([
                                'status' => 422,
                                'message' => 'So luong ton kho khong du.',
                            ], 422));
                        }

                        $variant->decrement('quantity', (int) $item->quantity);
                    }

                    $subtotal += (float) $item->total_price;
                }

                [$coupon, $discount] = $this->couponService->resolve(
                    $request->coupon_code ?: $request->code_discount,
                    $subtotal
                );
                $totalPrice = max($subtotal - $discount, 0);
                $nameParts = $this->splitName(
                    $deliveryAddress?->recipient_name ?? $request->user()->name ?? ''
                );
                $user = User::whereKey($request->user()->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($paymentMethod === 2 && (float) $user->wallet_balance < $totalPrice) {
                    abort(response()->json([
                        'status' => 422,
                        'message' => 'So du vi khong du de thanh toan don hang.',
                    ], 422));
                }

                $order = OrderModel::create([
                    'user_id' => $request->user()->id,
                    'coupon_id' => $coupon?->id ?? 0,
                    'code' => $this->generateOrderCode(),
                    'transaction_id' => null,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentMethod === 2 ? 1 : 2,
                    'status' => $paymentMethod === 3 ? 0 : 1,
                    'country_id' => (int) ($request->country_id ?? $request->user()->contry_id ?? 1),
                    'total_price' => $totalPrice,
                    'email' => $request->email ?: $request->user()->email,
                    'first_name' => $request->first_name ?: $nameParts['first_name'],
                    'last_name' => $request->last_name ?: $nameParts['last_name'],
                    'postal_code' => $request->postal_code ?: '',
                    'address' => strip_tags($deliveryAddress?->address_line ?? $request->address),
                    'phone_number' => $deliveryAddress?->phone ?? $request->phone_number,
                    'note' => $request->note ? strip_tags($request->note) : '',
                ]);

                if ($paymentMethod === 2) {
                    $user->decrement('wallet_balance', $totalPrice);
                    $order->update([
                        'transaction_id' => 'WALLET-' . $order->code,
                    ]);
                }

                foreach ($selectedItems as $item) {
                    $orderItem = OrderItemModel::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'total_price' => $item->total_price,
                    ]);

                    OrderProductAttributeValueItemModel::create([
                        'order_item_id' => $orderItem->id,
                        'product_attribute_value_id' => json_encode($item->attribute_ids ?? []),
                        'product_atribute_id_name' => $item->attribute_name_id,
                        'personalise_name' => $item->personalise_name,
                    ]);
                }

                if ($paymentMethod !== 3) {
                    $this->removeCheckedOutItemsFromCart(
                        $cart,
                        $selectedItems->pluck('id')->all(),
                        $coupon?->id,
                        $subtotal,
                        $discount,
                        $totalPrice
                    );
                }

                return $order->fresh([
                    'coupon',
                    'items.product:id,name,code,image,price,price_sale',
                    'items.orderProductAttributeValueItemModel',
                ]);
            });

            $data = $this->formatOrderDetail($order);
            $message = 'Dat hang thanh cong';

            if ((int) $order->payment_method === 3) {
                $data['payment_url'] = $this->vnpayPayment->createPaymentUrl($order, $request);
                $message = 'Tao don hang thanh cong, vui long thanh toan.';
            }

            return response()->json([
                'status' => 201,
                'message' => $message,
                'data' => $data,
            ], 201);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $exception) {
            throw $exception;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            throw $exception;
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 422,
                'message' => $exception->errors()['coupon_code'][0],
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 500,
                'message' => 'Khong the tao don hang.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = OrderModel::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $order) {
            return response()->json([
                'status' => 404,
                'message' => 'Don hang khong ton tai.',
            ], 404);
        }

        if (! in_array((int) $order->status, [1, 2], true)) {
            return response()->json([
                'status' => 422,
                'message' => 'Don hang hien tai khong the huy.',
            ], 422);
        }

        DB::transaction(function () use ($order) {
            $order->load('items');

            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariantModel::whereKey($item->product_variant_id)
                        ->lockForUpdate()
                        ->increment('quantity', (int) $item->quantity);
                }
            }

            $order->update(['status' => 5]);
        });
        $statusText = $this->orderStatusText(5);
        $request->user()->notify(new OrderStatusChangedNotification($order, 5, $statusText));
        $message = "Don hang {$order->code} da chuyen sang trang thai: {$statusText}.";
        $this->oneSignal->sendToUser($request->user(), 'Cap nhat trang thai don hang', $message, [
            'type' => 'order_status_changed',
            'order_id' => $order->id,
            'order_code' => $order->code,
            'status' => 5,
            'status_text' => $statusText,
        ]);
        $this->chatBot->sendSystemMessage(
            $request->user(),
            $message
        );

        return response()->json([
            'status' => 200,
            'message' => 'Da huy don hang',
            'data' => $this->formatOrderSummary($order->fresh()),
        ]);
    }

    private function orderStatusText(int $status): string
    {
        return match ($status) {
            1 => 'Cho kiem tra',
            2 => 'Dang chuan bi hang',
            3 => 'Dang giao hang',
            4 => 'Da giao hang',
            5 => 'Da huy',
            default => 'Khong xac dinh',
        };
    }

    private function formatOrderSummary(OrderModel $order): array
    {
        return [
            'id' => $order->id,
            'code' => $order->code,
            'total_price' => (float) $order->total_price,
            'payment_method' => (int) $order->payment_method,
            'payment_status' => (int) $order->payment_status,
            'status' => (int) $order->status,
            'items_count' => (int) ($order->items_count ?? $order->items()->count()),
            'created_at' => $this->formatOrderDateTime($order->created_at),
        ];
    }

    private function removeCheckedOutItemsFromCart(
        CardModel $cart,
        array $selectedItemIds,
        ?int $couponId,
        float $checkoutSubtotal,
        float $checkoutDiscount,
        float $checkoutTotalPrice
    ): void {
        CardItemModel::where('card_id', $cart->id)
            ->whereIn('id', $selectedItemIds)
            ->delete();

        $remainingItems = $cart->items()->get();

        if ($remainingItems->isEmpty()) {
            $cart->update([
                'coupon_id' => $couponId,
                'subtotal' => $checkoutSubtotal,
                'discount' => $checkoutDiscount,
                'total_price' => $checkoutTotalPrice,
                'status' => 2,
            ]);

            return;
        }

        $remainingSubtotal = (float) $remainingItems->sum('total_price');

        $cart->update([
            'coupon_id' => null,
            'subtotal' => $remainingSubtotal,
            'discount' => 0,
            'total_price' => $remainingSubtotal,
            'status' => 1,
        ]);
    }

    private function formatOrderDetail(OrderModel $order): array
    {
        $items = $order->items->map(function (OrderItemModel $item) use ($order) {
            $attributeRow = $item->orderProductAttributeValueItemModel->first();
            $attributeIds = json_decode($attributeRow?->product_attribute_value_id ?: '[]', true) ?: [];
            $hasReviewed = Rating::where('user_id', $order->user_id)
                ->where('product_id', $item->product_id)
                ->exists();

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product?->name,
                'product_code' => $item->product?->code,
                'product_image' => $this->getSelectedProductImage(
                    $item->product_id,
                    $attributeIds,
                    $item->product?->image
                ),
                'price' => (float) $item->price,
                'quantity' => (int) $item->quantity,
                'total_price' => (float) $item->total_price,
                'attribute_name_id' => $attributeRow?->product_atribute_id_name,
                'attribute_name' => $attributeRow?->product_atribute_id_name
                    ? ProductAttributeModel::where('id', $attributeRow->product_atribute_id_name)->value('name')
                    : null,
                'attribute_ids' => $attributeIds,
                'attributes' => $this->formatAttributes($attributeIds),
                'personalise_name' => $attributeRow?->personalise_name,
                'has_reviewed' => $hasReviewed,
                'can_review' => (int) $order->status === 4 && ! $hasReviewed,
            ];
        })->values();

        $subtotal = (float) $items->sum('total_price');
        $discount = max($subtotal - (float) $order->total_price, 0);

        return [
            'id' => $order->id,
            'code' => $order->code,
            'user_id' => $order->user_id,
            'coupon_code' => $order->coupon?->code,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_price' => (float) $order->total_price,
            'payment_method' => (int) $order->payment_method,
            'payment_status' => (int) $order->payment_status,
            'status' => (int) $order->status,
            'customer' => [
                'email' => $order->email,
                'first_name' => $order->first_name,
                'last_name' => $order->last_name,
                'phone_number' => $order->phone_number,
                'address' => $order->address,
                'postal_code' => $order->postal_code,
                'country_id' => $order->country_id,
                'note' => $order->note,
            ],
            'items' => $items,
            'created_at' => $this->formatOrderDateTime($order->created_at),
        ];
    }

    private function formatOrderDateTime($dateTime): ?string
    {
        if (! $dateTime) {
            return null;
        }

        return $dateTime->copy()
            ->setTimezone('Asia/Ho_Chi_Minh')
            ->format('d-m-Y H:i:s');
    }

    private function formatAttributes(array $attributeIds): array
    {
        if ($attributeIds === []) {
            return [];
        }

        return ProductAttributeValuesModel::with('attribute:id,name')
            ->whereIn('id', $attributeIds)
            ->get()
            ->map(fn (ProductAttributeValuesModel $attributeValue) => [
                'attribute_id' => $attributeValue->product_attribute_id,
                'attribute_name' => $attributeValue->attribute?->name,
                'attribute_value_id' => $attributeValue->id,
                'attribute_value' => $attributeValue->name,
            ])
            ->values()
            ->toArray();
    }

    private function getSelectedProductImage(int $productId, array $attributeIds, ?string $defaultImage): ?string
    {
        if ($attributeIds !== []) {
            $image = ProductImagesModel::where('product_id', $productId)
                ->whereIn('product_attribute_value_id', $attributeIds)
                ->value('image');

            $image ??= ProductVariantValueImage::whereIn('product_attribute_value_id', $attributeIds)
                ->value('image');

            if ($image) {
                return str_starts_with($image, '/') ? $image : '/storage/' . ltrim($image, '/');
            }
        }

        return $defaultImage;
    }

    private function getProductPrice(ProductsModel $product): float
    {
        $priceSale = (float) ($product->price_sale ?? 0);

        return $priceSale > 0 ? $priceSale : (float) $product->price;
    }

    private function getSellPrice(ProductsModel $product, ?ProductVariantModel $variant = null): float
    {
        if ($variant && (float) ($variant->price ?? 0) > 0) {
            return (float) $variant->price;
        }

        return $this->getProductPrice($product);
    }

    private function resolveVariantForCartItem(int $productId, CardItemModel $item): ?ProductVariantModel
    {
        if ($item->product_variant_id) {
            $variant = ProductVariantModel::where('product_id', $productId)
                ->where('status', 1)
                ->whereKey($item->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                abort(response()->json([
                    'status' => 422,
                    'message' => 'Bien the san pham trong gio hang khong hop le.',
                ], 422));
            }

            return $variant;
        }

        $hasVariants = ProductVariantModel::where('product_id', $productId)->exists();
        if (! $hasVariants) {
            return null;
        }

        $attributeIds = $this->normalizeAttributeIds($item->attribute_ids ?? []);
        $variant = ProductVariantModel::where('product_id', $productId)
            ->where('status', 1)
            ->with('attributeValues:id')
            ->lockForUpdate()
            ->get()
            ->first(fn (ProductVariantModel $variant) => $this->normalizeAttributeIds($variant->attributeValues->pluck('id')->all()) === $attributeIds);

        if (! $variant) {
            abort(response()->json([
                'status' => 422,
                'message' => 'To hop phan loai san pham trong gio hang khong ton tai hoac dang het ban.',
            ], 422));
        }

        return $variant;
    }

    private function normalizeAttributeIds(array $attributeIds): array
    {
        $attributeIds = array_values(array_unique(array_map('intval', $attributeIds)));
        sort($attributeIds);

        return $attributeIds;
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'DH' . now()->format('ymdHis') . random_int(100, 999);
        } while (OrderModel::where('code', $code)->exists());

        return $code;
    }

    private function splitName(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return [
                'first_name' => 'Khach',
                'last_name' => 'Hang',
            ];
        }

        $parts = preg_split('/\s+/', $name);
        $firstName = array_pop($parts);

        return [
            'first_name' => $firstName,
            'last_name' => trim(implode(' ', $parts)) ?: 'Khach',
        ];
    }
}
