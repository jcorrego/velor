<?php

namespace Database\Factories;

use App\Enums\Finance\RelatedPartyType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RelatedPartyTransaction>
 */
class RelatedPartyTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'type' => $this->faker->randomElement(RelatedPartyType::cases()),
            'owner_id' => User::factory(),
            'account_id' => Account::factory(),
            'description' => $this->faker->sentence(),
        ];
    }

    public function ownerContribution(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RelatedPartyType::OwnerContribution,
            'amount' => $this->faker->randomFloat(2, 1000, 50000),
            'description' => 'Owner contribution to business',
        ]);
    }

    public function ownerDraw(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RelatedPartyType::OwnerDraw,
            'amount' => $this->faker->randomFloat(2, 500, 20000),
            'description' => 'Owner draw from business',
        ]);
    }

    public function personalSpending(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RelatedPartyType::PersonalSpending,
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'description' => 'Personal spending from business account',
        ]);
    }

    public function reimbursement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RelatedPartyType::Reimbursement,
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'description' => 'Reimbursement for business expenses',
        ]);
    }

    public function forOwner(User $owner): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $owner->id,
        ]);
    }

    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }
}
