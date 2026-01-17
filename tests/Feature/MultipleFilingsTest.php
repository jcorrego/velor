<?php

use App\FilingStatus;
use App\Models\Filing;
use App\Models\FilingType;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\User;

test('user can create multiple filings for same tax year with different filing types', function () {
    $user = User::factory()->create();
    $jurisdiction = Jurisdiction::factory()->usa()->create();
    $taxYear = TaxYear::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'year' => 2025,
    ]);

    $form5472 = FilingType::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'code' => 'FORM_5472',
        'name' => 'Form 5472',
    ]);

    $form1040 = FilingType::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'code' => 'FORM_1040',
        'name' => 'Form 1040',
    ]);

    $scheduleE = FilingType::factory()->create([
        'jurisdiction_id' => $jurisdiction->id,
        'code' => 'SCHEDULE_E',
        'name' => 'Schedule E',
    ]);

    $filing1 = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $form5472->id,
        'status' => FilingStatus::Planning,
    ]);

    $filing2 = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $form1040->id,
        'status' => FilingStatus::InReview,
    ]);

    $filing3 = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $scheduleE->id,
        'status' => FilingStatus::Filed,
    ]);

    $userFilings = Filing::where('user_id', $user->id)
        ->where('tax_year_id', $taxYear->id)
        ->get();

    expect($userFilings)->toHaveCount(3)
        ->and($filing1->status)->toBe(FilingStatus::Planning)
        ->and($filing2->status)->toBe(FilingStatus::InReview)
        ->and($filing3->status)->toBe(FilingStatus::Filed);
});

test('each filing type has independent status tracking', function () {
    $user = User::factory()->create();
    $taxYear = TaxYear::factory()->create();
    $form1 = FilingType::factory()->create(['code' => 'FORM_1']);
    $form2 = FilingType::factory()->create(['code' => 'FORM_2']);

    $filing1 = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $form1->id,
        'status' => FilingStatus::Filed,
    ]);

    $filing2 = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $form2->id,
        'status' => FilingStatus::Planning,
    ]);

    expect($filing1->status)->toBe(FilingStatus::Filed)
        ->and($filing2->status)->toBe(FilingStatus::Planning)
        ->and($filing1->filing_type_id)->not->toBe($filing2->filing_type_id);
});

test('user cannot create duplicate filing for same tax year and filing type', function () {
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

test('filings can store jurisdiction-specific key metrics', function () {
    $user = User::factory()->create();
    $taxYear = TaxYear::factory()->create();
    $filingType = FilingType::factory()->create();

    $filing = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
        'key_metrics' => [
            'total_income' => 150000,
            'total_tax' => 35000,
            'effective_rate' => 23.33,
            'credits' => 5000,
        ],
    ]);

    expect($filing->key_metrics)->toBeArray()
        ->and($filing->key_metrics['total_income'])->toBe(150000)
        ->and($filing->key_metrics['total_tax'])->toBe(35000)
        ->and($filing->key_metrics['effective_rate'])->toBe(23.33)
        ->and($filing->key_metrics['credits'])->toBe(5000);
});

test('filing status transitions from planning to filed', function () {
    $filing = Filing::factory()->planning()->create();

    expect($filing->status)->toBe(FilingStatus::Planning);

    $filing->update(['status' => FilingStatus::InReview]);
    expect($filing->fresh()->status)->toBe(FilingStatus::InReview);

    $filing->update(['status' => FilingStatus::Filed]);
    expect($filing->fresh()->status)->toBe(FilingStatus::Filed);
});

test('different users can have same filing type for same tax year', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $taxYear = TaxYear::factory()->create();
    $filingType = FilingType::factory()->create();

    $filing1 = Filing::factory()->create([
        'user_id' => $user1->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
    ]);

    $filing2 = Filing::factory()->create([
        'user_id' => $user2->id,
        'tax_year_id' => $taxYear->id,
        'filing_type_id' => $filingType->id,
    ]);

    expect($filing1->filing_type_id)->toBe($filing2->filing_type_id)
        ->and($filing1->tax_year_id)->toBe($filing2->tax_year_id)
        ->and($filing1->user_id)->not->toBe($filing2->user_id);
});

test('complete usa filing workflow with multiple forms', function () {
    $user = User::factory()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $taxYear2025 = TaxYear::factory()->create([
        'jurisdiction_id' => $usa->id,
        'year' => 2025,
    ]);

    // Create US filing types
    $form5472 = FilingType::factory()->create([
        'jurisdiction_id' => $usa->id,
        'code' => 'FORM_5472',
        'name' => 'Information Return of a 25% Foreign-Owned U.S. Corporation',
    ]);

    $form1120 = FilingType::factory()->create([
        'jurisdiction_id' => $usa->id,
        'code' => 'FORM_1120',
        'name' => 'U.S. Corporation Income Tax Return (Pro-forma)',
    ]);

    $form1040NR = FilingType::factory()->create([
        'jurisdiction_id' => $usa->id,
        'code' => 'FORM_1040_NR',
        'name' => 'U.S. Nonresident Alien Income Tax Return',
    ]);

    // Create filings in different stages
    $filing5472 = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear2025->id,
        'filing_type_id' => $form5472->id,
        'status' => FilingStatus::Filed,
        'key_metrics' => ['filing_date' => '2025-04-15'],
    ]);

    $filing1120 = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear2025->id,
        'filing_type_id' => $form1120->id,
        'status' => FilingStatus::InReview,
        'key_metrics' => ['gross_income' => 250000],
    ]);

    $filing1040NR = Filing::factory()->create([
        'user_id' => $user->id,
        'tax_year_id' => $taxYear2025->id,
        'filing_type_id' => $form1040NR->id,
        'status' => FilingStatus::Planning,
    ]);

    $userUSAFilings = Filing::where('user_id', $user->id)
        ->where('tax_year_id', $taxYear2025->id)
        ->with('filingType')
        ->get();

    expect($userUSAFilings)->toHaveCount(3)
        ->and($userUSAFilings->where('status', FilingStatus::Filed)->count())->toBe(1)
        ->and($userUSAFilings->where('status', FilingStatus::InReview)->count())->toBe(1)
        ->and($userUSAFilings->where('status', FilingStatus::Planning)->count())->toBe(1);
});
