<?php

use App\Http\Controllers\HoldController;
use App\Http\Controllers\ProductController;


Route::get('/products/{id}',[ProductController::class,'show']);
Route::post('/holds',[HoldController::class,'store']);
