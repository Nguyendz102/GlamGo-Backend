<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CategoriesModel;
use App\Models\OrderModel;
use App\Models\ProductsModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashBoardControllerApi extends Controller
{
    private const ORDER_DELIVERED = 4;

    public function index()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $countOrder = OrderModel::count();
        $countOrderMonth = OrderModel::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        $revenueTotal = $this->sumDeliveredOrderRevenue();
        $revenueMonth = $this->sumDeliveredOrderRevenue($startOfMonth, $endOfMonth);

        $countProduct = ProductsModel::count();
        $countCategories = CategoriesModel::count();

        $days = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $days[] = $date->format('d/m/Y');
        }

        $orders = OrderModel::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();

        $revenueByDay = OrderModel::leftJoin('country', 'order.country_id', '=', 'country.id')
            ->select(
                DB::raw('DATE(`order`.created_at) as date'),
                DB::raw('SUM(`order`.total_price * COALESCE(country.rate, 1)) as total')
            )
            ->where('order.status', self::ORDER_DELIVERED)
            ->whereBetween('order.created_at', [$startOfMonth, $endOfMonth])
            ->groupBy(DB::raw('DATE(`order`.created_at)'))
            ->get();

        $listOrders = [];
        $listOrdersPrice = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');

            $order = $orders->firstWhere('date', $formattedDate);
            $listOrders[] = $order ? $order->total : 0;

            $revenue = $revenueByDay->firstWhere('date', $formattedDate);
            $listOrdersPrice[] = $revenue ? $revenue->total : 0;
        }

        return response()->json([
            'dataTitle' => [
                'order_count' => $countOrder,
                'order_price' => $revenueTotal,
                'order_count_month' => $countOrderMonth,
                'order_price_month' => $revenueMonth,
                'product_count' => $countProduct,
                'categories_count' => $countCategories,
            ],
            'dataChar' => [
                'date' => $days,
                'list_order' => $listOrders,
                'price_order' => $listOrdersPrice,
            ],
            'startOfMonth' => $startOfMonth,
            'endOfMonth' => $endOfMonth,
        ], 200);
    }

    private function sumDeliveredOrderRevenue(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = OrderModel::with('country:id,rate')
            ->where('status', self::ORDER_DELIVERED);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return (float) $query->get()->sum(function ($order) {
            return $order->total_price * ($order->country->rate ?? 1);
        });
    }
}
