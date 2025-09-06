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
        // We need to handle this in two approaches
        // First check if the table exists and has curriculum_id
        if (Schema::hasTable('curriculum_course') && Schema::hasColumn('curriculum_course', 'curriculum_id')) {
            // We'll try to rename the column first
            Schema::table('curriculum_course', function (Blueprint $table) {
                // Drop the foreign key constraint if it exists
                $foreignKeys = $this->listTableForeignKeys('curriculum_course');
                foreach ($foreignKeys as $foreignKey) {
                    if (str_contains($foreignKey, 'curriculum_id')) {
                        Schema::table('curriculum_course', function (Blueprint $table) use ($foreignKey) {
                            $table->dropForeign($foreignKey);
                        });
                        break;
                    }
                }
                
                // Now rename the column
                $table->renameColumn('curriculum_id', 'course_curriculum_id');
            });
            
            // Add the foreign key back
            Schema::table('curriculum_course', function (Blueprint $table) {
                $table->foreign('course_curriculum_id')
                    ->references('id')
                    ->on('course_curricula')
                    ->onDelete('cascade');
            });
            
            $this->info('Renamed curriculum_id to course_curriculum_id');
        }
        // If the table doesn't match our expectations, we might need to modify it differently
        else if (Schema::hasTable('curriculum_course') && !Schema::hasColumn('curriculum_course', 'course_curriculum_id')) {
            // Add the new column if it doesn't exist
            Schema::table('curriculum_course', function (Blueprint $table) {
                $table->foreignId('course_curriculum_id')
                    ->after('id')
                    ->constrained('course_curricula')
                    ->onDelete('cascade');
            });
            
            $this->info('Added course_curriculum_id column');
        }
        
        // Add is_required and sort_order columns if they don't exist
        if (Schema::hasTable('curriculum_course') && !Schema::hasColumn('curriculum_course', 'is_required')) {
            Schema::table('curriculum_course', function (Blueprint $table) {
                $table->boolean('is_required')->default(true)->after('semester');
                $table->integer('sort_order')->default(0)->after('is_required');
            });
            
            $this->info('Added is_required and sort_order columns');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only attempt to revert if we have the expected structure
        if (Schema::hasTable('curriculum_course') && Schema::hasColumn('curriculum_course', 'course_curriculum_id')) {
            // Drop is_required and sort_order columns if they exist
            if (Schema::hasColumn('curriculum_course', 'is_required')) {
                Schema::table('curriculum_course', function (Blueprint $table) {
                    $table->dropColumn('is_required');
                    $table->dropColumn('sort_order');
                });
            }
            
            // Drop foreign key constraint
            $foreignKeys = $this->listTableForeignKeys('curriculum_course');
            foreach ($foreignKeys as $foreignKey) {
                if (str_contains($foreignKey, 'course_curriculum_id')) {
                    Schema::table('curriculum_course', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey);
                    });
                    break;
                }
            }
            
            // Rename back to original
            Schema::table('curriculum_course', function (Blueprint $table) {
                $table->renameColumn('course_curriculum_id', 'curriculum_id');
            });
            
            // Add the original foreign key back
            Schema::table('curriculum_course', function (Blueprint $table) {
                $table->foreign('curriculum_id')
                    ->references('id')
                    ->on('course_curricula')
                    ->onDelete('cascade');
            });
        }
    }
    
    /**
     * Helper function to list table foreign keys
     */
    private function listTableForeignKeys($table) {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        
        $foreignKeys = [];
        
        if (method_exists($conn, 'listTableForeignKeys')) {
            $tableForeignKeys = $conn->listTableForeignKeys($table);
            
            foreach ($tableForeignKeys as $key) {
                $foreignKeys[] = $key->getName();
            }
        }
        
        return $foreignKeys;
    }
    
    /**
     * Helper method to output messages during migration
     */
    protected function info($message)
    {
        if (app()->runningInConsole()) {
            echo "\033[32mINFO: " . $message . "\033[0m\n";
        }
    }
};
