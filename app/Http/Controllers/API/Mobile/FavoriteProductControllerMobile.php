<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Models\FavoriteProduct;
use App\Models\ProductsModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteProductControllerMobile extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = ProductsModel::query()
            ->whereHas('favorites', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->where('status', 1)
            ->with([
                'category:id,name',
                'productImages:id,product_id,image,image_alt',
            ])
            ->latest('id')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Thanh cong',
            'data' => $products,
        ]);
    }

    public function store(Request $request, ProductsModel $product): JsonResponse
    {
        if ((int) $product->status !== 1) {
            return response()->json([
                'status' => 422,
                'message' => 'San pham khong kha dung.',
            ], 422);
        }

        FavoriteProduct::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Da them san pham vao yeu thich.',
            'data' => $product->load([
                'category:id,name',
                'productImages:id,product_id,image,image_alt',
            ]),
        ], 201);
    }

    public function destroy(Request $request, ProductsModel $product): JsonResponse
    {
        FavoriteProduct::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Da xoa san pham khoi yeu thich.',
            'data' => null,
        ]);
    }
}
