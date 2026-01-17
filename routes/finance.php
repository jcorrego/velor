<?php

use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\AssetController;
use App\Http\Controllers\Finance\TransactionCategoryController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Finance\TransactionImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Accounts
    Route::apiResource('accounts', AccountController::class);

    // Transactions
    Route::apiResource('transactions', TransactionController::class);
    Route::post('transactions/{transaction}/reconcile', [TransactionController::class, 'reconcile'])->name('transactions.reconcile');

    // Transaction Import
    Route::post('import/preview/{account}', [TransactionImportController::class, 'preview'])->name('import.preview');
    Route::post('import/confirm/{account}', [TransactionImportController::class, 'store'])->name('import.confirm');
    Route::get('import/parsers', [TransactionImportController::class, 'getParsers'])->name('import.parsers');

    // Assets
    Route::apiResource('assets', AssetController::class);
    Route::get('assets/{asset}/valuations', [AssetController::class, 'valuations'])->name('assets.valuations');

    // Transaction Categories
    Route::apiResource('transaction-categories', TransactionCategoryController::class);
});
