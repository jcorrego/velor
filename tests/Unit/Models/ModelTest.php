<?php

use App\EntityType;
use App\Enums\Finance\AccountType;
use App\Enums\Finance\AssetType;
use App\Enums\Finance\ImportFileType;
use App\Enums\Finance\OwnershipStructure;
use App\Enums\Finance\TaxFormCode;
use App\Enums\Finance\TransactionType;
use App\FilingStatus;
use App\Models\Account;
use App\Models\Address;
use App\Models\Asset;
use App\Models\CategoryTaxMapping;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Filing;
use App\Models\FilingType;
use App\Models\FxRate;
use App\Models\Jurisdiction;
use App\Models\ResidencyPeriod;
use App\Models\TaxYear;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\TransactionImport;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('defines model relationships', function (string $modelClass, string $relationMethod, string $relationClass, string $relatedClass) {
    $model = new $modelClass;
    $relation = $model->$relationMethod();

    expect($relation)->toBeInstanceOf($relationClass)
        ->and($relation->getRelated())->toBeInstanceOf($relatedClass);
})->with([
    [Asset::class, 'entity', BelongsTo::class, Entity::class],
    [Asset::class, 'address', BelongsTo::class, Address::class],
    [Address::class, 'user', BelongsTo::class, User::class],
    [Address::class, 'assets', HasMany::class, Asset::class],
    [Filing::class, 'user', BelongsTo::class, User::class],
    [Filing::class, 'taxYear', BelongsTo::class, TaxYear::class],
    [Filing::class, 'filingType', BelongsTo::class, FilingType::class],
    [TransactionImport::class, 'account', BelongsTo::class, Account::class],
    [FxRate::class, 'currencyFrom', BelongsTo::class, Currency::class],
    [FxRate::class, 'currencyTo', BelongsTo::class, Currency::class],
    [Currency::class, 'fxRatesFrom', HasMany::class, FxRate::class],
    [Currency::class, 'fxRatesTo', HasMany::class, FxRate::class],
    [Currency::class, 'accounts', HasMany::class, Account::class],
    [Account::class, 'currency', BelongsTo::class, Currency::class],
    [Account::class, 'entity', BelongsTo::class, Entity::class],
    [Account::class, 'transactions', HasMany::class, Transaction::class],
    [Account::class, 'transactionImports', HasMany::class, TransactionImport::class],
    [Entity::class, 'user', BelongsTo::class, User::class],
    [Entity::class, 'jurisdiction', BelongsTo::class, Jurisdiction::class],
    [FilingType::class, 'jurisdiction', BelongsTo::class, Jurisdiction::class],
    [FilingType::class, 'filings', HasMany::class, Filing::class],
    [TaxYear::class, 'jurisdiction', BelongsTo::class, Jurisdiction::class],
    [TaxYear::class, 'filings', HasMany::class, Filing::class],
    [TransactionCategory::class, 'transactions', HasMany::class, Transaction::class],
    [TransactionCategory::class, 'taxMappings', HasMany::class, CategoryTaxMapping::class],
    [TransactionCategory::class, 'categoryTaxMappings', HasMany::class, CategoryTaxMapping::class],
    [Jurisdiction::class, 'userProfiles', HasMany::class, UserProfile::class],
    [Jurisdiction::class, 'residencyPeriods', HasMany::class, ResidencyPeriod::class],
    [Jurisdiction::class, 'taxYears', HasMany::class, TaxYear::class],
    [Jurisdiction::class, 'filingTypes', HasMany::class, FilingType::class],
    [Jurisdiction::class, 'entities', HasMany::class, Entity::class],
    [Transaction::class, 'account', BelongsTo::class, Account::class],
    [Transaction::class, 'category', BelongsTo::class, TransactionCategory::class],
    [Transaction::class, 'originalCurrency', BelongsTo::class, Currency::class],
    [Transaction::class, 'convertedCurrency', BelongsTo::class, Currency::class],
    [UserProfile::class, 'user', BelongsTo::class, User::class],
    [UserProfile::class, 'jurisdiction', BelongsTo::class, Jurisdiction::class],
    [User::class, 'profiles', HasMany::class, UserProfile::class],
    [User::class, 'addresses', HasMany::class, Address::class],
    [ResidencyPeriod::class, 'user', BelongsTo::class, User::class],
    [ResidencyPeriod::class, 'jurisdiction', BelongsTo::class, Jurisdiction::class],
    [CategoryTaxMapping::class, 'transactionCategory', BelongsTo::class, TransactionCategory::class],
]);

it('defines expected casts for models', function (string $modelClass, array $expectedCasts) {
    $model = new $modelClass;
    $casts = $model->getCasts();

    foreach ($expectedCasts as $field => $type) {
        expect($casts)->toHaveKey($field)
            ->and($casts[$field])->toBe($type);
    }
})->with([
    [Asset::class, [
        'type' => AssetType::class,
        'ownership_structure' => OwnershipStructure::class,
        'acquisition_date' => 'date',
        'acquisition_cost' => 'decimal:2',
    ]],
    [Filing::class, [
        'status' => FilingStatus::class,
        'key_metrics' => 'json',
    ]],
    [TransactionImport::class, [
        'file_type' => ImportFileType::class,
        'imported_at' => 'timestamp',
        'parsed_count' => 'integer',
        'matched_count' => 'integer',
    ]],
    [FxRate::class, [
        'rate' => 'decimal:8',
        'rate_date' => 'date',
    ]],
    [Currency::class, [
        'is_active' => 'boolean',
    ]],
    [Account::class, [
        'type' => AccountType::class,
        'opening_date' => 'date',
        'closing_date' => 'date',
        'integration_metadata' => 'json',
    ]],
    [Entity::class, [
        'type' => EntityType::class,
        'ein_or_tax_id' => 'encrypted',
    ]],
    [TaxYear::class, [
        'year' => 'integer',
    ]],
    [TransactionCategory::class, [
        'income_or_expense' => 'string',
        'sort_order' => 'integer',
    ]],
    [Transaction::class, [
        'type' => TransactionType::class,
        'transaction_date' => 'date',
        'original_amount' => 'decimal:2',
        'converted_amount' => 'decimal:2',
        'fx_rate' => 'decimal:8',
        'tags' => 'json',
        'reconciled_at' => 'timestamp',
    ]],
    [UserProfile::class, [
        'tax_id' => 'encrypted',
        'display_currencies' => 'json',
    ]],
    [User::class, [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ]],
    [ResidencyPeriod::class, [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_fiscal_residence' => 'boolean',
    ]],
    [CategoryTaxMapping::class, [
        'tax_form_code' => TaxFormCode::class,
    ]],
]);

it('builds user initials from the first two words', function () {
    $user = new User(['name' => 'Juan Carlos Orrego']);

    expect($user->initials())->toBe('JC');
});
