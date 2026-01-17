<?php

use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\User;
use Livewire\Livewire;

it('creates a tax year via the management screen', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create([
        'name' => 'Spain',
        'iso_code' => 'ESP',
    ]);

    $this->actingAs($user);

    Livewire::test('management.tax-years')
        ->set('jurisdiction_id', $jurisdiction->id)
        ->set('year', 2027)
        ->call('save')
        ->assertHasNoErrors();

    expect(TaxYear::query()->where('jurisdiction_id', $jurisdiction->id)->where('year', 2027)->exists())
        ->toBeTrue();
});

it('prevents duplicate tax years for a jurisdiction', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->create([
        'name' => 'USA',
        'iso_code' => 'USA',
    ]);

    TaxYear::create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2025,
    ]);

    $this->actingAs($user);

    Livewire::test('management.tax-years')
        ->set('jurisdiction_id', $jurisdiction->id)
        ->set('year', 2025)
        ->call('save')
        ->assertHasErrors(['year' => 'unique']);
});
