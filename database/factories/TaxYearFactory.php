<?php

namespace Database\Factories;

use App\Models\Jurisdiction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxYear>
 */
class TaxYearFactory extends Factory
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
            'year' => $this->faker->unique()->numberBetween(2020, 2035),
        ];
    }
}
