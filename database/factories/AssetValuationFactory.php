<?php

namespace Database\Factories;

use App\Enums\Finance\ValuationMethod;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetValuation>
 */
class AssetValuationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $asset = Asset::factory();

        return [
            'asset_id' => $asset,
            'amount' => $this->faker->randomFloat(2, 50000, 1000000),
            'valuation_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'method' => $this->faker->randomElement(ValuationMethod::cases()),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function appraisal(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => ValuationMethod::Appraisal,
            'notes' => 'Professional appraisal conducted by certified appraiser.',
        ]);
    }

    public function marketComparable(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => ValuationMethod::MarketComparable,
            'notes' => 'Based on comparable properties in the market.',
        ]);
    }

    public function taxAssessed(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => ValuationMethod::TaxAssessed,
            'notes' => 'Tax assessed value from local tax authority.',
        ]);
    }

    public function forAsset(Asset $asset): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_id' => $asset->id,
        ]);
    }

    public function withDate(\DateTime $date): static
    {
        return $this->state(fn (array $attributes) => [
            'valuation_date' => $date,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'valuation_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}
