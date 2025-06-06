<?php

use App\Http\Controllers\api\Artical\ArticalCategoryControllerApi;
use App\Http\Controllers\api\artical\ArticalControllerApi;
use App\Http\Controllers\Api\Category\CategoryControllerApi;
use App\Http\Controllers\api\DashBoardControllerApi;
use App\Http\Controllers\api\mobile\ArticalControllerMobile;
use App\Http\Controllers\api\Mobile\CategoriesControllerMobile;
use App\Http\Controllers\api\Mobile\ProductsControllerMobile;
use App\Http\Controllers\API\Orders\DetailOrdersController;
use App\Http\Controllers\api\orders\OrderControllerApi;
use App\Http\Controllers\api\product\ProductAttributeController;
use App\Http\Controllers\api\product\ProductAttributeValueController;
use App\Http\Controllers\api\Product\ProductControllerApi;
use App\Http\Controllers\API\Transactions\TransactionControllerApi;
use App\Http\Controllers\BannerControllerApi;
use App\Http\Controllers\CouponControllerApi;
use App\Models\BannerModel;
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
Route::prefix("dashboard")->group(function () {
    Route::get('/', [DashBoardControllerApi::class, 'index']);
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


Route::group(['prefix' => 'artical-categories'], function () {
    Route::get('/', [ArticalCategoryControllerApi::class, 'index']);
    Route::get('/list', [ArticalCategoryControllerApi::class, 'list']);
    Route::get("/parent", [ArticalCategoryControllerApi::class, "parent"]);
    Route::post('/post-parent', [ArticalCategoryControllerApi::class, 'store']);
    Route::put('/update-parent/{id}', [ArticalCategoryControllerApi::class, 'update']);
});

Route::group(['prefix' => 'artical'], function () {
    Route::get('/', [ArticalControllerApi::class, 'index']);
    Route::post('/post-artical', [ArticalControllerApi::class, 'store']);
    Route::post('/update-artical/{id}', [ArticalControllerApi::class, 'update']);
});

Route::prefix("coupon")->group(function () {
    Route::get("/", [CouponControllerApi::class, "index"]);
    Route::post("/post-coupon", [CouponControllerApi::class, "store"]);
    Route::put("/edit/{id}", [CouponControllerApi::class, "edit"]);
    Route::get('/detail/{id}', [CouponControllerApi::class, 'detail']);
});

Route::group(['prefix' => 'transactions'], function () {
    Route::get('/', [TransactionControllerApi::class, 'index']);
    Route::get('/history-price', [TransactionControllerApi::class, 'historyPrice']);
});

Route::prefix("banner")->group(function () {
    Route::get("/", [BannerControllerApi::class, "index"]);
    Route::post("/", [BannerControllerApi::class, "store"]);
    Route::post("/{id}", [BannerControllerApi::class, "update"]);
    Route::delete("/{id}", [BannerControllerApi::class, "delete"]);
});

Route::group(['prefix' => 'orders'], function () {
    Route::get('/', [OrderControllerApi::class, 'index']);
    Route::post('/create', [OrderControllerApi::class, 'create']);
    Route::post('/check-data', [OrderControllerApi::class, 'checkData']);
    Route::get('/status', [OrderControllerApi::class, 'getStatusOrder']);
    Route::post('/edit/{id}', [OrderControllerApi::class, 'edit']);
    Route::get('/detail/{id}', [DetailOrdersController::class, 'index']);
});

Route::group(['prefix' => 'categories'], function () {
    Route::get('/', [CategoryControllerApi::class, 'index']);
    Route::get('/detail', [CategoryControllerApi::class, 'detail']);
    Route::post('/post-category', [CategoryControllerApi::class, 'store']);
    Route::post('/edit-category/{id}', [CategoryControllerApi::class, 'update']);
    //ds danh mục hoạt động
    Route::get('/on', [CategoryControllerApi::class, 'listCategoryOn']);
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
        // lấy chi tiết sản phẩm /api/v1/products/get-products-details
        Route::get("/get-products-details", [ProductsControllerMobile::class, "getProductDetails"]);
    });
    Route::group(['prefix' => 'artical'], function () {
        // lấy danh sách  bài viết  /api/v1/artical, truyền category_artical_id để lấy bài viết theo danh mục, truyền is_hot có value là 1 để lâý các bài viết nổi bật
        Route::get('/', [ArticalControllerMobile::class, 'index']);
        // lấy danh sách danh mục bài viết  /api/v1/artical/categories
        Route::get('/categories', [ArticalControllerMobile::class, 'categories']);
    });


    // api lấy ra các ảnh banner api/v1/get-banner
    Route::get('/get-banner', [BannerControllerApi::class, 'getBannerMobile']);
});
