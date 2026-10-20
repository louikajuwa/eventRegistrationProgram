<?php

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;

class PublicEventController extends Controller
{
    public function show(Event $event): mixed
    {
        // Drafts are hidden; closed / ended / full events still render, with registration shut.
        abort_if($event->status === EventStatus::Draft, 404);

        return view('public.events.show', [
            'registrationOpen' => $event->acceptsRegistrations() && ! $event->isFull(),
            'event' => $event->load('organization:id,name'),
            'questions' => $event->questions,
        ]);
    }
}
