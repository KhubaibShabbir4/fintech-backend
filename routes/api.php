<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Merchant\MerchantController;
use App\Http\Controllers\Admin\MerchantApprovalController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\Public\CheckoutController;

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

// =============================
// Public Checkout (Customer-Facing, No Auth)
// =============================
Route::prefix('payments')->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
    Route::post('/webhook', [CheckoutController::class, 'webhook']); // Stripe callback
    Route::get('/status/{reference}', [CheckoutController::class, 'status']);
});

// =============================
// Merchant APIs (Protected)
// =============================
Route::middleware('auth:sanctum', 'role:merchant')->group(function () {
    Route::post('/merchant/register', [MerchantController::class, 'store']); // to update the registration
});


Route::middleware(['auth:sanctum', 'role:merchant', 'merchant.verified'])->group(function () {
    // Merchant profile management
    Route::get('/merchant/profile', [MerchantController::class, 'profile']);
    Route::post('/merchant/update', [MerchantController::class, 'update']);

    // Merchant Transactions
    Route::get('/merchant/transactions', [TransactionController::class, 'index']);
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
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Merchant Approvals
    Route::get('/admin/dashboard', [MerchantApprovalController::class, 'dashboard']);
    Route::post('/admin/approve-merchant/{id}', [MerchantApprovalController::class, 'approveMerchant']);
    Route::patch('/admin/merchants/{id}/status', [MerchantApprovalController::class, 'setStatus']);

    // Transactions
    Route::get('/admin/transactions', [TransactionController::class, 'index']);
    Route::get('/admin/transactions/{id}', [TransactionController::class, 'show']);
    Route::post('/admin/transactions/{paymentId}/refund', [TransactionController::class, 'refund']);

    // Stats
    Route::get('/admin/stats/revenue', [StatsController::class, 'revenue']);
    Route::get('/admin/stats/methods', [StatsController::class, 'methods']);
    Route::get('/admin/stats/transactions', [StatsController::class, 'transactions']);
});
