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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('student_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->enum('sex', ['male', 'female', 'other']);
            $table->string('mobile_number');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('campus_id')->constrained()->onDelete('restrict');
            $table->foreignId('program_id')->nullable()->constrained();
            $table->string('program')->nullable()->comment('Program code the student is enrolled in');
            $table->string('program_code')->nullable()->comment('Program code for compatibility with existing queries');
            $table->enum('student_status', ['active', 'inactive', 'graduated', 'transferred', 'dropped'])->default('active');
            $table->string('avatar')->nullable();
            
            // Academic information
            $table->foreignId('academic_year_id')->nullable()->constrained();
            $table->integer('year_level')->nullable();
            $table->integer('semester')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
}; 