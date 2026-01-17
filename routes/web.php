<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('finance', 'finance')
    ->middleware(['auth', 'verified'])
    ->name('finance');

Route::get('finance/accounts/{account}/import', function (\App\Models\Account $account) {
    return view('finance.import', ['account' => $account]);
})->middleware(['auth', 'verified'])
    ->name('finance.import');

require __DIR__.'/settings.php';
require __DIR__.'/management.php';
require __DIR__.'/finance.php';
