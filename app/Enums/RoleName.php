<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin'; // platform operator (no organization)
    case OrgAdmin = 'org_admin';     // an organization's administrator

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::OrgAdmin => 'Organization Admin',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
