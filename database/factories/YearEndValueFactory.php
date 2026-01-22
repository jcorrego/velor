<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\TaxYear;
use App\Models\YearEndValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\YearEndValue>
 */
class YearEndValueFactory extends Factory
{
    /**
     * @return $this
     */
    public function configure(): static
    {
        return $this->afterCreating(function (YearEndValue $value): void {
            if ($value->account_id && $value->account) {
                $value->entity_id = $value->account->entity_id;
            }

            if ($value->asset_id && $value->asset) {
                $value->entity_id = $value->asset->entity_id;
            }

            if ($value->entity && $value->taxYear && $value->taxYear->jurisdiction_id !== $value->entity->jurisdiction_id) {
                $taxYear = TaxYear::factory()->create([
                    'jurisdiction_id' => $value->entity->jurisdiction_id,
                ]);
                $value->tax_year_id = $taxYear->id;
            }

            $value->save();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entity_id' => Entity::factory(),
            'tax_year_id' => TaxYear::factory(),
            'account_id' => Account::factory(),
            'asset_id' => null,
            'amount' => $this->faker->randomFloat(2, 1000, 500000),
        ];
    }

    public function forAsset(): static
    {
        return $this->state(fn () => [
            'account_id' => null,
            'asset_id' => Asset::factory(),
        ]);
    }
}
