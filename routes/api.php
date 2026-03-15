<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\GatewayController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/purchase', [PurchaseController::class, 'store']);

Route::middleware('auth.api')->group(function () {
    Route::get('/gateways', [GatewayController::class, 'index'])->middleware('role:ADMIN');
    Route::patch('/gateways/{gateway}/active', [GatewayController::class, 'toggle'])->middleware('role:ADMIN');
    Route::patch('/gateways/{gateway}/priority', [GatewayController::class, 'priority'])->middleware('role:ADMIN');

    Route::apiResource('users', UserController::class)->middleware('role:ADMIN,MANAGER');
    Route::apiResource('products', ProductController::class)->middleware('role:ADMIN,MANAGER,FINANCE');

    Route::get('/clients', [ClientController::class, 'index'])->middleware('role:ADMIN,MANAGER');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->middleware('role:ADMIN,MANAGER');

    Route::get('/transactions', [TransactionController::class, 'index'])->middleware('role:ADMIN,MANAGER,FINANCE');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->middleware('role:ADMIN,MANAGER,FINANCE');
    Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->middleware('role:ADMIN,FINANCE');
});
