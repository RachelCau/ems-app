<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Get the employee ID using the employee number
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmployeeId(Request $request)
    {
        $request->validate([
            'employee_number' => 'required|string',
        ]);

        $employeeNumber = $request->input('employee_number');
        $employee = Employee::findByEmployeeNumber($employeeNumber);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found with the given employee number',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'name' => $employee->first_name . ' ' . $employee->last_name,
        ]);
    }
} 