<?php

use App\Http\Controllers\Api\ProductListController;
use App\Http\Controllers\Api\PurchaseCheckController;
use Illuminate\Support\Facades\Route;

// Used by GoatFlow's "Requisição HTTP" block. Contract: C:\laragon\www\integracao-juliana\CONTRATO.md, 2.1.
Route::get('funil/comprou', PurchaseCheckController::class)
    ->middleware('throttle:120,1')
    ->name('api.funil.comprou');

// Lets GoatFlow's "Comprou na plataforma?" block offer the products in a list. Contract 2.3.
Route::get('funil/produtos', ProductListController::class)
    ->middleware('throttle:120,1')
    ->name('api.funil.produtos');
