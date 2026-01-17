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

        // Create entities
        \App\Models\Entity::factory()->llc()->create([
            'user_id' => $user->id,
            'jurisdiction_id' => $usa->id,
            'name' => 'JCO Services LLC',
        ]);
        $entity = \App\Models\Entity::where('user_id', $user->id)->first();

        \App\Models\Account::factory()
            ->for($entity)
            ->bancoSantander()
            ->active()
            ->create();

        \App\Models\Account::factory()
            ->for($entity)
            ->mercury()
            ->active()
            ->create();

        $basicCategories = [
            ['name' => 'Consulting Income', 'income_or_expense' => 'income', 'sort_order' => 10],
            ['name' => 'Rental Income', 'income_or_expense' => 'income', 'sort_order' => 20],
            ['name' => 'Software Subscriptions', 'income_or_expense' => 'expense', 'sort_order' => 30],
            ['name' => 'Bank Fees', 'income_or_expense' => 'expense', 'sort_order' => 40],
            ['name' => 'Repairs & Maintenance', 'income_or_expense' => 'expense', 'sort_order' => 50],
        ];

        foreach ($basicCategories as $category) {
            \App\Models\TransactionCategory::firstOrCreate(
                [
                    'name' => $category['name'],
                    'jurisdiction_id' => $entity->jurisdiction_id,
                    'entity_id' => $entity->id,
                ],
                [
                    'income_or_expense' => $category['income_or_expense'],
                    'sort_order' => $category['sort_order'],
                ]
            );
        }

        \App\Models\Asset::factory()
            ->for($entity)
            ->inSpain()
            ->residential()
            ->llc()
            ->create([
                'name' => 'Summberbreeze Apartment',
            ]);

        // Create tax years for 2025
        $taxYearSpain = \App\Models\TaxYear::create(['jurisdiction_id' => $spain->id, 'year' => 2025]);
        $taxYearUSA = \App\Models\TaxYear::create(['jurisdiction_id' => $usa->id, 'year' => 2025]);
        $taxYearColombia = \App\Models\TaxYear::create(['jurisdiction_id' => $colombia->id, 'year' => 2025]);

        $spainFilingTypes = \App\Models\FilingType::query()
            ->where('jurisdiction_id', $spain->id)
            ->whereIn('code', ['IRPF', '720'])
            ->pluck('id', 'code');

        $usaFilingTypes = \App\Models\FilingType::query()
            ->where('jurisdiction_id', $usa->id)
            ->whereIn('code', ['5472', '1120', '1040-NR'])
            ->pluck('id', 'code');

        $colombiaFilingTypes = \App\Models\FilingType::query()
            ->where('jurisdiction_id', $colombia->id)
            ->whereIn('code', ['RENTA'])
            ->pluck('id', 'code');

        $planningFilings = [
            ['tax_year_id' => $taxYearSpain->id, 'filing_type_id' => $spainFilingTypes['IRPF'] ?? null],
            ['tax_year_id' => $taxYearSpain->id, 'filing_type_id' => $spainFilingTypes['720'] ?? null],
            ['tax_year_id' => $taxYearUSA->id, 'filing_type_id' => $usaFilingTypes['5472'] ?? null],
            ['tax_year_id' => $taxYearUSA->id, 'filing_type_id' => $usaFilingTypes['1120'] ?? null],
            ['tax_year_id' => $taxYearUSA->id, 'filing_type_id' => $usaFilingTypes['1040-NR'] ?? null],
            ['tax_year_id' => $taxYearColombia->id, 'filing_type_id' => $colombiaFilingTypes['RENTA'] ?? null],
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
                ]
            );
        }

    }
}
