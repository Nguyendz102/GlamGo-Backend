<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OrderModel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerControllerApi extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->select('users.*')
            ->selectSub(
                OrderModel::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('user_id', 'users.id'),
                'total_orders'
            )
            ->selectSub(
                OrderModel::query()
                    ->selectRaw('COALESCE(SUM(total_price), 0)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('status', '!=', 5),
                'total_spent'
            )
            ->where('is_admin', User::IS_CUSTOMER)
            ->withCount(['addresses'])
            ->orderByDesc('created_at');

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->input('keyword'));

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('user_name', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status_id', (int) $request->input('status'));
        }

        if ($request->filled('date')) {
            $dates = $request->input('date');
            $startDate = $dates[0];
            $endDate = $dates[1] ?? $dates[0];
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $baseQuery = clone $query;
        $customers = $query->paginate(50);

        $customers->getCollection()->transform(function (User $customer) {
            return [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'user_name' => $customer->user_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'status_id' => (int) $customer->status_id,
                'addresses_count' => $customer->addresses_count,
                'total_orders' => (int) $customer->total_orders,
                'total_spent' => (float) $customer->total_spent,
                'created_at' => optional($customer->created_at)->format('d-m-Y'),
            ];
        });

        $result = $customers->toArray();
        $result['active_count'] = (clone $baseQuery)->where('status_id', 1)->count();
        $result['locked_count'] = (clone $baseQuery)->where('status_id', 0)->count();

        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        $customer = User::query()
            ->where('is_admin', User::IS_CUSTOMER)
            ->with([
                'addresses' => fn ($query) => $query
                    ->orderByDesc('is_default')
                    ->orderByDesc('updated_at'),
            ])
            ->findOrFail($id);

        $orders = OrderModel::query()
            ->where('user_id', $customer->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (OrderModel $order) => [
                'id' => $order->id,
                'code' => $order->code,
                'total_price' => (float) $order->total_price,
                'payment_status' => (int) $order->payment_status,
                'status' => (int) $order->status,
                'created_at' => optional($order->created_at)->format('d-m-Y'),
            ]);

        $summary = OrderModel::query()
            ->where('user_id', $customer->id)
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(CASE WHEN status != 5 THEN total_price ELSE 0 END), 0) as total_spent')
            ->first();

        return response()->json([
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'user_name' => $customer->user_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'avatar' => $customer->avatar,
            'address' => $customer->address,
            'status_id' => (int) $customer->status_id,
            'created_at' => optional($customer->created_at)->format('d-m-Y H:i'),
            'updated_at' => optional($customer->updated_at)->format('d-m-Y H:i'),
            'addresses' => $customer->addresses,
            'orders' => $orders,
            'total_orders' => (int) ($summary->total_orders ?? 0),
            'total_spent' => (float) ($summary->total_spent ?? 0),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status_id' => ['required', Rule::in([0, 1])],
        ]);

        $customer = User::query()
            ->where('is_admin', User::IS_CUSTOMER)
            ->findOrFail($id);

        $customer->update([
            'status_id' => (int) $validated['status_id'],
        ]);

        if ((int) $validated['status_id'] === 0) {
            $customer->tokens()->delete();
        }

        return response()->json([
            'message' => 'Cap nhat trang thai khach hang thanh cong',
            'data' => [
                'id' => $customer->id,
                'status_id' => (int) $customer->status_id,
            ],
        ]);
    }
}
