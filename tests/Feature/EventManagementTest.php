<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventQuestion;
use App\Models\Organization;
use App\Models\Registration;
use Database\Factories\EventQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Annual Summit',
            'status' => 'draft',
            'starts_at' => now()->addDays(5)->toDateTimeString(),
            'ends_at' => now()->addDays(6)->toDateTimeString(),
        ], $overrides);
    }

    public function test_org_admin_creates_event_with_slug_and_organization(): void
    {
        $admin = $this->orgAdmin($this->org);

        $this->actingAs($admin)->postJson('/events', $this->payload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'annual-summit')
            ->assertJsonPath('data.organization_id', $this->org->id);

        $this->assertDatabaseHas('events', ['title' => 'Annual Summit', 'organization_id' => $this->org->id]);
    }

    public function test_events_with_same_title_get_distinct_slugs(): void
    {
        $admin = $this->orgAdmin($this->org);

        $a = $this->actingAs($admin)->postJson('/events', $this->payload())->assertCreated()->json('data.slug');
        $b = $this->actingAs($admin)->postJson('/events', $this->payload())->assertCreated()->json('data.slug');

        $this->assertNotSame($a, $b);
    }

    public function test_org_admin_updates_event_and_slug_is_stable(): void
    {
        $admin = $this->orgAdmin($this->org);
        $event = Event::factory()->create(['organization_id' => $this->org->id, 'title' => 'Old', 'slug' => 'old-slug']);

        $this->actingAs($admin)->putJson("/events/{$event->id}", $this->payload(['title' => 'New Title', 'status' => 'published', 'capacity' => 50]))
            ->assertOk()
            ->assertJsonPath('data.title', 'New Title')
            ->assertJsonPath('data.slug', 'old-slug')
            ->assertJsonPath('data.capacity', 50);
    }

    public function test_org_admin_deletes_event_and_children_cascade(): void
    {
        $admin = $this->orgAdmin($this->org);
        $event = Event::factory()->create(['organization_id' => $this->org->id]);
        EventQuestionFactory::new()->create(['event_id' => $event->id]);
        Registration::factory()->create(['event_id' => $event->id]);

        $this->actingAs($admin)->deleteJson("/events/{$event->id}")->assertOk();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        $this->assertDatabaseCount('event_questions', 0);
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_end_before_start_is_rejected(): void
    {
        $admin = $this->orgAdmin($this->org);

        $this->actingAs($admin)->postJson('/events', $this->payload([
            'starts_at' => now()->addDays(5)->toDateTimeString(),
            'ends_at' => now()->addDays(4)->toDateTimeString(),
        ]))->assertStatus(422)->assertJsonValidationErrors(['ends_at']);
    }

    public function test_super_admin_without_organization_cannot_create_event(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/events', $this->payload())->assertForbidden();

        $this->assertDatabaseCount('events', 0);
    }

    public function test_valid_question_sync_persists_ordered_questions(): void
    {
        $admin = $this->orgAdmin($this->org);
        $event = Event::factory()->draft()->create(['organization_id' => $this->org->id]);

        $this->actingAs($admin)->putJson("/events/{$event->id}/questions", ['questions' => [
            ['label' => 'Email', 'type' => 'email', 'is_required' => true],
            ['label' => 'Full name', 'type' => 'text', 'is_required' => true],
            ['label' => 'Meal', 'type' => 'select', 'options' => ['Veg', ' Meat ', '']],
        ]])->assertOk()->assertJsonPath('success', true);

        $questions = EventQuestion::withoutGlobalScopes()->where('event_id', $event->id)->orderBy('sort_order')->get();
        $this->assertSame(['Email', 'Full name', 'Meal'], $questions->pluck('label')->all());
        $this->assertSame([0, 1, 2], $questions->pluck('sort_order')->all());
        $this->assertSame(['Veg', 'Meat'], $questions[2]->options);
        $this->assertSame($this->org->id, $questions[0]->organization_id);
    }

    public function test_question_sync_updates_existing_and_removes_omitted(): void
    {
        $admin = $this->orgAdmin($this->org);
        $event = Event::factory()->draft()->create(['organization_id' => $this->org->id]);
        $keep = EventQuestionFactory::new()->create(['event_id' => $event->id, 'label' => 'Keep me']);
        $drop = EventQuestionFactory::new()->create(['event_id' => $event->id, 'label' => 'Drop me']);

        $this->actingAs($admin)->putJson("/events/{$event->id}/questions", ['questions' => [
            ['id' => $keep->id, 'label' => 'Renamed', 'type' => 'text'],
        ]])->assertOk();

        $this->assertSame('Renamed', EventQuestion::withoutGlobalScopes()->find($keep->id)->label);
        $this->assertNull(EventQuestion::withoutGlobalScopes()->find($drop->id));
    }

    public function test_select_question_without_options_is_rejected(): void
    {
        $admin = $this->orgAdmin($this->org);
        $event = Event::factory()->draft()->create(['organization_id' => $this->org->id]);

        $this->actingAs($admin)->putJson("/events/{$event->id}/questions", ['questions' => [
            ['label' => 'Email', 'type' => 'email'],
            ['label' => 'Pick one', 'type' => 'select', 'options' => []],
        ]])->assertStatus(422);

        $this->assertSame(0, EventQuestion::withoutGlobalScopes()->where('event_id', $event->id)->count());
    }

    public function test_published_event_must_keep_an_email_question(): void
    {
        $admin = $this->orgAdmin($this->org);
        $event = Event::factory()->create(['organization_id' => $this->org->id]); // published

        $this->actingAs($admin)->putJson("/events/{$event->id}/questions", ['questions' => [
            ['label' => 'Full name', 'type' => 'text'],
        ]])->assertStatus(422);
    }

    public function test_user_without_permission_cannot_manage_events(): void
    {
        $this->seedRoles();
        $user = $this->plainUser($this->org);

        $this->actingAs($user)->postJson('/events', $this->payload())->assertForbidden();
    }
}
