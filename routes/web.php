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

Route::view('finance', 'finance')
    ->middleware(['auth', 'verified'])
    ->name('finance');

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

require __DIR__.'/settings.php';
require __DIR__.'/management.php';
