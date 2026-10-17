<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SubmitRegistrationRequest;
use App\Models\Event;
use App\Models\Registration;
use App\Services\RegistrationService;

class PublicRegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $service) {}

    public function store(SubmitRegistrationRequest $request, Event $event): mixed
    {
        $registration = $this->service->submit($event, $request->validated()['answers']);

        $redirect = route('public.events.thanks', ['event' => $event->slug, 'reference' => $registration->reference]);

        if ($request->expectsJson() || $request->ajax()) {
            return $this->created(['redirect' => $redirect], 'Registration complete.');
        }

        return redirect($redirect);
    }

    public function thanks(Event $event, string $reference): mixed
    {
        $registration = Registration::where('event_id', $event->id)
            ->where('reference', $reference)
            ->firstOrFail();

        return view('public.events.thanks', [
            'event' => $event->load('organization:id,name'),
            'registration' => $registration,
        ]);
    }
}
