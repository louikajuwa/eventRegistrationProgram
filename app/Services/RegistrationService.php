<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\NewRegistrationNotification;
use App\Notifications\RegistrationConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Public submit pipeline. Runs with NO tenant bound (public routes skip SetTenant),
 * so organization_id is copied explicitly from the event onto every row.
 */
class RegistrationService
{
    public function __construct(private readonly RealtimeService $realtime) {}

    /**
     * @param  array<int,mixed>  $answers  keyed by event_question_id => value (string|array)
     */
    public function submit(Event $event, array $answers): Registration
    {
        if ($event->isFull()) {
            throw ValidationException::withMessages([
                'capacity' => 'This event has reached capacity. Registration is closed.',
            ]);
        }

        $questions = $event->questions;
        [$name, $email, $phone] = $this->deriveContact($questions, $answers);

        $registration = DB::transaction(function () use ($event, $questions, $answers, $name, $email, $phone): Registration {
            $registration = Registration::create([
                'organization_id' => $event->organization_id,
                'event_id' => $event->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'reference' => $this->uniqueReference(),
            ]);

            foreach ($questions as $question) {
                $raw = $answers[$question->id] ?? null;
                $value = is_array($raw) ? implode(', ', array_filter($raw, fn ($v) => $v !== null && $v !== '')) : $raw;

                $registration->answers()->create([
                    'organization_id' => $event->organization_id,
                    'event_question_id' => $question->id,
                    'question_label' => $question->label,
                    'value' => $value !== '' ? $value : null,
                ]);
            }

            return $registration;
        });

        $this->afterCreated($event, $registration);

        return $registration;
    }

    /** Confirmation email to the registrant + admin bell + live dashboard push. */
    private function afterCreated(Event $event, Registration $registration): void
    {
        // Registrant confirmation (routed on-demand — registrants are not User models).
        Notification::route('mail', $registration->email)
            ->notify(new RegistrationConfirmedNotification($registration, $event));

        // Admin bell for the organization's admins.
        $admins = User::where('organization_id', $event->organization_id)
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::OrgAdmin->value))
            ->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewRegistrationNotification($registration, $event));
        }

        // Live registration count for the event dashboard.
        $this->realtime->publish(
            ['org.'.$event->organization_id],
            'registration.created',
            [
                'event_id' => $event->id,
                'registered' => $event->registrations()->count(),
                'name' => $registration->name,
                'reference' => $registration->reference,
            ],
        );
    }

    /**
     * Promote name / email / phone from the mapped answers so they drive the
     * confirmation email, list columns and search.
     *
     * @param  \Illuminate\Support\Collection<int,\App\Models\EventQuestion>  $questions
     * @return array{0:string,1:string,2:?string}
     */
    private function deriveContact($questions, array $answers): array
    {
        $name = null;
        $email = null;
        $phone = null;

        foreach ($questions as $q) {
            $val = $answers[$q->id] ?? null;
            $val = is_array($val) ? implode(', ', $val) : (is_string($val) ? trim($val) : $val);
            if ($val === null || $val === '') {
                continue;
            }

            if ($email === null && $q->type === QuestionType::Email) {
                $email = $val;
            } elseif ($phone === null && $q->type === QuestionType::Phone) {
                $phone = $val;
            } elseif ($name === null && $q->type === QuestionType::Text && str_contains(strtolower($q->label), 'name')) {
                $name = $val;
            }
        }

        // Fallbacks: first text answer for name; first email-looking answer for email.
        if ($name === null) {
            foreach ($questions as $q) {
                $val = $answers[$q->id] ?? null;
                if (is_string($val) && trim($val) !== '' && $q->type === QuestionType::Text) {
                    $name = trim($val);
                    break;
                }
            }
        }

        return [$name ?: 'Guest', $email ?: '', $phone];
    }

    private function uniqueReference(): string
    {
        do {
            $ref = 'REG-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
        } while (Registration::withoutGlobalScopes()->where('reference', $ref)->exists());

        return $ref;
    }
}
