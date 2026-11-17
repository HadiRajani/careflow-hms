<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fake patients for tests. Synthetic data only.
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => fake()->firstName() . ' ' . fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'phone' => '03' . fake()->numerify('#########'),
            'cnic' => null,
            'created_by' => User::factory(),
        ];
    }
}
