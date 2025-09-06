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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->enum('sex', ['male', 'female']);
            $table->string('mobile_number');
            $table->text('address')->nullable();
            $table->enum('employee_type', ['permanent', 'casual', 'cos']);
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('restrict')->comment('Academic dept. only');
            $table->foreignId('office_id')->nullable()->constrained()->onDelete('restrict')->comment('Admin offices like MIS');
            $table->foreignId('designation_id')->constrained('designations')->onDelete('restrict');
            $table->foreignId('campus_id')->constrained()->onDelete('restrict');
            $table->enum('employee_status', ['active', 'inactive'])->default('active');
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
}; 