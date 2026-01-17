<?php

namespace Database\Seeders;

use App\Models\FilingType;
use App\Models\Jurisdiction;
use Illuminate\Database\Seeder;

class FilingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $spain = Jurisdiction::where('iso_code', 'ESP')->first();
        $usa = Jurisdiction::where('iso_code', 'USA')->first();
        $colombia = Jurisdiction::where('iso_code', 'COL')->first();

        if ($spain) {
            $spanishTypes = [
                [
                    'code' => 'IRPF',
                    'name' => 'Modelo 100 (IRPF)',
                    'description' => 'Declaración de la Renta de las Personas Físicas',
                ],
                [
                    'code' => '720',
                    'name' => 'Modelo 720',
                    'description' => 'Declaración informativa sobre bienes y derechos situados en el extranjero',
                ],
            ];

            foreach ($spanishTypes as $type) {
                FilingType::updateOrCreate(
                    ['jurisdiction_id' => $spain->id, 'code' => $type['code']],
                    $type + ['jurisdiction_id' => $spain->id]
                );
            }
        }

        if ($usa) {
            $usaTypes = [
                [
                    'code' => '5472',
                    'name' => 'Form 5472',
                    'description' => 'Information Return of a 25% Foreign-Owned U.S. Corporation',
                ],
                [
                    'code' => '1120',
                    'name' => 'Form 1120 (Pro-forma)',
                    'description' => 'U.S. Corporation Income Tax Return (Pro-forma for disregarded entity)',
                ],
                [
                    'code' => '1040',
                    'name' => 'Form 1040',
                    'description' => 'U.S. Individual Income Tax Return',
                ],
                [
                    'code' => '1040-NR',
                    'name' => 'Form 1040-NR',
                    'description' => 'U.S. Nonresident Alien Income Tax Return',
                ],
                [
                    'code' => 'SCHEDULE-E',
                    'name' => 'Schedule E',
                    'description' => 'Supplemental Income and Loss (Rental Real Estate, Royalties, etc.)',
                ],
            ];

            foreach ($usaTypes as $type) {
                FilingType::updateOrCreate(
                    ['jurisdiction_id' => $usa->id, 'code' => $type['code']],
                    $type + ['jurisdiction_id' => $usa->id]
                );
            }
        }

        if ($colombia) {
            $colombiaTypes = [
                [
                    'code' => 'RENTA',
                    'name' => 'Declaración de Renta',
                    'description' => 'Declaración de Impuesto sobre la Renta y Complementarios',
                ],
            ];

            foreach ($colombiaTypes as $type) {
                FilingType::updateOrCreate(
                    ['jurisdiction_id' => $colombia->id, 'code' => $type['code']],
                    $type + ['jurisdiction_id' => $colombia->id]
                );
            }
        }
    }
}
