<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_venue(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->postJson('/api/venues', [
            'name' => 'Test', 'address' => 'Addr', 'capacity' => 50,
        ]);
        $response->assertStatus(201);
    }

    public function test_non_admin_cannot_create_venue(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'organizer']));
        $response = $this->postJson('/api/venues', [
            'name' => 'Test', 'address' => 'Addr', 'capacity' => 50,
        ]);
        $response->assertStatus(403);
    }

    public function test_can_list_venues(): void
    {
        Venue::factory()->count(3)->create();
        $this->getJson('/api/venues')->assertStatus(200)->assertJsonCount(3, 'data');
    }
}