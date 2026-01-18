<?php

use App\Livewire\Management\Currencies;
use App\Livewire\Management\Entities;
use App\Livewire\Management\Filings;
use App\Livewire\Management\Profiles;
use App\Livewire\Management\ResidencyPeriods;
use App\Livewire\Management\TaxYears;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('manage', 'manage/profiles');

    Route::livewire('manage/profiles', Profiles::class)->name('management.profiles');
    Route::livewire('manage/currencies', Currencies::class)->name('management.currencies');
    Route::livewire('manage/residency-periods', ResidencyPeriods::class)->name('management.residency-periods');
    Route::livewire('manage/entities', Entities::class)->name('management.entities');
    Route::livewire('manage/tax-years', TaxYears::class)->name('management.tax-years');
    Route::livewire('manage/filings', Filings::class)->name('management.filings');
});
