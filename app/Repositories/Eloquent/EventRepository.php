<?php

namespace App\Repositories\Eloquent;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;

class EventRepository extends BaseRepository implements EventRepositoryInterface
{
    protected array $searchable = ['title', 'slug', 'location'];

    protected array $sortable = ['id', 'title', 'starts_at', 'status', 'created_at'];

    protected array $filterable = ['status'];

    protected string $defaultSort = 'id';

    public function model(): string
    {
        return Event::class;
    }
}
