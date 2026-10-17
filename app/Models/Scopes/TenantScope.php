<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains a tenant-owned model to the current organization. No-op when no
 * tenant is set (console / queue / public-by-slug), so those flows resolve rows
 * explicitly without the scope silently hiding everything.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $id = TenantContext::id();
        if ($id !== null) {
            $builder->where($model->getTable().'.organization_id', $id);
        }
    }
}
