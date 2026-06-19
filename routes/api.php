<?php

use App\Http\Controllers\api\Artical\ArticalCategoryControllerApi;
use App\Http\Controllers\api\artical\ArticalControllerApi;
use App\Http\Controllers\Api\Category\CategoryControllerApi;
use App\Http\Controllers\api\DashBoardControllerApi;
use App\Http\Controllers\api\mobile\ArticalControllerMobile;
use App\Http\Controllers\API\Mobile\CartControllerMobile;
use App\Http\Controllers\api\Mobile\CategoriesControllerMobile;
use App\Http\Controllers\API\Mobile\CouponControllerMobile;
use App\Http\Controllers\API\Mobile\FavoriteProductControllerMobile;
use App\Http\Controllers\API\Mobile\NotificationControllerMobile;
use App\Http\Controllers\API\Mobile\OrderControllerMobile;
use App\Http\Controllers\API\Mobile\ProductRatingControllerMobile;
use App\Http\Controllers\API\Mobile\UserAddressControllerMobile;
use App\Http\Controllers\API\Mobile\VnpayPaymentControllerMobile;
use App\Http\Controllers\api\Mobile\ProductsControllerMobile;
use App\Http\Controllers\API\Orders\DetailOrdersController;
use App\Http\Controllers\api\orders\OrderControllerApi;
use App\Http\Controllers\api\product\ProductAttributeController;
use App\Http\Controllers\api\product\ProductAttributeValueController;
use App\Http\Controllers\api\Product\ProductControllerApi;
use App\Http\Controllers\API\Product\ProductVariantController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\CustomerControllerApi;
use App\Http\Controllers\API\RatingControllerApi;
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

Route::prefix('admin/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'loginAdmin']);
    Route::post('/register', [AuthController::class, 'registerAdmin']);
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
Route::prefix("customers")->group(function () {
    Route::get("/", [CustomerControllerApi::class, "index"]);
    Route::get("/{id}", [CustomerControllerApi::class, "show"]);
    Route::patch("/{id}/status", [CustomerControllerApi::class, "updateStatus"]);
});

Route::prefix("chat")->group(function () {
    Route::get("/conversations", [ChatController::class, "adminConversations"]);
    Route::get("/customers/{customer}/messages", [ChatController::class, "adminMessages"]);
    Route::post("/customers/{customer}/messages", [ChatController::class, "adminSend"]);
    Route::post("/customers/{customer}/take-over", [ChatController::class, "adminTakeOver"]);
    Route::post("/customers/{customer}/release-to-bot", [ChatController::class, "adminReleaseToBot"]);
});

Route::prefix("ratings")->group(function () {
    Route::get("/", [RatingControllerApi::class, "index"]);
    Route::post("/{id}/reply", [RatingControllerApi::class, "reply"]);
    Route::patch("/{id}/status", [RatingControllerApi::class, "updateStatus"]);
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
    Route::get("/{id}/variants", [ProductVariantController::class, "index"]);
    Route::post("/{id}/variants", [ProductVariantController::class, "store"]);
    Route::put("/{id}/variants/{variantId}", [ProductVariantController::class, "update"]);
    Route::delete("/{id}/variants/{variantId}", [ProductVariantController::class, "destroy"]);
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
    Route::post('/discount', [CouponControllerApi::class, 'getDiscount']);
    Route::delete('/{id}', [CouponControllerApi::class, 'destroy']);
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
});
// API client App mobile
Route::group(['prefix' => 'v1'], function () {
    Route::prefix('payments/vnpay')->group(function () {
        Route::get('/return', [VnpayPaymentControllerMobile::class, 'return']);
        Route::get('/ipn', [VnpayPaymentControllerMobile::class, 'ipn']);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'loginCustomer']);
        Route::post('/register', [AuthController::class, 'registerCustomer']);
        Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/wallet/top-up', [AuthController::class, 'topUpWallet']);
            Route::post('/push-subscription', [AuthController::class, 'syncPushSubscription']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
        Route::prefix('chat')->group(function () {
            Route::get('/messages', [ChatController::class, 'customerMessages']);
            Route::post('/messages', [ChatController::class, 'customerSend']);
        });

        Route::prefix('cart')->group(function () {
            Route::get('/', [CartControllerMobile::class, 'index']);
            Route::post('/items', [CartControllerMobile::class, 'store']);
            Route::put('/items/{id}', [CartControllerMobile::class, 'update']);
            Route::delete('/items/{id}', [CartControllerMobile::class, 'destroy']);
            Route::delete('/clear', [CartControllerMobile::class, 'clear']);
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderControllerMobile::class, 'index']);
            Route::post('/checkout', [OrderControllerMobile::class, 'checkout']);
            Route::get('/{id}', [OrderControllerMobile::class, 'show']);
            Route::post('/{id}/cancel', [OrderControllerMobile::class, 'cancel']);
        });

        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoriteProductControllerMobile::class, 'index']);
            Route::post('/{product}', [FavoriteProductControllerMobile::class, 'store']);
            Route::delete('/{product}', [FavoriteProductControllerMobile::class, 'destroy']);
        });

        Route::prefix('addresses')->group(function () {
            Route::get('/', [UserAddressControllerMobile::class, 'index']);
            Route::post('/', [UserAddressControllerMobile::class, 'store']);
            Route::get('/{id}', [UserAddressControllerMobile::class, 'show']);
            Route::put('/{id}', [UserAddressControllerMobile::class, 'update']);
            Route::delete('/{id}', [UserAddressControllerMobile::class, 'destroy']);
            Route::post('/{id}/default', [UserAddressControllerMobile::class, 'setDefault']);
        });

        Route::prefix('coupons')->group(function () {
            Route::get('/', [CouponControllerMobile::class, 'index']);
            Route::post('/validate', [CouponControllerMobile::class, 'validateCoupon']);
        });

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationControllerMobile::class, 'index']);
            Route::post('/read-all', [NotificationControllerMobile::class, 'markAllAsRead']);
            Route::post('/{id}/read', [NotificationControllerMobile::class, 'markAsRead']);
        });
    });

    Route::group(['prefix' => 'categories'], function () {
        // lấy danh sách danh mục  /api/v1/categories
        Route::get('/', [CategoriesControllerMobile::class, 'index']);
    });
    Route::prefix("products")->group(function () {
        // lấy danh sách sản phẩm /api/v1/products
        Route::get("/", [ProductsControllerMobile::class, "index"]);
        Route::get("/recommended", [ProductsControllerMobile::class, "recommended"]);
        Route::get("/featured", [ProductsControllerMobile::class, "featured"]);
        // lấy danh sách sản phẩm theo danh mục /api/v1/products/get-products-by-category
        Route::get("/get-products-by-category", [ProductsControllerMobile::class, "getProductsByCategory"]);
        // lấy chi tiết sản phẩm /api/v1/products/get-products-details
        Route::get("/get-products-details", [ProductsControllerMobile::class, "getProductDetails"]);
        Route::get("/{id}/ratings", [ProductRatingControllerMobile::class, "index"]);
        Route::middleware(['auth:sanctum', 'role:customer'])->post("/{id}/ratings", [ProductRatingControllerMobile::class, "store"]);
        Route::middleware(['auth:sanctum', 'role:customer'])->put("/{id}/ratings/{ratingId}", [ProductRatingControllerMobile::class, "update"]);
        Route::middleware(['auth:sanctum', 'role:customer'])->delete("/{id}/ratings/{ratingId}", [ProductRatingControllerMobile::class, "destroy"]);
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
