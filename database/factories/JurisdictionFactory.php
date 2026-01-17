<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Jurisdiction>
 */
class JurisdictionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'iso_code' => strtoupper(fake()->unique()->lexify('???')),
            'timezone' => fake()->timezone(),
            'default_currency' => fake()->currencyCode(),
            'tax_year_start_month' => 1,
            'tax_year_start_day' => 1,
        ];
    }

    /**
     * Indicate Spain jurisdiction.
     */
    public function spain(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Spain',
            'iso_code' => 'ESP',
            'timezone' => 'Europe/Madrid',
            'default_currency' => 'EUR',
        ]);
    }

    /**
     * Indicate USA jurisdiction.
     */
    public function usa(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'United States',
            'iso_code' => 'USA',
            'timezone' => 'America/New_York',
            'default_currency' => 'USD',
        ]);
    }

    /**
     * Indicate Colombia jurisdiction.
     */
    public function colombia(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Colombia',
            'iso_code' => 'COL',
            'timezone' => 'America/Bogota',
            'default_currency' => 'COP',
        ]);
    }
}
