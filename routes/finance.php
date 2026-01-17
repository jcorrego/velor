<?php

use App\Http\Controllers\Api\ExternalServiceIntegrationController;
use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\AssetController;
use App\Http\Controllers\Finance\CategoryTaxMappingController;
use App\Http\Controllers\Finance\ReportController;
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

    // Category Tax Mappings
    Route::apiResource('category-tax-mappings', CategoryTaxMappingController::class)->only(['index', 'store', 'destroy']);

    // Reports
    Route::get('reports/rental-properties/{asset}', [ReportController::class, 'rentalProperty'])
        ->name('reports.rental-properties');

    // External Service Integrations
    Route::post('integrations/mercury/verify', [ExternalServiceIntegrationController::class, 'verifyMercury'])->name('integrations.mercury.verify');
    Route::get('integrations/mercury/rate-limit', [ExternalServiceIntegrationController::class, 'getMercuryRateLimit'])->name('integrations.mercury.rate-limit');
});

// Webhook endpoints (no auth required, signature verified instead)
Route::post('webhooks/{service}', [ExternalServiceIntegrationController::class, 'handleWebhook'])->name('webhooks.handle');
