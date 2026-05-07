<?php

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkshopFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+30 days');
        return [
            'organizer_id' => Organizer::factory(),
            'venue_id' => Venue::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+3 hours'),
            'max_participants' => fake()->numberBetween(5, 50),
            'price' => fake()->randomFloat(2, 0, 500),
        ];
    }
}