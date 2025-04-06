<?php

namespace App\Http\Controllers\api\product;

use App\Http\Controllers\api\Product\ProductControllerApi;
use App\Http\Controllers\Controller;
use App\Models\ProductAttributeValuesModel;
use App\Models\ProductImagesModel;
use App\Models\ProductsModel;
use App\Models\ProductVariantValueImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductAttributeValueController extends Controller
{
    private $rules, $message, $productController;
    public function __construct()
    {
        $this->rules = [
            'name' => 'required',
            'price' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'product_images*' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ];
        $this->message = [
            'name.required' => 'Tên không được để trống',
            'price.required' => 'Giá không được để trống',
            'image.mimes' => 'Ảnh không hợp lệ',
            'product_images*.mimes' => 'Ảnh không hợp lệ',
        ];
        $this->productController = new ProductControllerApi;
    }
    public function index(Request $request, $id)
    {
        $query = ProductAttributeValuesModel::where("product_attribute_id", $id)->orderBy('created_at', 'desc');
        if ($request->name) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        $query->with([
            "productImages:id,product_attribute_value_id,image,image_alt",
            "productAttributeValueImage:id,product_attribute_value_id,image,alt_image",
            "attribute.product.country"
        ]);
        $query->paginate(50);
        $products = $query->paginate(50); // Lấy dữ liệu dạng phân trang
        // Format lại đường dẫn ảnh
        $products->getCollection()->transform(function ($product) {
            // Nếu có nhiều ảnh, format luôn các ảnh liên quan
            if ($product->productImages) {
                // foreach ($product->productImages as $image) {
                $product->image = Storage::url($product->productImages->image);
                // }
            }
            // Lấy `current` từ `country`
            $product->country_current = optional($product->attribute->product->country)->current;

            return $product;
        });
        return response()->json($products);
    }

    public function store(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), $this->rules, $this->message);
            if ($validator->fails()) {
                return response()->json(["message" => $validator->errors()], 422);
            }

            $requestData = $request->all();
            $data = [
                'product_attribute_id' => $id,
                'name' => $requestData['name'],
                'price' => str_replace([',', '.'], '', $requestData['price']),
            ];

            $attributeValue = ProductAttributeValuesModel::create($data);
            // upload ảnh
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('uploads/images', 'public');
                $dataProduct = [
                    'product_id' => 0,
                    "product_attribute_value_id" => $attributeValue->id,
                    "image" =>  $imagePath,
                    "image_alt" =>  $imagePath,
                    "status_id" => 0,
                ];
                ProductImagesModel::create($dataProduct);
            }

            $imageAttributeValuePath = null;
            // Lưu ảnh sản phẩm nếu có
            if ($request->hasFile('product_images')) {
                foreach ($request->file('product_images') as $image) {
                    $imageAttributeValuePath = $image->store('uploads/attribute_value_images', 'public');

                    ProductVariantValueImage::create([
                        'product_attribute_value_id' => $attributeValue->id,
                        'image' => $imageAttributeValuePath,
                        'alt_image' => $imageAttributeValuePath
                    ]);
                }
            }

            DB::commit();
            return response()->json(["message" => "Giá trị đã được thêm thành công!"], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["message" => "Vui lòng gọi IT", 'error' => $e->getMessage() ?? ''], 500);
        }
    }
    public function update(Request $request, $id)
    {
        // try {
        $validator = Validator::make($request->all(), $this->rules, $this->message);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()], 422);
        }
        $attributeValue = ProductAttributeValuesModel::find($id);

        $requestData = $request->all();
        $data = [
            'name' => $requestData['name'],
            'price' => str_replace([',', '.'], '', $requestData['price']),
        ];

        $attributeValue->update($data);
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = ProductImagesModel::where('product_attribute_value_id', $attributeValue->id)->first();
            $imagePath = $request->file('image')->store('uploads/images', 'public');

            $dataProduct = [
                'product_attribute_value_id' => $attributeValue->id,
                'image'  => $imagePath,
                'image_alt' => $imagePath,
                "product_id" => 0,
                "status_id" => 0,
            ];

            if ($image) {
                $image->update($dataProduct);
            } else {
                ProductImagesModel::create($dataProduct);
            }
        }

        // **Xử lý nhiều ảnh sản phẩm**
        if ($request->hasFile('product_images')) {
            $oldImages = ProductVariantValueImage::where('product_attribute_value_id', $attributeValue->id)->get();

            foreach ($oldImages as $oldImage) {
                if (Storage::disk('public')->exists($oldImage->image)) {
                    Storage::disk('public')->delete($oldImage->image);
                }
            }
            ProductVariantValueImage::where('product_attribute_value_id', $attributeValue->id)->delete();
            foreach ($request->file('product_images') as $image) {
                $imagePath = $image->store('uploads/attribute_value_images', 'public');

                ProductVariantValueImage::create([
                    'product_attribute_value_id' => $attributeValue->id,
                    'image' => $imagePath,
                    'alt_image' => $imagePath
                ]);
            }
        }


        // Xóa ảnh liên quan c�� nếu tồn tại
        // $requestData["image"] = json_decode($requestData["image"], true);
        // // dd( $requestData["image"]);
        // if (!empty($requestData["image"])) {
        //     $this->productController->deleteManyProductImage($requestData["image"], ["product_attribute_value_id" => $id]);
        // }

        // if ($request->hasFile('image_related')) {
        //     // dd($request->all());
        //     $dataProduct = [
        //         'product_attribute_value_id' => $product->id,
        //         "product_id" => 0,
        //         "status_id" => 0,
        //     ];
        //     $this->productController->uploadManyProductImage($request->file('image_related'), $dataProduct);
        // }
        // dd(123);

        return response()->json(["message" => "Giá trị đã được sửa thành công!"], 201);
        // } catch (\Exception $e) {
        //     return response()->json(["message" => "Vui lòng gọi IT"], 500);
        // }
    }

    // public function destroy($id)
    // {
    //     $product = ProductsModel::find($id);
    //     if ($product) {
    //         $product->update([
    //             "status_id" => 6,
    //         ]);
    //         return response()->json(["message" => "Giá trị đã ẩn thành công!"], 200);
    //     }
    //     return response()->json(["message" => "Giá trị không tồn tại!"], 404);
    // }

    public function deleteImage(Request $request, $id)
    {
        // Tìm ảnh trong database
        $image = ProductImagesModel::find($id);

        if (!$image) {
            return response()->json(['message' => 'Ảnh không tồn tại'], 404);
        }

        // Xóa file ảnh trong storage
        if (Storage::disk('public')->exists($image->image)) {
            Storage::disk('public')->delete($image->image);
        }

        // Xóa ảnh trong database
        $image->delete();

        return response()->json(['message' => 'Ảnh đã được xóa thành công']);
    }

    public function deleteImageAttributeValue(Request $request, $id)
    {
        // Tìm ảnh trong database
        $image = ProductVariantValueImage::find($id);

        if (!$image) {
            return response()->json(['message' => 'Ảnh không tồn tại'], 404);
        }

        // Xóa file ảnh trong storage
        if (Storage::disk('public')->exists($image->image)) {
            Storage::disk('public')->delete($image->image);
        }

        // Xóa ảnh trong database
        $image->delete();

        return response()->json(['message' => 'Ảnh đã được xóa thành công']);
    }

    // client

    public function showImageProductAttributeValue(Request $request, $id)
    {
        $mainImage = ProductVariantValueImage::where('product_attribute_value_id', $id)->firstOrFail();
        $dataImage = ProductVariantValueImage::where('product_attribute_value_id', $id)->get();
        return response()->json(['data' => $dataImage, 'image_main' => $mainImage]);
    }

    public function priceAttributeValue(Request $request, $id)
    {
        $data = ProductAttributeValuesModel::findOrFail($id);
        return response()->json([
            'data' => $data,
        ]);
    }
}
