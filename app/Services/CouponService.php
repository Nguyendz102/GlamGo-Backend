<?php

namespace App\Services;

use App\Models\CouponModel;
use App\Models\OrderModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function resolve(?string $code, float $subtotal): array
    {
        $normalizedCode = strtoupper(trim((string) $code));

        if ($normalizedCode === '') {
            return [null, 0.0];
        }

        $coupon = CouponModel::where('code', $normalizedCode)->first();

        if (! $coupon) {
            $this->fail('Ma giam gia khong ton tai.');
        }

        $this->assertUsable($coupon, $subtotal);

        return [$coupon, $this->calculateDiscount($coupon, $subtotal)];
    }

    public function listAvailable(float $subtotal): Collection
    {
        return CouponModel::where('status', CouponModel::ACTIVE)
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (CouponModel $coupon) {
                try {
                    $this->assertActiveAndWithinLimit($coupon);

                    return true;
                } catch (ValidationException) {
                    return false;
                }
            })
            ->map(fn (CouponModel $coupon) => $this->format($coupon, $subtotal))
            ->values();
    }

    public function format(CouponModel $coupon, float $subtotal): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'description' => $coupon->description,
            'discount_type' => (float) $coupon->discount_type,
            'type_unit' => (int) $coupon->type_unit,
            'min_order_value' => (float) $coupon->min_order_value,
            'max_value' => (float) $coupon->max_value,
            'start_date' => optional($coupon->start_date)->format('Y-m-d H:i:s'),
            'end_date' => optional($coupon->end_date)->format('Y-m-d H:i:s'),
            'usage_limit' => (int) $coupon->usage_limit,
            'discount_amount' => $subtotal >= (float) $coupon->min_order_value
                ? $this->calculateDiscount($coupon, $subtotal)
                : 0.0,
            'is_eligible' => $subtotal >= (float) $coupon->min_order_value,
        ];
    }

    private function assertUsable(CouponModel $coupon, float $subtotal): void
    {
        $this->assertActiveAndWithinLimit($coupon);

        if ((float) $coupon->min_order_value > $subtotal) {
            $this->fail('Don hang chua dat gia tri toi thieu de dung ma giam gia.');
        }
    }

    private function assertActiveAndWithinLimit(CouponModel $coupon): void
    {
        if ((int) $coupon->status !== CouponModel::ACTIVE) {
            $this->fail('Ma giam gia dang khong hoat dong.');
        }

        $now = Carbon::now(self::TIMEZONE);
        $startDate = $coupon->start_date?->copy()->setTimezone(self::TIMEZONE);
        $endDate = $coupon->end_date?->copy()->setTimezone(self::TIMEZONE);

        if ($startDate && $startDate->isAfter($now)) {
            $this->fail('Ma giam gia chua den ngay su dung.');
        }

        if ($endDate && $endDate->isBefore($now)) {
            $this->fail('Ma giam gia da het han.');
        }

        $usageCount = OrderModel::where('coupon_id', $coupon->id)
            ->where('status', '!=', 5)
            ->count();

        if ((int) $coupon->usage_limit > 0 && $usageCount >= (int) $coupon->usage_limit) {
            $this->fail('Ma giam gia da het luot su dung.');
        }
    }

    private function calculateDiscount(CouponModel $coupon, float $subtotal): float
    {
        $discount = (int) $coupon->type_unit === 1
            ? $subtotal * ((float) $coupon->discount_type / 100)
            : (float) $coupon->discount_type;

        if ((float) $coupon->max_value > 0) {
            $discount = min($discount, (float) $coupon->max_value);
        }

        return round(min($discount, $subtotal), 2);
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages([
            'coupon_code' => [$message],
        ]);
    }
}
