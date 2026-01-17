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

it('groups tax years by jurisdiction and orders years descending', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->create([
        'name' => 'Spain',
        'iso_code' => 'ESP',
    ]);
    $usa = Jurisdiction::factory()->create([
        'name' => 'USA',
        'iso_code' => 'USA',
    ]);

    TaxYear::create(['jurisdiction_id' => $spain->id, 'year' => 2026]);
    TaxYear::create(['jurisdiction_id' => $spain->id, 'year' => 2024]);
    TaxYear::create(['jurisdiction_id' => $spain->id, 'year' => 2025]);

    TaxYear::create(['jurisdiction_id' => $usa->id, 'year' => 2023]);
    TaxYear::create(['jurisdiction_id' => $usa->id, 'year' => 2025]);

    $this->actingAs($user);

    $component = Livewire::test('management.tax-years');
    $groups = $component->viewData('taxYears');

    expect($groups->keys()->all())->toContain('Spain')
        ->and($groups->keys()->all())->toContain('USA');

    $spainYears = $groups->get('Spain')->pluck('year')->values()->all();
    $usaYears = $groups->get('USA')->pluck('year')->values()->all();

    expect($spainYears)->toBe([2026, 2025, 2024])
        ->and($usaYears)->toBe([2025, 2023]);
});
