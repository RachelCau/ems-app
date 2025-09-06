<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class FixRoleGuards extends Command
{
    protected $signature = 'roles:fix-guards';
    protected $description = 'Fix guard names for roles and permissions';

    public function handle()
    {
        $this->info('Starting to fix guard names...');

        // Clear the cache first
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Fix permissions
        $this->info('Fixing permissions...');
        Permission::query()->update(['guard_name' => 'web']);

        // Fix roles
        $this->info('Fixing roles...');
        Role::query()->update(['guard_name' => 'web']);

        // Fix model_has_roles for User model
        $this->info('Fixing model_has_roles...');
        DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\Employee')
            ->update(['model_type' => 'App\\Models\\User']);

        // Fix model_has_permissions
        $this->info('Fixing model_has_permissions...');
        DB::table('model_has_permissions')
            ->where('model_type', 'App\\Models\\Employee')
            ->update(['model_type' => 'App\\Models\\User']);
            
        // Sync employee roles with user roles
        $this->info('Syncing employee roles with users...');
        $employeesWithUsers = Employee::with('user')->whereHas('user')->get();
        
        foreach ($employeesWithUsers as $employee) {
            if ($employee->role_id) {
                $role = Role::find($employee->role_id);
                if ($role) {
                    // Ensure user has correct role
                    $employee->user->syncRoles([$role->name]);
                    $this->info("Synced role {$role->name} to user {$employee->user->email}");
                    
                    // Sync employee permissions based on role
                    $employee->syncPermissions($role->permissions);
                }
            }
        }

        $this->info('All guard names have been fixed!');
        
        return Command::SUCCESS;
    }
} 