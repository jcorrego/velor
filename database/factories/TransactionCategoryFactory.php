<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionCategory>
 */
class TransactionCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'income_or_expense' => $this->faker->randomElement(['income', 'expense']),
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    public function rentalIncome(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Rental Income',
            'income_or_expense' => 'income',
            'sort_order' => 10,
        ]);
    }

    public function propertyMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Rental Property Maintenance and Repairs',
            'income_or_expense' => 'expense',
            'sort_order' => 20,
        ]);
    }

    public function propertyManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Property Management Fees',
            'income_or_expense' => 'expense',
            'sort_order' => 21,
        ]);
    }

    public function insurance(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Property Insurance',
            'income_or_expense' => 'expense',
            'sort_order' => 22,
        ]);
    }

    public function propertyTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Property Tax',
            'income_or_expense' => 'expense',
            'sort_order' => 23,
        ]);
    }

    public function utilities(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Utilities',
            'income_or_expense' => 'expense',
            'sort_order' => 24,
        ]);
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'income_or_expense' => 'income',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'income_or_expense' => 'expense',
        ]);
    }
}
