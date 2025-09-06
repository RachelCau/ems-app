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
        // Drop the existing enum constraint and recreate it with the SUMMER option
        Schema::table('courses', function (Blueprint $table) {
            // MySQL specific approach
            DB::statement("ALTER TABLE courses MODIFY COLUMN semester ENUM('First Semester', 'Second Semester', 'SUMMER')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        Schema::table('courses', function (Blueprint $table) {
            // MySQL specific approach
            DB::statement("ALTER TABLE courses MODIFY COLUMN semester ENUM('First Semester', 'Second Semester')");
        });
    }
};
