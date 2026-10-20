<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Events are tenant-owned; a platform admin (no organization) has nowhere to put one.
        if ($this->user()?->organization_id === null) {
            throw new AuthorizationException('Platform administrators cannot create events; sign in as an organization admin.');
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'status' => ['required', Rule::in(EventStatus::values())],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
