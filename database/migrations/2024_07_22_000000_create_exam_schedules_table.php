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
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->foreignId('room_id')->nullable()->constrained();
            $table->integer('capacity')->nullable();
            $table->foreignId('campus_id')->nullable()->constrained();
            $table->timestamps();
        });

        Schema::create('applicant_exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_schedule_id')->constrained()->onDelete('cascade');
            $table->string('applicant_number')->nullable();
            $table->enum('status', ['Scheduled', 'Attended', 'Missed', 'Cancelled', 'Passed', 'Failed']);
            $table->decimal('score', 8, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('score_remarks')->nullable();
            $table->integer('total_items')->default(100);
            $table->timestamps();
        });

        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('answer');
            $table->integer('points')->default(1);
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('applicant_exam_schedules');
        Schema::dropIfExists('exam_schedules');
    }
}; 