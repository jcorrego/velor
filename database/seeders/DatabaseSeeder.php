<?php

namespace Database\Seeders;

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

        // Seed personal development data (optional, enabled for now)
        $this->call(PersonalUserSeeder::class);
    }
}