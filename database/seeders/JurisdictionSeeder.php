<?php

namespace Database\Seeders;

use App\Models\Jurisdiction;
use Illuminate\Database\Seeder;

class JurisdictionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jurisdictions = [
            [
                'name' => 'Spain',
                'iso_code' => 'ESP',
                'timezone' => 'Europe/Madrid',
                'default_currency' => 'EUR',
                'tax_year_start_month' => 1,
                'tax_year_start_day' => 1,
            ],
            [
                'name' => 'United States',
                'iso_code' => 'USA',
                'timezone' => 'America/New_York',
                'default_currency' => 'USD',
                'tax_year_start_month' => 1,
                'tax_year_start_day' => 1,
            ],
            [
                'name' => 'Colombia',
                'iso_code' => 'COL',
                'timezone' => 'America/Bogota',
                'default_currency' => 'COP',
                'tax_year_start_month' => 1,
                'tax_year_start_day' => 1,
            ],
        ];

        foreach ($jurisdictions as $jurisdiction) {
            Jurisdiction::updateOrCreate(
                ['iso_code' => $jurisdiction['iso_code']],
                $jurisdiction
            );
        }
    }
}
