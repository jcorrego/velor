<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed currencies, jurisdictions and filing types
        $this->call([
            CurrencySeeder::class,
            JurisdictionSeeder::class,
            FilingTypeSeeder::class,
        ]);

        // Create test user
        $user = User::factory()->create([
            'name' => 'Juan Carlos Orrego',
            'email' => 'jcorrego@gmail.com',
            'password' => bcrypt('password'),
        ]);

        // Create user profiles for Spain, USA, and Colombia
        $spain = \App\Models\Jurisdiction::where('iso_code', 'ESP')->first();
        $usa = \App\Models\Jurisdiction::where('iso_code', 'USA')->first();
        $colombia = \App\Models\Jurisdiction::where('iso_code', 'COL')->first();

        \App\Models\UserProfile::factory()->spain()->create(['user_id' => $user->id]);
        \App\Models\UserProfile::factory()->usa()->create(['user_id' => $user->id]);
        \App\Models\UserProfile::factory()->colombia()->create(['user_id' => $user->id]);

        // Create residency periods
        \App\Models\ResidencyPeriod::factory()->current()->fiscalResidence()->create([
            'user_id' => $user->id,
            'jurisdiction_id' => $spain->id,
            'start_date' => '2025-06-24',
        ]);
        \App\Models\ResidencyPeriod::factory()->current()->fiscalResidence()->create([
            'user_id' => $user->id,
            'jurisdiction_id' => $colombia->id,
            'start_date' => '1978-11-13',
        ]);

        // Create entities
        $usEntity = \App\Models\Entity::factory()->llc()->create([
            'user_id' => $user->id,
            'jurisdiction_id' => $usa->id,
            'name' => 'JCO Services LLC',
            'ein_or_tax_id' => '352795672',
        ]);

        $spainEntity = \App\Models\Entity::factory()->individual()->create([
            'user_id' => $user->id,
            'jurisdiction_id' => $spain->id,
            'name' => 'JCO Spain',
        ]);

        $colombiaEntity = \App\Models\Entity::factory()->individual()->create([
            'user_id' => $user->id,
            'jurisdiction_id' => $colombia->id,
            'name' => 'JCO Colombia',
        ]);

        $spainAccount = \App\Models\Account::factory()
            ->for($spainEntity)
            ->bancoSantander()
            ->checking()
            ->active()
            ->create([
                'opening_date' => '2025-07-01',
                'closing_date' => null,
            ]);

        $usAccount = \App\Models\Account::factory()
            ->for($usEntity)
            ->mercury()
            ->checking()
            ->active()
            ->create();

        $colombiaAccount = \App\Models\Account::factory()
            ->for($colombiaEntity)
            ->bancolombia()
            ->checking()
            ->active()
            ->create();

        $basicCategories = [
            ['name' => 'Consulting Income', 'income_or_expense' => 'income', 'sort_order' => 10],
            ['name' => 'Rental Income', 'income_or_expense' => 'income', 'sort_order' => 20],
            ['name' => 'Software Subscriptions', 'income_or_expense' => 'expense', 'sort_order' => 30],
            ['name' => 'Bank Fees', 'income_or_expense' => 'expense', 'sort_order' => 40],
            ['name' => 'Repairs & Maintenance', 'income_or_expense' => 'expense', 'sort_order' => 50],
        ];

        // Create categories for US entity
        $usCurrencyId = \App\Models\Currency::where('code', 'USD')->first()->id;
        $eurCurrencyId = \App\Models\Currency::where('code', 'EUR')->first()->id;
        $copCurrencyId = \App\Models\Currency::where('code', 'COP')->first()->id;

        foreach ($basicCategories as $category) {
            \App\Models\TransactionCategory::firstOrCreate(
                [
                    'name' => $category['name'],
                ],
                [
                    'income_or_expense' => $category['income_or_expense'],
                    'sort_order' => $category['sort_order'],
                ]
            );
        }

        // Create Spain Taxes category
        $taxesCategory = \App\Models\TransactionCategory::firstOrCreate(
            [
                'name' => 'Taxes',
            ],
            [
                'income_or_expense' => 'expense',
                'sort_order' => 60,
            ]
        );

        // Create category rule for TGSS (Social Security) payments
        \App\Models\DescriptionCategoryRule::firstOrCreate(
            [
                'jurisdiction_id' => $spain->id,
                'description_pattern' => 'Recibo Tgss. Cotizacion',
            ],
            [
                'category_id' => $taxesCategory->id,
                'is_active' => true,
                'notes' => 'Spanish Social Security (TGSS) contribution payments',
            ]
        );

        $usAsset = \App\Models\Asset::factory()
            ->for($usEntity)
            ->inUSA()
            ->residential()
            ->llc()
            ->create([
                'name' => 'Summerbreeze Apartment',
                'acquisition_date' => '2023-12-28',
                'acquisition_cost' => 285000.00,
            ]);

        $colombiaAssetOne = \App\Models\Asset::factory()
            ->for($colombiaEntity)
            ->inColombia()
            ->residential()
            ->individual()
            ->create([
                'name' => 'Apto Arrecifes de la Abadia',
            ]);

        $colombiaAssetTwo = \App\Models\Asset::factory()
            ->for($colombiaEntity)
            ->inColombia()
            ->residential()
            ->individual()
            ->create([
                'name' => 'Apto Bogota Luis Carlos',
            ]);

        $spainAsset = \App\Models\Asset::factory()
            ->for($spainEntity)
            ->inSpain()
            ->vehicle()
            ->individual()
            ->create([
                'name' => 'Peugeot 5008',
            ]);

        $this->call(AddressSeeder::class);

        $usAddress = \App\Models\Address::query()
            ->where('country', 'United States')
            ->first();

        $spainAddress = \App\Models\Address::query()
            ->where('country', 'Spain')
            ->first();

        $colombiaAddress = \App\Models\Address::query()
            ->where('country', 'Colombia')
            ->first();

        if ($usAddress) {
            $usAsset->address()->associate($usAddress);
            $usAsset->save();

            $usEntity->address()->associate($usAddress);
            $usEntity->save();
        }

        if ($spainAddress) {
            $spainAsset->address()->associate($spainAddress);
            $spainAsset->save();

            $spainEntity->address()->associate($spainAddress);
            $spainEntity->save();
        }
        if ($colombiaAddress) {
            $colombiaAssetOne->address()->associate($colombiaAddress);
            $colombiaAssetOne->save();
            $colombiaAssetTwo->address()->associate($colombiaAddress);
            $colombiaAssetTwo->save();

            $colombiaEntity->address()->associate($colombiaAddress);
            $colombiaEntity->save();
        }

        // Create tax years for 2025
        $taxYearSpain = \App\Models\TaxYear::create(['jurisdiction_id' => $spain->id, 'year' => 2025]);
        $taxYearUSA = \App\Models\TaxYear::create(['jurisdiction_id' => $usa->id, 'year' => 2025]);
        $taxYearColombia = \App\Models\TaxYear::create(['jurisdiction_id' => $colombia->id, 'year' => 2025]);

        \App\Models\YearEndValue::firstOrCreate(
            [
                'entity_id' => $usEntity->id,
                'asset_id' => $usAsset->id,
                'tax_year_id' => $taxYearUSA->id,
            ],
            [
                'amount' => 206841.00,
            ]
        );

        \App\Models\YearEndValue::firstOrCreate(
            [
                'entity_id' => $usEntity->id,
                'account_id' => $usAccount->id,
                'tax_year_id' => $taxYearUSA->id,
            ],
            [
                'amount' => 14647.49,
            ]
        );

        \App\Models\YearEndValue::firstOrCreate(
            [
                'entity_id' => $colombiaEntity->id,
                'asset_id' => $colombiaAssetOne->id,
                'tax_year_id' => $taxYearColombia->id,
            ],
            [
                'amount' => $colombiaAssetOne->acquisition_cost,
            ]
        );

        \App\Models\YearEndValue::firstOrCreate(
            [
                'entity_id' => $colombiaEntity->id,
                'asset_id' => $colombiaAssetTwo->id,
                'tax_year_id' => $taxYearColombia->id,
            ],
            [
                'amount' => $colombiaAssetTwo->acquisition_cost,
            ]
        );

        \App\Models\YearEndValue::firstOrCreate(
            [
                'entity_id' => $spainEntity->id,
                'asset_id' => $spainAsset->id,
                'tax_year_id' => $taxYearSpain->id,
            ],
            [
                'amount' => $spainAsset->acquisition_cost,
            ]
        );

        $spainFilingTypes = \App\Models\FilingType::query()
            ->where('jurisdiction_id', $spain->id)
            ->whereIn('code', ['IRPF', '720'])
            ->pluck('id', 'code');

        $usaFilingTypes = \App\Models\FilingType::query()
            ->where('jurisdiction_id', $usa->id)
            ->whereIn('code', ['5472', '4562', '1120', '1040-NR', 'SCHEDULE-E'])
            ->pluck('id', 'code');

        $colombiaFilingTypes = \App\Models\FilingType::query()
            ->where('jurisdiction_id', $colombia->id)
            ->whereIn('code', ['RENTA'])
            ->pluck('id', 'code');

        $planningFilings = [
            ['tax_year_id' => $taxYearSpain->id, 'filing_type_id' => $spainFilingTypes['IRPF'] ?? null, 'code' => 'IRPF'],
            ['tax_year_id' => $taxYearSpain->id, 'filing_type_id' => $spainFilingTypes['720'] ?? null, 'code' => '720'],
            ['tax_year_id' => $taxYearUSA->id, 'filing_type_id' => $usaFilingTypes['5472'] ?? null, 'code' => '5472'],
            ['tax_year_id' => $taxYearUSA->id, 'filing_type_id' => $usaFilingTypes['4562'] ?? null, 'code' => '4562'],
            ['tax_year_id' => $taxYearUSA->id, 'filing_type_id' => $usaFilingTypes['1120'] ?? null, 'code' => '1120'],
            ['tax_year_id' => $taxYearUSA->id, 'filing_type_id' => $usaFilingTypes['1040-NR'] ?? null, 'code' => '1040-NR'],
            ['tax_year_id' => $taxYearUSA->id, 'filing_type_id' => $usaFilingTypes['SCHEDULE-E'] ?? null, 'code' => 'SCHEDULE-E'],
            ['tax_year_id' => $taxYearColombia->id, 'filing_type_id' => $colombiaFilingTypes['RENTA'] ?? null, 'code' => 'RENTA'],
        ];

        foreach ($planningFilings as $filing) {
            if (! $filing['filing_type_id']) {
                continue;
            }

            \App\Models\Filing::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'tax_year_id' => $filing['tax_year_id'],
                    'filing_type_id' => $filing['filing_type_id'],
                ],
                [
                    'status' => \App\FilingStatus::Planning,
                    'key_metrics' => null,
                ]
            );
        }

        // Create US transactions for tax reporting
        $rentalIncomeCategory = \App\Models\TransactionCategory::where('name', 'Rental Income')
            ->first();

        // Create rental-specific maintenance category for Schedule E
        $rentalMaintenanceCategory = \App\Models\TransactionCategory::firstOrCreate(
            [
                'name' => 'Rental Property Maintenance',
            ],
            [
                'income_or_expense' => 'expense',
                'sort_order' => 55,
            ]
        );

        \App\Models\CategoryTaxMapping::firstOrCreate(
            [
                'category_id' => $rentalIncomeCategory->id,
                'tax_form_code' => \App\Enums\Finance\TaxFormCode::ScheduleE,
                'line_item' => 'line_1',
            ],
            ['country' => 'USA']
        );

        \App\Models\CategoryTaxMapping::firstOrCreate(
            [
                'category_id' => $rentalMaintenanceCategory->id,
                'tax_form_code' => \App\Enums\Finance\TaxFormCode::ScheduleE,
                'line_item' => 'line_18',
            ],
            ['country' => 'USA']
        );

        $consultingIncomeCategory = \App\Models\TransactionCategory::where('name', 'Consulting Income')->first();
        $softwareSubscriptionsCategory = \App\Models\TransactionCategory::where('name', 'Software Subscriptions')->first();
        $repairsCategory = \App\Models\TransactionCategory::where('name', 'Repairs & Maintenance')->first();

        if ($consultingIncomeCategory) {
            \App\Models\CategoryTaxMapping::firstOrCreate(
                [
                    'category_id' => $consultingIncomeCategory->id,
                    'tax_form_code' => \App\Enums\Finance\TaxFormCode::IRPF,
                    'line_item' => 'Rendimientos de actividades económicas',
                ],
                ['country' => 'Spain']
            );
        }

        if ($rentalIncomeCategory) {
            \App\Models\CategoryTaxMapping::firstOrCreate(
                [
                    'category_id' => $rentalIncomeCategory->id,
                    'tax_form_code' => \App\Enums\Finance\TaxFormCode::IRPF,
                    'line_item' => 'Ingresos por alquileres',
                ],
                ['country' => 'Spain']
            );
        }

        if ($softwareSubscriptionsCategory) {
            \App\Models\CategoryTaxMapping::firstOrCreate(
                [
                    'category_id' => $softwareSubscriptionsCategory->id,
                    'tax_form_code' => \App\Enums\Finance\TaxFormCode::IRPF,
                    'line_item' => 'Gastos deducibles',
                ],
                ['country' => 'Spain']
            );
        }

        if ($repairsCategory) {
            \App\Models\CategoryTaxMapping::firstOrCreate(
                [
                    'category_id' => $repairsCategory->id,
                    'tax_form_code' => \App\Enums\Finance\TaxFormCode::IRPF,
                    'line_item' => 'Reparaciones y conservación',
                ],
                ['country' => 'Spain']
            );
        }

        if ($taxesCategory) {
            \App\Models\CategoryTaxMapping::firstOrCreate(
                [
                    'category_id' => $taxesCategory->id,
                    'tax_form_code' => \App\Enums\Finance\TaxFormCode::IRPF,
                    'line_item' => 'Impuestos',
                ],
                ['country' => 'Spain']
            );
        }

        $expenseCategories = \App\Models\TransactionCategory::query()
            ->where('income_or_expense', 'expense')
            ->get();

        foreach ($expenseCategories as $category) {
            $hasIrpfMapping = \App\Models\CategoryTaxMapping::query()
                ->where('category_id', $category->id)
                ->where('tax_form_code', \App\Enums\Finance\TaxFormCode::IRPF)
                ->exists();

            if (! $hasIrpfMapping) {
                \App\Models\CategoryTaxMapping::firstOrCreate(
                    [
                        'category_id' => $category->id,
                        'tax_form_code' => \App\Enums\Finance\TaxFormCode::IRPF,
                        'line_item' => 'Gastos deducibles',
                    ],
                    ['country' => 'Spain']
                );
            }
        }

        // Create Form 5472 categories
        $ownerContributionCategory = \App\Models\TransactionCategory::firstOrCreate(
            ['name' => 'Owner Contribution'],
            ['income_or_expense' => 'income', 'sort_order' => 100]
        );
        $ownerDrawCategory = \App\Models\TransactionCategory::firstOrCreate(
            ['name' => 'Owner Draw'],
            ['income_or_expense' => 'expense', 'sort_order' => 101]
        );
        $reimbursementCategory = \App\Models\TransactionCategory::firstOrCreate(
            ['name' => 'Reimbursement'],
            ['income_or_expense' => 'income', 'sort_order' => 102]
        );

        $colombiaIncomeCategory = \App\Models\TransactionCategory::firstOrCreate(
            ['name' => 'Consulting Income'],
            ['income_or_expense' => 'income']
        );

        $colombiaExpenseCategory = \App\Models\TransactionCategory::firstOrCreate(
            ['name' => 'Bank Fees'],
            ['income_or_expense' => 'expense']
        );

        // Create Form 5472 tax mappings
        \App\Models\CategoryTaxMapping::firstOrCreate(
            [
                'category_id' => $ownerContributionCategory->id,
                'tax_form_code' => \App\Enums\Finance\TaxFormCode::Form5472,
                'line_item' => 'owner_contribution',
            ],
            ['country' => 'USA']
        );
        \App\Models\CategoryTaxMapping::firstOrCreate(
            [
                'category_id' => $ownerDrawCategory->id,
                'tax_form_code' => \App\Enums\Finance\TaxFormCode::Form5472,
                'line_item' => 'owner_draw',
            ],
            ['country' => 'USA']
        );
        \App\Models\CategoryTaxMapping::firstOrCreate(
            [
                'category_id' => $reimbursementCategory->id,
                'tax_form_code' => \App\Enums\Finance\TaxFormCode::Form5472,
                'line_item' => 'reimbursement',
            ],
            ['country' => 'USA']
        );

        \App\Models\CategoryTaxMapping::firstOrCreate(
            [
                'category_id' => $colombiaIncomeCategory->id,
                'tax_form_code' => \App\Enums\Finance\TaxFormCode::ColombianDeclaration,
                'line_item' => 'income',
            ],
            ['country' => 'Colombia']
        );

        \App\Models\CategoryTaxMapping::firstOrCreate(
            [
                'category_id' => $colombiaExpenseCategory->id,
                'tax_form_code' => \App\Enums\Finance\TaxFormCode::ColombianDeclaration,
                'line_item' => 'expense',
            ],
            ['country' => 'Colombia']
        );

        // Create rental income transaction for 2025
        \App\Models\Transaction::create([
            'account_id' => $usAccount->id,
            'transaction_date' => '2025-01-15',
            'type' => \App\Enums\Finance\TransactionType::Income,
            'original_amount' => 2500.00,
            'original_currency_id' => $usCurrencyId,
            'converted_amount' => 2272.73, // Converted to EUR at ~0.909 rate
            'converted_currency_id' => $eurCurrencyId,
            'fx_rate' => 0.909,
            'fx_source' => 'ecb',
            'category_id' => $rentalIncomeCategory->id,
            'counterparty_name' => 'Tenant - John Doe',
            'description' => 'Monthly rent payment',
        ]);

        // Create rental expense transaction for 2025 (will show in Schedule E)
        \App\Models\Transaction::create([
            'account_id' => $usAccount->id,
            'transaction_date' => '2025-01-20',
            'type' => \App\Enums\Finance\TransactionType::Expense,
            'original_amount' => -450.00,
            'original_currency_id' => $usCurrencyId,
            'converted_amount' => -409.09, // Converted to EUR at ~0.909 rate
            'converted_currency_id' => $eurCurrencyId,
            'fx_rate' => 0.909,
            'fx_source' => 'ecb',
            'category_id' => $rentalMaintenanceCategory->id,
            'counterparty_name' => 'ABC Plumbing Services',
            'description' => 'Plumbing repair - leaky faucet',
        ]);

        // Create owner contribution for Form 5472
        \App\Models\Transaction::create([
            'account_id' => $usAccount->id,
            'transaction_date' => '2025-01-05',
            'type' => \App\Enums\Finance\TransactionType::Income,
            'original_amount' => 10000.00,
            'original_currency_id' => $usCurrencyId,
            'converted_amount' => 9090.00,
            'converted_currency_id' => $eurCurrencyId,
            'fx_rate' => 0.909,
            'fx_source' => 'ecb',
            'category_id' => $ownerContributionCategory->id,
            'description' => 'Initial capital contribution to LLC',
            'tags' => json_encode(['owner_id' => $user->id]),
        ]);

        // Create owner draw for Form 5472
        \App\Models\Transaction::create([
            'account_id' => $usAccount->id,
            'transaction_date' => '2025-02-10',
            'type' => \App\Enums\Finance\TransactionType::Expense,
            'original_amount' => -1500.00,
            'original_currency_id' => $usCurrencyId,
            'converted_amount' => -1363.50,
            'converted_currency_id' => $eurCurrencyId,
            'fx_rate' => 0.909,
            'fx_source' => 'ecb',
            'category_id' => $ownerDrawCategory->id,
            'description' => 'Owner distribution',
            'tags' => json_encode(['owner_id' => $user->id]),
        ]);

        // Create reimbursement for Form 5472
        \App\Models\Transaction::create([
            'account_id' => $usAccount->id,
            'transaction_date' => '2025-03-05',
            'type' => \App\Enums\Finance\TransactionType::Income,
            'original_amount' => 250.00,
            'original_currency_id' => $usCurrencyId,
            'converted_amount' => 227.25,
            'converted_currency_id' => $eurCurrencyId,
            'fx_rate' => 0.909,
            'fx_source' => 'ecb',
            'category_id' => $reimbursementCategory->id,
            'description' => 'Reimbursement for business expenses paid personally',
            'tags' => json_encode(['owner_id' => $user->id]),
        ]);

        \App\Models\Transaction::create([
            'account_id' => $colombiaAccount->id,
            'transaction_date' => '2025-02-12',
            'type' => \App\Enums\Finance\TransactionType::Income,
            'original_amount' => 4200000.00,
            'original_currency_id' => $copCurrencyId,
            'converted_amount' => 4200000.00,
            'converted_currency_id' => $copCurrencyId,
            'fx_rate' => 1.0,
            'fx_source' => 'manual',
            'category_id' => $colombiaIncomeCategory->id,
            'counterparty_name' => 'Cliente Colombia SAS',
            'description' => 'Consulting services - February',
        ]);

        \App\Models\Transaction::create([
            'account_id' => $colombiaAccount->id,
            'transaction_date' => '2025-02-18',
            'type' => \App\Enums\Finance\TransactionType::Expense,
            'original_amount' => -15000.00,
            'original_currency_id' => $copCurrencyId,
            'converted_amount' => -15000.00,
            'converted_currency_id' => $copCurrencyId,
            'fx_rate' => 1.0,
            'fx_source' => 'manual',
            'category_id' => $colombiaExpenseCategory->id,
            'counterparty_name' => 'Bancolombia',
            'description' => 'Monthly account fee',
        ]);
    }
}
