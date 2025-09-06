<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Student;
use App\Models\Employee;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update student user credentials to use student_number as username
        $students = Student::with('user')->whereHas('user')->get();
        $studentCount = 0;
        
        foreach ($students as $student) {
            if ($student->user && $student->student_number) {
                // Only update if username is different from student_number
                if ($student->user->username !== $student->student_number) {
                    $student->user->username = $student->student_number;
                    $student->user->save();
                    $studentCount++;
                }
            }
        }
        
        // 2. Update employee user credentials to use employee_id as username
        $employees = Employee::with('user')->whereHas('user')->get();
        $employeeCount = 0;
        
        foreach ($employees as $employee) {
            if ($employee->user && $employee->employee_id) {
                // Only update if username is different from employee_id
                if ($employee->user->username !== $employee->employee_id) {
                    $employee->user->username = $employee->employee_id;
                    $employee->user->save();
                    $employeeCount++;
                }
            }
        }
        
        \Illuminate\Support\Facades\Log::info("User credential update completed", [
            'students_updated' => $studentCount,
            'employees_updated' => $employeeCount
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data update migration, no rollback needed
    }
};
