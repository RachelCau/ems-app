<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignRegistrarPermissions extends Command
{
    protected $signature = 'roles:assign-registrar-permissions';
    protected $description = 'Assign Student Enrollment permissions to Registrar role';

    public function handle()
    {
        $this->info('Starting to assign Student Enrollment permissions to Registrar role...');

        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Find Registrar role
        $registrarRole = Role::where('name', 'Registrar')->first();
        
        if (!$registrarRole) {
            $this->error('Registrar role not found. Creating it...');
            $registrarRole = Role::create([
                'name' => 'Registrar',
                'guard_name' => 'web'
            ]);
            $this->info('Registrar role created.');
        }
        
        // List of permissions to assign to Registrar
        $permissions = [
            // Student Enrollment management
            'view student enrollments',
            'create student enrollments',
            'edit student enrollments',
            'delete student enrollments',
            
            // Student management
            'view students',
            'create students',
            'edit students',
            
            // Applicant management
            'view applicants',
            'edit applicants',
            
            // Enrolled Courses management
            'view enrolled courses',
            'create enrolled courses',
            'edit enrolled courses',
            'delete enrolled courses',
        ];
        
        // Create permissions if they don't exist
        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }
        
        // Get the permission objects
        $permissionObjects = Permission::whereIn('name', $permissions)->get();
        
        // Assign permissions to the Registrar role
        $registrarRole->syncPermissions($permissionObjects);
        
        $this->info('Permissions assigned to Registrar role:');
        foreach ($permissions as $permission) {
            $this->info('- ' . $permission);
        }
        
        $this->info('Done!');
        
        return Command::SUCCESS;
    }
} 