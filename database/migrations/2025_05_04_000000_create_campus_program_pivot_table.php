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
        Schema::create('campus_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained()->onDelete('cascade');
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            // Prevent duplicate entries
            $table->unique(['campus_id', 'program_id']);
        });
        
        // Migrate existing data from JSON arrays to the pivot table
        $programs = DB::table('programs')->get();
        
        foreach ($programs as $program) {
            $campusIds = json_decode($program->campus_id, true);
            
            if (is_array($campusIds)) {
                foreach ($campusIds as $campusId) {
                    // Avoid inserting invalid data
                    if (!is_numeric($campusId)) continue;
                    
                    // Check if campus exists
                    $campusExists = DB::table('campuses')->where('id', $campusId)->exists();
                    if (!$campusExists) continue;
                    
                    DB::table('campus_program')->insert([
                        'campus_id' => $campusId,
                        'program_id' => $program->id,
                        'is_primary' => $campusIds[0] == $campusId, // First campus is primary
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campus_program');
    }
}; 