<?php

namespace App\Http\Controllers\api\Artical;

use App\Http\Controllers\Controller;
use App\Models\CategoriesArticalModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArticalCategoryControllerApi extends Controller
{
    public function index(Request $request)
    {
        $query = CategoriesArticalModel::orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        $articalCategories = $query->paginate(50);
        return response()->json($articalCategories);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories_artical,name',
            'slug' => 'required|string|max:255|unique:categories_artical,slug',
            'status' => 'required|integer',
            'parent_id' => 'nullable',
        ], [
            'name.required' => 'Tên danh mục không được để trống.',
            'name.string' => 'Tên danh mục phải là chuỗi.',
            'name.max' => 'Tên danh mục phải nhỏ hơn 255 ký tự.',
            'slug.required' => 'Slug không được để trống.',
            'slug.string' => 'Slug phải là chuỗi.',
            'slug.max' => 'Slug phải nhỏ hơn 255 ký tự.',
            'status.required' => 'Trạng thái không được để trống',
            'slug.unique' => 'Slug đã tồn tại.',
            'name.unique' => 'Tên danh mục đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $category = $request->all();


        $categoryPost = CategoriesArticalModel::create([
            'name' => $category['name'],
            'slug' => $category['slug'],
            'status' => $category['status'],
            'parent_id' => $category['parent_id'] ?? 0,
            'created_at' => now()
        ]);


        return response()->json($categoryPost, 201);
    }
    public function parent()
    {
        $articalParentCategories = CategoriesArticalModel::where('parent_id', 0)->get();
        return response()->json($articalParentCategories);
    }
}
