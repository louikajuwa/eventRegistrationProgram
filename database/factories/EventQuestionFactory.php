<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Event;
use App\Models\EventQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * EventQuestion has no HasFactory trait, so use EventQuestionFactory::new() directly.
 *
 * @extends Factory<EventQuestion>
 */
class EventQuestionFactory extends Factory
{
    protected $model = EventQuestion::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'organization_id' => fn (array $attrs) => Event::withoutGlobalScopes()->find($attrs['event_id'])->organization_id,
            'label' => fake()->unique()->words(2, true),
            'type' => QuestionType::Text->value,
            'options' => null,
            'is_required' => false,
            'sort_order' => 0,
        ];
    }

    public function required(): static
    {
        return $this->state(fn () => ['is_required' => true]);
    }

    public function email(): static
    {
        return $this->state(fn () => ['type' => QuestionType::Email->value, 'label' => 'Email']);
    }

    /** @param string[] $options */
    public function select(array $options = ['A', 'B', 'C']): static
    {
        return $this->state(fn () => ['type' => QuestionType::Select->value, 'options' => $options]);
    }
}
