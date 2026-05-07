<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'participant_user_id' => User::factory(['role' => 'participant']),
            'status' => 'pending',
            'paid' => false,
        ];
    }
}