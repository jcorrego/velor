<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated users can access owner-flow report page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.owner-flow'));

    $response->assertSuccessful()
        ->assertSee('Owner-Flow Summary')
        ->assertSeeLivewire('finance.owner-flow-report');
});

test('guests cannot access owner-flow report page', function () {
    $response = $this->get(route('finance.us-tax.owner-flow'));

    $response->assertRedirect();
});

test('authenticated users can access form 4562 page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.form-4562'));

    $response->assertSuccessful()
        ->assertSee('Form 4562 Guidance & Data')
        ->assertSeeLivewire('finance.form-4562-guidance');
});

test('form 4562 shows message when no filings exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.form-4562'));

    $response->assertSuccessful()
        ->assertSee('No Form 4562 filings found');
});

test('form 4562 displays filing when one exists', function () {
    $user = User::factory()->create();
    $usa = \App\Models\Jurisdiction::where('iso_code', 'USA')->first()
        ?? \App\Models\Jurisdiction::factory()->create(['iso_code' => 'USA', 'name' => 'United States']);

    $taxYear = \App\Models\TaxYear::factory()->create([
        'jurisdiction_id' => $usa->id,
        'year' => 2025,
    ]);

    $filingType = \App\Models\FilingType::factory()->create([
        'jurisdiction_id' => $usa->id,
        'code' => '4562',
        'name' => 'Form 4562',
    ]);

    \App\Models\Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
        'status' => \App\FilingStatus::Planning,
    ]);

    $response = $this->actingAs($user)->get(route('finance.us-tax.form-4562'));

    $response->assertSuccessful()
        ->assertSee('2025')
        ->assertSee('Planning');
});

test('guests cannot access form 4562 page', function () {
    $response = $this->get(route('finance.us-tax.form-4562'));

    $response->assertRedirect();
});

test('authenticated users can access schedule e report page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.schedule-e'));

    $response->assertSuccessful()
        ->assertSee('Schedule E')
        ->assertSee('Supplemental Income and Loss')
        ->assertSeeLivewire('finance.schedule-e-rental-report');
});

test('schedule e shows message when no filings exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.schedule-e'));

    $response->assertSuccessful()
        ->assertSee('No Schedule E filings found');
});

test('schedule e displays filing when one exists', function () {
    $user = User::factory()->create();
    $usa = \App\Models\Jurisdiction::where('iso_code', 'USA')->first()
        ?? \App\Models\Jurisdiction::factory()->create(['iso_code' => 'USA', 'name' => 'United States']);

    $taxYear = \App\Models\TaxYear::factory()->create([
        'jurisdiction_id' => $usa->id,
        'year' => 2025,
    ]);

    $filingType = \App\Models\FilingType::factory()->create([
        'jurisdiction_id' => $usa->id,
        'code' => 'SCHEDULE-E',
        'name' => 'Schedule E',
    ]);

    $filing = \App\Models\Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
        'status' => \App\FilingStatus::Planning,
    ]);

    $response = $this->actingAs($user)->get(route('finance.us-tax.schedule-e'));

    $response->assertSuccessful()
        ->assertSee('2025')
        ->assertSee('Planning');
});

test('guests cannot access schedule e report page', function () {
    $response = $this->get(route('finance.us-tax.schedule-e'));

    $response->assertRedirect();
});

test('authenticated users can access form 1040-nr report page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.form-1040nr'));

    $response->assertSuccessful()
        ->assertSee('Form 1040-NR')
        ->assertSeeLivewire('finance.form-1040nr-report');
});

test('form 1040-nr shows message when no filings exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.form-1040nr'));

    $response->assertSuccessful()
        ->assertSee('No Form 1040-NR filings found');
});

test('form 1040-nr displays filing when one exists', function () {
    $user = User::factory()->create();
    \App\Models\Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
    );
    $usa = \App\Models\Jurisdiction::where('iso_code', 'USA')->first()
        ?? \App\Models\Jurisdiction::factory()->create(['iso_code' => 'USA', 'name' => 'United States']);

    $taxYear = \App\Models\TaxYear::factory()->create([
        'jurisdiction_id' => $usa->id,
        'year' => 2025,
    ]);

    $filingType = \App\Models\FilingType::factory()->create([
        'jurisdiction_id' => $usa->id,
        'code' => '1040-NR',
        'name' => 'Form 1040-NR',
    ]);

    \App\Models\Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
        'status' => \App\FilingStatus::Planning,
    ]);

    $response = $this->actingAs($user)->get(route('finance.us-tax.form-1040nr'));

    $response->assertSuccessful()
        ->assertSee('2025')
        ->assertSee('Planning');
});

test('guests cannot access form 1040-nr report page', function () {
    $response = $this->get(route('finance.us-tax.form-1040nr'));

    $response->assertRedirect();
});

test('authenticated users can access form 1120 report page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.form-1120'));

    $response->assertSuccessful()
        ->assertSee('Form 1120')
        ->assertSeeLivewire('finance.form-1120-report');
});

test('form 1120 shows message when no filings exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.form-1120'));

    $response->assertSuccessful()
        ->assertSee('No Form 1120 filings found');
});

test('form 1120 displays filing when one exists', function () {
    $user = User::factory()->create();
    \App\Models\Currency::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
    );
    $usa = \App\Models\Jurisdiction::where('iso_code', 'USA')->first()
        ?? \App\Models\Jurisdiction::factory()->create(['iso_code' => 'USA', 'name' => 'United States']);

    $taxYear = \App\Models\TaxYear::factory()->create([
        'jurisdiction_id' => $usa->id,
        'year' => 2025,
    ]);

    $filingType = \App\Models\FilingType::factory()->create([
        'jurisdiction_id' => $usa->id,
        'code' => '1120',
        'name' => 'Form 1120',
    ]);

    \App\Models\Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
        'status' => \App\FilingStatus::Planning,
    ]);

    $response = $this->actingAs($user)->get(route('finance.us-tax.form-1120'));

    $response->assertSuccessful()
        ->assertSee('2025')
        ->assertSee('Planning');
});

test('guests cannot access form 1120 report page', function () {
    $response = $this->get(route('finance.us-tax.form-1120'));

    $response->assertRedirect();
});
