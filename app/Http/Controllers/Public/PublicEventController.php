<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;

class PublicEventController extends Controller
{
    public function show(Event $event): mixed
    {
        abort_unless($event->status->acceptsRegistrations(), 404);

        return view('public.events.show', [
            'event' => $event->load('organization:id,name'),
            'questions' => $event->questions,
        ]);
    }
}
