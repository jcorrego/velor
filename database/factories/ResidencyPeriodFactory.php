<?php

namespace Database\Factories;

use App\Models\Jurisdiction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResidencyPeriod>
 */
class ResidencyPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'user_id' => User::factory(),
            'jurisdiction_id' => Jurisdiction::factory(),
            'start_date' => $startDate,
            'end_date' => fake()->optional(0.3)->dateTimeBetween($startDate, 'now'),
            'is_fiscal_residence' => false,
        ];
    }

    /**
     * Indicate current residency (no end date).
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => null,
        ]);
    }

    /**
     * Indicate fiscal residence.
     */
    public function fiscalResidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_fiscal_residence' => true,
        ]);
    }
}
