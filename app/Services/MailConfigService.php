<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Applies an organization's admin-managed SMTP settings over the .env defaults at
 * runtime, right before a message is sent.
 *
 * Multi-tenant twist vs. the single-tenant reference: mail config can't be applied
 * once at boot, because each organization has its own SMTP creds and a queue worker
 * doesn't know the tenant at boot. So the queued confirmation notification calls
 * applyForOrganization() with the event's organization_id just before sending.
 */
class MailConfigService
{
    public function applyForOrganization(int $organizationId): void
    {
        if (! $this->settingsTableReady()) {
            return; // migrations not run yet / during install
        }

        $map = [
            'mail_host' => 'mail.mailers.smtp.host',
            'mail_port' => 'mail.mailers.smtp.port',
            'mail_username' => 'mail.mailers.smtp.username',
            'mail_encryption' => 'mail.mailers.smtp.scheme',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name' => 'mail.from.name',
        ];

        $settings = Setting::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereIn('key', array_merge(array_keys($map), ['mail_password']))
            ->pluck('value', 'key');

        foreach ($map as $key => $configPath) {
            $val = $settings[$key] ?? null;
            if ($val !== null && $val !== '') {
                config([$configPath => $val]);
            }
        }

        $password = $settings['mail_password'] ?? null;
        if ($password) {
            config(['mail.mailers.smtp.password' => $this->maybeDecrypt($password)]);
        }
    }

    private function maybeDecrypt(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value; // stored in plain (e.g., seeded) — use as-is
        }
    }

    private function settingsTableReady(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
