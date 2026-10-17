<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Event;
use App\Models\EventQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Event create/update + the form-builder question sync. organization_id is stamped
 * automatically by the BelongsToTenant trait from the current tenant.
 */
class EventService
{
    public function create(array $data): Event
    {
        $data['slug'] = $this->uniqueSlug($data['title']);

        return Event::create($data)->refresh();
    }

    public function update(Event $event, array $data): Event
    {
        // Title changes keep the original slug (the public link stays stable).
        unset($data['slug']);
        $event->fill($data)->save();

        return $event->refresh();
    }

    /**
     * Replace the event's questions with the given ordered set: update existing rows,
     * create new ones, delete removed ones — all in one transaction.
     *
     * @param  array<int,array{id?:int|null,label:string,type:string,options?:array|null,is_required?:bool}>  $questions
     */
    public function syncQuestions(Event $event, array $questions): void
    {
        DB::transaction(function () use ($event, $questions): void {
            $keptIds = [];

            foreach (array_values($questions) as $order => $q) {
                $type = QuestionType::from($q['type']);
                $options = $type->hasOptions()
                    ? array_values(array_filter(array_map('trim', $q['options'] ?? []), fn ($o) => $o !== ''))
                    : null;

                $attributes = [
                    'label' => $q['label'],
                    'type' => $type->value,
                    'options' => $options,
                    'is_required' => (bool) ($q['is_required'] ?? false),
                    'sort_order' => $order,
                ];

                if (! empty($q['id']) && ($existing = $event->questions()->whereKey($q['id'])->first())) {
                    $existing->update($attributes);
                    $keptIds[] = $existing->id;
                } else {
                    $created = $event->questions()->create(array_merge($attributes, [
                        'organization_id' => $event->organization_id,
                    ]));
                    $keptIds[] = $created->id;
                }
            }

            $event->questions()->whereNotIn('id', $keptIds ?: [0])->delete();
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 1;
        while (Event::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
