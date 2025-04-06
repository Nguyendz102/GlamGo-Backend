<?php

use App\Http\Controllers\Api\Category\CategoryControllerApi;
use App\Http\Controllers\api\Mobile\CategoriesControllerMobile;
use App\Http\Controllers\api\Mobile\ProductsControllerMobile;
use App\Http\Controllers\api\product\ProductAttributeController;
use App\Http\Controllers\api\product\ProductAttributeValueController;
use App\Http\Controllers\api\Product\ProductControllerApi;
use App\Models\ProductAttributeModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// API WEB quản trị
Route::group(['prefix' => 'categories'], function () {
    Route::get('/', [CategoryControllerApi::class, 'index']);
    Route::post('/post-category', [CategoryControllerApi::class, 'store']);
    Route::post('/edit-category/{id}', [CategoryControllerApi::class, 'update']);
});
Route::prefix("products")->group(function () {
    Route::get("/", [ProductControllerApi::class, "index"]);
    Route::post("/", [ProductControllerApi::class, "store"]);
    Route::post("/{id}", [ProductControllerApi::class, "update"]);
    Route::delete("/{id}", [ProductControllerApi::class, "destroy"]);
});
Route::prefix("product-attribute")->group(function () {
    Route::get("/{id}", [ProductAttributeController::class, "index"]);
    Route::post("/{id}", [ProductAttributeController::class, "store"]);
    Route::put("/{id}", [ProductAttributeController::class, "update"]);
    Route::delete("/{id}", [ProductAttributeController::class, "destroy"]);
});

Route::prefix("product-attribute-value")->group(function () {
    Route::get("/{id}", [ProductAttributeValueController::class, "index"]);
    Route::post("/{id}", [ProductAttributeValueController::class, "store"]);
    Route::post("update/{id}", [ProductAttributeValueController::class, "update"]);
    Route::delete("/{id}", [ProductAttributeValueController::class, "destroy"]);
    Route::delete("delete-image/{id}", [ProductAttributeValueController::class, "deleteImage"]);
    Route::delete("delete-image-product-attribute-value/{id}", [ProductAttributeValueController::class, "deleteImageAttributeValue"]);
});


// API client App mobile
Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'categories'], function () {
        // lấy danh sách danh mục  /api/v1/categories
        Route::get('/', [CategoriesControllerMobile::class, 'index']);
    });
    Route::prefix("products")->group(function () {
        // lấy danh sách sản phẩm /api/v1/products
        Route::get("/", [ProductsControllerMobile::class, "index"]);
        // lấy danh sách sản phẩm theo danh mục /api/v1/products/get-products-by-category
        Route::get("/get-products-by-category", [ProductsControllerMobile::class, "getProductsByCategory"]);

        // Route::get("/get-products-details", [ProductsControllerMobile::class, "getProductsByCategory"]);
    });
});
