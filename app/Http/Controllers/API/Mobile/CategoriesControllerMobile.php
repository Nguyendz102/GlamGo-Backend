<?php

namespace App\Http\Controllers\api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CategoriesModel;
use Illuminate\Http\Request;

class CategoriesControllerMobile extends Controller
{
    public function index()
    {
        $query = CategoriesModel::with(['status'])->orderBy('created_at', 'desc');
        $query->where('status_id', 1);
        $categories = $query->paginate(20);
        $categories->getCollection()->transform(function ($category) {
            $parentCategory = CategoriesModel::find($category->parent_id);
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'slug' => $category->slug,
                'images' => $category->images,
                'parent_id' => $category->parent_id,
                'parent_name' => $parentCategory->name ?? null,
                'status_id' => $category->status_id,
            ];
        });
        return response()->json(['message' => 'Thành công', 'categories' => $categories, 'status' => 200], 200);
    }
}
