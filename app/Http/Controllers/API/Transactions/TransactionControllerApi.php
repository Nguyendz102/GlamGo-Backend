<?php

namespace App\Http\Controllers\API\Transactions;

use App\Http\Controllers\Controller;
use App\Models\HistoryPriceProductModel;
use App\Models\TransactionsModel;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionControllerApi extends Controller
{
    public function index(Request $request)
    {
        $query = TransactionsModel::with(
            'status',
            'order:id,code,total_price,payment_method,payment_status,status,country_id,created_at,first_name,last_name,phone_number,email',
            'order.country:id,current,rate,sign',
            'order.orderItems:id,order_id,product_id,product_variant_id,price,quantity,total_price',
            'order.orderItems.product:id,name,code,country_id',
            'order.orderItems.product.country:id,current,rate,sign'
        )->orderBy('created_at', 'desc');
        if (!empty($request->search)) {
            $search = $request->search;
            $query->whereHas('order', function ($q) use ($search) {
                $q->where('code', 'like', "%" . $search . "%");
            });
        }

        if ($request->filled('date')) {
            $dates = $request->input('date');

            // Lấy ngày đầu và ngày cuối, thêm khoảng thời gian đầy đủ
            $startDate = Carbon::parse($dates[0])->startOfDay(); // 2025-03-27 00:00:00
            $endDate   = Carbon::parse($dates[1] ?? $dates[0])->endOfDay(); // 2025-03-27 23:59:59

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalAmount = (clone $query)->get()->sum(function ($transaction) {
            $rate = $transaction->order?->country?->rate
                ?? $transaction->order?->orderItems?->first()?->product?->country?->rate
                ?? 1;

            return (float) $transaction->amount * (float) $rate;
        });

        $data = $query->paginate(50);

        $data->getCollection()->transform(function ($transaction) {
            $rate = $transaction->order?->country?->rate
                ?? $transaction->order?->orderItems?->first()?->product?->country?->rate
                ?? 1;
            $transaction->total_price = $transaction->amount * $rate;
            return $transaction;
        });
        $paginationData = $data->toArray();
        $response = array_merge($paginationData, ['total_amount' => $totalAmount]);
        return response()->json($response);
    }
    public function historyPrice(Request $request)
    {
        $query = HistoryPriceProductModel::with('product:id,name,code,country_id', 'product.country:id,name,sign')->orderBy('id', 'desc');
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%" . $search . "%")
                    ->orWhere('code', 'like', "%" . $search . "%");
            });
        }
        if ($request->filled('date')) {
            $dates = $request->input('date');

            // Lấy ngày đầu và ngày cuối, thêm khoảng thời gian đầy đủ
            $startDate = Carbon::parse($dates[0])->startOfDay(); // 2025-03-27 00:00:00
            $endDate   = Carbon::parse($dates[1] ?? $dates[0])->endOfDay(); // 2025-03-27 23:59:59

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        $data = $query->paginate(50);
        return response()->json($data);
    }
}
