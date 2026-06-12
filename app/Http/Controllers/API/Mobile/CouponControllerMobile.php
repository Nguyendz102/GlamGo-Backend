<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CardModel;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CouponControllerMobile extends Controller
{
    public function __construct(private readonly CouponService $couponService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $subtotal = $this->getCartSubtotal($request);

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => [
                'subtotal' => $subtotal,
                'coupons' => $this->couponService->listAvailable($subtotal),
            ],
        ]);
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);
        $subtotal = $this->getCartSubtotal($request);

        try {
            [$coupon, $discount] = $this->couponService->resolve($validated['code'], $subtotal);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 422,
                'message' => $exception->errors()['coupon_code'][0],
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Ma giam gia hop le.',
            'data' => [
                'coupon' => $this->couponService->format($coupon, $subtotal),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_price' => round($subtotal - $discount, 2),
            ],
        ]);
    }

    private function getCartSubtotal(Request $request): float
    {
        $cart = CardModel::where('user_id', $request->user()->id)
            ->where('status', 1)
            ->first();

        return $cart ? (float) $cart->items()->sum('total_price') : 0.0;
    }
}
