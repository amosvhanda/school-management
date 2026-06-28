<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        $names = [
            'Mufakose 1 High School',
            'Allan Wilson Boys High',
            'Prince Edward School',
            'Highfield High 1',
            'Glen View 1 High',
            'Dzivarasekwa High',
            'Churchill Boys High',
        ];
        $name = $this->faker->randomElement($names);
        $slug = Str::slug($name);
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6)) . $this->faker->unique()->numberBetween(1, 999);

        return [
            'name' => $name,
            'code' => $code,
            'address' => $this->faker->streetAddress() . ', Harare',
            'phone' => '+263 ' . $this->faker->numberBetween(70, 78) . ' ' . $this->faker->numberBetween(100, 999) . ' ' . $this->faker->numberBetween(100, 999),
            'email' => 'info@' . $slug . '.co.zw',
            'currency_default' => 'USD',
            'academic_year' => (string) now()->year,
            'current_term' => 'Term 1',
            'settings' => null,
        ];
    }
}
