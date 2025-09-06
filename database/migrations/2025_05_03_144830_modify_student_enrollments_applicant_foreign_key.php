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
        // Drop the existing foreign key constraint
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
        });

        // Add the foreign key back with CASCADE on delete
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreign('applicant_id')
                  ->references('id')
                  ->on('applicants')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the cascading delete constraint
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
        });

        // Restore the original constraint without cascade
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreign('applicant_id')
                  ->references('id')
                  ->on('applicants');
        });
    }
}; 