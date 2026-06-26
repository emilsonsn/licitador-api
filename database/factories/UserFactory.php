<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'has_notification' => fake()->boolean(),
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_active' => true,
            'surname' => fake()->lastName(),
            'birthday' => fake()->dateTimeBetween('-40 years', '-18 years'),
            'postalcode' => fake()->postcode(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'created_at' => Carbon::createFromTimestamp(fake()
                ->dateTimeBetween('2024-01-01', '2024-12-31')
                ->getTimestamp()),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
