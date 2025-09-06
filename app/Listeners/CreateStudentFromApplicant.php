<?php

namespace App\Listeners;

use App\Events\ApplicantEnrolled;
use App\Models\Applicant;
use App\Models\Student;
use App\Models\User;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CreateStudentFromApplicant
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ApplicantEnrolled $event): void
    {
        $applicant = $event->applicant;
        
        Log::info('Processing applicant enrollment from manual action', [
            'applicant_id' => $applicant->id,
            'applicant_name' => $applicant->first_name . ' ' . $applicant->last_name,
            'email' => $applicant->email,
            'campus_id' => $applicant->campus_id,
            'program_id' => $applicant->program_id,
            'desired_program' => $applicant->desired_program,
        ]);
        
        // Check for existing enrollment first to avoid duplicates
        $existingEnrollment = \App\Models\StudentEnrollment::where('applicant_id', $applicant->applicant_number)->first();
        if ($existingEnrollment) {
            Log::info('Student enrollment already exists, skipping creation', [
                'applicant_id' => $applicant->id,
                'applicant_number' => $applicant->applicant_number,
                'enrollment_id' => $existingEnrollment->id,
            ]);
            return;
        }
        
        // Check for missing required data
        $this->validateApplicantData($applicant);
        
        DB::beginTransaction();
        
        try {
            // Generate student number
            $applicantService = app(\App\Services\ApplicantService::class);
            $studentNumber = $applicantService->generateStudentNumber($applicant);
            
            Log::info('Generated student number', [
                'applicant_id' => $applicant->id,
                'student_number' => $studentNumber,
            ]);
            
            // Create or retrieve a user account
            $user = $this->createUserAccount($applicant, $studentNumber);
            
            if (!$user || !$user->exists) {
                throw new \Exception('Failed to create or retrieve user account');
            }
            
            Log::info('User account created/updated', [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ]);
            
            // Get current academic year for student
            $academicYear = AcademicYear::where('is_active', true)->first();
            
            if (!$academicYear) {
                // Fallback to the latest academic year if no active year found
                $academicYear = AcademicYear::latest('id')->first();
                
                if (!$academicYear) {
                    Log::warning('No academic year found for student enrollment');
                }
            }
            
            // Create the student record
            $student = $this->createStudentRecord($applicant, $user, $studentNumber, $academicYear ? $academicYear->id : null);
            
            if (!$student || !$student->exists) {
                throw new \Exception('Failed to create or update student record');
            }
            
            // Important: Update the applicant with the student number to establish the link
            $applicant->student_number = $studentNumber;
            $applicant->save();
            
            Log::info('Student record created/updated', [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'academic_year' => $academicYear ? $academicYear->name : 'Not set',
                'year_level' => $student->year_level,
                'semester' => $student->semester,
            ]);
            
            // Create student enrollment record
            $enrollment = \App\Models\StudentEnrollment::create([
                'student_id' => $student->id,
                'applicant_id' => $applicant->applicant_number,
                'program_id' => $applicant->program_id,
                'program_code' => $applicant->program ? $applicant->program->code : null,
                'campus_id' => $applicant->campus_id,
                'academic_year_id' => $academicYear ? $academicYear->id : null,
                'year_level' => 1,
                'semester' => 1,
                'status' => 'enrolled',
                'is_new_student' => true,
                'remarks' => 'Manual enrollment',
            ]);
            
            Log::info('Student enrollment created', [
                'enrollment_id' => $enrollment->id,
                'student_id' => $student->id,
                'applicant_id' => $applicant->applicant_number,
            ]);
            
            // Send email with credentials
            $this->sendCredentialsEmail($student, $studentNumber, $user->temp_password);
            
            Log::info('Applicant successfully enrolled as student', [
                'applicant_id' => $applicant->id,
                'student_id' => $student->id,
                'user_id' => $user->id,
                'student_number' => $studentNumber,
            ]);
            
            // IMPORTANT: The student record is now independent from the applicant record.
            // Deleting the applicant will NOT affect the student record as we don't maintain
            // a foreign key relationship between students and applicants.
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to enroll applicant as student', [
                'applicant_id' => $applicant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Re-throw the exception so it propagates up to the command
            throw $e;
        }
    }
    
    /**
     * Create a user account for the student.
     */
    private function createUserAccount($applicant, $studentNumber)
    {
        try {
            // Check if a user with this student_number already exists
            $user = User::where('username', $studentNumber)->first();
            
            if (!$user) {
                // Create a new user
                $user = new User();
                $user->username = $studentNumber;
                $user->email = $applicant->email;
                $user->user_type = 'student';
                
                // Generate a random password
                $password = Str::random(8);
                $user->password = bcrypt($password);
                
                // Store temporary password for sending in email
                $user->temp_password = $password;
                
                $result = $user->save();
                
                Log::info('New user created', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'save_result' => $result,
                ]);
            } else {
                // Generate a new password for existing user
                $password = Str::random(8);
                $user->password = bcrypt($password);
                $user->temp_password = $password;
                $result = $user->save();
                
                Log::info('Existing user updated', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'save_result' => $result,
                ]);
            }
            
            return $user;
        } catch (\Exception $e) {
            Log::error('Error creating user account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Create a student record from the applicant data.
     */
    private function createStudentRecord($applicant, $user, $studentNumber, $academicYearId = null)
    {
        try {
            // Check if a student with this student_number already exists
            $student = Student::where('student_number', $studentNumber)->first();
            
            // Also check if there's a student with the same email
            if (!$student) {
                $student = Student::where('email', $applicant->email)->first();
            }
            
            if (!$student) {
                // Create a new student record
                $student = new Student();
            }
            
            // Populate student fields from applicant data
            $student->user_id = $user->id;
            $student->student_number = $studentNumber;
            $student->first_name = $applicant->first_name;
            $student->middle_name = $applicant->middle_name;
            $student->last_name = $applicant->last_name;
            $student->suffix = $applicant->suffix ?? null;
            $student->sex = $applicant->sex ?? 'Male'; // Default to Male if not provided
            
            // Handle mobile_number - must have a value as the database column is NOT NULL
            if (!empty($applicant->mobile)) {
                $student->mobile_number = $applicant->mobile;
            } else {
                // Provide a default value to avoid null constraint violation
                $student->mobile_number = '00000000000'; 
                Log::info('Using placeholder mobile number because applicant has none', [
                    'applicant_id' => $applicant->id,
                ]);
            }
            
            $student->email = $applicant->email;
            $student->campus_id = $applicant->campus_id;
            
            // Set program code from applicant
            if ($applicant->program) {
                $student->program_code = $applicant->program->code;
            } elseif (!empty($applicant->desired_program)) {
                $student->program_code = $applicant->desired_program;
            } elseif (!empty($applicant->program_id)) {
                // Try to get the program via program_id
                $program = \App\Models\Program::find($applicant->program_id);
                if ($program) {
                    $student->program_code = $program->code;
                }
            }
            
            // Set academic year, year level, and semester
            $student->academic_year_id = $academicYearId;
            $student->year_level = 1; // Default to first year
            $student->semester = 1;   // Default to first semester
            
            // Log the program information
            Log::info('Setting student program', [
                'applicant_id' => $applicant->id,
                'applicant_number' => $applicant->applicant_number,
                'program_field' => $applicant->desired_program ?? 'Not set',
                'program_relationship' => $applicant->program ? $applicant->program->code : 'No related program',
                'final_program_code' => $student->program_code ?? 'Not set',
            ]);
            
            $student->student_status = 'active';
            
            // Copy avatar if exists
            if (isset($applicant->avatar) && $applicant->avatar) {
                $student->avatar = $applicant->avatar;
            }
            
            $result = $student->save();
            
            Log::info('Student record saved', [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'program_code' => $student->program_code,
                'save_result' => $result,
            ]);
            
            return $student;
        } catch (\Exception $e) {
            Log::error('Error creating student record', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Send email with login credentials.
     */
    private function sendCredentialsEmail($student, $username, $password)
    {
        try {
            Mail::to($student->email)->send(new \App\Mail\StudentCredentials($student, $username, $password));
            
            Log::info('Student credentials email sent', [
                'student_id' => $student->id,
                'email' => $student->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send student credentials email', [
                'student_id' => $student->id,
                'email' => $student->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Validate that the applicant has all required fields for student creation
     * 
     * @throws \Exception If validation fails
     */
    private function validateApplicantData(Applicant $applicant): void
    {
        $errors = [];
        
        if (empty($applicant->email)) {
            $errors[] = "Missing email";
        }
        
        if (empty($applicant->campus_id)) {
            $errors[] = "Missing campus_id";
        } else {
            $campus = \App\Models\Campus::find($applicant->campus_id);
            if (!$campus) {
                $errors[] = "Invalid campus_id: {$applicant->campus_id} - campus not found";
            }
        }
        
        // Check for program info
        $hasProgram = false;
        if (!empty($applicant->program_id)) {
            $program = \App\Models\Program::find($applicant->program_id);
            if ($program) {
                $hasProgram = true;
            } else {
                $errors[] = "Invalid program_id: {$applicant->program_id} - program not found";
            }
        }
        
        if (!$hasProgram && empty($applicant->desired_program)) {
            $errors[] = "Missing program information - both program_id and desired_program are empty";
        }
        
        if (!empty($errors)) {
            Log::error('Applicant data validation failed', [
                'applicant_id' => $applicant->id,
                'errors' => $errors,
            ]);
            throw new \Exception("Applicant data validation failed: " . implode(", ", $errors));
        }
    }
} 