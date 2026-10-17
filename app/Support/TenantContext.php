<?php

namespace App\Support;

/**
 * Request-lifetime holder for the "current tenant" (organization_id).
 *
 * Set once per request by the SetTenant middleware from the authenticated user.
 * The TenantScope reads it to constrain every tenant-owned query; when it is null
 * (console, queue worker, or public-by-slug flows) the scope is a no-op and rows
 * must be resolved explicitly.
 */
class TenantContext
{
    private static ?int $id = null;

    public static function set(?int $organizationId): void
    {
        self::$id = $organizationId;
    }

    public static function id(): ?int
    {
        return self::$id;
    }

    public static function has(): bool
    {
        return self::$id !== null;
    }

    public static function forget(): void
    {
        self::$id = null;
    }

    /**
     * Run a callback with tenant scoping temporarily disabled, then restore it.
     * Used by public flows that resolve a tenant's rows explicitly (e.g. by slug).
     *
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public static function runWithout(callable $callback): mixed
    {
        $previous = self::$id;
        self::$id = null;
        try {
            return $callback();
        } finally {
            self::$id = $previous;
        }
    }
}
