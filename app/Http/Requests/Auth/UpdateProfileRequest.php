<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Changing the sign-in email is a sensitive change: re-confirm the password.
        $emailChanging = strtolower((string) $this->input('email')) !== strtolower((string) $this->user()->email);

        return [
            'current_password' => [$emailChanging ? 'required' : 'nullable', 'string', 'current_password'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
        ];
    }
}
