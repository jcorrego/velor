<?php

namespace Database\Factories;

use App\Enums\Finance\TransactionType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalCurrency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
        );
        $convertedCurrency = Currency::firstOrCreate(
            ['code' => 'EUR'],
            ['name' => 'Euro', 'symbol' => '€', 'is_active' => true]
        );
        $originalAmount = $this->faker->randomFloat(2, 10, 10000);

        return [
            'transaction_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'account_id' => Account::factory(),
            'type' => $this->faker->randomElement(TransactionType::cases()),
            'original_amount' => $originalAmount,
            'original_currency_id' => $originalCurrency,
            'converted_amount' => $originalAmount * $this->faker->randomFloat(4, 0.8, 1.2),
            'converted_currency_id' => $convertedCurrency,
            'fx_rate' => $this->faker->randomFloat(4, 0.8, 1.2),
            'fx_source' => $this->faker->randomElement(['ecb', 'manual', 'override']),
            'category_id' => TransactionCategory::factory(),
            'counterparty_name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'tags' => $this->faker->optional()->words(2),
            'reconciled_at' => $this->faker->optional(0.8)->dateTime(),
            'import_source' => $this->faker->randomElement(['manual', 'csv', 'pdf', null]),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Income,
            'original_amount' => $this->faker->randomFloat(2, 100, 5000),
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Expense,
            'original_amount' => $this->faker->randomFloat(2, 10, 2000),
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Transfer,
            'original_amount' => $this->faker->randomFloat(2, 100, 10000),
        ]);
    }

    public function fee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Fee,
            'original_amount' => $this->faker->randomFloat(2, 1, 50),
        ]);
    }

    public function inEuro(): static
    {
        return $this->state(function (array $attributes) {
            $eur = Currency::where('code', 'EUR')->first() ?? Currency::factory()->euro()->create();

            return [
                'original_currency_id' => $eur->id,
                'converted_currency_id' => $eur->id,
                'fx_rate' => 1.0,
            ];
        });
    }

    public function multiCurrency(): static
    {
        return $this->state(function (array $attributes) {
            $currencies = Currency::all();
            if ($currencies->count() < 2) {
                Currency::factory()->euro()->create();
                Currency::factory()->usd()->create();
                $currencies = Currency::all();
            }

            $from = $currencies->random();
            $to = $currencies->where('id', '!=', $from->id)->random();

            return [
                'original_currency_id' => $from->id,
                'converted_currency_id' => $to->id,
            ];
        });
    }

    public function reconciled(): static
    {
        return $this->state(fn (array $attributes) => [
            'reconciled_at' => now(),
        ]);
    }

    public function unreconciled(): static
    {
        return $this->state(fn (array $attributes) => [
            'reconciled_at' => null,
        ]);
    }

    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }

    public function rentalIncome(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Income,
            'description' => 'Rental income',
            'counterparty_name' => $this->faker->name(),
        ]);
    }

    public function propertyExpense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Expense,
            'description' => $this->faker->randomElement([
                'Property maintenance and repairs',
                'Property management fee',
                'Insurance premium',
                'Property tax',
                'Utilities',
            ]),
        ]);
    }
}
