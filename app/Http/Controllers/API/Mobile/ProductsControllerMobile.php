<?php

namespace App\Http\Controllers\api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ProductsModel;
use Illuminate\Http\Request;

class ProductsControllerMobile extends Controller
{
    private function activeProductQuery()
    {
        return ProductsModel::with([
            'category:id,name',
            'productImages:id,product_id,image,image_alt',
        ])
            ->where('status', 1)
            ->whereHas('category', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('created_at', 'desc');
    }

    private function paginatedResponse($query, Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 50));

        $products = $query->paginate($perPage)->toArray();
        $products['message'] = 'Thanh cong';
        $products['status'] = 200;

        return response()->json($products, 200);
    }

    public function index()
    {
        $query = $this->activeProductQuery();

        $products = $query->paginate(20)->toArray();
        $products['message'] = 'Thanh cong';
        $products['status'] = 200;

        return response()->json($products, 200);
    }

    public function getProductsByCategory(Request $request)
    {
        $query = $this->activeProductQuery();

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->paginate(20)->toArray();
        $products['message'] = 'Thanh cong';
        $products['status'] = 200;

        return response()->json($products, 200);
    }

    public function recommended(Request $request)
    {
        $query = $this->activeProductQuery()
            ->where('is_recommen', 1);

        return $this->paginatedResponse($query, $request);
    }

    public function featured(Request $request)
    {
        $query = $this->activeProductQuery()
            ->where('features', 0);

        return $this->paginatedResponse($query, $request);
    }

    public function getProductDetails(Request $request)
    {
        $product = ProductsModel::with([
            'productImages:id,product_id,image',
            'attribute:id,product_id,name',
            'attribute.attributeValue:id,product_attribute_id,name',
            'variants.attributeValues.attribute:id,name',
        ])->where('products.id', $request->id)->first();

        if (! $product) {
            return response()->json([
                'message' => 'San pham khong ton tai',
                'data' => null,
                'status' => 404,
            ], 404);
        }

        $sameCategory = ProductsModel::where('products.category_id', $product->category_id)
            ->where('products.status', 1)
            ->where('products.id', '!=', $product->id)
            ->with('productImages2')
            ->select(
                'products.name',
                'products.id',
                'products.image',
                'products.slug',
                'products.image_alt',
                'products.price',
                'products.price_sale'
            )
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $data = $product->toArray();
        $data['variants'] = $product->variants->map(fn ($variant) => [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'sku' => $variant->sku,
            'price' => $variant->price,
            'quantity' => (int) $variant->quantity,
            'status' => (int) $variant->status,
            'attribute_ids' => $variant->attributeValues->pluck('id')->map(fn ($id) => (int) $id)->values(),
            'attributes' => $variant->attributeValues->map(fn ($attributeValue) => [
                'attribute_id' => $attributeValue->product_attribute_id,
                'attribute_name' => $attributeValue->attribute?->name,
                'attribute_value_id' => $attributeValue->id,
                'attribute_value' => $attributeValue->name,
            ])->values(),
        ])->values();
        $data['sameCategory'] = $sameCategory;

        return response()->json([
            'message' => 'Thanh cong',
            'data' => $data,
            'status' => 200,
        ], 200);
    }
}
