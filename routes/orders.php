<?php

use App\Orders\Infrastructure\Http\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::post('/orders', [OrderController::class, 'store']);
    Route::patch('/orders/{orderId}/status', [OrderController::class, 'updateStatus']);
});
