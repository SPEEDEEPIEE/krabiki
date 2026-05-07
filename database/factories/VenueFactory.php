<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VenueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Venue',
            'address' => fake()->address(),
            'capacity' => fake()->numberBetween(10, 200),
            'hourly_fee' => fake()->randomFloat(2, 20, 500),
        ];
    }
}