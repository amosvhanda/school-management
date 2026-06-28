<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        $number = fake()->numberBetween(101, 399);

        return [
            'school_id' => School::factory(),
            'name' => "Room {$number}",
            'code' => "R{$number}",
            'type' => fake()->randomElement(['classroom', 'laboratory', 'hall']),
            'capacity' => fake()->numberBetween(25, 45),
            'location' => fake()->optional()->randomElement(['Block A', 'Block B', 'Main Building']),
            'is_active' => true,
        ];
    }
}
