<?php

namespace Database\Factories;

use App\Enums\Finance\ImportBatchStatus;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportBatch>
 */
class ImportBatchFactory extends Factory
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
            'status' => ImportBatchStatus::Pending,
            'transaction_count' => $this->faker->numberBetween(1, 50),
            'proposed_transactions' => [],
        ];
    }
}
