<?php

namespace App\Http\Controllers\api\Product;

use App\Http\Controllers\Controller;
use App\Models\ProductsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductControllerApi extends Controller
{
    public function index(Request $request)
    {
        $query = ProductsModel::with([
            'category:id,name',
            'productImages:id,product_id,image,image_alt',
        ])->orderBy('created_at', 'desc');

        if ($request->name) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->code) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->tag) {
            $query->where('hashtag', 'like', '%' . $request->tag . '%');
        }
        if ($request->country) {
            $query->where('country_id', $request->country);
        }

        $query->paginate(50);
        $products = $query->paginate(10); // Lấy dữ liệu dạng phân trang
        // Format lại đường dẫn ảnh
        $products->getCollection()->transform(function ($product) {
            if ($product->image) {
                $product->image = Storage::url($product->image);
            }

            // Nếu có nhiều ảnh, format luôn các ảnh liên quan
            if ($product->productImages) {
                foreach ($product->productImages as $image) {
                    $image->image = Storage::url($image->image);
                }
            }
            return $product;
        });

        return response()->json($products);
    }
}
