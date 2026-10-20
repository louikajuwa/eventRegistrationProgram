<?php

namespace Tests;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::forget(); // static state must not leak between tests
    }

    protected function tearDown(): void
    {
        TenantContext::forget();
        parent::tearDown();
    }

    protected function seedRoles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function orgAdmin(Organization $org, array $attributes = []): User
    {
        $this->seedRoles();
        $user = User::factory()->create(array_merge([
            'organization_id' => $org->id,
            'is_active' => true,
        ], $attributes));
        $user->assignRole(RoleName::OrgAdmin->value);

        return $user;
    }

    protected function superAdmin(): User
    {
        $this->seedRoles();
        $user = User::factory()->create(['organization_id' => null, 'is_active' => true]);
        $user->assignRole(RoleName::SuperAdmin->value);

        return $user;
    }

    /** Active org user with no role/permissions. */
    protected function plainUser(Organization $org): User
    {
        return User::factory()->create(['organization_id' => $org->id, 'is_active' => true]);
    }
}
