<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\RegistrationAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::factory()->create();
        $this->event = Event::factory()->create(['organization_id' => $this->org->id]);
    }

    public function test_index_lists_registrations_as_paginated_json(): void
    {
        Registration::factory()->count(3)->create(['event_id' => $this->event->id]);

        $this->actingAs($this->orgAdmin($this->org))->getJson('/registrations')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_can_search_by_name(): void
    {
        Registration::factory()->create(['event_id' => $this->event->id, 'name' => 'Zelda Findable']);
        Registration::factory()->create(['event_id' => $this->event->id, 'name' => 'Someone Else']);

        $this->actingAs($this->orgAdmin($this->org))->getJson('/registrations?search=Zelda')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Zelda Findable');
    }

    public function test_toggle_attendance_sets_and_clears_checked_in_at(): void
    {
        $registration = Registration::factory()->create(['event_id' => $this->event->id]);
        $admin = $this->orgAdmin($this->org);

        $this->actingAs($admin)->putJson("/registrations/{$registration->id}/attendance", ['attended' => true])->assertOk();
        $registration->refresh();
        $this->assertTrue($registration->attended);
        $this->assertNotNull($registration->checked_in_at);

        $this->actingAs($admin)->putJson("/registrations/{$registration->id}/attendance", ['attended' => false])->assertOk();
        $registration->refresh();
        $this->assertFalse($registration->attended);
        $this->assertNull($registration->checked_in_at);
    }

    public function test_status_can_be_set_to_inactive(): void
    {
        $registration = Registration::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->orgAdmin($this->org))
            ->putJson("/registrations/{$registration->id}/status", ['status' => 'inactive'])
            ->assertOk();

        $this->assertSame(RegistrationStatus::Inactive, $registration->refresh()->status);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $registration = Registration::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->orgAdmin($this->org))
            ->putJson("/registrations/{$registration->id}/status", ['status' => 'banana'])
            ->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_delete_removes_registration_and_its_answers(): void
    {
        $registration = Registration::factory()->create(['event_id' => $this->event->id]);
        RegistrationAnswer::create([
            'organization_id' => $this->org->id,
            'registration_id' => $registration->id,
            'question_label' => 'Q',
            'value' => 'A',
        ]);

        $this->actingAs($this->orgAdmin($this->org))->deleteJson("/registrations/{$registration->id}")->assertOk();

        $this->assertDatabaseMissing('registrations', ['id' => $registration->id]);
        $this->assertDatabaseCount('registration_answers', 0);
    }

    public function test_user_without_permissions_is_forbidden(): void
    {
        $this->seedRoles();
        $registration = Registration::factory()->create(['event_id' => $this->event->id]);
        $user = $this->plainUser($this->org);

        $this->actingAs($user)->getJson('/registrations')->assertForbidden();
        $this->actingAs($user)->putJson("/registrations/{$registration->id}/attendance", ['attended' => true])->assertForbidden();
        $this->actingAs($user)->putJson("/registrations/{$registration->id}/status", ['status' => 'inactive'])->assertForbidden();
        $this->actingAs($user)->deleteJson("/registrations/{$registration->id}")->assertForbidden();

        $this->assertNotNull(Registration::withoutGlobalScopes()->find($registration->id));
    }
}
