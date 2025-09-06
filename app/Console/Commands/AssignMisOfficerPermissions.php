<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignMisOfficerPermissions extends Command
{
    protected $signature = 'roles:assign-mis-permissions';
    protected $description = 'Assign all widget permissions to MIS Officer role';

    public function handle()
    {
        $this->info('Starting to assign all widget permissions to MIS Officer role...');

        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Find MIS Officer role
        $misOfficerRole = Role::where('name', 'MIS Officer')->first();
        
        if (!$misOfficerRole) {
            $this->error('MIS Officer role not found. Creating it...');
            $misOfficerRole = Role::create([
                'name' => 'MIS Officer',
                'guard_name' => 'web'
            ]);
            $this->info('MIS Officer role created.');
        }
        
        // List of permissions to assign to MIS Officer (all relevant permissions)
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
            
            // Admission management
            'view admissions',
            'edit admissions',
            
            // Dashboard specific permissions
            'view dashboard',
            'view admissions stats',
            'view program charts',
        ];
        
        // Create permissions if they don't exist
        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }
        
        // Get the permission objects
        $permissionObjects = Permission::whereIn('name', $permissions)->get();
        
        // Assign permissions to the MIS Officer role
        $misOfficerRole->syncPermissions($permissionObjects);
        
        $this->info('Permissions assigned to MIS Officer role:');
        foreach ($permissions as $permission) {
            $this->info('- ' . $permission);
        }
        
        $this->info('Done!');
        
        return Command::SUCCESS;
    }
} 