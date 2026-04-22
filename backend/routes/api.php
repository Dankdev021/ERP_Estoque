<?php

use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ProdutoController;
use Illuminate\Support\Facades\Route;

Route::prefix('compras')->name('compras.')->group(function () {
    Route::get('/', [CompraController::class, 'index'])->name('index');
    Route::post('/', [CompraController::class, 'store'])->name('store');
});

Route::prefix('produtos')->name('produtos.')->group(function () {
    Route::get('/', [ProdutoController::class, 'index'])->name('index');
    Route::post('/', [ProdutoController::class, 'store'])->name('store');
    Route::match(['put', 'patch'], '/{produto}', [ProdutoController::class, 'update'])->name('update');
    Route::delete('/{produto}', [ProdutoController::class, 'destroy'])->name('destroy');
});
