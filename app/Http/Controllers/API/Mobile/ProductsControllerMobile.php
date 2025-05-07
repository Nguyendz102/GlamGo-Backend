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
            $q->where('status', 1);
        });
        $products = $query->paginate(20)->toArray();
        $products['message'] = 'Thành công';
        $products['status'] = 200;
        return response()->json($products, 200);
    }

    public function getProductsByCategory(Request $request)
    {
        $query = ProductsModel::with([
            'category:id,name',
            'productImages:id,product_id,image,image_alt',
        ])->orderBy('created_at', 'desc');
        $query->where('status', 1);
        $query->whereHas('category', function ($q) {
            $q->where('status', 1);
        });
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        $products = $query->paginate(20)->toArray();
        $products['message'] = 'Thành công';
        $products['status'] = 200;
        return response()->json($products, 200);
    }

    public function getProductDetails(Request $request)
    {
        $query = ProductsModel::with([
            'productImages:id,product_id,image',
            'attribute:id,product_id,name',
            'attribute.attributeValue:id,product_attribute_id,name',
        ])->where('products.id', $request->id)
            ->first();
        $sameCategory = ProductsModel::where('products.category_id', $query->category_id)
            ->where('products.status', 1)
            ->where('products.id', '!=', $query->id)
            ->with('productImages2')
            ->select('products.name', 'products.id', 'products.image', 'products.slug', 'products.image_alt', 'products.price', 'products.price_sale')
            ->orderBy('id', 'desc')
            ->get();
        $data = $query->toArray();
        $data['sameCategory'] = $sameCategory;
        $data['message'] = 'Thành công';
        return response()->json(['message' => 'Thành công', 'data' => $query, 'sameCategory' => $sameCategory, 'status' => 200], 200);
    }
}
