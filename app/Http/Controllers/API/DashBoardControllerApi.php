<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CategoriesModel;
use App\Models\OrderModel;
use App\Models\ProductsModel;
use App\Models\TransactionsModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashBoardControllerApi extends Controller
{
    public function index()
    {
        $countOrder = OrderModel::count();
        $countPriceOrder = OrderModel::with('country:id,rate')
            ->get()
            ->sum(function ($order) {
                return $order->total_price * ($order->country->rate ?? 1);
            });

        $countOrderMonth = OrderModel::whereMonth('created_at', date('m'))->count();

        $countPriceOrderMonth = OrderModel::with('country:id,rate')
            ->whereMonth('created_at', date('m'))
            ->get()
            ->sum(function ($order) {
                return $order->total_price * ($order->country->rate ?? 1);
            });



        $countProduct = ProductsModel::count();
        $countCategories = CategoriesModel::count();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // lấy mảng ngày của tháng hiện tại
        $days = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $days[] = $date->format('d/m/Y');
        }
        // lấy ra tổng số lượng các đơn hàng theo ngày trong tháng này
        $orders = OrderModel::select(DB::raw("DATE(created_at) as date"), DB::raw("count(*) as total"))
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy(DB::raw("DATE(created_at)"))
            ->get();


        // cái này là tính giá theo giá trị của orders 
        // $price_orders = OrderModel::join('country', 'order.country_id', '=', 'country.id')
        //     ->select(
        //         DB::raw("DATE(order.created_at) as date"),
        //         DB::raw("SUM(order.total_price * country.rate) as total")
        //     )
        //     ->whereBetween('order.created_at', [$startOfMonth, $endOfMonth])
        //     ->groupBy(DB::raw("DATE(order.created_at)"))
        //     ->get();

        // tính giá theo giá trị cả transition
        $price_orders = TransactionsModel::whereBetween('transactions.created_at', [$startOfMonth, $endOfMonth])
            ->select(
                DB::raw("DATE(transactions.created_at) as date"),
                DB::raw("SUM(transactions.amount) as total")
            )
            ->groupBy(DB::raw("DATE(transactions.created_at)"))
            ->get();
        $listOrders = [];
        $listOrdersPrice = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            // lấy số lượng đơn hàng ngày hiện tại
            $order = $orders->firstWhere('date', $formattedDate);
            $listOrders[] = $order ? $order->total : 0;
            // lấy số giá trị đơn hàng ngày hiện đang tính
            $priceOrder = $price_orders->firstWhere('date', $formattedDate);
            $listOrdersPrice[] = $priceOrder ? $priceOrder->total : 0;
        }
        $dataTotal = [
            'order_count' => $countOrder,
            'order_price' => $countPriceOrder,

            'order_count_month' => $countOrderMonth,
            'order_price_month' => $countPriceOrderMonth,

            'product_count' => $countProduct,
            'categories_count' => $countCategories,
        ];
        $data_char = [
            'date' => $days,
            'list_order' => $listOrders,
            'price_order' => $listOrdersPrice,

        ];

        return response()->json([
            'dataTitle' => $dataTotal,
            'dataChar' => $data_char,
            'startOfMonth' => $startOfMonth,
            'endOfMonth' => $startOfMonth,
        ], 200);
    }
}
