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
            'cnpj' => $this->generateCNPJ(),
            'corporate_reason' => fake()->company(),
            'fantasy_name' => fake()->companySuffix(),
            'opening_date' => fake()->dateTimeBetween('-10 years', 'now'),
            'created_at' => Carbon::createFromTimestamp(fake()
                ->dateTimeBetween('2024-01-01', '2024-12-31')
                ->getTimestamp()),
        ];
    }

    function generateCNPJ() {
        $n = [];
        for ($i = 0; $i < 12; $i++) {
            $n[] = rand(0, 9);
        }
    
        $d1 = 0;
        for ($i = 0, $j = 5; $i < 12; $i++, $j--) {
            $j = $j == 1 ? 9 : $j;
            $d1 += $n[$i] * $j;
        }
        $d1 = $d1 % 11 < 2 ? 0 : 11 - ($d1 % 11);
    
        $d2 = 0;
        for ($i = 0, $j = 6; $i < 13; $i++, $j--) {
            $j = $j == 1 ? 9 : $j;
            $d2 += ($n[$i] ?? $d1) * $j;
        }
        $d2 = $d2 % 11 < 2 ? 0 : 11 - ($d2 % 11);
    
        return implode('', $n) . $d1 . $d2;
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
