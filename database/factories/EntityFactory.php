<?php

namespace Database\Factories;

use App\EntityType;
use App\Models\Jurisdiction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entity>
 */
class EntityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jurisdiction_id' => Jurisdiction::factory(),
            'type' => fake()->randomElement(EntityType::cases()),
            'name' => fake()->company(),
            'ein_or_tax_id' => fake()->optional(0.5)->numerify('##-#######'),
        ];
    }

    /**
     * Indicate Individual entity type.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntityType::Individual,
            'name' => fake()->name(),
            'ein_or_tax_id' => null,
        ]);
    }

    /**
     * Indicate LLC entity type.
     */
    public function llc(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntityType::LLC,
            'name' => fake()->company().' LLC',
            'ein_or_tax_id' => fake()->numerify('##-#######'),
        ]);
    }
}
