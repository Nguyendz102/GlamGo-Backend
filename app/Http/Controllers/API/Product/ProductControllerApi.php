<?php

namespace App\Http\Controllers\api\Product;

use App\Http\Controllers\Controller;
use App\Models\ProductImagesModel;
use App\Models\ProductsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductControllerApi extends Controller
{
    private $rules, $message;
    public function __construct()
    {
        $this->rules = [
            'name' => 'required',
            'code' => 'required',
            'category_id' => 'required',
            'price' => 'required',
            'features' => 'required',
            'hashtag' => 'required',
            'status' => 'required',
            'image_alt' => 'required',
            'meta_title' => 'required',
            'slug' => 'required|unique:products,slug',
        ];
        $this->message = [
            'name.required' => 'Tên không được để trống',
            'code.required' => 'Mã không được để trống',
            'category_id.required' => 'Danh mục không được để trống',
            'price.required' => 'Giá không được để trống',
            'features.required' => 'Sản phẩm nổi bật không được để trống',
            'hashtag.required' => 'Từ khóa không được để trống',
            'status.required' => 'Trạng thái không được để trống',
            'slug.required' => 'Slug không được để trống',
            'slug.unique' => 'Slug đã tồn tại trong hệ thống!',
            'image_alt.required' => 'Alt ảnh chính không được để trống',
            'meta_title.required' => 'SEO tiêu đề sản phẩm không được để trống',
        ];
    }
    public function index(Request $request)
    {
        $query = ProductsModel::with([
            'category:id,name',
            'productImages:id,product_id,image,image_alt',
        ])->orderBy('created_at', 'desc');

        if ($request->name) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->code) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->tag) {
            $query->where('hashtag', 'like', '%' . $request->tag . '%');
        }
        if ($request->country) {
            $query->where('country_id', $request->country);
        }

        $products = $query->paginate(10);
        return response()->json($products);
    }

    public function uploadManyProductImage($files, $dataProduct = [])
    {
        try {
            if ($files && is_array($files)) {
                foreach ($files as $file) {
                    $storedPath = $file->store('image_product', 'public');

                    $url = '/storage/' . $storedPath;

                    $dataProductImage = array_merge($dataProduct, [
                        "image" => $url,
                        "image_alt" => $url,
                    ]);

                    ProductImagesModel::create($dataProductImage);
                }
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi upload ảnh liên quan: ' . $e->getMessage());
            throw $e; // Ném lại exception để xử lý ở hàm gọi
        }
    }
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), $this->rules, $this->message);
            if ($validator->fails()) {
                return response()->json(["message" => $validator->errors()], 422);
            }
            // Upload ảnh chính
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $imageUrl = '/storage/' . $imagePath;
            }
            $requestData = $request->all();
            $data = [
                'category_id' => $requestData['category_id'],
                'name' => $requestData['name'],
                'code' => $requestData['code'],
                'slug' => $requestData['slug'],
                'price' => str_replace([','], '', $requestData['price']),
                'import_price' => str_replace([','], '', $requestData['import_price']),
                'price_sale'        => str_replace([','], '', $requestData['price_sale']),
                'is_recommen'      => $requestData['is_recommen'],
                'features' => $requestData['features'],
                'meta_title' => $requestData['meta_title'],
                'meta_description' => $requestData['meta_description'],
                'hashtag' => $requestData['hashtag'],
                'image' => $imageUrl,
                'image_alt' => $requestData['image_alt'],
                'status' => $requestData['status'],
            ];
            $product = ProductsModel::create($data);
            if ($request->hasFile('related_images')) {
                $dataProduct = [
                    'product_id' => $product->id,
                    "product_attribute_value_id" => 0,
                    "status" => 1,
                ];
                $this->uploadManyProductImage($request->file('related_images'), $dataProduct);
            }
            return response()->json(["message" => "Sản phẩm đã được thêm thành công!"], 201);
        } catch (\Exception $e) {
            return response()->json(["message" => "Đã xảy ra lỗi khi thêm sản phẩm!", "error" => $e->getMessage()], 500);
        }
    }
    public function update(Request $request)
    {
        try {
            $this->rules['slug'] = 'required|unique:products,slug,' . $request->id;
            $validator = Validator::make($request->all(), $this->rules, $this->message);
            if ($validator->fails()) {
                return response()->json(["message" => $validator->errors()], 422);
            }
            $requestData = $request->all();

            // Xử lý ảnh chính
            if ($request->hasFile('image')) {
                // Xóa ảnh cũ nếu tồn tại
                $existingProduct = ProductsModel::find($request->id);
                if (
                    $existingProduct &&
                    $existingProduct->image &&
                    Storage::disk('public')->exists(str_replace('/storage/', '', $existingProduct->image))
                ) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $existingProduct->image));
                }

                // Upload ảnh mới
                $imagePath = $request->file('image')->store('products', 'public');
                $imageUrl = '/storage/' . $imagePath;
            } else {
                // Giữ nguyên ảnh cũ nếu không có ảnh mới
                $existingProduct = ProductsModel::find($request->id);
                $imageUrl = $existingProduct ? $existingProduct->image : null;
            }


            $data = [
                'category_id' => $requestData['category_id'],
                'name' => $requestData['name'],
                'code' => $requestData['code'],
                'slug' => $requestData['slug'],
                'price' => str_replace([','], '', $requestData['price']),
                'import_price' => str_replace([','], '', $requestData['import_price']),
                'price_sale' => str_replace([','], '', $requestData['price_sale']),
                'is_recommen' => $requestData['is_recommen'],
                'features' => $requestData['features'],
                'meta_title' => $requestData['meta_title'],
                'meta_description' => $requestData['meta_description'],
                'hashtag' => $requestData['hashtag'],
                'image' => $imageUrl,
                'image_alt' => $requestData['image_alt'],
                'status' => $requestData['status'],
            ];

            ProductsModel::where('id', '=', (int)$request->id)->update($data);
            if ($request->hasFile('related_images')) {
                // Upload ảnh mới
                $dataProduct = [
                    'product_id' => (int)$request->id,
                    "product_attribute_value_id" => 0,
                    "status" => 1,
                ];
                $this->uploadManyProductImage($request->file('related_images'), $dataProduct);
            }
            return response()->json(["message" => "Sản phẩm đã được cập nhật thành công!"], 200);
        } catch (\Exception $e) {
            return response()->json(["message" => "Đã xảy ra lỗi khi sửa sản phẩm!", "error" => $e->getMessage()], 500);
        }
    }
    public function destroy($id)
    {
        $product = ProductImagesModel::find($id);
        if ($product) {
            $imagePath = $product->image;
            // Xóa ảnh cũ nếu tồn tại
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $product->delete();
            return response()->json(["message" => "Xóa ảnh thành công!"], 200);
        }
        return response()->json(["message" => "Ảnh không tồn tại!"], 404);
    }
}
