<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\QuestionType;
use App\Enums\RoleName;
use App\Models\Event;
use App\Models\EventQuestion;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewRegistrationNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class]);

        // Platform operator (no organization).
        User::updateOrCreate(
            ['email' => 'superadmin@events.test'],
            ['name' => 'Super Admin', 'password' => 'password', 'is_active' => true, 'organization_id' => null],
        )->syncRoles([RoleName::SuperAdmin->value]);

        // ── Org 1: Acme Events (keeps the known login + the Nairobi event) ─────────────
        $acme = $this->org('Acme Events', 'acme-events', 'hello@acme.test', 'admin@events.test', 'Acme Admin');

        $nairobi = $this->event($acme, [
            'title' => 'Nairobi Tech Summit 2026',
            'slug' => 'nairobi-tech-summit-2026',
            'description' => 'A one-day gathering for builders across East Africa.',
            'location' => 'KICC, Nairobi',
            'starts_at' => now()->addMonths(2)->setTime(9, 0),
            'ends_at' => now()->addMonths(2)->setTime(17, 0),
            'capacity' => 500,
            'status' => EventStatus::Published,
            'email_subject' => "You're registered for {event_title} 🎉",
            'email_body' => "Hi {name},\n\nThanks for registering for {event_title}!\n\n📅 {event_date}\n📍 {event_location}\n\nYour reference is {reference} — please have it ready at check-in.\n\nSee you there,\nThe Acme Events team",
        ], $this->conferenceQuestions(), 38);

        $this->event($acme, [
            'title' => 'Product Makers Meetup',
            'slug' => 'product-makers-meetup',
            'description' => 'Monthly meetup for product people.',
            'location' => 'iHub, Nairobi',
            'starts_at' => now()->subWeeks(2)->setTime(18, 0),
            'ends_at' => now()->subWeeks(2)->setTime(20, 30),
            'capacity' => 80,
            'status' => EventStatus::Closed,
            // blank email → uses org default
        ], $this->workshopQuestions(), 61);

        $this->event($acme, [
            'title' => 'DevOps Deep Dive (Draft)',
            'slug' => 'devops-deep-dive',
            'description' => 'Hands-on infrastructure workshop.',
            'location' => 'Online',
            'starts_at' => now()->addMonths(3),
            'capacity' => null,
            'status' => EventStatus::Draft,
        ], $this->workshopQuestions(), 0);

        // ── Org 2: Summit Collective ───────────────────────────────────────────────────
        $summit = $this->org('Summit Collective', 'summit-collective', 'team@summit.test', 'admin@summit-collective.test', 'Summit Admin');

        $this->event($summit, [
            'title' => 'Founders Fireside 2026',
            'slug' => 'founders-fireside-2026',
            'description' => 'An evening with East African founders.',
            'location' => 'Radisson Blu, Nairobi',
            'starts_at' => now()->addMonth()->setTime(17, 30),
            'ends_at' => now()->addMonth()->setTime(21, 0),
            'capacity' => 150,
            'status' => EventStatus::Published,
            'email_subject' => 'See you at {event_title}, {name}!',
            'email_body' => "Hi {name},\n\nYou're confirmed for {event_title}.\n\nWhen: {event_date}\nWhere: {event_location}\n\nBring this reference: {reference}\n\n— Summit Collective",
        ], $this->conferenceQuestions(), 27);

        $this->event($summit, [
            'title' => 'Design Systems Workshop',
            'slug' => 'design-systems-workshop',
            'description' => 'From tokens to components.',
            'location' => 'The Alchemist, Nairobi',
            'starts_at' => now()->subWeeks(1)->setTime(10, 0),
            'ends_at' => now()->subWeeks(1)->setTime(16, 0),
            'capacity' => 40,
            'status' => EventStatus::Closed,
        ], $this->workshopQuestions(), 33);

        // ── Org 3: Grand Events Co ───────────────────────────────────────────────────────
        $grand = $this->org('Grand Events Co', 'grand-events-co', 'hi@grandevents.test', 'admin@grand-events-co.test', 'Grand Admin');

        $this->event($grand, [
            'title' => 'Marketing Summit 2026',
            'slug' => 'marketing-summit-2026',
            'description' => 'Growth, brand and performance marketing.',
            'location' => 'Sarit Centre, Nairobi',
            'starts_at' => now()->addMonths(2)->setTime(8, 30),
            'ends_at' => now()->addMonths(2)->setTime(18, 0),
            'capacity' => 600,
            'status' => EventStatus::Published,
        ], $this->conferenceQuestions(), 44);

        $this->event($grand, [
            'title' => 'Startup Career Fair',
            'slug' => 'startup-career-fair',
            'description' => 'Meet 30+ hiring startups.',
            'location' => 'University of Nairobi',
            'starts_at' => now()->addWeeks(3)->setTime(9, 0),
            'ends_at' => now()->addWeeks(3)->setTime(15, 0),
            'capacity' => 1000,
            'status' => EventStatus::Published,
            'email_subject' => '🎟 Your spot at {event_title}',
            'email_body' => "Hi {name},\n\nYou're registered for {event_title} on {event_date} at {event_location}.\n\nReference: {reference}\n\nSee you there!",
        ], $this->webinarQuestions(), 52);

        // Bell notifications for each org's admin, built from that org's recent registrations
        // (real registrations dispatch NewRegistrationNotification; seeded ones bypass it).
        $this->seedNotifications();
    }

    /** Populate the notification bell for each org admin from recent registrations. */
    private function seedNotifications(): void
    {
        Organization::all()->each(function (Organization $org): void {
            $admin = User::where('organization_id', $org->id)->first();
            if (! $admin) {
                return;
            }

            $recent = Registration::withoutGlobalScopes()
                ->where('organization_id', $org->id)
                ->with('event:id,title')
                ->latest('id')->take(12)->get();

            foreach ($recent as $i => $r) {
                $note = $admin->notifications()->make([
                    'type' => NewRegistrationNotification::class,
                    'data' => [
                        'type' => 'registration',
                        'title' => 'New registration',
                        'message' => $r->name.' registered for '.($r->event?->title ?? 'an event'),
                        'url' => url('/events/'.$r->event_id.'/dashboard'),
                    ],
                    // Leave the 4 newest unread so the bell shows a badge.
                    'read_at' => $i < 4 ? null : $r->created_at,
                ]);
                $note->id = (string) Str::uuid();
                $note->created_at = $r->created_at;
                $note->updated_at = $r->created_at;
                $note->timestamps = false;
                $note->save();
            }
        });
    }

    /** Create an organization + its admin + org-default email settings. */
    private function org(string $name, string $slug, string $email, string $adminEmail, string $adminName): Organization
    {
        $org = Organization::updateOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'email' => $email, 'is_active' => true],
        );

        User::updateOrCreate(
            ['email' => $adminEmail],
            ['name' => $adminName, 'password' => 'password', 'is_active' => true, 'organization_id' => $org->id],
        )->syncRoles([RoleName::OrgAdmin->value]);

        Setting::updateOrCreate(
            ['organization_id' => $org->id, 'key' => 'default_email_subject'],
            ['value' => "You're registered for {event_title}", 'is_encrypted' => false],
        );
        Setting::updateOrCreate(
            ['organization_id' => $org->id, 'key' => 'default_email_body'],
            ['value' => "Hi {name},\n\nThanks for registering for {event_title}.\n\n📅 {event_date}\n📍 {event_location}\n\nYour reference is {reference}.\n\n— {$name}", 'is_encrypted' => false],
        );

        return $org;
    }

    /**
     * Create an event, its questions, and (for non-draft events) registrations with answers.
     *
     * @param  array<int,array{label:string,type:QuestionType,required:bool,options?:array}>  $questionsSpec
     */
    private function event(Organization $org, array $attrs, array $questionsSpec, int $regCount): Event
    {
        $event = Event::updateOrCreate(
            ['slug' => $attrs['slug']],
            array_merge($attrs, ['organization_id' => $org->id]),
        );

        if ($event->questions()->count() === 0) {
            foreach (array_values($questionsSpec) as $i => $q) {
                EventQuestion::create([
                    'organization_id' => $org->id,
                    'event_id' => $event->id,
                    'label' => $q['label'],
                    'type' => $q['type']->value,
                    'options' => $q['options'] ?? null,
                    'is_required' => $q['required'],
                    'sort_order' => $i,
                ]);
            }
        }

        if ($regCount > 0 && $event->registrations()->count() === 0) {
            $questions = $event->questions()->get();
            for ($n = 0; $n < $regCount; $n++) {
                $this->registration($event, $questions);
            }
        }

        return $event;
    }

    /** Create one registration with a full set of answers. */
    private function registration(Event $event, $questions): void
    {
        $name = fake()->name();
        $emailAddr = fake()->unique()->safeEmail();
        $phone = fake()->numerify('+2547########');

        $ts = Carbon::now()->subDays(random_int(0, 35))->subHours(random_int(0, 23))->subMinutes(random_int(0, 59));
        $status = random_int(1, 100) <= 12 ? 'inactive' : 'active';
        $attended = $status === 'active' && random_int(1, 100) <= 45;

        $reg = new Registration;
        $reg->forceFill([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'name' => $name,
            'email' => $emailAddr,
            'phone' => $phone,
            'status' => $status,
            'attended' => $attended,
            'checked_in_at' => $attended ? (clone $ts)->addMinutes(random_int(5, 120)) : null,
            'reference' => 'REG-'.$ts->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3))),
            'created_at' => $ts,
            'updated_at' => $ts,
        ]);
        $reg->timestamps = false;
        $reg->save();

        foreach ($questions as $q) {
            $reg->answers()->create([
                'organization_id' => $event->organization_id,
                'event_question_id' => $q->id,
                'question_label' => $q->label,
                'value' => $this->answerFor($q, $name, $emailAddr, $phone),
            ]);
        }
    }

    private function answerFor(EventQuestion $q, string $name, string $email, string $phone): ?string
    {
        $label = strtolower($q->label);

        return match ($q->type) {
            QuestionType::Email => $email,
            QuestionType::Phone => $phone,
            QuestionType::Number => (string) fake()->numberBetween(1, 40),
            QuestionType::Date => fake()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            QuestionType::Textarea => fake()->sentence(10),
            QuestionType::Select, QuestionType::Radio => fake()->randomElement($q->options ?: ['N/A']),
            QuestionType::Checkbox => implode(', ', fake()->randomElements($q->options ?: ['N/A'], random_int(1, max(1, count($q->options ?: [1]))))),
            default => str_contains($label, 'name') ? $name : fake()->words(2, true),
        };
    }

    /** @return array<int,array{label:string,type:QuestionType,required:bool,options?:array}> */
    private function conferenceQuestions(): array
    {
        return [
            ['label' => 'Full name', 'type' => QuestionType::Text, 'required' => true],
            ['label' => 'Email address', 'type' => QuestionType::Email, 'required' => true],
            ['label' => 'Phone number', 'type' => QuestionType::Phone, 'required' => false],
            ['label' => 'Company', 'type' => QuestionType::Text, 'required' => false],
            ['label' => 'Which track are you attending?', 'type' => QuestionType::Radio, 'required' => true,
                'options' => ['AI & Machine Learning', 'Cloud & DevOps', 'Product & Design']],
            ['label' => 'T-shirt size', 'type' => QuestionType::Select, 'required' => false,
                'options' => ['S', 'M', 'L', 'XL']],
            ['label' => 'Dietary requirements', 'type' => QuestionType::Text, 'required' => false],
        ];
    }

    /** @return array<int,array{label:string,type:QuestionType,required:bool,options?:array}> */
    private function workshopQuestions(): array
    {
        return [
            ['label' => 'Full name', 'type' => QuestionType::Text, 'required' => true],
            ['label' => 'Email address', 'type' => QuestionType::Email, 'required' => true],
            ['label' => 'Experience level', 'type' => QuestionType::Radio, 'required' => true,
                'options' => ['Beginner', 'Intermediate', 'Advanced']],
            ['label' => 'Topics of interest', 'type' => QuestionType::Checkbox, 'required' => false,
                'options' => ['Kubernetes', 'CI/CD', 'Observability', 'Security']],
        ];
    }

    /** @return array<int,array{label:string,type:QuestionType,required:bool,options?:array}> */
    private function webinarQuestions(): array
    {
        return [
            ['label' => 'Full name', 'type' => QuestionType::Text, 'required' => true],
            ['label' => 'Email address', 'type' => QuestionType::Email, 'required' => true],
            ['label' => 'Job title', 'type' => QuestionType::Text, 'required' => false],
            ['label' => 'How did you hear about us?', 'type' => QuestionType::Select, 'required' => false,
                'options' => ['Twitter/X', 'LinkedIn', 'A friend', 'Newsletter', 'Other']],
        ];
    }
}
