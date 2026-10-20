<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Company self-registration: creates the organization + its first admin user in one
 * transaction, and assigns the OrgAdmin role.
 */
class OrganizationRegistrationService
{
    /**
     * @param  array{company:string,name:string,email:string,password:string}  $data
     */
    public function register(array $data): User
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->createOrganizationAndAdmin($data);
            } catch (UniqueConstraintViolationException $e) {
                // Slug collision from a concurrent signup: regenerate and retry.
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }
    }

    private function createOrganizationAndAdmin(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $org = Organization::create([
                'name' => $data['company'],
                'slug' => $this->uniqueSlug($data['company']),
                'email' => $data['email'],
                'is_active' => true,
            ]);

            $user = User::create([
                'organization_id' => $org->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->assignRole(RoleName::OrgAdmin->value);

            return $user;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'org';
        $slug = $base;
        $i = 1;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
