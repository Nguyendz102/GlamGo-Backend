<?php

namespace App\Http\Controllers\api\artical;

use App\Http\Controllers\Controller;
use App\Models\ArticalModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArticalControllerApi extends Controller
{
    public function index(Request $request)
    {
        $query = ArticalModel::with('categoryArtical', 'product')->orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }
        $articalCategories = $query->paginate(50);
        return response()->json($articalCategories);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_artical_id' => 'required|integer|exists:categories_artical,id',
            'title' => 'required|string|max:255|unique:artical,title',
            'meta_tittle' => 'required|string|max:255',
            'meta_description' => 'required|string|max:500',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'content' => 'required|string',
            'slug' => 'required|string|max:255|unique:artical,slug',
            'status'    => 'required|in:0,1',
            'is_hot'    => 'required|in:0,1',
        ], [
            'category_artical_id.required' => 'Danh mục bài viết không được để trống.',
            'category_artical_id.integer' => 'Danh mục bài viết phải là số nguyên.',
            'category_artical_id.exists' => 'Danh mục bài viết không tồn tại.',

            'title.required' => 'Tiêu đề không được để trống.',
            'title.string' => 'Tiêu đề phải là chuỗi.',
            'title.max' => 'Tiêu đề phải nhỏ hơn 255 ký tự.',
            'title.unique' => 'Tiêu đã tồn tại.',

            'meta_tittle.required' => 'Tiêu đề SEO không được để trống.',
            'meta_tittle.string' => 'Tiêu đề SEO phải là chuỗi.',
            'meta_tittle.max' => 'Tiêu đề SEO phải nhỏ hơn 255 ký tự.',

            'meta_description.required' => 'Mô tả SEO  không được để trống.',
            'meta_description.string' => 'Mô tả SEO phải là chuỗi.',
            'meta_description.max' => 'Mô tả SEO  phải nhỏ hơn 500 ký tự.',

            'image.required' => 'Ảnh không được để trống.',
            'image.image' => 'Ảnh phải là một tệp hình ảnh.',
            'image.mimes' => 'Ảnh chỉ được có định dạng jpeg, png, jpg, gif.',
            'image.max' => 'Ảnh không được vượt quá 2MB.',

            'content.required' => 'Nội dung không được để trống.',
            'content.string' => 'Nội dung phải là chuỗi.',

            'slug.required' => 'Slug không được để trống.',
            'slug.string' => 'Slug phải là chuỗi.',
            'slug.max' => 'Slug phải nhỏ hơn 255 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',

            'status.in'          => 'Trạng thái không hợp lệ.',
            'is_hot.in'          => 'Bài viết nổi bật không hợp lệ.',
            'status.required' => 'Trạng thái không được để trống.',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('articals', 'public');
            $imageUrl = '/storage/' . $imagePath;
        }

        $article = $request->all();
        if ($article['product_id'] == null) {
            $article['product_id'] = 0;
        }
        $article['image'] = $imageUrl;
        ArticalModel::create($article);

        return response()->json($article, 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_artical_id' => 'required|integer|exists:categories_artical,id',
            'title' => 'required|string|max:255|unique:artical,title,' . $id,
            'meta_tittle' => 'required|string|max:255',
            'meta_description' => 'required|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'content' => 'required|string',
            'slug' => 'required|string|max:255|unique:artical,slug,' . $id,
        ], [
            'category_artical_id.required' => 'Danh mục bài viết không được để trống.',
            'category_artical_id.integer' => 'Danh mục bài viết phải là số nguyên.',
            'category_artical_id.exists' => 'Danh mục bài viết không tồn tại.',

            'title.required' => 'Tiêu đề không được để trống.',
            'title.string' => 'Tiêu đề phải là chuỗi.',
            'title.max' => 'Tiêu đề phải nhỏ hơn 255 ký tự.',
            'title.unique' => 'Tiêu đã tồn tại.',

            'meta_tittle.required' => 'Tiêu đề SEO không được để trống.',
            'meta_tittle.string' => 'Tiêu đề SEO phải là chuỗi.',
            'meta_tittle.max' => 'Tiêu đề SEO phải nhỏ hơn 255 ký tự.',

            'meta_description.required' => 'Mô tả SEO  không được để trống.',
            'meta_description.string' => 'Mô tả SEO phải là chuỗi.',
            'meta_description.max' => 'Mô tả SEO  phải nhỏ hơn 500 ký tự.',

            'image.required' => 'Ảnh không được để trống.',
            'image.image' => 'Ảnh phải là một tệp hình ảnh.',
            'image.mimes' => 'Ảnh chỉ được có định dạng jpeg, png, jpg, gif.',
            'image.max' => 'Ảnh không được vượt quá 2MB.',

            'content.required' => 'Nội dung không được để trống.',
            'content.string' => 'Nội dung phải là chuỗi.',

            'slug.required' => 'Slug không được để trống.',
            'slug.string' => 'Slug phải là chuỗi.',
            'slug.max' => 'Slug phải nhỏ hơn 255 ký tự.',
            'slug.unique' => 'Slug đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $artical = ArticalModel::findOrFail($id);

        $imageUrl = $artical->image;

        if ($request->hasFile('image')) {
            if ($artical->images) {
                $oldImagePath = ltrim(str_replace('/storage/', '',  $artical->image), '/');
                Storage::disk('public')->delete($oldImagePath);
            }
            $imagePath = $request->file('image')->store('artical', 'public');
            $imageUrl = '/storage/' . $imagePath;
        }
        $data = $request->all();
        $data['image'] = $imageUrl;
        $artical->update($data);

        return response()->json($data, 201);
    }
}
