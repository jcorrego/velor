<?php

use App\FilingStatus;
use App\Models\Filing;
use App\Models\FilingType;
use App\Models\TaxYear;
use App\Models\User;

test('filing can be created with factory', function () {
    $filing = Filing::factory()->create();

    expect($filing->exists)->toBeTrue()
        ->and($filing->user_id)->toBeInt()
        ->and($filing->tax_year_id)->toBeInt()
        ->and($filing->filing_type_id)->toBeInt()
        ->and($filing->status)->toBeInstanceOf(FilingStatus::class);
});

test('status is cast to enum', function () {
    $filing = Filing::factory()->create(['status' => FilingStatus::InReview]);

    expect($filing->status)->toBeInstanceOf(FilingStatus::class)
        ->and($filing->status)->toBe(FilingStatus::InReview)
        ->and($filing->status->value)->toBe('in_review');
});

test('key_metrics is cast to json', function () {
    $metrics = ['total_income' => 50000, 'total_tax' => 10000];
    $filing = Filing::factory()->create([
        'key_metrics' => $metrics,
    ]);

    expect($filing->key_metrics)->toBeArray()
        ->and($filing->key_metrics)->toBe($metrics);
});

test('filing belongs to user', function () {
    $user = User::factory()->create();
    $filing = Filing::factory()->create(['user_id' => $user->id]);

    expect($filing->user)->toBeInstanceOf(User::class)
        ->and($filing->user->id)->toBe($user->id);
});

test('filing belongs to tax year', function () {
    $taxYear = TaxYear::factory()->create();
    $filing = Filing::factory()->create(['tax_year_id' => $taxYear->id]);

    expect($filing->taxYear)->toBeInstanceOf(TaxYear::class)
        ->and($filing->taxYear->id)->toBe($taxYear->id);
});

test('filing belongs to filing type', function () {
    $filingType = FilingType::factory()->create();
    $filing = Filing::factory()->create(['filing_type_id' => $filingType->id]);

    expect($filing->filingType)->toBeInstanceOf(FilingType::class)
        ->and($filing->filingType->id)->toBe($filingType->id);
});

test('filing factory has state methods for statuses', function () {
    $planning = Filing::factory()->planning()->create();
    $inReview = Filing::factory()->inReview()->create();
    $filed = Filing::factory()->filed()->create();

    expect($planning->status)->toBe(FilingStatus::Planning)
        ->and($inReview->status)->toBe(FilingStatus::InReview)
        ->and($filed->status)->toBe(FilingStatus::Filed);
});

test('unique constraint on user tax year and filing type', function () {
    $user = User::factory()->create();
    $taxYear = TaxYear::factory()->create();
    $filingType = FilingType::factory()->create();

    Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
    ]);
});

test('user can have multiple filings for same tax year with different filing types', function () {
    $user = User::factory()->create();
    $taxYear = TaxYear::factory()->create();
    $form5472 = FilingType::factory()->create(['code' => 'FORM_5472']);
    $form1040 = FilingType::factory()->create(['code' => 'FORM_1040']);

    $filing1 = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $form5472->id,
    ]);

    $filing2 = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $form1040->id,
    ]);

    expect($filing1->user_id)->toBe($user->id)
        ->and($filing2->user_id)->toBe($user->id)
        ->and($filing1->tax_year_id)->toBe($taxYear->id)
        ->and($filing2->tax_year_id)->toBe($taxYear->id)
        ->and($filing1->filing_type_id)->not->toBe($filing2->filing_type_id);
});
