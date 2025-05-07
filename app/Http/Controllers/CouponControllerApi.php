<?php

namespace App\Http\Controllers;

use App\Models\CouponModel;
use App\Models\OrderModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CouponControllerApi extends Controller
{
    public function index(Request $request)
    {
        $query = CouponModel::orderBy('created_at', 'desc');
        $today = Carbon::today();

        CouponModel::where('end_date', '<', $today)
            ->where('status', '!=', 0)
            ->update(['status' => 0]);

        if ($request->filled('date')) {
            $dates = $request->input('date');
            $formattedDates = array_map(fn($date) => Carbon::parse($date)->format('Y-m-d'), $dates);

            if (count($formattedDates) === 1) {
                $query->whereDate('start_date', $formattedDates[0]);
            } else {
                $query->whereBetween('start_date', $formattedDates);
            }
        }

        if ($request->has('code')) {
            $query->where('code', 'like', "%{$request->input('code')}%");
        }
        $data = $query->paginate(50);
        return response()->json($data);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255|unique:coupon,code',
            'discount' => ['required', 'numeric', Rule::when($request->type_unit == 1, ['max:100'])],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required',
            'min_order_value' => 'required|numeric|min:0',
            'max_value' => 'required|numeric|min:0',
            'type_unit' => 'required|in:1,2',
            'usage_limit' => 'required|integer|min:1',
        ], [
            'code.required' => 'Mã giảm giá không được để trống.',
            'code.string' => 'Mã giảm giá phải là chuỗi.',
            'code.unique' => 'Mã giảm giá đã tồn tại.',
            'discount.required' => 'Giá trị giảm giá không được để trống.',
            'start_date.required' => 'Ngày bắt đầu thực hiện không được để trống.',
            'end_date.required' => 'Ngày kết thúc không được để trống.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'status.required' => 'Trạng thái không được để trống.',
            'min_order_value.required' => 'Giá trị tối thiểu không được để trống.',
            'max_value.required' => 'Giá trị tối đa không được để trống.',
            'type_unit.required' => 'Loại phiếu giảm giá không được để trống.',
            'usage_limit.required' => 'Số lần dùng không được để trống.',
            'discount.max' => 'Giá trị giảm giá không được lớn hơn 100%.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post_coupon = CouponModel::create([
            'code' => $request->code,
            'description' => $request->description,
            'discount_type' => $request->discount,
            'min_order_value' => $request->min_order_value,
            'max_value' => $request->max_value,
            'status' => $request->status,
            'start_date' => Carbon::parse($request->start_date)->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d'),
            'end_date' => Carbon::parse($request->end_date)->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d'),
            'usage_limit' => $request->usage_limit,
            'type_unit' => $request->type_unit,
            'created_at' => now(),
        ]);

        return response()->json($post_coupon, 201);
    }

    public function edit(Request $request, $id)
    {
        $today = Carbon::today();
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:255', Rule::unique('coupon', 'code')->ignore($id)],
            'discount' => ['required', 'numeric', Rule::when($request->type_unit == 1, ['max:100'])],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required',
            'min_order_value' => 'required|numeric|min:0',
            'max_value' => 'required|numeric|min:0',
            'type_unit' => 'required|in:1,2',
            'usage_limit' => 'required|integer|min:1',
        ], [
            'code.required' => 'Mã giảm giá không được để trống.',
            'country_id.required' => 'Quốc gia không được để trống.',
            'code.string' => 'Mã giảm giá phải là chuỗi.',
            'code.unique' => 'Mã giảm giá đã tồn tại.',
            'discount.required' => 'Giá trị giảm giá không được để trống.',
            'start_date.required' => 'Ngày bắt đầu thực hiện không được để trống.',
            'end_date.required' => 'Ngày kết thúc không được để trống.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'status.required' => 'Trạng thái không được để trống.',
            'min_order_value.required' => 'Giá trị tối thiểu không được để trống.',
            'max_value.required' => 'Giá trị tối đa không được để trống.',
            'type_unit.required' => 'Loại phiếu giảm giá không được để trống.',
            'usage_limit.required' => 'Số lần dùng không được để trống.',
            'discount.max' => 'Giá trị giảm giá không được lớn hơn 100%.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $start_date = Carbon::parse($request->start_date)->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d');
        $end_date = Carbon::parse($request->end_date)->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d');
        $coupon = CouponModel::findOrFail($id);
        $coupon->update([
            'code' => $request->code,
            'description' => $request->description,
            'discount_type' => $request->discount,
            'min_order_value' => $request->min_order_value,
            'max_value' => $request->max_value,
            'status' => $request->status,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'usage_limit' => $request->usage_limit,
            'type_unit' => $request->type_unit,
            'updated_at' => now(),
        ]);
        return response()->json($coupon, 200);
    }

    public function detail($id)
    {
        $coupon = CouponModel::with(['orders.users'])->findOrFail($id);
        if (!$coupon) {
            return response()->json(['error' => 'Coupon not found.'], 404);
        }

        foreach ($coupon->orders as $order) {
            // Chỉ áp dụng giảm giá nếu tổng tiền của đơn hàng đạt giá trị tối thiểu
            if ($order->total_price >= $coupon->min_order_value) {
                if ($coupon->type_unit == 1) {
                    // tiền giảm
                    $discountAmount = $order->total_price * $coupon->discount_type / 100;
                } elseif ($coupon->type_unit == 2) {
                    $discountAmount = $coupon->discount_type;
                } else {
                    $discountAmount = 0;
                }

                // Nếu có giới hạn giảm tối đa và số tiền giảm vượt quá giới hạn thì lấy giá trị tối đa
                if ($coupon->max_value > 0 && $discountAmount > $coupon->max_value) {
                    $discountAmount = $coupon->max_value;
                }
            } else {
                $discountAmount = 0;
            }
            $order->discount_amount = $discountAmount;
            $order->final_total = $order->total_price - $discountAmount;
        }

        return response()->json($coupon, 200);
    }
    function getDiscount(Request $request)
    {
        $code_discount = $request->input('code');
        $total_price = $request->input('total_price');
        // $current_coupon = session()->get('applied_coupon');

        // if ($current_coupon && $current_coupon['code'] === $code_discount) {
        //     return response()->json(['error' => 'This coupon code has been applied.'], 400);
        // }

        $coupon = CouponModel::where('code', $code_discount)->first();

        if (!$coupon) {
            return response()->json(['error' => 'Coupon code does not exist.'], 404);
        }

        $count_order = OrderModel::where('coupon_id', $coupon->id)->count();

        if ($coupon->usage_limit > 0 && $count_order >= $coupon->usage_limit) {
            return response()->json(['error' => 'The coupon code has reached its expiration date.'], 404);
        }

        if ($coupon->min_order_value >= $total_price) {
            return response()->json(['error' => 'Orders must meet minimum: ' . number_format($coupon->min_order_value)], 404);
        }

        if ($coupon->start_date > now()->endOfDay() || $coupon->end_date < now()->startOfDay()) {
            return response()->json(['error' => 'Coupon code has expired.'], 404);
        }

        if ($coupon->type_unit == 1) {
            $discountAmount = $total_price * $coupon->discount_type / 100;
        } elseif ($coupon->type_unit == 2) {
            $discountAmount = $coupon->discount_type;
        } else {
            $discountAmount = 0;
        }
        if ($coupon->max_value > 0 && $discountAmount > $coupon->max_value) {
            $discountAmount = $coupon->max_value;
        }

        $rounded_total = round($total_price - $discountAmount, 2);

        // Lưu mã giảm giá đã áp dụng vào session
        // session()->put('applied_coupon', [
        //     'code' => $code_discount,
        //     'discount_amount' => $discountAmount,
        //     'coupon_id' => $coupon->id
        // ]);

        return response()->json([
            'new_total_price' => $rounded_total,
            'discount_amount' => $discountAmount,
            'coupon_code' => $code_discount,
            // 'replaced' => $current_coupon ? true : false
        ], 200);
    }
}
