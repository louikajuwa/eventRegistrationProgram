<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\SyncQuestionsRequest;
use App\Models\Event;
use App\Services\EventService;

class EventQuestionController extends Controller
{
    public function __construct(private readonly EventService $service) {}

    /** The form-builder page (Blade) + JSON list of questions. */
    public function index(Event $event): mixed
    {
        $this->authorizeTenant($event);

        if (request()->expectsJson() || request()->ajax()) {
            return $this->ok($event->questions);
        }

        return view('events.form-builder', ['event' => $event]);
    }

    public function sync(SyncQuestionsRequest $request, Event $event): mixed
    {
        $this->authorizeTenant($event);
        $this->service->syncQuestions($event, $request->validated()['questions']);

        return $this->ok($event->questions()->get(), 'Form saved.');
    }
}
