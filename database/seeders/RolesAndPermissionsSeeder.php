<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [

            // Academic Year management
            'view academic years',
            'create academic years',
            'edit academic years',
            'delete academic years',

            // Student management
            'view students',
            'create students',
            'edit students',
            'delete students',

            // Program management
            'view programs',
            'create programs',
            'edit programs',
            'delete programs',

            // Course management
            'view courses',
            'create courses',
            'edit courses',
            'delete courses',

            // Program Category management
            'view program categories',
            'create program categories',
            'edit program categories',
            'delete program categories',

            // Department management
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',

            // Student Enrollment management
            'view student enrollments',
            'create student enrollments',
            'edit student enrollments',
            'delete student enrollments',

            // Enrolled Courses management
            'view enrolled courses',
            'create enrolled courses',
            'edit enrolled courses',
            'delete enrolled courses',

            // Applicant management
            'view applicants',
            'create applicants',
            'edit applicants',
            'delete applicants',

            // Admission Documents management
            'view admission documents',
            'create admission documents',
            'edit admission documents',
            'delete admission documents',

            // Exam Schedule management
            'view exam schedules',
            'create exam schedules',
            'edit exam schedules',
            'delete exam schedules',

            // Interview Schedule management
            'view interview schedules',
            'create interview schedules',
            'edit interview schedules',
            'delete interview schedules',

            // Exam Question management
            'view exam questions',
            'create exam questions',
            'edit exam questions',
            'delete exam questions',

            // Barangay management
            'view barangays',
            'create barangays',
            'edit barangays',
            'delete barangays',

            // City management
            'view cities',
            'create cities',
            'edit cities',
            'delete cities',

            // Province management
            'view provinces',
            'create provinces',
            'edit provinces',
            'delete provinces',

            // Campus management
            'view campuses',
            'create campuses',
            'edit campuses',
            'delete campuses',

            // Room management
            'view rooms',
            'create rooms',
            'edit rooms',
            'delete rooms',

            // Office management
            'view offices',
            'create offices',
            'edit offices',
            'delete offices',

            // Employee management
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',

            // Role management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create super-admin role if it doesn't exist
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super admin',
            'guard_name' => 'web'
        ]);


        // Super admin gets all permissions
        $superAdminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        // Create a super admin user for testing if needed
        $user = User::where('email', 'admin@example.com')->first();
        if (!$user) {
            $user = User::create([
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'user_type' => 'admin',
            ]);
        }
        $user->assignRole('super admin');
    }
}
