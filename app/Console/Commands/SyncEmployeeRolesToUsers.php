<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class SyncEmployeeRolesToUsers extends Command
{
    protected $signature = 'users:sync-roles';
    protected $description = 'Sync roles from employees to their associated users';

    public function handle()
    {
        $this->info('Starting role synchronization...');

        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Get all employees with their users
        $employeesWithUsers = Employee::with('user', 'role')->whereHas('user')->get();
        
        $this->info("Found {$employeesWithUsers->count()} employees with user accounts.");
        
        foreach ($employeesWithUsers as $employee) {
            if ($employee->role_id) {
                $role = Role::find($employee->role_id);
                if ($role) {
                    // Directly assign the role to the user
                    $user = $employee->user;
                    
                    // First detach any existing roles
                    DB::table('model_has_roles')
                        ->where('model_id', $user->id)
                        ->where('model_type', 'App\\Models\\User')
                        ->delete();
                    
                    // Then attach the new role
                    DB::table('model_has_roles')->insert([
                        'role_id' => $role->id,
                        'model_id' => $user->id,
                        'model_type' => 'App\\Models\\User',
                    ]);
                    
                    $this->info("Synced role '{$role->name}' to user {$user->email}");
                }
            }
        }

        $this->info('Role synchronization completed!');
        
        return Command::SUCCESS;
    }
} 