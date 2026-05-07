<?php

namespace Database\Seeders;

use App\Models\Organizer;
use App\Models\User;
use App\Models\Venue;
use App\Models\Workshop;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $orgUser = User::create([
            'name' => 'Organizer User',
            'email' => 'organizer@example.com',
            'password' => bcrypt('password'),
            'role' => 'organizer',
        ]);

        $organizer = Organizer::create([
            'user_id' => $orgUser->id,
            'organization_name' => 'Creative Workshops Inc.',
            'contacts' => 'contact@cw.com',
        ]);

        User::create([
            'name' => 'Participant User',
            'email' => 'participant@example.com',
            'password' => bcrypt('password'),
            'role' => 'participant',
        ]);

        $v1 = Venue::create(['name' => 'Art Studio', 'address' => '123 Main St', 'capacity' => 20, 'hourly_fee' => 50]);
        $v2 = Venue::create(['name' => 'Conference Center', 'address' => '456 Ave', 'capacity' => 100, 'hourly_fee' => 200]);

        Workshop::create([
            'organizer_id' => $organizer->id, 'venue_id' => $v1->id,
            'title' => 'Painting Basics', 'description' => 'Learn painting.',
            'starts_at' => now()->addDays(7), 'ends_at' => now()->addDays(7)->addHours(3),
            'max_participants' => 15, 'price' => 75,
        ]);

        Workshop::create([
            'organizer_id' => $organizer->id, 'venue_id' => $v2->id,
            'title' => 'Business Workshop', 'description' => 'Improve skills.',
            'starts_at' => now()->addDays(14), 'ends_at' => now()->addDays(14)->addHours(4),
            'max_participants' => 30, 'price' => 150,
        ]);
    }
}