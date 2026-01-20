<?php

use App\Models\Filing;
use App\Models\FilingType;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated users can access IRPF summary page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.spain-tax.irpf'));

    $response->assertSuccessful()
        ->assertSee('IRPF Summary')
        ->assertSeeLivewire('finance.spain-irpf-report');
});

test('irpf summary shows message when no filings exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.spain-tax.irpf'));

    $response->assertSuccessful()
        ->assertSee('No IRPF filings found');
});

test('irpf summary displays filing when one exists', function () {
    $user = User::factory()->create();
    \App\Models\Currency::factory()->euro()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $spain->id,
        'year' => 2025,
    ]);

    $filingType = FilingType::factory()->create([
        'jurisdiction_id' => $spain->id,
        'code' => 'IRPF',
        'name' => 'Modelo 100 (IRPF)',
    ]);

    Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
        'status' => \App\FilingStatus::Planning,
    ]);

    $response = $this->actingAs($user)->get(route('finance.spain-tax.irpf'));

    $response->assertSuccessful()
        ->assertSee('2025')
        ->assertSee('Planning');
});

test('guests cannot access IRPF summary page', function () {
    $response = $this->get(route('finance.spain-tax.irpf'));

    $response->assertRedirect();
});

test('authenticated users can access Modelo 720 dashboard page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.spain-tax.modelo-720'));

    $response->assertSuccessful()
        ->assertSee('Modelo 720')
        ->assertSeeLivewire('finance.modelo-720-dashboard');
});

test('modelo 720 dashboard shows message when no filings exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.spain-tax.modelo-720'));

    $response->assertSuccessful()
        ->assertSee('No Modelo 720 filings found');
});

test('modelo 720 dashboard displays filing when one exists', function () {
    $user = User::factory()->create();
    \App\Models\Currency::factory()->euro()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $spain->id,
        'year' => 2025,
    ]);

    $filingType = FilingType::factory()->create([
        'jurisdiction_id' => $spain->id,
        'code' => '720',
        'name' => 'Modelo 720',
    ]);

    Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
        'status' => \App\FilingStatus::Planning,
    ]);

    $response = $this->actingAs($user)->get(route('finance.spain-tax.modelo-720'));

    $response->assertSuccessful()
        ->assertSee('2025')
        ->assertSee('Planning');
});

test('guests cannot access Modelo 720 dashboard page', function () {
    $response = $this->get(route('finance.spain-tax.modelo-720'));

    $response->assertRedirect();
});
