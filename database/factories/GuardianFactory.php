<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guardian>
 */
class GuardianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+263'.fake()->numberBetween(700000000, 799999999),
            'relationship' => fake()->randomElement(['parent', 'guardian', 'sibling']),
            'address' => fake()->address(),
            'national_id' => fake()->optional()->numerify('##-######X##'),
            'occupation' => fake()->optional()->jobTitle(),
            'is_primary' => true,
            'can_receive_notifications' => true,
        ];
    }
}
