<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FxRate>
 */
class FxRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'currency_from_id' => Currency::factory(),
            'currency_to_id' => Currency::factory(),
            'rate' => $this->faker->randomFloat(4, 0.5, 2.5),
            'rate_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'source' => $this->faker->randomElement(['ECB', 'BOE', 'BANCO_SANTANDER', 'XE']),
        ];
    }

    public function eurToUsd(): static
    {
        return $this->state(function (array $attributes) {
            $eur = Currency::where('code', 'EUR')->first() ?? Currency::factory()->euro()->create();
            $usd = Currency::where('code', 'USD')->first() ?? Currency::factory()->usd()->create();

            return [
                'currency_from_id' => $eur->id,
                'currency_to_id' => $usd->id,
                'rate' => $this->faker->randomFloat(4, 1.0, 1.1),
            ];
        });
    }

    public function eurToCop(): static
    {
        return $this->state(function (array $attributes) {
            $eur = Currency::where('code', 'EUR')->first() ?? Currency::factory()->euro()->create();
            $cop = Currency::where('code', 'COP')->first() ?? Currency::factory()->cop()->create();

            return [
                'currency_from_id' => $eur->id,
                'currency_to_id' => $cop->id,
                'rate' => $this->faker->randomFloat(0, 4000, 4500),
            ];
        });
    }

    public function eurToGbp(): static
    {
        return $this->state(function (array $attributes) {
            $eur = Currency::where('code', 'EUR')->first() ?? Currency::factory()->euro()->create();
            $gbp = Currency::where('code', 'GBP')->first() ?? Currency::factory()->gbp()->create();

            return [
                'currency_from_id' => $eur->id,
                'currency_to_id' => $gbp->id,
                'rate' => $this->faker->randomFloat(4, 0.85, 0.95),
            ];
        });
    }

    public function fromEcb(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'ECB',
        ]);
    }

    public function specificDate(\DateTime $date): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_date' => $date,
        ]);
    }
}
