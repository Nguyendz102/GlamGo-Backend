<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use App\Models\CategoriesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryControllerApi extends Controller
{
    public function index(Request $request)
    {
        $query = CategoriesModel::with(['status'])->orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        $categories = $query->paginate(10);
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
                'status' => $category->status,
            ];
        });

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'status' => 'required|integer',
            'images' => 'nullable|sometimes|image|mimes:jpg,jpeg,png,gif|max:2048',
        ], [
            'name.required' => 'Tên danh mục không được để trống.',
            'name.string' => 'Tên danh mục phải là chuỗi.',
            'name.max' => 'Tên danh mục phải nhỏ hơn 255 ký tự.',
            'slug.required' => 'Slug không được để trống.',
            'slug.string' => 'Slug phải là chuỗi.',
            'slug.max' => 'Slug phải nhỏ hơn 255 ký tự.',
            'status.required' => 'Trạng thái không được để trống',
            'slug.unique' => 'Slug đã tồn tại.',
            'images.image' => 'File tải lên phải là hình ảnh.',
            'images.mimes' => 'Chỉ chấp nhận các định dạng jpg, jpeg, png, gif.',
            'images.max' => 'Dung lượng ảnh tối đa là 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $imageUrl = null;

        if ($request->hasFile('images')) {
            $imagePath = $request->file('images')->store('public/categories');
            $imageUrl = Storage::url($imagePath);
        }

        $category = CategoriesModel::create([
            'name' => $request->name,
            'description' => $request->description,
            'slug' => $request->slug,
            'images' => $imageUrl,
            'status' => $request->status,
            'parent_id' => $request->parent_id ?? 0,
            'created_at' => now()
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $id,
            'status' => 'required|integer',
            'images' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120',
            'parent_id' => 'nullable|integer',
        ], [
            'name.required' => 'Tên danh mục không được để trống.',
            'name.string' => 'Tên danh mục phải là chuỗi.',
            'name.max' => 'Tên danh mục phải nhỏ hơn 255 ký tự.',
            'slug.required' => 'Slug không được để trống.',
            'slug.string' => 'Slug phải là chuỗi.',
            'slug.max' => 'Slug phải nhỏ hơn 255 ký tự.',
            'status.required' => 'Trạng thái không được để trống',
            'slug.unique' => 'Slug đã tồn tại.',
            'images.image' => 'File tải lên phải là hình ảnh.',
            'images.mimes' => 'Chỉ chấp nhận các định dạng jpg, jpeg, png, gif.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $category = CategoriesModel::findOrFail($id);

        $imageUrl = $category->images;

        if ($request->hasFile('images')) {
            if ($category->images) {
                $oldImagePath = str_replace('/storage', 'public', $category->images);
                Storage::delete($oldImagePath);
            }
            $imagePath = $request->file('images')->store('public/categories');
            $imageUrl = Storage::url($imagePath);
        }

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'slug' => $request->slug,
            'images' => $imageUrl,
            'status' => $request->status,
            'parent_id' => $request->parent_id ?? 0,
            'updated_at' => now()
        ]);

        return response()->json($category, 200);
    }
}
