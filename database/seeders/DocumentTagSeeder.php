<?php

namespace Database\Seeders;

use App\Models\DocumentTag;
use Illuminate\Database\Seeder;

class DocumentTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentTag::factory()->count(5)->create();
    }
}
