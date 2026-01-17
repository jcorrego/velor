<?php

use App\Models\Filing;
use App\Models\Jurisdiction;
use App\Models\TaxYear;

test('tax year can be created with factory', function () {
    $taxYear = TaxYear::factory()->create();

    expect($taxYear->exists)->toBeTrue()
        ->and($taxYear->jurisdiction_id)->toBeInt()
        ->and($taxYear->year)->toBeInt();
});

test('year is cast to integer', function () {
    $taxYear = TaxYear::factory()->create(['year' => 2025]);

    expect($taxYear->year)->toBeInt()
        ->and($taxYear->year)->toBe(2025);
});

test('tax year belongs to jurisdiction', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $taxYear = TaxYear::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($taxYear->jurisdiction)->toBeInstanceOf(Jurisdiction::class)
        ->and($taxYear->jurisdiction->id)->toBe($jurisdiction->id);
});

test('tax year has many filings', function () {
    $taxYear = TaxYear::factory()->create();
    Filing::factory()->count(3)->create(['tax_year_id' => $taxYear->id]);

    expect($taxYear->filings)->toHaveCount(3)
        ->and($taxYear->filings->first())->toBeInstanceOf(Filing::class);
});

test('unique constraint on jurisdiction and year', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2025,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2025,
    ]);
});
