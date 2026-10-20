<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use App\Enums\QuestionType;
use App\Models\Event;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Tenant check before validation so another org's event never leaks validation detail.
        $event = $this->route('event');
        $user = $this->user();
        abort_unless(
            $user && (! $event instanceof Event || $user->isSuperAdmin() || (int) $event->organization_id === (int) $user->organization_id),
            404,
        );

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'questions' => ['present', 'array'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.label' => ['required', 'string', 'max:255'],
            'questions.*.type' => ['required', Rule::in(QuestionType::values())],
            'questions.*.is_required' => ['nullable', 'boolean'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Choice questions need at least one non-empty option; a published event needs an email question. */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $questions = $this->input('questions');
            if (! is_array($questions) || $validator->errors()->isNotEmpty()) {
                return;
            }

            $hasEmail = false;
            foreach (array_values($questions) as $i => $q) {
                $type = QuestionType::tryFrom((string) ($q['type'] ?? ''));
                if ($type === QuestionType::Email) {
                    $hasEmail = true;
                }
                if ($type?->hasOptions()) {
                    $options = array_filter(array_map(fn ($o) => trim((string) $o), (array) ($q['options'] ?? [])), fn ($o) => $o !== '');
                    if ($options === []) {
                        $validator->errors()->add("questions.$i.options", 'Add at least one option to this question.');
                    }
                }
            }

            $event = $this->route('event');
            if ($event instanceof Event && $event->status === EventStatus::Published && ! $hasEmail) {
                $validator->errors()->add('questions', 'A published event needs at least one Email question so registrants can be confirmed.');
            }
        }];
    }
}
