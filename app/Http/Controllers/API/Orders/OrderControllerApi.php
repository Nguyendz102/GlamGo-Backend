<?php

namespace App\Http\Controllers\api\orders;

use App\Http\Controllers\Controller;
use App\Mail\OrderShipped;
use App\Models\CouponModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderProductAttributeValueItemModel;
use App\Models\ProductsModel;
use App\Models\TransactionsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrderControllerApi extends Controller
{
    public function index(Request $request)
    {
        $query = OrderModel::with(['users', 'coupon', 'status', 'country:id,sign'])->orderBy('created_at', 'desc');

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->input('code') . '%');
        }

        if ($request->filled('date')) {
            $dates = $request->input('date');
            $startDate = $dates[0];
            $endDate   = $dates[1] ?? $dates[0];
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalSum = $query->sum('total_price');
        $total_price_du_tinh = (clone $query)->where('status', '!=', 4)->sum('total_price');
        $total_price_thuc_te = (clone $query)->where('status', 4)->sum('total_price');

        $orders = $query->paginate(50);

        $orders->getCollection()->transform(function ($order) {
            return [
                'id' => $order->id,
                'created_at' => $order->created_at->format('d-m-Y'),
                'code' => $order->code,
                'name_cus' => $order->last_name . ' ' . $order->first_name,
                'code_coupon' => $order->coupon->code ?? 'Không có',
                'total' => $order->total_price ?? 0,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'note' => $order->note,
            ];
        });

        $result = $orders->toArray();
        $result['total_sum'] = $totalSum;
        $result['total_price_thuc_te'] = $total_price_thuc_te;
        $result['total_price_du_tinh'] = $total_price_du_tinh;
        return response()->json($result);
    }

    public function edit(Request $request, $id)
    {
        $order = OrderModel::find($id);
        $order->update(['status' => $request->input('status')]);
        return response()->json($order, 200);
    }
    public function checkData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'regex:/^(?!(\.))[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/u', 'max:255'],
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'postal_code'  => 'required|string|max:50',
            'address'      => 'required|string|max:255',
            'country_id'   => 'required',
            'phone_number' => ['required', 'regex:/^(?:\+?[1-9]\d{1,14}|0[1-9]\d{8})$/'],
            'note'         => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ!',
                'errors'  => $validator->errors()
            ], 422);
        };

        $validatedData = $validator->validated();
        // Lọc dữ liệu đầu vào cho trường 'note' (ví dụ: loại bỏ thẻ HTML)
        if (isset($validatedData['note']) && is_string($validatedData['note'])) {
            $validatedData['note'] = strip_tags($validatedData['note']);
        }

        // Áp dụng htmlspecialchars() cho tất cả các trường
        foreach ($validatedData as $key => $value) {
            if (is_string($value)) {
                $validatedData[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }

        session()->put('data_info', $validatedData);

        return response()->json([
            'message' => 'Dữ liệu hợp lệ!',
            'sess' => session()->get('data_info'),
            'data' => $validator
        ]);
    }

    public function create(Request $request)
    {
        try {

            DB::beginTransaction();
            // 1. Làm sạch dữ liệu đầu vào cho coupon code
            $couponCode = trim(htmlspecialchars($request->code_discount));
            $coupon_id = CouponModel::where('code', $couponCode)->value('id');

            // 2. Lấy dữ liệu từ session và làm sạch
            $dataInfo = session()->get('data_info', []);
            $dataInfo = array_map('trim', $dataInfo); // Loại bỏ khoảng trắng thừa
            $dataInfo = array_map('htmlspecialchars', $dataInfo); // Mã hóa HTML

            // 3. Xác thực và làm sạch dữ liệu từ request->cart
            $cartItems = [];
            if ($request->has('cart') && is_array($request->cart)) {
                foreach ($request->cart as $item) {
                    $cartItem = [
                        'product_id' => intval($item['product_id']),
                        'price' => floatval($item['price']),
                        'quantity' => intval($item['quantity']),
                        'attribute_name_id' => isset($item['attribute_name_id']) ? intval($item['attribute_name_id']) : 0,
                        'attribute_ids' => [],
                        'nameInput' => isset($item['nameInput']) ? trim(htmlspecialchars($item['nameInput'])) : '',
                    ];

                    // Làm sạch attribute_ids
                    if (isset($item['attribute_ids']) && is_array($item['attribute_ids'])) {
                        foreach ($item['attribute_ids'] as $attribute) {
                            $cartItem['attribute_ids'][] = trim(htmlspecialchars($attribute['codeNumber']));
                        }
                    }

                    $cartItems[] = $cartItem;
                }
            }

            // 4. Xác thực và làm sạch dữ liệu giá
            $totalPrice = is_numeric($request->new_price_discount) ? floatval($request->new_price_discount) : (is_numeric($request->total) ? floatval($request->total) : 0);

            // 5. Tạo đơn hàng
            $order = OrderModel::create([
                'user_id' => intval($request->user_id ?? 0),
                'coupon_id' => $coupon_id ?? 0,
                'code' => 'DH' . time(),
                'transaction_id' => null,
                'payment_method' => 1,
                'payment_status' => 2,
                'status' => 1,
                "country_id" => 1,
                'total_price' => $totalPrice,
                "email" => $dataInfo['email'],
                "first_name" => $dataInfo['first_name'],
                "last_name" => $dataInfo['last_name'],
                "postal_code" => $dataInfo['postal_code'],
                "address" => $dataInfo['address'],
                "phone_number" => $dataInfo['phone_number'],
                "note" => $dataInfo['note'],
            ]);

            // 6. Tạo mục đơn hàng
            foreach ($cartItems as $item) {
                $orderItem = OrderItemModel::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total_price' => $item['price'] * $item['quantity'],
                ]);

                OrderProductAttributeValueItemModel::create([
                    'order_item_id' => $orderItem->id,
                    'product_attribute_value_id' => json_encode($item['attribute_ids']),
                    'product_atribute_id_name' => $item['attribute_name_id'],
                    'personalise_name' => $item['nameInput'],
                ]);
            }

            DB::commit();

            return response()->json($order, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getStatusOrder(Request $request)
    {
        $statuses = [
            1 => 'Chờ kiểm tra',
            2 => 'Đang chuẩn bị hàng',
            3 => 'Đang giao hàng',
            4 => 'Đã giao hàng',
            5 => 'Đã hủy',
        ];

        $currentId = (int) $request->id;

        $nextStatuses = [];

        // Xác định trạng thái kế tiếp
        if ($currentId >= 1 && $currentId <= 3) {
            $nextId = $currentId + 1;

            if (isset($statuses[$nextId])) {
                $nextStatuses[] = [
                    'id' => $nextId,
                    'name' => $statuses[$nextId],
                ];
            }

            // Trạng thái hủy luôn hiển thị nếu chưa đến bước 4
            $nextStatuses[] = [
                'id' => 5,
                'name' => $statuses[5],
            ];
        }

        return response()->json($nextStatuses);
    }

    // public function createTransaction(Request $request, $idOrder)
    // {
    //     try {
    //         $dataOrder = OrderModel::with('orderItems:id,order_id,product_id')->find($idOrder);
    //         $firstProductId = $dataOrder->orderItems->first()->product_id;
    //         $sign = ProductsModel::with('country:id,sign')->where('id', $firstProductId)->first()->country->sign;

    //         // Lưu thông tin thanh toán vào bảng payments
    //         $payment = TransactionsModel::create([
    //             'order_id'               => $idOrder,
    //             'payment_method'         => 1, // PayPal
    //             'amount'                 => $dataOrder->total_price,
    //             'country_id'                 => $dataOrder->country_id,
    //             'payment_getway_response' => json_encode($request->detail), // Lưu toàn bộ response từ PayPal
    //             'status_id'              => 1, // Trạng thái thanh toán: thanhf coong
    //         ]);

    //         $dataOrder->update([
    //             'transaction_id' => $request->detail['id'],
    //             'payment_status' => 1, // Đã thanh toán
    //         ]);

    //         $orderSendMail = [
    //             'customer_name' => $dataOrder->first_name . ' ' . $dataOrder->last_name,
    //             'order_code' => $dataOrder->code,
    //             'payment_method' => $dataOrder->payment_method == 1 ? 'Paypal' : "Origin unknown",
    //             'address' => $dataOrder->address,
    //             'total_price' => $sign . ' ' . $dataOrder->total_price,
    //         ];
    //         Mail::to($dataOrder->email)->send(new OrderShipped($orderSendMail));
    //         return response()->json([
    //             'order'   => $dataOrder,
    //             'message' => 'Đặt hàng thành công!'
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'message' => $th->getMessage()
    //         ], 500);
    //         //throw $th;
    //     }
    // }
}
