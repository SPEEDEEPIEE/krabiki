<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\Registration;
use App\Models\User;
use App\Models\Venue;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_register(): void
    {
        $v = Venue::factory()->create();
        $o = Organizer::factory()->create();
        $w = Workshop::factory()->create([
            'organizer_id' => $o->id, 'venue_id' => $v->id,
            'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3),
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'participant']));
        $this->postJson('/api/registrations', ['workshop_id' => $w->id])
            ->assertStatus(201)->assertJsonPath('data.status', 'pending');
    }

    public function test_approve_full(): void
    {
        $v = Venue::factory()->create(['capacity' => 5]);
        $ou = User::factory()->create(['role' => 'organizer']);
        $o = Organizer::factory()->create(['user_id' => $ou->id]);
        $w = Workshop::factory()->create([
            'organizer_id' => $o->id, 'venue_id' => $v->id,
            'max_participants' => 2, 'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(3),
        ]);

        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        Registration::create(['workshop_id' => $w->id, 'participant_user_id' => $u1->id, 'status' => 'approved']);
        Registration::create(['workshop_id' => $w->id, 'participant_user_id' => $u2->id, 'status' => 'approved']);

        $u3 = User::factory()->create(['role' => 'participant']);
        Sanctum::actingAs($u3);
        $this->postJson('/api/registrations', ['workshop_id' => $w->id])
           ->assertStatus(422)->assertJson(['message' => 'К сожалению, все места заняты. Попробуйте другой мастер-класс.']);
    }

    public function test_foreign_organizer_approve(): void
    {
        $v = Venue::factory()->create();
        $o1u = User::factory()->create(['role' => 'organizer']);
        $o2u = User::factory()->create(['role' => 'organizer']);
        $o1 = Organizer::factory()->create(['user_id' => $o1u->id]);
        $o2 = Organizer::factory()->create(['user_id' => $o2u->id]);
        $w2 = Workshop::factory()->create([
            'organizer_id' => $o2->id, 'venue_id' => $v->id,
            'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3),
        ]);

        $p = User::factory()->create();
        $r = Registration::create(['workshop_id' => $w2->id, 'participant_user_id' => $p->id, 'status' => 'pending']);

        Sanctum::actingAs($o1u);
        $this->patchJson("/api/registrations/{$r->id}/status", ['status' => 'approved'])
            ->assertStatus(403);
    }
}