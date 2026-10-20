<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Registration> */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'organization_id' => fn (array $attrs) => Event::withoutGlobalScopes()->find($attrs['event_id'])->organization_id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'status' => RegistrationStatus::Active->value,
            'attended' => false,
            'checked_in_at' => null,
            'reference' => 'REG-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Inactive->value]);
    }
}
