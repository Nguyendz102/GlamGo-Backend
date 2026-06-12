<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CardItemModel;
use App\Models\CardModel;
use App\Models\ProductAttributeModel;
use App\Models\ProductAttributeValuesModel;
use App\Models\ProductImagesModel;
use App\Models\ProductVariantModel;
use App\Models\ProductVariantValueImage;
use App\Models\ProductsModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CartControllerMobile extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cart = $this->getActiveCart($request->user()->id);
        $cart->load(['items.product.productImages:id,product_id,image,image_alt', 'items.variant']);

        $this->refreshCartTotals($cart);

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $this->formatCart($cart->fresh(['items.product.productImages:id,product_id,image,image_alt', 'items.variant'])),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = ProductsModel::where('id', $request->product_id)
            ->where('status', 1)
            ->first();

        if (! $product) {
            return response()->json([
                'status' => 404,
                'message' => 'San pham khong ton tai hoac dang ngung ban.',
            ], 404);
        }

        $attributeIds = $this->normalizeAttributeIds($request->input('attribute_ids', []));
        $attributeError = $this->validateProductAttributes($product->id, $request->attribute_name_id, $attributeIds);

        if ($attributeError) {
            return response()->json([
                'status' => 422,
                'message' => $attributeError,
            ], 422);
        }

        $variant = $this->resolveVariantForProduct($product->id, $attributeIds, $request->input('product_variant_id'));
        if ($variant instanceof JsonResponse) {
            return $variant;
        }

        $cart = DB::transaction(function () use ($request, $product, $attributeIds, $variant) {
            $cart = $this->getActiveCart($request->user()->id);
            $lockedVariant = $variant
                ? ProductVariantModel::whereKey($variant->id)->lockForUpdate()->firstOrFail()
                : null;
            $price = $this->getSellPrice($product, $lockedVariant);
            $personaliseName = $request->personalise_name ?: $request->nameInput;

            $item = CardItemModel::where('card_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('product_variant_id', $lockedVariant?->id)
                ->where('attribute_name_id', $request->attribute_name_id)
                ->where('personalise_name', $personaliseName)
                ->get()
                ->first(function (CardItemModel $item) use ($attributeIds) {
                    return $this->normalizeAttributeIds($item->attribute_ids ?? []) === $attributeIds;
                });

            $newQuantity = ($item ? (int) $item->quantity : 0) + (int) $request->quantity;
            $stockError = $this->validateVariantStock($lockedVariant, $newQuantity);
            if ($stockError) {
                abort(response()->json([
                    'status' => 422,
                    'message' => $stockError,
                ], 422));
            }

            if ($item) {
                $item->quantity = $newQuantity;
                $item->price = $price;
                $item->total_price = $item->quantity * $price;
                $item->save();
            } else {
                CardItemModel::create([
                    'card_id' => $cart->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $lockedVariant?->id,
                    'attribute_name_id' => $request->attribute_name_id,
                    'attribute_ids' => $attributeIds,
                    'personalise_name' => $personaliseName,
                    'price' => $price,
                    'quantity' => (int) $request->quantity,
                    'total_price' => $price * (int) $request->quantity,
                ]);
            }

            $this->refreshCartTotals($cart);

            return $cart;
        });

        return response()->json([
            'status' => 201,
            'message' => 'Da them san pham vao gio hang',
            'data' => $this->formatCart($cart->fresh(['items.product.productImages:id,product_id,image,image_alt', 'items.variant'])),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Du lieu khong hop le',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item = $this->findUserCartItem($request->user()->id, $id);

        if (! $item) {
            return response()->json([
                'status' => 404,
                'message' => 'San pham trong gio hang khong ton tai.',
            ], 404);
        }

        DB::transaction(function () use ($item, $request) {
            $product = ProductsModel::findOrFail($item->product_id);
            $variant = $item->product_variant_id
                ? ProductVariantModel::whereKey($item->product_variant_id)->lockForUpdate()->first()
                : null;
            $stockError = $this->validateVariantStock($variant, (int) $request->quantity);
            if ($stockError) {
                abort(response()->json([
                    'status' => 422,
                    'message' => $stockError,
                ], 422));
            }

            $price = $this->getSellPrice($product, $variant);

            $item->quantity = (int) $request->quantity;
            $item->price = $price;
            $item->total_price = $price * (int) $request->quantity;
            $item->save();

            $this->refreshCartTotals($item->cart);
        });

        $cart = $this->getActiveCart($request->user()->id);

        return response()->json([
            'status' => 200,
            'message' => 'Da cap nhat gio hang',
            'data' => $this->formatCart($cart->fresh(['items.product.productImages:id,product_id,image,image_alt', 'items.variant'])),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = $this->findUserCartItem($request->user()->id, $id);

        if (! $item) {
            return response()->json([
                'status' => 404,
                'message' => 'San pham trong gio hang khong ton tai.',
            ], 404);
        }

        DB::transaction(function () use ($item) {
            $cart = $item->cart;
            $item->delete();
            $this->refreshCartTotals($cart);
        });

        $cart = $this->getActiveCart($request->user()->id);

        return response()->json([
            'status' => 200,
            'message' => 'Da xoa san pham khoi gio hang',
            'data' => $this->formatCart($cart->fresh(['items.product.productImages:id,product_id,image,image_alt', 'items.variant'])),
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getActiveCart($request->user()->id);

        DB::transaction(function () use ($cart) {
            $cart->items()->delete();
            $this->refreshCartTotals($cart);
        });

        return response()->json([
            'status' => 200,
            'message' => 'Da xoa gio hang',
            'data' => $this->formatCart($cart->fresh(['items.product.productImages:id,product_id,image,image_alt', 'items.variant'])),
        ]);
    }

    private function validator(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'attribute_name_id' => ['nullable', 'integer', Rule::exists('product_attribute', 'id')],
            'attribute_ids' => ['nullable', 'array'],
            'attribute_ids.*' => ['integer', Rule::exists('product_attribute_values', 'id')],
            'personalise_name' => ['nullable', 'string', 'max:255'],
            'nameInput' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function getActiveCart(int $userId): CardModel
    {
        return CardModel::firstOrCreate(
            [
                'user_id' => $userId,
                'status' => 1,
            ],
            [
                'code' => 'GH' . now()->format('ymdHis') . random_int(100, 999),
                'subtotal' => 0,
                'discount' => 0,
                'total_price' => 0,
            ]
        );
    }

    private function findUserCartItem(int $userId, int $itemId): ?CardItemModel
    {
        return CardItemModel::where('id', $itemId)
            ->whereHas('cart', function ($query) use ($userId) {
                $query->where('user_id', $userId)->where('status', 1);
            })
            ->with('cart')
            ->first();
    }

    private function normalizeAttributeIds(array $attributeIds): array
    {
        $attributeIds = array_values(array_unique(array_map('intval', $attributeIds)));
        sort($attributeIds);

        return $attributeIds;
    }

    private function validateProductAttributes(int $productId, ?int $attributeNameId, array $attributeIds): ?string
    {
        if ($attributeNameId) {
            $belongsToProduct = ProductAttributeModel::where('id', $attributeNameId)
                ->where('product_id', $productId)
                ->exists();

            if (! $belongsToProduct) {
                return 'Thuoc tinh khong thuoc san pham nay.';
            }
        }

        if ($attributeIds === []) {
            return null;
        }

        $validCount = ProductAttributeValuesModel::whereIn('product_attribute_values.id', $attributeIds)
            ->join('product_attribute', 'product_attribute.id', '=', 'product_attribute_values.product_attribute_id')
            ->where('product_attribute.product_id', $productId)
            ->count();

        return $validCount === count($attributeIds) ? null : 'Gia tri thuoc tinh khong thuoc san pham nay.';
    }

    private function resolveVariantForProduct(int $productId, array $attributeIds, mixed $variantId = null): ProductVariantModel|JsonResponse|null
    {
        $hasVariants = ProductVariantModel::where('product_id', $productId)->exists();
        if (! $hasVariants && ! $variantId) {
            return null;
        }

        if ($variantId) {
            $variant = ProductVariantModel::where('product_id', $productId)
                ->where('status', 1)
                ->with('attributeValues:id')
                ->find((int) $variantId);

            if (! $variant) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Bien the san pham khong hop le hoac dang het ban.',
                ], 422);
            }

            if ($attributeIds !== [] && $this->normalizeAttributeIds($variant->attributeValues->pluck('id')->all()) !== $attributeIds) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Bien the san pham khong khop voi thuoc tinh da chon.',
                ], 422);
            }

            return $variant;
        }

        $variant = ProductVariantModel::where('product_id', $productId)
            ->where('status', 1)
            ->with('attributeValues:id')
            ->get()
            ->first(fn (ProductVariantModel $variant) => $this->normalizeAttributeIds($variant->attributeValues->pluck('id')->all()) === $attributeIds);

        if (! $variant) {
            return response()->json([
                'status' => 422,
                'message' => 'To hop phan loai san pham khong ton tai hoac dang het ban.',
            ], 422);
        }

        return $variant;
    }

    private function validateVariantStock(?ProductVariantModel $variant, int $quantity): ?string
    {
        if (! $variant) {
            return null;
        }

        if ((int) $variant->status !== 1) {
            return 'Bien the san pham dang ngung ban.';
        }

        if ((int) $variant->quantity < $quantity) {
            return 'So luong ton kho khong du.';
        }

        return null;
    }

    private function getSellPrice(ProductsModel $product, ?ProductVariantModel $variant = null): float
    {
        if ($variant && (float) ($variant->price ?? 0) > 0) {
            return (float) $variant->price;
        }

        return $this->getProductPrice($product);
    }

    private function getProductPrice(ProductsModel $product): float
    {
        $priceSale = (float) ($product->price_sale ?? 0);

        return $priceSale > 0 ? $priceSale : (float) $product->price;
    }

    private function refreshCartTotals(CardModel $cart): void
    {
        $subtotal = (float) $cart->items()->sum('total_price');
        $discount = (float) ($cart->discount ?? 0);

        $cart->update([
            'subtotal' => $subtotal,
            'total_price' => max($subtotal - $discount, 0),
        ]);
    }

    private function formatCart(CardModel $cart): array
    {
        return [
            'id' => $cart->id,
            'code' => $cart->code,
            'user_id' => $cart->user_id,
            'subtotal' => (float) $cart->subtotal,
            'discount' => (float) $cart->discount,
            'total_price' => (float) $cart->total_price,
            'total_quantity' => (int) $cart->items->sum('quantity'),
            'items' => $cart->items->map(function (CardItemModel $item) {
                $attributeIds = $item->attribute_ids ?? [];

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
                    'attribute_name_id' => $item->attribute_name_id,
                    'attribute_ids' => $attributeIds,
                    'attributes' => $this->formatAttributes($attributeIds),
                    'personalise_name' => $item->personalise_name,
                    'price' => (float) $item->price,
                    'stock_quantity' => $item->variant ? (int) $item->variant->quantity : null,
                    'quantity' => (int) $item->quantity,
                    'total_price' => (float) $item->total_price,
                ];
            })->values(),
        ];
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
}
