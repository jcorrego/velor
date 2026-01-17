<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = [
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => '$'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
        ];

        static $currencyIndex = 0;
        $currency = $currencies[$currencyIndex % count($currencies)];
        $currencyIndex++;

        return [
            'code' => $currency['code'],
            'name' => $currency['name'],
            'symbol' => $currency['symbol'],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function euro(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
        ]);
    }

    public function usd(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
        ]);
    }

    public function cop(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'COP',
            'name' => 'Colombian Peso',
            'symbol' => '$',
        ]);
    }

    public function gbp(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
        ]);
    }

    public function jpy(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'JPY',
            'name' => 'Japanese Yen',
            'symbol' => '¥',
        ]);
    }
}
