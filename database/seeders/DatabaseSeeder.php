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

        // Create tax years for 2025
        $taxYearSpain = \App\Models\TaxYear::create(['jurisdiction_id' => $spain->id, 'year' => 2025]);
        $taxYearUSA = \App\Models\TaxYear::create(['jurisdiction_id' => $usa->id, 'year' => 2025]);
        $taxYearColombia = \App\Models\TaxYear::create(['jurisdiction_id' => $colombia->id, 'year' => 2025]);
    }
}
