<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // First add role_id if it doesn't exist
            if (!Schema::hasColumn('employees', 'role_id')) {
                Schema::table('employees', function (Blueprint $table) {
                    $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
                });
            }
            
            // Only process if designation_id still exists
            if (Schema::hasColumn('employees', 'designation_id')) {
                // Update existing employees to assign a role based on their designation
                $employees = DB::table('employees')->get();
                
                foreach ($employees as $employee) {
                    // Get the designation's roles through the pivot table
                    if (Schema::hasTable('designation_role')) {
                        $designationRoles = DB::table('designation_role')
                            ->where('designation_id', $employee->designation_id)
                            ->pluck('role_id');
                            
                        // If the designation has roles, assign the first one to the employee
                        if ($designationRoles->count() > 0) {
                            DB::table('employees')
                                ->where('id', $employee->id)
                                ->update(['role_id' => $designationRoles->first()]);
                        }
                    }
                }
                
                // Now drop the designation_id column with its foreign key
                Schema::table('employees', function (Blueprint $table) {
                    // Check if foreign key exists before trying to drop it
                    $sm = Schema::getConnection()->getDoctrineSchemaManager();
                    $foreignKeys = array_map(function($key) { return $key->getName(); }, $sm->listTableForeignKeys('employees'));
                    
                    if (in_array('employees_designation_id_foreign', $foreignKeys)) {
                        $table->dropForeign(['designation_id']);
                    }
                    
                    $table->dropColumn('designation_id');
                });
            }
            
            // We need to disconnect all relationships to designation before dropping
            // See if any other tables reference designations
            $tables = DB::select("SELECT TABLE_NAME, COLUMN_NAME
                                  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                  WHERE REFERENCED_TABLE_NAME = 'designations'
                                        AND TABLE_NAME != 'employees'");
                                        
            foreach ($tables as $tableInfo) {
                Schema::table($tableInfo->TABLE_NAME, function (Blueprint $table) use ($tableInfo) {
                    $table->dropForeign([$tableInfo->COLUMN_NAME]);
                });
            }
            
            // Drop designation_role pivot table if it exists
            Schema::dropIfExists('designation_role');
            
            // Drop designations table if it exists
            Schema::dropIfExists('designations');
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Migration error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate designations table if it doesn't exist
        if (!Schema::hasTable('designations')) {
            Schema::create('designations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
            
            // Add default designation
            DB::table('designations')->insert([
                'name' => 'Default Designation',
                'description' => 'Default designation created during migration rollback',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Recreate designation_role table if it doesn't exist
        if (!Schema::hasTable('designation_role')) {
            Schema::create('designation_role', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('designation_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();

                $table->foreign('designation_id')
                    ->references('id')
                    ->on('designations')
                    ->onDelete('cascade');

                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->onDelete('cascade');
            });
        }
        
        // Add back designation_id column if it doesn't exist
        if (!Schema::hasColumn('employees', 'designation_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('designation_id')->default(1)->constrained('designations');
            });
        }
    }
};
