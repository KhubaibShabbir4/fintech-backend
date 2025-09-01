<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Merchant\MerchantController;
use App\Http\Controllers\Admin\MerchantApprovalController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =============================
// Auth Routes
// =============================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']); // creates admin or merchant
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Direct logout route (alternative to /auth/logout)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// =============================
// Public Checkout (Customer-Facing, No Auth)
// =============================
Route::prefix('payments')->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
    Route::post('/webhook', [PaymentController::class, 'webhook']); // Stripe callback
    Route::get('/status/{reference}', [CheckoutController::class, 'status']);
});

// Stripe redirect pages (singular path as required)
Route::get('/payment/success', [PaymentController::class, 'success']);
Route::get('/payment/cancel', [PaymentController::class, 'cancel']);

// =============================
// Merchant APIs (Protected)
// =============================
Route::middleware('auth:sanctum', 'role:merchant,api')->group(function () {
    Route::post('/merchant/register', [MerchantController::class, 'store']); // to update the registration
    Route::post('/merchant/onboarding-link', [MerchantController::class, 'onboardingLink']);
});


Route::middleware(['auth:sanctum', 'role:merchant,api', 'merchant.verified'])->group(function () {
    // Merchant profile management
    Route::get('/merchant/profile', [MerchantController::class, 'profile']);
    Route::get('/merchant/user-profile', [MerchantController::class, 'userProfile']);
    Route::post('/merchant/update', [MerchantController::class, 'update']);

    // Merchant Transactions
    Route::get('/merchant/transactions', [TransactionController::class, 'index']);
    Route::get('/merchant/transactions/export', [TransactionController::class, 'exportCsv']);
    Route::get('/merchant/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/merchant/transactions/{paymentId}/refund', [TransactionController::class, 'refund']);

    // Merchant Stats
    Route::get('/merchant/stats/revenue', [StatsController::class, 'revenue']);
    Route::get('/merchant/stats/methods', [StatsController::class, 'methods']);
    Route::get('/merchant/stats/transactions', [StatsController::class, 'transactions']);
});

// =============================
// Admin APIs (Protected)
// =============================
Route::middleware(['auth:sanctum', 'role:admin,api'])->group(function () {
    // Merchant Approvals
    Route::get('/admin/dashboard', [MerchantApprovalController::class, 'dashboard']);
    Route::post('/admin/approve-merchant/{id}', [MerchantApprovalController::class, 'approveMerchant']);
    Route::patch('/admin/merchants/{id}/status', [MerchantApprovalController::class, 'setStatus']);

    // Fetch all merchants with user information
    Route::get('/admin/merchants', [MerchantApprovalController::class, 'getAllMerchants']);

    // Transactions
    Route::get('/admin/transactions', [TransactionController::class, 'index']);
    Route::get('/admin/transactions/export', [TransactionController::class, 'exportCsv']);
    Route::get('/admin/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/admin/transactions/{paymentId}/refund', [TransactionController::class, 'refund']);

    // Stats
    Route::get('/admin/stats/revenue', [StatsController::class, 'revenue']);
    Route::get('/admin/stats/methods', [StatsController::class, 'methods']);
    Route::get('/admin/stats/transactions', [StatsController::class, 'transactions']);
});
