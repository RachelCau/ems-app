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
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->date('dateofbirth')->nullable();
            $table->enum('sex', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            
            // Location fields
            $table->foreignId('province_id')->nullable()->constrained('provinces');
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->foreignId('barangay_id')->nullable()->constrained('barangays');
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('barangay')->nullable();
            $table->string('zip')->nullable();
            
            // Contact information
            $table->string('mobile')->nullable();
            $table->string('landline')->nullable();
            $table->string('email')->nullable();
            
            // Family information
            $table->string('father_name')->nullable();
            $table->string('father_mobile')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_mobile')->nullable();
            $table->string('guardian_name')->nullable();
            $table->text('guardian_address')->nullable();
            $table->string('guardian_mobile')->nullable();
            
            // School information
            $table->string('school_year')->nullable();
            $table->string('school_type')->nullable();
            $table->string('school_name')->nullable();
            $table->text('school_address')->nullable();
            $table->string('strand')->nullable();
            $table->string('grade')->nullable();
            
            // Application details
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->string('program_category')->nullable();
            $table->foreignId('program_id')->nullable()->constrained('programs');
            $table->string('desired_program')->nullable();
            $table->boolean('transferee')->default(false);
            $table->enum('status', [
                'pending', 
                'approved', 
                'for entrance exam', 
                'for interview', 
                'for enrollment', 
                'declined', 
                'officially enrolled'
            ])->default('pending');
            $table->string('enrollment_status')->nullable();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('restrict');
            $table->string('applicant_number')->unique();
            $table->string('student_number')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
}; 