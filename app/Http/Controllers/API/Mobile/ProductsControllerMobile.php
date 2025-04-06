<?php

namespace App\Http\Controllers\api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ProductsModel;
use Illuminate\Http\Request;

class ProductsControllerMobile extends Controller
{
    //
    public function index()
    {
        $query = ProductsModel::with([
            'category:id,name',
            'productImages:id,product_id,image,image_alt',
        ])->orderBy('created_at', 'desc');
        $query->where('status', 1);
        $query->whereHas('category', function ($q) {
            $q->where('status_id', 1);
        });
        $products = $query->paginate(20);
        return response()->json($products);
    }

    public function getProductsByCategory(Request $request)
    {
        $query = ProductsModel::with([
            'category:id,name',
            'productImages:id,product_id,image,image_alt',
        ])->orderBy('created_at', 'desc');
        $query->where('status', 1);
        $query->whereHas('category', function ($q) {
            $q->where('status_id', 1);
        });
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        $products = $query->paginate(20);
        return response()->json($products);
    }
}
