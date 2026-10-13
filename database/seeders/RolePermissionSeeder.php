<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache permission spatie agar perubahan langsung berlaku.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1) Buat semua izin.
        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        // 2) Buat peran lalu tempelkan izin sesuai peta per peran.
        foreach (UserRole::cases() as $role) {
            $roleModel = Role::findOrCreate($role->value, 'web');
            $roleModel->syncPermissions(Permission::forRole($role));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
