<?php

namespace App\Http\Controllers\API\Orders;

use App\Http\Controllers\Controller;
use App\Models\OrderItemModel;
use App\Models\ProductAttributeModel;
use App\Models\ProductAttributeValuesModel;
use Illuminate\Http\Request;

class DetailOrdersController extends Controller
{
    public function index(Request $request, $id)
    {

        $query = OrderItemModel::where('order_id', $id)->with(['order', 'orderProductAttributeValueItemModel.attributeValue.attribute'])->orderBy('id', 'desc');
        $detail = $query->paginate(50);
        $count = $query->count();
        // phần lấy title của chi tiết đơn hàng 
        $firstDetail = $detail->first();

        $total_price = 0;
        foreach ($detail as $item) {
            $total_price += $item->total_price;
        }

        $total_discount = 0;

        if ($firstDetail?->order?->coupon?->type_unit == 1) {
            $total_discount = $total_price * ($firstDetail?->order?->coupon?->discount_type / 100);
            if ($firstDetail?->order?->coupon?->max_value > 0 && $total_discount > $firstDetail?->order?->coupon?->max_value) {
                $total_discount = $firstDetail?->order?->coupon?->max_value;
            }
        } else {
            $total_discount = $firstDetail?->order?->coupon?->discount_type;
        }

        $thanh_tien = $total_price - $total_discount;
        $customerDetail = $firstDetail ? [
            'name' => $firstDetail->order->last_name . ' ' . $firstDetail->order->first_name,
            'address' => $firstDetail->order->address,
            'phone' => $firstDetail->order->phone_number,
            'date' => $firstDetail->order->created_at,
            'code_order' => $firstDetail->order->code,
            'discout_code' => $firstDetail->order->coupon->code ?? 'Không có',
            'total_discount' => $total_discount ?? 0,
            'status' => $firstDetail->order->status,
            'payment_method' => $firstDetail->order->payment_method,
            'payment_status' => $firstDetail->order->payment_status,
            'total_price' => $total_price,
            'thanh_tien' => $thanh_tien,
            'count_sp' => $count,
            'transaction_id_paypal' => $firstDetail?->order?->transaction_id,
            'current_coutry' => 'VND',

        ] : [];

        $detail->getCollection()->transform(function ($detail) {

            $attributes = $detail->orderProductAttributeValueItemModel->map(function ($item) {
                $attributesName = ProductAttributeModel::where('id', $item->product_atribute_id_name)->first();
                $productAttributeValueIds = json_decode($item->product_attribute_value_id, true);
                $attributeValues = ProductAttributeValuesModel::with('attribute:id,name')
                    ->whereIn('id', $productAttributeValueIds)
                    ->get();

                $result = $attributeValues->map(function ($attributeValue) {
                    return [
                        'attribute_value' => $attributeValue->name ?? null,
                        'attribute_name' => $attributeValue->attribute->name ?? null,
                    ];
                })->toArray();

                if ($attributesName) {
                    $result[] = [
                        'attribute_value' => $item->personalise_name ?? null,
                        'attribute_name' => $attributesName->name ?? null
                    ];
                }
                return $result;
            });

            return [
                'id' => $detail->id,
                'product_img' => $detail->product->image,
                'name' => $detail->product->name,
                'code' => $detail->product->code,
                'order_id' => $detail->order_id,
                'product_id' => $detail->product_id,
                'price' => $detail->price,
                'quantity' => $detail->quantity,
                'total_price' => $detail->total_price,
                'created_at' => $detail->created_at->format('d-m-Y'),
                'order_attributes' => $attributes,
                'current_coutry' => "VND"
            ];
        });

        return response()->json([
            'customer' => $customerDetail,
            'order_details' => $detail,
        ]);
    }
}
