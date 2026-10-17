<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Registration;
use App\Services\MailConfigService;
use App\Services\SettingService;
use App\Support\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation email sent to the registrant. The subject/body come from the event's
 * customizable template (falling back to a built-in default), with {placeholders}
 * substituted safely. Queued — and because SMTP is per-tenant, it re-applies the
 * event's organization mail config just before sending (the boot-time apply can't
 * know the tenant on a queue worker).
 */
class RegistrationConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Registration $registration,
        public Event $event,
    ) {
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $orgId = $this->event->organization_id;
        app(MailConfigService::class)->applyForOrganization($orgId);
        $settings = app(SettingService::class);

        $vars = EmailTemplateRenderer::varsFor($this->registration, $this->event);

        // Template resolution: per-event → organization default → built-in.
        $subjectTpl = $this->event->email_subject
            ?: ($settings->get($orgId, 'default_email_subject') ?: 'You are registered for {event_title}');
        $bodyTpl = $this->event->email_body
            ?: ($settings->get($orgId, 'default_email_body') ?: $this->defaultBody());

        $subject = EmailTemplateRenderer::render($subjectTpl, $vars);
        $body = EmailTemplateRenderer::render($bodyTpl, $vars);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.registration-confirmed', [
                'subject' => $subject,
                'body' => $body,
                'vars' => $vars,
                'event' => $this->event,
            ]);
    }

    private function defaultBody(): string
    {
        return "Hi {name},\n\nThanks for registering for {event_title}.\n\nYour reference is {reference}. Please have it ready at check-in.\n\nSee you there!";
    }
}
