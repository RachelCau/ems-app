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
        Schema::table('course_curricula', function (Blueprint $table) {
            // Add year_level column
            $table->integer('year_level')->nullable()->after('academic_year_id');
            
            // Add semester column if it doesn't exist
            if (!Schema::hasColumn('course_curricula', 'semester')) {
                $table->integer('semester')->nullable()->after('year_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_curricula', function (Blueprint $table) {
            // Drop the columns in reverse order
            $table->dropColumn('year_level');
            
            // Drop semester column if it was added (check first to avoid errors)
            if (Schema::hasColumn('course_curricula', 'semester')) {
                $table->dropColumn('semester');
            }
        });
    }
};
