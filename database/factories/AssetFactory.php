<?php

namespace Database\Factories;

use App\Enums\Finance\AssetType;
use App\Enums\Finance\OwnershipStructure;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Jurisdiction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $acquisitionDate = $this->faker->dateTimeBetween('-20 years', '-1 year');

        return [
            'name' => $this->faker->words(4, true),
            'type' => $this->faker->randomElement(AssetType::cases()),
            'jurisdiction_id' => Jurisdiction::factory(),
            'entity_id' => Entity::factory(),
            'ownership_structure' => $this->faker->randomElement(OwnershipStructure::cases()),
            'acquisition_date' => $acquisitionDate,
            'acquisition_cost' => $this->faker->randomFloat(2, 50000, 500000),
            'acquisition_currency_id' => Currency::firstOrCreate(
                ['code' => 'USD'],
                ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
            )->id,
            'depreciation_method' => $this->faker->randomElement(['straight_line', 'declining_balance']),
            'useful_life_years' => $this->faker->randomElement([15, 20, 27.5, 39]),
            'annual_depreciation_amount' => null,
        ];
    }

    public function residential(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssetType::Residential,
            'name' => $this->faker->words(2, true).' Residential Property',
            'acquisition_cost' => $this->faker->randomFloat(2, 150000, 500000),
            'useful_life_years' => 27.5,
        ]);
    }

    public function commercial(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssetType::Commercial,
            'name' => $this->faker->words(2, true).' Commercial Property',
            'acquisition_cost' => $this->faker->randomFloat(2, 300000, 1000000),
            'useful_life_years' => 39,
        ]);
    }

    public function land(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AssetType::Land,
            'name' => $this->faker->words(2, true).' Land',
            'acquisition_cost' => $this->faker->randomFloat(2, 50000, 300000),
            'useful_life_years' => null,
        ]);
    }

    public function inSpain(): static
    {
        $spainJurisdiction = Jurisdiction::where('country_code', 'ES')->first()
            ?? Jurisdiction::factory()->create(['country_code' => 'ES', 'name' => 'Spain']);

        return $this->state(fn (array $attributes) => [
            'jurisdiction_id' => $spainJurisdiction->id,
            'acquisition_currency_id' => Currency::where('code', 'EUR')->first()?->id ?? Currency::factory()->euro()->create()->id,
        ]);
    }

    public function inColombia(): static
    {
        $colombiaJurisdiction = Jurisdiction::where('country_code', 'CO')->first()
            ?? Jurisdiction::factory()->create(['country_code' => 'CO', 'name' => 'Colombia']);

        return $this->state(fn (array $attributes) => [
            'jurisdiction_id' => $colombiaJurisdiction->id,
            'acquisition_currency_id' => Currency::where('code', 'COP')->first()?->id ?? Currency::factory()->cop()->create()->id,
        ]);
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'ownership_structure' => OwnershipStructure::Individual,
        ]);
    }

    public function llc(): static
    {
        return $this->state(fn (array $attributes) => [
            'ownership_structure' => OwnershipStructure::LLC,
        ]);
    }
}
