<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_full_is_false_without_capacity(): void
    {
        $event = Event::factory()->create(['capacity' => null]);
        Registration::factory()->count(3)->create(['event_id' => $event->id]);

        $this->assertFalse($event->isFull());
    }

    public function test_is_full_counts_active_registrations(): void
    {
        $event = Event::factory()->create(['capacity' => 2]);
        Registration::factory()->count(2)->create(['event_id' => $event->id]);

        $this->assertTrue($event->isFull());
    }

    public function test_is_full_ignores_inactive_registrations(): void
    {
        $event = Event::factory()->create(['capacity' => 2]);
        Registration::factory()->create(['event_id' => $event->id]);
        Registration::factory()->inactive()->count(3)->create(['event_id' => $event->id]);

        $this->assertFalse($event->isFull());
    }

    public function test_accepts_registrations_for_published_future_event(): void
    {
        $this->assertTrue(Event::factory()->create()->acceptsRegistrations());
    }

    public function test_does_not_accept_registrations_when_draft_closed_or_ended(): void
    {
        $this->assertFalse(Event::factory()->draft()->create()->acceptsRegistrations());
        $this->assertFalse(Event::factory()->closed()->create()->acceptsRegistrations());
        $this->assertFalse(Event::factory()->ended()->create()->acceptsRegistrations());
    }
}
