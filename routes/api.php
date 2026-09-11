<?php

use App\Orders\Infrastructure\Http\TableController;
use App\Orders\Infrastructure\Http\ProductController;
use App\Orders\Infrastructure\Http\OrderController;
use App\Orders\Infrastructure\Http\EstablishmentController;
use App\Orders\Infrastructure\Http\RecipeController;
use App\Orders\Infrastructure\Http\InventoryController;
use App\Orders\Infrastructure\Http\HealthController;
use Illuminate\Support\Facades\Route;

// ponytail: no auth in the PoC (no Sanctum installed); add `->middleware('auth:sanctum')` here when Sanctum is wired
Route::get('/health', HealthController::class);

Route::get('/tables', [TableController::class, 'index']);
Route::post('/tables/{id}/request-bill', [TableController::class, 'requestBill']);
Route::post('/tables/{id}/settle', [TableController::class, 'settle']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
Route::get('/establishment', [EstablishmentController::class, 'show']);
Route::put('/establishment', [EstablishmentController::class, 'update']);

Route::get('/recipes', [RecipeController::class, 'index']);
Route::get('/recipes/{id}', [RecipeController::class, 'show']);

Route::get('/inventory', [InventoryController::class, 'index']);
Route::get('/inventory/movements', [InventoryController::class, 'movements']);
Route::post('/inventory/waste', [InventoryController::class, 'waste']);
