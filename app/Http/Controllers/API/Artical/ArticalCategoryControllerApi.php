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
            'parent_id' => 'nullable',
            'status' => 'nullable|in:0,1',
        ], [
            'name.required' => 'Tên danh mục không được để trống.',
            'name.string' => 'Tên danh mục phải là chuỗi.',
            'name.max' => 'Tên danh mục phải nhỏ hơn 255 ký tự.',
            'slug.required' => 'Slug không được để trống.',
            'slug.string' => 'Slug phải là chuỗi.',
            'slug.max' => 'Slug phải nhỏ hơn 255 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',
            'name.unique' => 'Tên danh mục đã tồn tại.',
            'status.required' => 'Trạng thái không được để trống.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $category = $request->all();

        $categoryPost = CategoriesArticalModel::create([
            'name' => $category['name'],
            'slug' => $category['slug'],
            'status' =>  1,
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


    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255|unique:categories_artical,name,' . $id,
            'slug'      => 'required|string|max:255|unique:categories_artical,slug,' . $id,
            'parent_id' => 'nullable|integer|not_in:' . $id,
            'status'    => 'required|in:0,1',

        ], [
            'name.required'      => 'Tên danh mục không được để trống.',
            'name.string'        => 'Tên danh mục phải là chuỗi.',
            'name.max'           => 'Tên danh mục phải nhỏ hơn 255 ký tự.',
            'slug.required'      => 'Slug không được để trống.',
            'slug.string'        => 'Slug phải là chuỗi.',
            'slug.max'           => 'Slug phải nhỏ hơn 255 ký tự.',
            'slug.unique'        => 'Slug đã tồn tại.',
            'name.unique'        => 'Tên danh mục đã tồn tại.',
            'status.required' => 'Trạng thái không được để trống.',
            'parent_id.not_in'   => 'Danh mục không thể là cha của chính nó.',
            'status.in'          => 'Trạng thái không hợp lệ.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $category = CategoriesArticalModel::findOrFail($id);
        $data = $request->all();
        $category->update([
            'name'      => $data['name'],
            'slug'      => $data['slug'],
            'status'    => $data['status'],
            'parent_id' => $data['parent_id'] ?? 0,
            'updated_at' => now()
        ]);

        return response()->json($data, 201);
    }
    public function list(Request $request)
    {
        $articalCategories = CategoriesArticalModel::where('status', 1)->get();
        return response()->json($articalCategories);
    }
}
