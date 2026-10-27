<?php

use App\Http\Controllers\Api\PurchaseCheckController;
use Illuminate\Support\Facades\Route;

// Used by GoatFlow's "Requisição HTTP" block. Contract: C:\laragon\www\integracao-juliana\CONTRATO.md, 2.1.
Route::get('funil/comprou', PurchaseCheckController::class)
    ->middleware('throttle:120,1')
    ->name('api.funil.comprou');
