<?php

namespace App\Http\Controllers;

use App\Models\CouponModel;
use App\Services\CouponService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponControllerApi extends Controller
{
    public function __construct(private readonly CouponService $couponService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = CouponModel::orderByDesc('created_at');

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . strtoupper(trim((string) $request->code)) . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('date') && is_array($request->date)) {
            $dates = array_map(fn ($date) => Carbon::parse($date)->format('Y-m-d'), $request->date);
            $query->when(
                count($dates) === 1,
                fn ($builder) => $builder->whereDate('start_date', $dates[0]),
                fn ($builder) => $builder->whereBetween('start_date', [$dates[0], $dates[1]])
            );
        }

        return response()->json($query->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $coupon = CouponModel::create($this->validatedData($request));

        return response()->json($coupon, 201);
    }

    public function edit(Request $request, int $id): JsonResponse
    {
        $coupon = CouponModel::findOrFail($id);
        $coupon->update($this->validatedData($request, $id));

        return response()->json($coupon->fresh());
    }

    public function detail(int $id): JsonResponse
    {
        return response()->json(CouponModel::with(['orders.users'])->findOrFail($id));
    }

    public function destroy(int $id): JsonResponse
    {
        $coupon = CouponModel::findOrFail($id);

        if ($coupon->orders()->exists()) {
            return response()->json([
                'message' => 'Khong the xoa ma giam gia da duoc su dung.',
            ], 409);
        }

        $coupon->delete();

        return response()->json([
            'message' => 'Da xoa ma giam gia.',
        ]);
    }

    public function getDiscount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'total_price' => ['required', 'numeric', 'min:0'],
        ]);

        [$coupon, $discount] = $this->couponService->resolve(
            $validated['code'],
            (float) $validated['total_price']
        );

        return response()->json([
            'new_total_price' => round((float) $validated['total_price'] - $discount, 2),
            'discount_amount' => $discount,
            'coupon_code' => $coupon->code,
        ]);
    }

    private function validatedData(Request $request, ?int $id = null): array
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
            'discount_type' => $request->input('discount_type', $request->input('discount')),
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('coupon', 'code')->ignore($id)],
            'description' => ['nullable', 'string'],
            'discount_type' => [
                'required',
                'numeric',
                'min:0',
                Rule::when((int) $request->type_unit === 1, ['max:100']),
            ],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'max_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'integer', Rule::in([CouponModel::INACTIVE, CouponModel::ACTIVE])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'type_unit' => ['required', 'integer', Rule::in([1, 2])],
        ]);

        return [
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'min_order_value' => $data['min_order_value'] ?? 0,
            'max_value' => $data['max_value'] ?? 0,
            'status' => $data['status'],
            'start_date' => $this->toUtcDateTime($data['start_date'] ?? null, false),
            'end_date' => $this->toUtcDateTime($data['end_date'] ?? null, true),
            'usage_limit' => $data['usage_limit'] ?? 0,
            'type_unit' => $data['type_unit'],
        ];
    }

    private function toUtcDateTime(?string $value, bool $endOfDate): ?string
    {
        if (! $value) {
            return null;
        }

        $dateTime = Carbon::parse($value, CouponService::TIMEZONE);

        if (! preg_match('/\d{1,2}:\d{2}/', $value)) {
            $endOfDate ? $dateTime->endOfDay() : $dateTime->startOfDay();
        }

        return $dateTime->utc()->format('Y-m-d H:i:s');
    }
}
