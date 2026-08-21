<?php

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

use App\Orders\Infrastructure\Http\TableController;
use App\Orders\Infrastructure\Http\ProductController;
use App\Orders\Infrastructure\Http\OrderController;
use App\Orders\Infrastructure\Http\EstablishmentController;

Route::get('/tables', [TableController::class, 'index']);
Route::post('/tables/{id}/request-bill', [TableController::class, 'requestBill']);
Route::post('/tables/{id}/settle', [TableController::class, 'settle']);
Route::get('/products', [ProductController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
Route::get('/establishment', [EstablishmentController::class, 'show']);
Route::put('/establishment', [EstablishmentController::class, 'update']);

