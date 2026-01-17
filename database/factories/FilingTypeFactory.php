<?php

namespace Database\Factories;

use App\Models\Jurisdiction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FilingType>
 */
class FilingTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jurisdiction_id' => Jurisdiction::factory(),
            'code' => fake()->unique()->lexify('????'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
