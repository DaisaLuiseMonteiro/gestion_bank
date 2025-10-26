<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'admin_code' => $this->faker->unique()->numerify('ADM###'),
            'department' => $this->faker->randomElement(['IT', 'Finance', 'HR', 'Operations']),
        ];
    }
}
