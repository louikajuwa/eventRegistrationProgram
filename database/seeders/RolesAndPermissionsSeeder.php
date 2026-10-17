<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Modules and their actions. Permission name = "module.action".
     *
     * @var array<string, string[]>
     */
    private array $modules = [
        'events' => ['view', 'create', 'update', 'delete'],
        'registrations' => ['view', 'update', 'delete'], // update = attendance + status; delete = remove a registration
        'reports' => ['view'],
        'settings' => ['view', 'update'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];
        foreach ($this->modules as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";
                Permission::findOrCreate($name, 'web');
                $all[] = $name;
            }
        }

        // Super Admin — everything (also covered by Gate::before).
        Role::findOrCreate(RoleName::SuperAdmin->value, 'web')->syncPermissions($all);

        // Organization Admin — full control of their own organization's data.
        Role::findOrCreate(RoleName::OrgAdmin->value, 'web')->syncPermissions($all);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
