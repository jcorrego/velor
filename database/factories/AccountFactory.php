<?php

namespace Database\Factories;

use App\Enums\Finance\AccountType;
use App\Models\Currency;
use App\Models\Entity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $openingDate = $this->faker->dateTimeBetween('-10 years', '-1 month');

        return [
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(AccountType::cases()),
            'currency_id' => Currency::factory(),
            'entity_id' => Entity::factory(),
            'opening_date' => $openingDate,
            'closing_date' => $this->faker->optional(0.2)->dateTimeBetween($openingDate),
            'integration_metadata' => null,
        ];
    }

    public function bancoSantander(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Banco Santander',
            'currency_id' => Currency::where('code', 'EUR')->first()?->id ?? Currency::factory()->euro()->create()->id,
        ]);
    }

    public function mercury(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Mercury USD Account',
            'type' => AccountType::Digital,
            'currency_id' => Currency::where('code', 'USD')->first()?->id ?? Currency::factory()->usd()->create()->id,
        ]);
    }

    public function bancolombia(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Bancolombia COP',
            'currency_id' => Currency::where('code', 'COP')->first()?->id ?? Currency::factory()->cop()->create()->id,
        ]);
    }

    public function checking(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Checking,
        ]);
    }

    public function savings(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Savings,
        ]);
    }

    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Digital,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'closing_date' => null,
        ]);
    }

    public function closed(): static
    {
        $openingDate = $this->faker->dateTimeBetween('-10 years', '-1 month');

        return $this->state(fn (array $attributes) => [
            'opening_date' => $openingDate,
            'closing_date' => $this->faker->dateTimeBetween($openingDate),
        ]);
    }

    public function euro(): static
    {
        return $this->state(fn (array $attributes) => [
            'currency_id' => Currency::where('code', 'EUR')->first()?->id ?? Currency::factory()->euro()->create()->id,
        ]);
    }
}
