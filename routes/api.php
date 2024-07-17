<?php

use App\Http\Controllers\Affilinates\AffilinateController;
use App\Http\Controllers\Affilinates\TicketReplyController;
use App\Http\Controllers\Affilinates\WithdrawalTicketController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\Dashboard\ChartDashboardController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Products\ProductsController;
use App\Http\Controllers\Products\ProductVariationController;
use App\Http\Controllers\UploadController;
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
    Route::get('/users/role/user', [AuthController::class, 'listUsersWithRoleUser']);
    Route::post('users/login-or-register', [AuthController::class, 'loginOrRegister']);
    Route::post('/admin/login', [AuthController::class, 'loginWeb']);
    Route::post('/admin/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::put('/admin/change-password', [AuthController::class, 'changePassword']); //update password user with old password
    Route::put('/users/{id}', [AuthController::class, 'updateProfile']);//update info user
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    // Route::post('send-password-reset-link', [PasswordResetRequestController::class, 'sendEmail']);
    // Route::post('/reset-password', [ChangePasswordController::class, 'passwordResetProcess'])->name('password.update');
});

Route::post('order-items', [OrderController::class, 'placeOrder']);
Route::get('order-items', [OrderController::class, 'getOrders']);
Route::get('order-history', [OrderController::class, 'getOrderHistory']);
Route::get('/orders/filter', [OrderController::class, 'filterOrders']);
Route::put('/orders/{orderId}/note', [OrderController::class, 'updateNote']);


Route::post('/vnpay-return', [PaymentController::class, 'vnpayReturn']);


Route::post('upload-file', [UploadController::class, 'uploadFile']);
Route::resource('categories', CategoryController::class);

Route::resource('brands', BrandController::class);
Route::apiResource('coupons', CouponController::class);

Route::get('/search-products', [ProductsController::class, 'search']);
// Route::get('/search-products', [ProductsController::class, 'searchSQL']);

Route::get('related-product/{product}', [OrderController::class, 'getRelatedProducts']);
Route::get('order/best-seller/', [OrderController::class, 'getTopSellingProducts']);
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancelOrder']);

Route::post('products/import-excel', [ProductsController::class, 'import']);
Route::get('products/export-products', [ProductsController::class, 'exportProducts']);
Route::post('/products/update-commission-percentage', [ProductsController::class, 'updateSpecialCommissionPercentage']);

Route::get('/orders', [OrderController::class, 'index']);
Route::put('/orders/{orderId}/status', [OrderController::class, 'updateOrderStatus']);
Route::post('/update-order-status', [OrderController::class, 'updateOrderStatusCallback']);

Route::get('/referrals', [AffilinateController::class, 'index']);
Route::get('/referrals/{id}', [AffilinateController::class, 'show']);
Route::put('/referrals/{id}/status', [AffilinateController::class, 'updateStatus']);
Route::get('/referral-details/{userId}', [AffilinateController::class, 'getReferralDetails']);

Route::post('/withdrawal-tickets', [WithdrawalTicketController::class, 'store']);
Route::get('/withdrawal-tickets', [WithdrawalTicketController::class, 'index']);
Route::put('/withdrawal-tickets/{id}/status', [WithdrawalTicketController::class, 'updateStatus']);
Route::get('/user/{userId}/tickets', [WithdrawalTicketController::class, 'getTicketsByUser']);


Route::post('/withdrawal-tickets/{id}/replies', [TicketReplyController::class, 'store']);
Route::get('/withdrawal-tickets/{id}/replies', [TicketReplyController::class, 'index']);

Route::get('/orders/weekly-totals', [ChartDashboardController::class, 'getTotalOrdersByMonth']);


// Route::resource('categories', CategoryController::class)->only(['show', 'index']);
// Route::resource('brands', BrandController::class)->only(['show', 'index']);
// Route::resource('products', ProductsController::class)->only(['show', 'index']);
Route::resource('products', ProductsController::class);

Route::group(['middleware' => ['jwt.auth.admin']], function () {
    //category
    //brand
    //product
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
