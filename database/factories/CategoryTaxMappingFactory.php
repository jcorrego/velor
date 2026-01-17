<?php

namespace Database\Factories;

use App\Enums\Finance\TaxFormCode;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CategoryTaxMapping>
 */
class CategoryTaxMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => TransactionCategory::factory(),
            'tax_form_code' => $this->faker->randomElement(TaxFormCode::cases()),
            'line_item' => $this->faker->words(3, true),
            'country' => $this->faker->randomElement(['USA', 'Spain']),
        ];
    }

    public function forScheduleE(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_form_code' => TaxFormCode::ScheduleE,
            'country' => 'USA',
        ]);
    }

    public function forIRPF(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_form_code' => TaxFormCode::IRPF,
            'country' => 'Spain',
        ]);
    }

    public function rentalIncomeScheduleE(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_form_code' => TaxFormCode::ScheduleE,
            'line_item' => 'Rents received',
            'country' => 'USA',
        ]);
    }

    public function repairsScheduleE(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_form_code' => TaxFormCode::ScheduleE,
            'line_item' => 'Repairs and maintenance',
            'country' => 'USA',
        ]);
    }

    public function managementScheduleE(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_form_code' => TaxFormCode::ScheduleE,
            'line_item' => 'Management fees',
            'country' => 'USA',
        ]);
    }

    public function insuranceScheduleE(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_form_code' => TaxFormCode::ScheduleE,
            'line_item' => 'Insurance',
            'country' => 'USA',
        ]);
    }

    public function rentalIncomeIRPF(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_form_code' => TaxFormCode::IRPF,
            'line_item' => 'Ingresos por alquileres',
            'country' => 'Spain',
        ]);
    }

    public function forCategory(TransactionCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }
}
