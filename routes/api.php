<?php

use App\Http\Controllers\BasketTotalController;
use App\Http\Controllers\ProductsController;
use Illuminate\Support\Facades\Route;

Route::get('/products', ProductsController::class);
Route::post('/basket/total', BasketTotalController::class);
