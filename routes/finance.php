<?php

use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\AssetController;
use App\Http\Controllers\Finance\TransactionCategoryController;
use App\Http\Controllers\Finance\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Accounts
    Route::apiResource('accounts', AccountController::class);

    // Transactions
    Route::apiResource('transactions', TransactionController::class);
    Route::post('transactions/{transaction}/reconcile', [TransactionController::class, 'reconcile'])->name('transactions.reconcile');

    // Assets
    Route::apiResource('assets', AssetController::class);
    Route::get('assets/{asset}/valuations', [AssetController::class, 'valuations'])->name('assets.valuations');

    // Transaction Categories
    Route::apiResource('transaction-categories', TransactionCategoryController::class);
});
