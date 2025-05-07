<?php

namespace App\Http\Controllers\api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CategoriesModel;
use Illuminate\Http\Request;

class CategoriesControllerMobile extends Controller
{
    public function index()
    {
        $categories = CategoriesModel::with('status')
            ->where('status', 1)
            ->orderByDesc('created_at')
            ->paginate(20);

        $categories->getCollection()->transform(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'slug' => $category->slug,
                'images' => $category->images,
                'parent_id' => $category->parent_id,
                'parent_name' => optional(CategoriesModel::find($category->parent_id))->name,
                'status' => $category->status,
            ];
        });

        return response()->json([
            'status' => 200,
            'message' => 'Thành công',
            ...$categories->toArray(),
        ]);
    }
}
