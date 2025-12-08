<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view reports',
            'export reports',
            'manage presets',
            'view all reports',      // Admin only - can see any user's reports
            'manage scheduled reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Student role: can only view their own reports
        $student = Role::create(['name' => 'student']);
        $student->givePermissionTo('view reports');

        // Faculty/Instructor role: can view, export, manage presets and schedules
        $faculty = Role::create(['name' => 'instructor']);
        $faculty->givePermissionTo([
            'view reports',
            'export reports',
            'manage presets',
            'manage scheduled reports',
        ]);

        // Admin role: has all permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());


        // Create demo users
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@ppklms.test',
        ]);
        $adminUser->assignRole('admin');

        $facultyUser = User::factory()->create([
            'name' => 'Instructor User',
            'email' => 'instructor@ppklms.test',
        ]);
        $facultyUser->assignRole('instructor');

        $studentUser = User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@ppklms.test',
        ]);
        $studentUser->assignRole('student');

        $this->command->info('✓ Roles and permissions seeded successfully!');
        $this->command->info('✓ Demo users created:');
        $this->command->info('  - admin@ppklms.test (password)');
        $this->command->info('  - instructor@ppklms.test (password)');
        $this->command->info('  - student@ppklms.test (password)');

    }
}
