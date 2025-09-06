<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            // Make student_id nullable
            if (Schema::hasColumn('student_enrollments', 'student_id')) {
                $table->foreignId('student_id')->nullable()->change();
            } else {
                $table->foreignId('student_id')->nullable()->after('applicant_id');
            }
            
            // Add program_id if it doesn't exist
            if (!Schema::hasColumn('student_enrollments', 'program_id')) {
                $table->foreignId('program_id')->nullable()->after('program_code');
            }
            
            // Add academic_year_id if it doesn't exist
            if (!Schema::hasColumn('student_enrollments', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('program_id');
            }
            
            // Add is_new_student if it doesn't exist
            if (!Schema::hasColumn('student_enrollments', 'is_new_student')) {
                $table->boolean('is_new_student')->default(true)->after('status');
            }
            
            // Add year_level if it doesn't exist
            if (!Schema::hasColumn('student_enrollments', 'year_level')) {
                $table->integer('year_level')->default(1)->after('is_new_student');
            }
            
            // Add semester if it doesn't exist
            if (!Schema::hasColumn('student_enrollments', 'semester')) {
                $table->integer('semester')->default(1)->after('year_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            // Revert changes - careful with removing columns that might have data
            if (Schema::hasColumn('student_enrollments', 'student_id')) {
                // Make student_id required again rather than removing it
                $table->foreignId('student_id')->nullable(false)->change();
            }
            
            // We'll leave the added columns as they are, as removing them might be destructive
        });
    }
};
