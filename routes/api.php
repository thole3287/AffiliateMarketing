<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\Products\ProductVariationController;
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

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('users/login-or-register', [AuthController::class, 'loginOrRegister']);
    Route::post('/admin/login', [AuthController::class, 'loginWeb']);
    Route::post('/admin/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::put('/admin/change-password', [AuthController::class, 'changePassword']); //update password user with old password
    Route::put('/users/{id}', [AuthController::class, 'update']);//update info user
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    // Route::post('send-password-reset-link', [PasswordResetRequestController::class, 'sendEmail']);
    // Route::post('/reset-password', [ChangePasswordController::class, 'passwordResetProcess'])->name('password.update');
});


Route::resource('categories', CategoryController::class)->only(['show', 'index']);
Route::resource('brands', BrandController::class)->only(['show', 'index']);
Route::resource('products', ProductsController::class)->only(['show', 'index']);
Route::group(['middleware' => ['jwt.auth.admin']], function () {
    //category
    Route::resource('categories', CategoryController::class);
    //brand
    Route::resource('brands', BrandController::class);
    //product
    Route::resource('products', ProductsController::class);
    //api variations
    Route::get('products/{product}/variations', [ProductVariationController::class, 'index']);
    Route::post('products/{product}/variations', [ProductVariationController::class, 'store']);
    Route::get('products/{product}/variations/{variation}', [ProductVariationController::class, 'show']);
    Route::put('products/{product}/variations/{variation}', [ProductVariationController::class, 'update']);
    Route::delete('products/{product}/variations/{variation}', [ProductVariationController::class, 'destroy']);
});



// Route::put('/categories/{id}', [CategoryController::class, 'update']);
// Route::apiResource('categories', CategoryController::class);
// Route::put('/categories/{id}', [CategoryController::class, 'update']);
