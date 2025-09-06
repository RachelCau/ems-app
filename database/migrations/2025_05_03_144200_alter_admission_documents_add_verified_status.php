<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, we need to modify the enum to include 'Verified'
        DB::statement("ALTER TABLE admission_documents MODIFY COLUMN status ENUM('Submitted', 'Missing', 'Invalid', 'Verified') NOT NULL DEFAULT 'Missing'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the enum to its original state
        DB::statement("ALTER TABLE admission_documents MODIFY COLUMN status ENUM('Submitted', 'Missing', 'Invalid') NOT NULL DEFAULT 'Missing'");
    }
};
