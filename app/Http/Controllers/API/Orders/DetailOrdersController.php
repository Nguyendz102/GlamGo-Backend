<?php

namespace App\Http\Controllers\API\Orders;

use App\Http\Controllers\Controller;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\ProductAttributeModel;
use App\Models\ProductAttributeValuesModel;
use Illuminate\Http\Request;

class DetailOrdersController extends Controller
{
    public function index(Request $request, $id)
    {
        $order = OrderModel::with(['users', 'coupon'])->findOrFail($id);

        $query = OrderItemModel::where('order_id', $id)
            ->with(['product.category', 'orderProductAttributeValueItemModel.attributeValue.attribute'])
            ->orderBy('id', 'desc');

        $detail = $query->paginate(50);
        $count = $query->count();

        $totalPrice = OrderItemModel::where('order_id', $id)->sum('total_price');
        $totalDiscount = $this->calculateDiscount((float) $totalPrice, $order);
        $statusInfo = $this->getOrderStatusInfo((int) $order->status);
        $paymentStatusInfo = $this->getPaymentStatusInfo((int) $order->payment_status);
        $paymentMethodInfo = $this->getPaymentMethodInfo((int) $order->payment_method);

        $customerDetail = [
            'name' => trim($order->last_name . ' ' . $order->first_name),
            'address' => $order->address,
            'phone' => $order->phone_number,
            'date' => $order->created_at,
            'code_order' => $order->code,
            'discout_code' => $order->coupon->code ?? 'Không có',
            'total_discount' => $totalDiscount,
            'status' => (int) $order->status,
            'status_name' => $statusInfo['name'],
            'status_color' => $statusInfo['color'],
            'payment_method' => (int) $order->payment_method,
            'payment_method_name' => $paymentMethodInfo['name'],
            'payment_status' => (int) $order->payment_status,
            'payment_status_name' => $paymentStatusInfo['name'],
            'payment_status_color' => $paymentStatusInfo['color'],
            'total_price' => (float) $totalPrice,
            'thanh_tien' => max((float) $totalPrice - $totalDiscount, 0),
            'count_sp' => $count,
            'transaction_id_paypal' => $order->transaction_id,
            'current_coutry' => 'VND',
            'account' => $order->users ? [
                'id' => $order->users->id,
                'code' => $order->users->code,
                'name' => $order->users->name,
                'user_name' => $order->users->user_name,
                'email' => $order->users->email,
                'phone' => $order->users->phone,
            ] : null,
            'account_name' => $order->users?->name ?? 'Khách vãng lai',
            'account_email' => $order->users?->email,
        ];

        $detail->getCollection()->transform(function ($detail) {
            $attributes = $detail->orderProductAttributeValueItemModel->map(function ($item) {
                $attributesName = ProductAttributeModel::where('id', $item->product_atribute_id_name)->first();
                $productAttributeValueIds = json_decode($item->product_attribute_value_id, true) ?: [];
                $attributeValues = ProductAttributeValuesModel::with('attribute:id,name')
                    ->whereIn('id', $productAttributeValueIds)
                    ->get();

                $result = $attributeValues->map(function ($attributeValue) {
                    return [
                        'attribute_value' => $attributeValue->name ?? null,
                        'attribute_name' => $attributeValue->attribute->name ?? null,
                    ];
                })->toArray();

                $hasAttribute = collect($result)->contains(
                    fn ($attribute) => $attribute['attribute_name'] === $attributesName?->name
                );

                if ($attributesName && filled($item->personalise_name) && ! $hasAttribute) {
                    $result[] = [
                        'attribute_value' => $item->personalise_name ?? null,
                        'attribute_name' => $attributesName->name ?? null,
                    ];
                }

                return $result;
            });

            return [
                'id' => $detail->id,
                'product_img' => $detail->product?->image,
                'name' => $detail->product?->name,
                'code' => $detail->product?->code,
                'product_detail' => $detail->product ? [
                    'id' => $detail->product->id,
                    'name' => $detail->product->name,
                    'code' => $detail->product->code,
                    'image' => $detail->product->image,
                    'category_name' => $detail->product->category?->name,
                    'price' => $detail->product->price,
                    'price_sale' => $detail->product->price_sale,
                    'meta_title' => $detail->product->meta_title,
                    'meta_description' => $detail->product->meta_description,
                    'hashtag' => $detail->product->hashtag,
                    'status' => $detail->product->status,
                ] : null,
                'order_id' => $detail->order_id,
                'product_id' => $detail->product_id,
                'product_variant_id' => $detail->product_variant_id,
                'price' => $detail->price,
                'quantity' => $detail->quantity,
                'total_price' => $detail->total_price,
                'created_at' => $detail->created_at->format('d-m-Y'),
                'order_attributes' => $attributes,
                'current_coutry' => 'VND',
            ];
        });

        return response()->json([
            'customer' => $customerDetail,
            'order_details' => $detail,
        ]);
    }

    private function calculateDiscount(float $totalPrice, OrderModel $order): float
    {
        if (! $order->coupon) {
            return 0;
        }

        if ((int) $order->coupon->type_unit === 1) {
            $discount = $totalPrice * ((float) $order->coupon->discount_type / 100);

            if ((float) $order->coupon->max_value > 0 && $discount > (float) $order->coupon->max_value) {
                return (float) $order->coupon->max_value;
            }

            return min($discount, $totalPrice);
        }

        return min((float) $order->coupon->discount_type, $totalPrice);
    }

    private function getOrderStatusInfo(int $status): array
    {
        return match ($status) {
            1 => ['name' => 'Chờ kiểm tra', 'color' => '#ffc107'],
            2 => ['name' => 'Đang chuẩn bị hàng', 'color' => '#0dcaf0'],
            3 => ['name' => 'Đang giao hàng', 'color' => '#17a2b8'],
            4 => ['name' => 'Đã giao hàng', 'color' => '#28a745'],
            5 => ['name' => 'Đã hủy', 'color' => '#dc3545'],
            default => ['name' => 'Không xác định', 'color' => '#6c757d'],
        };
    }

    private function getPaymentStatusInfo(int $status): array
    {
        return match ($status) {
            1 => ['name' => 'Đã thanh toán', 'color' => 'rgb(8 205 47)'],
            0, 2 => ['name' => 'Chưa thanh toán', 'color' => 'rgb(221 21 21)'],
            default => ['name' => 'Không xác định', 'color' => '#6c757d'],
        };
    }

    private function getPaymentMethodInfo(int $method): array
    {
        return match ($method) {
            1 => ['name' => 'Thanh toán khi nhận hàng'],
            2 => ['name' => 'Vi'],
            3 => ['name' => 'Chuyển khoản'],
            default => ['name' => 'Không xác định'],
        };
    }
}
