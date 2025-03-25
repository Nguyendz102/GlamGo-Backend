<?php

use App\Http\Controllers\Api\Category\CategoryControllerApi;
use App\Http\Controllers\api\Product\ProductControllerApi;
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
// API client App mobile