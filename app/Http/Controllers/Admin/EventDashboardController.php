<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;

class EventDashboardController extends Controller
{
    public function show(Event $event): mixed
    {
        $this->authorizeTenant($event);

        $registered = $event->activeRegistrationsCount();
        $attended = $event->registrations()->where('attended', true)->count();

        $recent = $event->registrations()->latest('id')->take(10)->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'email' => $r->email,
                'reference' => $r->reference,
                'at' => $r->created_at?->diffForHumans(),
            ]);

        return view('events.dashboard', [
            'event' => $event,
            'registered' => $registered,
            'attended' => $attended,
            'recent' => $recent,
        ]);
    }
}
