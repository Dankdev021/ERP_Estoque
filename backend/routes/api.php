<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/produtos', [ProductController::class, 'index']);
Route::post('/produtos', [ProductController::class, 'store']);
Route::put('/produtos/{produto}', [ProductController::class, 'update']);
Route::patch('/produtos/{produto}', [ProductController::class, 'update']);
Route::delete('/produtos/{produto}', [ProductController::class, 'destroy']);
