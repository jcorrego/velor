<?php

namespace Database\Factories;

use App\FilingStatus;
use App\Models\FilingType;
use App\Models\TaxYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Filing>
 */
class FilingFactory extends Factory
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
            'tax_year_id' => TaxYear::factory(),
            'filing_type_id' => FilingType::factory(),
            'status' => fake()->randomElement(FilingStatus::cases()),
            'due_date' => null,
            'key_metrics' => null,
        ];
    }

    /**
     * Indicate Planning status.
     */
    public function planning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FilingStatus::Planning,
        ]);
    }

    /**
     * Indicate InReview status.
     */
    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FilingStatus::InReview,
        ]);
    }

    /**
     * Indicate Filed status.
     */
    public function filed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FilingStatus::Filed,
        ]);
    }
}
