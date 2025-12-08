<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view reports',
            'create reports',
            'export reports',
            'view all reports', // Admin only
            'manage users',     // Admin only
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->givePermissionTo(['view reports', 'create reports', 'export reports']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create default users
        $admin = User::firstOrCreate(
            ['email' => 'admin@lms.test'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('admin');

        $user = User::firstOrCreate(
            ['email' => 'user@lms.test'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );
        $user->assignRole('user');

        $this->command->info('✓ Roles and permissions created');
        $this->command->info('✓ Admin: admin@lms.test / password');
        $this->command->info('✓ User: user@lms.test / password');
    }
}
