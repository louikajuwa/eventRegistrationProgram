<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use App\Models\RegistrationAnswer;
use App\Notifications\RegistrationConfirmedNotification;
use Database\Factories\EventQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function eventWithForm(array $eventAttrs = []): array
    {
        $event = Event::factory()->create($eventAttrs);
        $name = EventQuestionFactory::new()->required()->create(['event_id' => $event->id, 'label' => 'Full name', 'sort_order' => 0]);
        $email = EventQuestionFactory::new()->email()->required()->create(['event_id' => $event->id, 'sort_order' => 1]);

        return [$event, $name, $email];
    }

    private function regCount(Event $event): int
    {
        return Registration::withoutGlobalScopes()->where('event_id', $event->id)->count();
    }

    public function test_published_event_page_shows_title(): void
    {
        $event = Event::factory()->create(['title' => 'Spring Gala']);

        $this->get("/e/{$event->slug}")->assertOk()->assertSee('Spring Gala');
    }

    public function test_draft_event_page_is_not_found(): void
    {
        $event = Event::factory()->draft()->create();

        $this->get("/e/{$event->slug}")->assertNotFound();
    }

    public function test_registration_happy_path_creates_registration_answers_and_sends_confirmation(): void
    {
        Notification::fake();
        [$event, $name, $email] = $this->eventWithForm();

        $response = $this->postJson("/e/{$event->slug}", [
            'answers' => [$name->id => 'Jane Doe', $email->id => 'jane@example.com'],
        ]);

        $registration = Registration::withoutGlobalScopes()->where('event_id', $event->id)->firstOrFail();

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.redirect', route('public.events.thanks', ['event' => $event->slug, 'reference' => $registration->reference]));

        $this->assertSame('Jane Doe', $registration->name);
        $this->assertSame('jane@example.com', $registration->email);
        $this->assertSame($event->organization_id, $registration->organization_id);
        $this->assertSame(2, RegistrationAnswer::withoutGlobalScopes()->where('registration_id', $registration->id)->count());

        Notification::assertSentOnDemand(
            RegistrationConfirmedNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'jane@example.com'
        );
    }

    public function test_required_question_missing_returns_validation_error(): void
    {
        [$event, $name, $email] = $this->eventWithForm();

        $this->postJson("/e/{$event->slug}", ['answers' => [$email->id => 'jane@example.com']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["answers.{$name->id}"]);

        $this->assertSame(0, $this->regCount($event));
    }

    public function test_select_value_outside_options_is_rejected(): void
    {
        $event = Event::factory()->create();
        $select = EventQuestionFactory::new()->select(['Red', 'Blue'])->create(['event_id' => $event->id, 'label' => 'Colour']);

        $this->postJson("/e/{$event->slug}", ['answers' => [$select->id => 'Green']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["answers.{$select->id}"]);
    }

    public function test_full_event_rejects_new_registration(): void
    {
        [$event, $name, $email] = $this->eventWithForm(['capacity' => 1]);
        Registration::factory()->create(['event_id' => $event->id]);

        $this->postJson("/e/{$event->slug}", [
            'answers' => [$name->id => 'Late Larry', $email->id => 'late@example.com'],
        ])->assertStatus(422)->assertJsonValidationErrors(['capacity']);

        $this->assertSame(1, $this->regCount($event));
    }

    public function test_inactive_registrations_do_not_count_toward_capacity(): void
    {
        Notification::fake();
        [$event, $name, $email] = $this->eventWithForm(['capacity' => 1]);
        $existing = Registration::factory()->inactive()->create(['event_id' => $event->id]);
        $this->assertSame(RegistrationStatus::Inactive, $existing->status);

        $this->postJson("/e/{$event->slug}", [
            'answers' => [$name->id => 'New Nina', $email->id => 'nina@example.com'],
        ])->assertCreated();

        $this->assertSame(2, $this->regCount($event));
    }

    public function test_duplicate_email_is_rejected_ignoring_case_and_whitespace(): void
    {
        Notification::fake();
        [$event, $name, $email] = $this->eventWithForm();

        $this->postJson("/e/{$event->slug}", [
            'answers' => [$name->id => 'First', $email->id => 'A@x.com'],
        ])->assertCreated();

        $this->postJson("/e/{$event->slug}", [
            'answers' => [$name->id => 'Second', $email->id => ' a@x.com '],
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->assertSame(1, $this->regCount($event));
    }

    public function test_ended_event_shows_page_but_rejects_submissions(): void
    {
        [$event, $name, $email] = $this->eventWithForm();
        $event->update(['starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(2)]);

        $this->get("/e/{$event->slug}")->assertOk();

        $this->postJson("/e/{$event->slug}", [
            'answers' => [$name->id => 'Too Late', $email->id => 'late@example.com'],
        ])->assertStatus(422);

        $this->assertSame(0, $this->regCount($event));
    }

    public function test_event_without_email_question_registers_with_null_email_and_sends_no_confirmation(): void
    {
        Notification::fake();
        $event = Event::factory()->create();
        $name = EventQuestionFactory::new()->required()->create(['event_id' => $event->id, 'label' => 'Full name']);

        $this->postJson("/e/{$event->slug}", ['answers' => [$name->id => 'No Mail Nick']])
            ->assertCreated();

        $registration = Registration::withoutGlobalScopes()->where('event_id', $event->id)->firstOrFail();
        $this->assertNull($registration->email);
        Notification::assertSentOnDemandTimes(RegistrationConfirmedNotification::class, 0);
    }

    public function test_submission_endpoint_is_throttled_to_twenty_per_minute(): void
    {
        $event = Event::factory()->create();

        for ($i = 0; $i < 20; $i++) {
            $this->postJson("/e/{$event->slug}", ['answers' => []])->assertStatus(422);
        }

        $this->postJson("/e/{$event->slug}", ['answers' => []])->assertStatus(429);
    }

    public function test_thanks_page_renders_for_own_reference(): void
    {
        $event = Event::factory()->create();
        $registration = Registration::factory()->create(['event_id' => $event->id]);

        $this->get("/e/{$event->slug}/thanks/{$registration->reference}")->assertOk();
    }

    public function test_thanks_page_is_not_found_for_reference_of_another_event(): void
    {
        $event = Event::factory()->create();
        $other = Event::factory()->create();
        $foreign = Registration::factory()->create(['event_id' => $other->id]);

        $this->get("/e/{$event->slug}/thanks/{$foreign->reference}")->assertNotFound();
    }
}
