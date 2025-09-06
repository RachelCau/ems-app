<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Student;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure Student role exists
        $studentRole = Role::firstOrCreate([
            'name' => 'Student',
            'guard_name' => 'web'
        ]);

        // Find all users associated with student records
        $studentUsers = User::whereIn('id', function($query) {
            $query->select('user_id')
                ->from('students');
        })->get();

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($studentUsers as $user) {
            try {
                // Update user type to 'student'
                $user->user_type = 'student';
                $user->save();

                // Remove admin role if assigned
                $user->roles()->detach(
                    Role::where('name', 'admin')->orWhere('name', 'super admin')->pluck('id')
                );

                // Assign Student role
                $user->assignRole($studentRole);

                $updatedCount++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error updating user {$user->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        \Illuminate\Support\Facades\Log::info("Finished updating student roles", [
            'total_processed' => count($studentUsers),
            'updated_count' => $updatedCount,
            'error_count' => $errorCount
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data fix, so no need for a rollback
    }
};
