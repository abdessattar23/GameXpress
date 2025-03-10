<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super_admin_role = Role::create(['name' => 'super_admin']);
        $product_manager_role = Role::create(['name' => 'product_manager']);
        $user_manager_role = Role::create(['name' => 'user_manager']);

        $permissions = [
            'view_dashboard',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $super_admin_role->givePermissionTo(Permission::all());

        $product_manager_role->givePermissionTo([
            'view_dashboard',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_categories',
            'create_categories',
            'edit_categories',
            'delete_categories'
        ]);

        $user_manager_role->givePermissionTo([
            'view_dashboard',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users'
        ]);

        $super_admin = User::find(1);
        $super_admin->assignRole('super_admin');
    }
}
