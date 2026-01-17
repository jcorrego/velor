<?php

namespace Database\Factories;

use App\Enums\Finance\ImportFileType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionImport>
 */
class TransactionImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'file_type' => $this->faker->randomElement(ImportFileType::cases()),
            'file_name' => $this->faker->fileName(),
            'parsed_count' => $this->faker->numberBetween(10, 500),
            'matched_count' => $this->faker->numberBetween(0, 500),
            'imported_at' => $this->faker->optional(0.8)->dateTime(),
        ];
    }

    public function csv(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => ImportFileType::CSV,
            'file_name' => $this->faker->word().'_export_'.$this->faker->date().'.csv',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_type' => ImportFileType::PDF,
            'file_name' => $this->faker->word().'_statement_'.$this->faker->date().'.pdf',
        ]);
    }

    public function bancoSantander(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => 'SANTANDER_'.$this->faker->date('Ymd').'.csv',
        ]);
    }

    public function mercury(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => 'MERCURY_'.$this->faker->date('Ymd').'.csv',
        ]);
    }

    public function bancolombia(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_name' => 'BANCOLOMBIA_'.$this->faker->date('Ymd').'.csv',
        ]);
    }

    public function fullyMatched(): static
    {
        return $this->state(fn (array $attributes) => [
            'matched_count' => $attributes['parsed_count'],
            'imported_at' => now(),
        ]);
    }

    public function partiallyMatched(): static
    {
        return $this->state(function (array $attributes) {
            $parsed = $attributes['parsed_count'];

            return [
                'matched_count' => (int) ($parsed * 0.75),
                'imported_at' => now(),
            ];
        });
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'imported_at' => null,
        ]);
    }

    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }
}
