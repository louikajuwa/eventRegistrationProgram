<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventQuestion;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use Database\Factories\EventQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminA;

    private Event $eventB;

    private Registration $regB;

    protected function setUp(): void
    {
        parent::setUp();

        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $this->adminA = $this->orgAdmin($orgA);

        $this->eventB = Event::factory()->create(['organization_id' => $orgB->id, 'title' => 'Org B Secret Event']);
        EventQuestionFactory::new()->create(['event_id' => $this->eventB->id]);
        $this->regB = Registration::factory()->create([
            'event_id' => $this->eventB->id,
            'name' => 'Bobby Tenantb',
            'email' => 'bobby@orgb.test',
        ]);
    }

    private function assertDenied($response): void
    {
        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    public function test_cannot_update_another_orgs_event(): void
    {
        $this->assertDenied($this->actingAs($this->adminA)->putJson("/events/{$this->eventB->id}", [
            'title' => 'Hijacked', 'status' => 'published',
        ]));

        $this->assertSame('Org B Secret Event', Event::withoutGlobalScopes()->find($this->eventB->id)->title);
    }

    public function test_cannot_delete_another_orgs_event(): void
    {
        $this->assertDenied($this->actingAs($this->adminA)->deleteJson("/events/{$this->eventB->id}"));

        $this->assertNotNull(Event::withoutGlobalScopes()->find($this->eventB->id));
    }

    public function test_cannot_read_another_orgs_event_questions(): void
    {
        $this->assertDenied($this->actingAs($this->adminA)->getJson("/events/{$this->eventB->id}/questions"));
    }

    public function test_cannot_sync_another_orgs_event_questions(): void
    {
        $this->assertDenied($this->actingAs($this->adminA)->putJson("/events/{$this->eventB->id}/questions", [
            'questions' => [['label' => 'Email', 'type' => 'email'], ['label' => 'Injected', 'type' => 'text']],
        ]));

        $this->assertSame(1, EventQuestion::withoutGlobalScopes()->where('event_id', $this->eventB->id)->count());
    }

    public function test_cannot_view_another_orgs_event_dashboard(): void
    {
        $this->assertDenied($this->actingAs($this->adminA)->get("/events/{$this->eventB->id}/dashboard"));
    }

    public function test_cannot_view_another_orgs_registration(): void
    {
        $this->assertDenied($this->actingAs($this->adminA)->getJson("/registrations/{$this->regB->id}"));
    }

    public function test_cannot_toggle_attendance_on_another_orgs_registration(): void
    {
        $this->assertDenied($this->actingAs($this->adminA)->putJson("/registrations/{$this->regB->id}/attendance", ['attended' => true]));

        $this->assertFalse(Registration::withoutGlobalScopes()->find($this->regB->id)->attended);
    }

    public function test_cannot_change_status_of_another_orgs_registration(): void
    {
        $this->assertDenied($this->actingAs($this->adminA)->putJson("/registrations/{$this->regB->id}/status", ['status' => 'inactive']));

        $this->assertSame('active', Registration::withoutGlobalScopes()->find($this->regB->id)->status->value);
    }

    public function test_cannot_delete_another_orgs_registration(): void
    {
        $this->assertDenied($this->actingAs($this->adminA)->deleteJson("/registrations/{$this->regB->id}"));

        $this->assertNotNull(Registration::withoutGlobalScopes()->find($this->regB->id));
    }

    public function test_registration_list_excludes_other_orgs_rows(): void
    {
        $eventA = Event::factory()->create(['organization_id' => $this->adminA->organization_id]);
        Registration::factory()->create(['event_id' => $eventA->id, 'name' => 'Alice Tenanta']);

        $response = $this->actingAs($this->adminA)->getJson('/registrations')->assertOk();

        $response->assertSee('Alice Tenanta')->assertDontSee('Bobby Tenantb')->assertDontSee('bobby@orgb.test');
        $this->assertCount(1, $response->json('data'));
    }

    public function test_report_export_excludes_other_orgs_rows(): void
    {
        $eventA = Event::factory()->create(['organization_id' => $this->adminA->organization_id]);
        Registration::factory()->create(['event_id' => $eventA->id, 'name' => 'Alice Tenanta']);

        $response = $this->actingAs($this->adminA)
            ->get('/reports/export?type=registrations&format=csv')
            ->assertOk();

        $content = file_get_contents($response->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('Alice Tenanta', $content);
        $this->assertStringNotContainsString('Bobby Tenantb', $content);
    }
}
