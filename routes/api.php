<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;


Route::post('/orders', [OrderController::class, 'store']);
Route::get('/customers/orders', [OrderController::class, 'history']);
Route::get('/products/low-stock', [OrderController::class, 'lowStock']);