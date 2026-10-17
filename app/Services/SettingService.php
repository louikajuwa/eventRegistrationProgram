<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Per-tenant settings store. Every method is scoped to an organization_id so each
 * organization keeps its own SMTP creds and email defaults. Values are cached per
 * organization; encrypted values (e.g. mail password) stay encrypted at rest.
 */
class SettingService
{
    private function cacheKey(int $organizationId): string
    {
        return 'settings.org.'.$organizationId;
    }

    /** @return array<string,mixed> raw stored values (encrypted ones stay encrypted) */
    public function all(int $organizationId): array
    {
        return Cache::rememberForever(
            $this->cacheKey($organizationId),
            fn () => Setting::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->pluck('value', 'key')->all(),
        );
    }

    public function get(int $organizationId, string $key, mixed $default = null): mixed
    {
        return $this->all($organizationId)[$key] ?? $default;
    }

    /** @param array<string,array{value:mixed,encrypt?:bool}> $pairs */
    public function setMany(int $organizationId, array $pairs): void
    {
        foreach ($pairs as $key => $opt) {
            Setting::withoutGlobalScopes()->updateOrCreate(
                ['organization_id' => $organizationId, 'key' => $key],
                [
                    'value' => (! empty($opt['encrypt']) && $opt['value']) ? Crypt::encryptString((string) $opt['value']) : $opt['value'],
                    'is_encrypted' => ! empty($opt['encrypt']),
                ],
            );
        }
        $this->flush($organizationId);
    }

    public function flush(int $organizationId): void
    {
        Cache::forget($this->cacheKey($organizationId));
    }
}
