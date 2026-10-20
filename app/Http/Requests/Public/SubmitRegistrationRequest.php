<?php

namespace App\Http\Requests\Public;

use App\Enums\EventStatus;
use App\Enums\QuestionType;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Builds validation rules dynamically from the bound event's questions, so the
 * server enforces exactly the questionnaire the organizer defined.
 */
class SubmitRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Drafts are 404; closed / ended events are a 422 (the service re-checks under lock, incl. capacity). */
    protected function prepareForValidation(): void
    {
        $event = $this->event();
        abort_if($event === null || $event->status === EventStatus::Draft, 404);

        if (! $event->acceptsRegistrations()) {
            throw ValidationException::withMessages(['event' => 'Registration is closed for this event.']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['answers' => ['required', 'array']];

        foreach ($this->event()->questions as $q) {
            $key = 'answers.'.$q->id;
            $required = $q->is_required;
            $set = [];

            if ($q->type === QuestionType::Checkbox) {
                $set[] = $required ? 'required' : 'nullable';
                $set[] = 'array';
                $rules[$key.'.*'] = [Rule::in($q->options ?? [])];
            } else {
                $set[] = $required ? 'required' : 'nullable';
                $set[] = match ($q->type) {
                    QuestionType::Email => 'email',
                    QuestionType::Number => 'numeric',
                    QuestionType::Date => 'date',
                    default => 'string',
                };
                if (in_array($q->type, [QuestionType::Select, QuestionType::Radio], true)) {
                    $set[] = Rule::in($q->options ?? []);
                }
                if (in_array($q->type, [QuestionType::Text, QuestionType::Textarea, QuestionType::Phone], true)) {
                    $set[] = 'max:5000';
                }
            }

            $rules[$key] = $set;
        }

        return $rules;
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        $attrs = [];
        foreach ($this->event()->questions as $q) {
            $attrs['answers.'.$q->id] = strtolower($q->label);
            $attrs['answers.'.$q->id.'.*'] = strtolower($q->label);
        }

        return $attrs;
    }

    public function event(): ?Event
    {
        $event = $this->route('event');

        return $event instanceof Event ? $event : null;
    }
}
