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
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('applicant_id')->nullable()->constrained('applicants');
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->foreignId('campus_id')->constrained('campuses');
            $table->string('program_code')->nullable()->comment('Program code the student is enrolled in');
            $table->foreignId('program_id')->nullable()->constrained('programs');
            $table->integer('year_level')->default(1);
            $table->integer('semester')->default(1);
            $table->enum('status', ['enrolled', 'dropped', 'pending', 'withdrawn', 'completed'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('enrolled_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses');
            $table->string('student_number')->nullable();
            $table->enum('status', ['enrolled', 'completed', 'dropped', 'failed', 'incomplete'])->default('enrolled');
            $table->string('grade')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrolled_courses');
        Schema::dropIfExists('student_enrollments');
    }
}; 