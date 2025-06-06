<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use App\Models\CategoriesModel;
use App\Models\ProductsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryControllerApi extends Controller
{
    public function index(Request $request)
    {
        $query = CategoriesModel::orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        $categories = $query->paginate(50);
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
                'status_name' => $category->status->name ?? null,
                'status_color' => $category->status->color ?? null,
            ];
        });

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'status' => 'required|integer|exists:status,id',
            'images' => 'nullable|sometimes|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
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
            'status' => 'required|integer|exists:status,id',
            'country' => 'required|integer|exists:country,id',
            'images' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'parent_id' => 'nullable|integer|not_in:' . $id,

        ], [
            'name.required' => 'Tên danh mục không được để trống.',
            'name.string' => 'Tên danh mục phải là chuỗi.',
            'name.max' => 'Tên danh mục phải nhỏ hơn 255 ký tự.',
            'slug.required' => 'Slug không được để trống.',
            'country.required' => 'Quốc gia không được để trống.',
            'slug.string' => 'Slug phải là chuỗi.',
            'slug.max' => 'Slug phải nhỏ hơn 255 ký tự.',
            'status.required' => 'Trạng thái không được để trống',
            'slug.unique' => 'Slug đã tồn tại.',
            'images.image' => 'File tải lên phải là hình ảnh.',
            'images.mimes' => 'Chỉ chấp nhận các định dạng jpg, jpeg, png, gif.',
            'parent_id.not_in'   => 'Danh mục không thể là cha của chính nó.',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $category = CategoriesModel::findOrFail($id);

        $imageUrl = $category->images;

        if ($request->hasFile('images')) {
            // Delete old image if exists
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
            'country_id' => $request->country,
            'parent_id' => $request->parent_id ?? 0,
            'updated_at' => now()
        ]);

        return response()->json($category, 200);
    }

    public function listCategoryOn(Request $request)
    {
        $categories = CategoriesModel::where('status', 1)->orderBy('id', 'desc')->get();
        return response()->json(['data' => $categories], 200);
    }
    public function detail()
    {
        $categories = CategoriesModel::query()
            ->select('id', 'name', 'images')
            ->withCount('products as count')
            ->withSum('products as price', 'price')
            ->get();
        return response()->json($categories, 200);
    }
}
