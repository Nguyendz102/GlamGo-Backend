<?php

namespace App\Http\Controllers;

use App\Models\BannerModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BannerControllerApi extends Controller
{
    function index()
    {
        $query = BannerModel::orderBy('created_at', 'desc')->paginate(10);

        return response()->json($query);
    }

    function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required',
            'image' => 'nullable|sometimes|image|mimes:jpg,jpeg,png,gif|max:2048',
            'image_alt' => 'required|string|max:255',
        ], [
            'url.required' => 'Url không được để trống.',
            'image.image' => 'File tải lên phải là hình ảnh.',
            'image.mimes' => 'Chỉ chấp nhận các định dạng jpg, jpeg, png, gif.',
            'image.required' => 'Ảnh không được để trống.',
            'image.max' => 'Dung lượng ảnh tối đa là 2MB.',
            'image_alt.required' => 'Alt ảnh không được để trống.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $imageUrl = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('banner', 'public');
            $imageUrl = '/storage/' . $imagePath;
        }
        $query = BannerModel::create([
            'image' => $imageUrl,
            'url' => $request->url,
            'image_alt' => $request->image_alt,
            'created_at' => now(),
        ]);

        return response()->json($query);
    }

    function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required',
            'image' => 'nullable|sometimes|image|mimes:jpg,jpeg,png,gif|max:2048',
        ], [
            'url.required' => 'Url không được để trống.',
            'image.image' => 'File tải lên phải là hình ảnh.',
            'image.mimes' => 'Chỉ chấp nhận các định dạng jpg, jpeg, png, gif.',
            'image.max' => 'Dung lượng ảnh tối đa là 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $banner = BannerModel::findOrFail($id);

        $banner->url = $request->url;
        $banner->image_alt = $request->image_alt;
        $banner->updated_at = now();

        if ($request->hasFile('image')) {
            if ($banner->image) {
                $oldImagePath = str_replace('/storage/', '', $banner->image);
                Storage::disk('public')->delete($oldImagePath);
            }

            $imagePath = $request->file('image')->store('banner', 'public');
            $banner->image = '/storage/' . $imagePath;
        }


        $banner->save();

        return response()->json([
            'success' => true,
            'message' => 'Banner đã được cập nhật thành công',
            'data' => $banner
        ]);
    }

    function delete(Request $request, $id)
    {
        $banner = BannerModel::findOrFail($id);
        $banner->delete();
        return response()->json([
            'success' => true,
            'message' => 'Banner đã được xóa thành công',
        ]);
    }

    function getBannerMobile()
    {
        $banner = BannerModel::orderBy('created_at', 'desc')->get();
        $response = [
            'data' => $banner,
            'message' => 'Thành công',
            'status' => 200,
        ];
        return response()->json($response);
    }
}
