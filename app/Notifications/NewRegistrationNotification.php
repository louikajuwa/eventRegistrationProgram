<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Admin bell notification (database channel) for a new registration. The
 * NotificationSent listener in AppServiceProvider turns this into a live socket push
 * to the recipient's room, so an OrgAdmin sees it appear in real time.
 */
class NewRegistrationNotification extends Notification implements ShouldQueue
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
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'registration',
            'title' => 'New registration',
            'message' => $this->registration->name.' registered for '.$this->event->title,
            'url' => url('/events/'.$this->event->id.'/dashboard'),
        ];
    }
}
