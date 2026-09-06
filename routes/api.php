<?php

use App\Orders\Infrastructure\Http\TableController;
use App\Orders\Infrastructure\Http\ProductController;
use App\Orders\Infrastructure\Http\OrderController;
use App\Orders\Infrastructure\Http\EstablishmentController;
use Illuminate\Support\Facades\Route;

// ponytail: PoC has no real auth (no Sanctum installed); gate the only mutating endpoint that touches global config
Route::put('/establishment', [EstablishmentController::class, 'update'])
    ->middleware('auth');

Route::get('/tables', [TableController::class, 'index']);
Route::post('/tables/{id}/request-bill', [TableController::class, 'requestBill']);
Route::post('/tables/{id}/settle', [TableController::class, 'settle']);
Route::get('/products', [ProductController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
Route::get('/establishment', [EstablishmentController::class, 'show']);