<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('livewire.auth.login');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('docs', 'docs')
    ->middleware(['auth', 'verified'])
    ->name('docs');

Route::view('finance/accounts', 'finance.accounts')
    ->middleware(['auth', 'verified'])
    ->name('finance.accounts');

Route::view('finance/transactions', 'finance.transactions')
    ->middleware(['auth', 'verified'])
    ->name('finance.transactions');

Route::view('finance/assets', 'finance.assets')
    ->middleware(['auth', 'verified'])
    ->name('finance.assets');

Route::view('finance/categories', 'finance.categories')
    ->middleware(['auth', 'verified'])
    ->name('finance.categories');

Route::view('finance/mappings', 'finance.mappings')
    ->middleware(['auth', 'verified'])
    ->name('finance.mappings');

Route::get('finance/import', function () {
    $accounts = \App\Models\Account::query()
        ->whereHas('entity', fn ($query) => $query->where('user_id', auth()->id()))
        ->orderBy('name')
        ->get();

    return view('finance.import-index', ['accounts' => $accounts]);
})->middleware(['auth', 'verified'])
    ->name('finance.import.index');

Route::get('finance/accounts/{account}/import', function (\App\Models\Account $account) {
    return view('finance.import', ['account' => $account]);
})->middleware(['auth', 'verified'])
    ->name('finance.import');

Route::view('finance/us-tax/owner-flow', 'finance.us-tax.owner-flow')
    ->middleware(['auth', 'verified'])
    ->name('finance.us-tax.owner-flow');

Route::view('finance/us-tax/schedule-e', 'finance.us-tax.schedule-e')
    ->middleware(['auth', 'verified'])
    ->name('finance.us-tax.schedule-e');

Route::view('finance/colombia-tax/summary', 'finance.colombia-tax.summary')
    ->middleware(['auth', 'verified'])
    ->name('finance.colombia-tax.summary');

require __DIR__.'/settings.php';
require __DIR__.'/management.php';
