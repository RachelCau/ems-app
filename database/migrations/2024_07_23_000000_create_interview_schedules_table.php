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
        Schema::create('interview_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('interview_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('capacity');
            $table->string('venue')->nullable();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('applicant_interview_schedule', function (Blueprint $table) {
            $table->id();
            
            // Applicant relation with cascade delete
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->foreignId('interview_schedule_id')->nullable()->constrained()->nullOnDelete();
            
            // Interview details
            $table->unsignedBigInteger('interviewer_id')->nullable();
            $table->dateTime('interview_datetime')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('Assigned');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_interview_schedule');
        Schema::dropIfExists('interview_schedules');
    }
}; 