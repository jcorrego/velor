<?php

namespace Database\Factories;

use App\Models\Jurisdiction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserProfile>
 */
class UserProfileFactory extends Factory
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
            'name' => fake()->name(),
            'tax_id' => fake()->numerify('###-##-####'),
            'default_currency' => fake()->currencyCode(),
            'display_currencies' => null,
        ];
    }

    /**
     * Indicate Spain profile.
     */
    public function spain(): static
    {
        return $this->state(fn (array $attributes) => [
            'jurisdiction_id' => Jurisdiction::where('iso_code', 'ESP')->first()?->id ?? Jurisdiction::factory()->spain(),
            'name' => 'Juan Carlos Correa',
            'tax_id' => 'X1234567Y',
            'default_currency' => 'EUR',
        ]);
    }

    /**
     * Indicate USA profile.
     */
    public function usa(): static
    {
        return $this->state(fn (array $attributes) => [
            'jurisdiction_id' => Jurisdiction::where('iso_code', 'USA')->first()?->id ?? Jurisdiction::factory()->usa(),
            'name' => 'John Correa',
            'tax_id' => '123-45-6789',
            'default_currency' => 'USD',
        ]);
    }

    /**
     * Indicate Colombia profile.
     */
    public function colombia(): static
    {
        return $this->state(fn (array $attributes) => [
            'jurisdiction_id' => Jurisdiction::where('iso_code', 'COL')->first()?->id ?? Jurisdiction::factory()->colombia(),
            'name' => 'Juan Carlos Correa',
            'tax_id' => '987654321',
            'default_currency' => 'COP',
        ]);
    }
}
