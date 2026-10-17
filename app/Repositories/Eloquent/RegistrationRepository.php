<?php

namespace App\Repositories\Eloquent;

use App\Models\Registration;
use App\Repositories\Contracts\RegistrationRepositoryInterface;

class RegistrationRepository extends BaseRepository implements RegistrationRepositoryInterface
{
    protected array $searchable = ['name', 'email', 'reference'];

    protected array $sortable = ['id', 'name', 'email', 'attended', 'status', 'created_at'];

    protected array $filterable = ['event_id', 'attended', 'status'];

    protected string $defaultSort = 'id';

    public function model(): string
    {
        return Registration::class;
    }
}
