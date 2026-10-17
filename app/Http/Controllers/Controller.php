<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Controller
{
    use ApiResponse;

    /** Per-page size for searchable-select option endpoints (default 10, hard cap 100). */
    protected function optionLimit(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 10), 1), 100);
    }

    /**
     * Guard a route-bound, tenant-owned model against cross-tenant access. Belt-and-
     * braces on top of the TenantScope global scope (independent of middleware order).
     */
    protected function authorizeTenant(Model $model): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->isSuperAdmin() || (int) $model->organization_id === (int) $user->organization_id),
            404,
        );
    }
}
