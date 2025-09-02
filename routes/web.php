<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

// Payment redirect pages (served via web middleware to render Blade views)
Route::get('/payment/success', [PaymentController::class, 'success']);
Route::get('/payment/cancel', [PaymentController::class, 'cancel']);
