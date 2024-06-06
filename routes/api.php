<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');


Route::middleware(['auth:api', 'scopes:create-transaction'])->group(function () {
    Route::post('/create-payment-link', [PaymentController::class, 'createPaymentLink']);
});

Route::middleware(['auth:api', 'scopes:update-transaction'])->group(function () {
    Route::post('/update-transaction-status', [TransactionController::class, 'updateStatus']);
});
