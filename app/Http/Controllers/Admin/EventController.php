<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Services\EventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly EventService $service,
    ) {}

    public function index(Request $request): mixed
    {
        if ($request->expectsJson() || $request->ajax()) {
            $paginator = $this->events->paginate($request->all());
            $paginator->getCollection()->loadCount([
                'registrations' => fn ($q) => $q->active(),
                'registrations as attended_count' => fn ($q) => $q->where('attended', true),
            ]);

            return $this->paginate($paginator);
        }

        return view('events.index');
    }

    public function store(StoreEventRequest $request): mixed
    {
        return $this->created($this->service->create($request->validated()), 'Event created.');
    }

    public function update(UpdateEventRequest $request, Event $event): mixed
    {
        $this->authorizeTenant($event);

        return $this->ok($this->service->update($event, $request->validated()), 'Event updated.');
    }

    public function destroy(Event $event): mixed
    {
        $this->authorizeTenant($event);
        $event->delete();

        return $this->ok(null, 'Event deleted.');
    }

    /** Dedicated per-event confirmation email editor page. */
    public function email(Event $event): mixed
    {
        $this->authorizeTenant($event);

        return view('events.email', ['event' => $event]);
    }

    public function updateEmail(Request $request, Event $event): mixed
    {
        $this->authorizeTenant($event);

        $data = $request->validate([
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string', 'max:10000'],
        ]);

        return $this->ok($this->service->update($event, $data), 'Confirmation email saved.');
    }
}
