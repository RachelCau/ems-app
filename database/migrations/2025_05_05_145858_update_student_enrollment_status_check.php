<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Applicant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update any existing records with 'enrolled' status to 'officially enrolled'
        DB::table('applicants')
            ->where('status', 'enrolled')
            ->update(['status' => 'officially enrolled']);
            
        // Log the update
        $count = DB::table('applicants')
            ->where('status', 'officially enrolled')
            ->count();
            
        \Illuminate\Support\Facades\Log::info("Updated applicant status from 'enrolled' to 'officially enrolled'", [
            'count' => $count
        ]);
    }

    /**
     * Reverse the migrations.
     * Note: We're not providing a rollback for this migration as it's a data fix.
     */
    public function down(): void
    {
        // No need to revert this change
    }
};
