<?php

use App\Models\Filing;
use App\Models\FilingType;
use App\Models\Jurisdiction;

test('filing type can be created with factory', function () {
    $filingType = FilingType::factory()->create();

    expect($filingType->exists)->toBeTrue()
        ->and($filingType->jurisdiction_id)->toBeInt()
        ->and($filingType->code)->toBeString()
        ->and($filingType->name)->toBeString();
});

test('filing type belongs to jurisdiction', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $filingType = FilingType::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($filingType->jurisdiction)->toBeInstanceOf(Jurisdiction::class)
        ->and($filingType->jurisdiction->id)->toBe($jurisdiction->id);
});

test('filing type has many filings', function () {
    $filingType = FilingType::factory()->create();
    Filing::factory()->count(3)->create(['filing_type_id' => $filingType->id]);

    expect($filingType->filings)->toHaveCount(3)
        ->and($filingType->filings->first())->toBeInstanceOf(Filing::class);
});

test('unique constraint on jurisdiction and code', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    FilingType::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'code' => 'FORM_1040',
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    FilingType::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'code' => 'FORM_1040',
    ]);
});

test('different jurisdictions can have same filing code', function () {
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();

    $spainForm = FilingType::factory()->create([
        'jurisdiction_id' => $spain->id,
        'code' => 'ANNUAL_RETURN',
    ]);

    $usaForm = FilingType::factory()->create([
        'jurisdiction_id' => $usa->id,
        'code' => 'ANNUAL_RETURN',
    ]);

    expect($spainForm->code)->toBe('ANNUAL_RETURN')
        ->and($usaForm->code)->toBe('ANNUAL_RETURN')
        ->and($spainForm->jurisdiction_id)->not->toBe($usaForm->jurisdiction_id);
});
