<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\User;
use App\Models\Venue;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkshopTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_workshops(): void
    {
        Venue::factory()->create();
        Organizer::factory()->create();
        Workshop::factory()->count(5)->create();

        $this->getJson('/api/v1/workshops')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_filter_by_organizer(): void
    {
        $v = Venue::factory()->create();
        $o1 = Organizer::factory()->create();
        $o2 = Organizer::factory()->create();
        Workshop::factory()->create(['organizer_id' => $o1->id, 'venue_id' => $v->id]);
        Workshop::factory()->create(['organizer_id' => $o2->id, 'venue_id' => $v->id]);

        $this->getJson('/api/v1/workshops?organizer_id=' . $o1->id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_organizer_can_create_workshop(): void
    {
        $v = Venue::factory()->create();
        $u = User::factory()->create(['role' => 'organizer']);
        Organizer::factory()->create(['user_id' => $u->id]);
        Sanctum::actingAs($u);

        $this->postJson('/api/v1/workshops', [
            'venue_id' => $v->id,
            'title' => 'Test Workshop',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(3)->toDateTimeString(),
            'max_participants' => 10,
            'price' => 50.00,
        ])->assertStatus(201);
    }

    public function test_participant_cannot_create_workshop(): void
    {
        $v = Venue::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => 'participant']));

        $this->postJson('/api/v1/workshops', [
            'venue_id' => $v->id,
            'title' => 'Test Workshop',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(3)->toDateTimeString(),
            'max_participants' => 10,
        ])->assertStatus(403);
    }
}