<?php

namespace Database\Factories;

use App\Models\Jurisdiction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jurisdiction_id' => Jurisdiction::factory(),
            'tax_year_id' => null,
            'title' => fake()->sentence(3),
            'original_name' => fake()->word().'.pdf',
            'stored_path' => 'documents/'.fake()->uuid().'.pdf',
            'storage_disk' => 'local',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 500_000),
            'is_legal' => false,
            'extracted_text' => null,
        ];
    }
}
