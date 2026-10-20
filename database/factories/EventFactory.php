<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'organization_id' => Organization::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => fake()->paragraph(),
            'location' => fake()->city(),
            'starts_at' => now()->addDays(10),
            'ends_at' => now()->addDays(11),
            'capacity' => null,
            'status' => EventStatus::Published->value,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Draft->value]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Closed->value]);
    }

    /** Published, but the end time has already passed. */
    public function ended(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDays(2),
        ]);
    }
}
