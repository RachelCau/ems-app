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
        Schema::create('admission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->onDelete('cascade');
            $table->string('document_type')->comment('e.g., "Form 138", "PSA Birth Cert"');
            $table->enum('status', ['Submitted', 'Missing', 'Invalid'])->default('Missing');
            $table->text('remarks')->nullable()->comment('Optional notes');
            $table->timestamp('submitted_at')->nullable();
            $table->string('file_path')->nullable()->comment('Path to stored document file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_documents');
    }
}; 