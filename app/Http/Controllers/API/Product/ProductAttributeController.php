<?php

namespace App\Http\Controllers\api\product;

use App\Http\Controllers\Controller;
use App\Models\ProductAttributeModel;
use App\Models\ProductsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductAttributeController extends Controller
{
    private $rules, $message;
    public function __construct()
    {
        $this->rules = [
            'name' => 'required',
        ];
        $this->message = [
            'name.required' => 'Tên không được để trống',
        ];
    }
    public function index(Request $request, $id)
    {
        $query = ProductAttributeModel::where("product_id", $id)->orderBy('created_at', 'desc');
        if ($request->name) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        $products = $query->paginate(50);
        return response()->json($products);
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), $this->rules, $this->message);
            if ($validator->fails()) {
                return response()->json(["message" => $validator->errors()], 422);
            }
            $requestData = $request->all();
            $data = [
                'product_id' => $id,
                'name' => $requestData['name'],
            ];
            $product = ProductAttributeModel::create($data);
            return response()->json(["message" => "Thuộc tính đã được thêm thành công!"], 201);
        } catch (\Exception $e) {
            return response()->json(["message" => "Vui lòng gọi IT"], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), $this->rules, $this->message);
            if ($validator->fails()) {
                return response()->json(["message" => $validator->errors()], 422);
            }
            // Upload ảnh chính
            $product = ProductAttributeModel::find($id);

            $requestData = $request->all();
            $data = [
                'name' => $requestData['name'],
            ];
            $product->update($data);

            return response()->json(["message" => "Thuộc tính đã được sửa thành công!"], 201);
        } catch (\Exception $e) {
            return response()->json(["message" => "Vui lòng gọi IT"], 500);
        }
    }

    // public function destroy($id)
    // {
    //     $product = ProductsModel::find($id);
    //     if ($product) {
    //         $product->update([
    //             "status_id" => 0,
    //         ]);
    //         return response()->json(["message" => "Thuộc tính đã ẩn thành công!"], 200);
    //     }
    //     return response()->json(["message" => "Thuộc tính không tồn tại!"], 404);
    // }
}
