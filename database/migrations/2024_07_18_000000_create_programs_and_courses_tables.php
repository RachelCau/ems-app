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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->json('campus_id')->nullable()->comment('JSON array of campus IDs associated with this program');
            $table->foreignId('program_category_id')->constrained('program_categories');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('department_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('unit');
            $table->enum('level', ['First Year', 'Second Year', 'Third Year', 'Fourth Year']);
            $table->enum('semester', ['First Semester', 'Second Semester']);
            $table->foreignId('program_id')->nullable()->constrained();
            $table->foreignId('academic_year_id')->nullable()->constrained();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('course_prerequisite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('prerequisite_id')->constrained('courses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('program_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('course_curricula', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version');
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('curriculum_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('course_curricula')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->integer('year_level');
            $table->integer('semester');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_course');
        Schema::dropIfExists('course_curricula');
        Schema::dropIfExists('program_course');
        Schema::dropIfExists('course_prerequisite');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('department_program');
        Schema::dropIfExists('programs');
    }
}; 