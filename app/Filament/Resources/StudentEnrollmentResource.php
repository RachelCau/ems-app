<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentEnrollmentResource\Pages;
use App\Filament\Resources\StudentEnrollmentResource\RelationManagers;
use App\Models\StudentEnrollment;
use App\Models\Applicant;
use App\Models\Student;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use App\Events\ApplicationStatusChanged;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Spatie\Permission\Models\Role;

class StudentEnrollmentResource extends Resource
{
    protected static ?string $model = Applicant::class;

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $navigationLabel = 'Student Enrollments';

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', 'for enrollment');
    }

    /**
     * Determine if this resource should be registered in the navigation.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        
        // Hide for Admission Officer role
        if ($user && $user->roles->contains('name', 'Admission Officer')) {
            return false;
        }

        // Hide for Program Head role
        if ($user && $user->roles->contains('name', 'Program Head')) {
            return false;
        }
        
        // Explicitly show for Registrar role
        if ($user && $user->roles->contains('name', 'Registrar')) {
            return true;
        }
        
        // Show for all other roles with appropriate permissions
        return true;
    }

    /**
     * Lookup program ID and code based on desired_program field
     * 
     * @param string|null $desiredProgram
     * @return array [program_id, program_code]
     */
    private static function lookupProgramFromDesiredProgram($desiredProgram): array
    {
        if (empty($desiredProgram)) {
            return [null, null];
        }
        
        // First try exact match with program code
        $program = \App\Models\Program::where('code', $desiredProgram)->first();
        
        if (!$program) {
            // Try to find by name (partial match)
            $program = \App\Models\Program::where('name', 'like', "%{$desiredProgram}%")->first();
        }
        
        if (!$program) {
            // Check if desired_program contains a parenthesized code like "Bachelor of Science (BSCS)"
            if (preg_match('/\(([^)]+)\)/', $desiredProgram, $matches)) {
                $extractedCode = trim($matches[1]);
                $program = \App\Models\Program::where('code', $extractedCode)->first();
            }
        }
        
        return $program ? [$program->id, $program->code] : [null, null];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Enrollment Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('middle_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('program_id')
                            ->relationship('program', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Program'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'for entrance exam' => 'For Entrance Exam',
                                'for interview' => 'For Interview',
                                'for enrollment' => 'For Enrollment',
                                'enrolled' => 'Enrolled',
                                'declined' => 'Declined',
                            ])
                            ->required()
                            ->searchable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'middle_name', 'last_name'])
                    ->sortable(['first_name']),
                Tables\Columns\TextColumn::make('desired_program')
                    ->label('Program')
                    ->formatStateUsing(function (Applicant $record, $state) {
                        if ($record->program) {
                            return $record->program->name;
                        }
                        return $record->desired_program ?? 'Not specified';
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('studentEnrollment.status')
                    ->label('Enrollment Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ?? 'Not enrolled')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('enroll')
                        ->label('Enroll')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirm Enrollment')
                        ->modalDescription('Are you sure you want to enroll this applicant? This will create a student record and enrollment.')
                        ->modalSubmitActionLabel('Confirm Enrollment')
                        ->action(function (Applicant $record): void {
                            try {
                                Log::info('Starting enrollment process for applicant', [
                                    'applicant_id' => $record->id,
                                    'current_status' => $record->status
                                ]);

                                DB::beginTransaction();
                                
                                // Check if student already exists
                                $student = Student::where('email', $record->email)->first();
                                
                                if (!$student) {
                                    // Generate the same identifier for both username and student_number
                                    // First check if the applicant already has a student_number
                                    $studentNumber = $record->student_number;
                                    
                                    if (empty($studentNumber)) {
                                        // Generate username/student_number in format: [CampusCode(alpha)][Year(2digits)][CampusCode(numeric)][SequentialNumber]
                                        $campus = $record->campus;
                                        $academicYear = AcademicYear::where('is_active', true)->first();
                                        
                                        // Get campus codes
                                        $campusCodeAlpha = substr($campus?->code ?? 'UN', 0, 2); // Default to 'UN' if no campus
                                        $campusCodeNumeric = str_pad($campus?->id ?? 0, 2, '0', STR_PAD_LEFT);
                                        
                                        // Get year portion (last 2 digits)
                                        $yearCode = substr($academicYear?->name ?? date('Y'), 2, 2);
                                        
                                        // Get latest sequential number and increment
                                        $latestUser = User::where('username', 'like', $campusCodeAlpha . $yearCode . $campusCodeNumeric . '%')->orderBy('id', 'desc')->first();
                                        
                                        $sequentialNumber = 1;
                                        if ($latestUser && preg_match('/^[A-Z]{2}\d{2}\d{2}(\d{4})$/', $latestUser->username, $matches)) {
                                            $sequentialNumber = intval($matches[1]) + 1;
                                        }
                                        
                                        $studentNumber = $campusCodeAlpha . $yearCode . $campusCodeNumeric . str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);
                                        
                                        // Update the applicant with this student number
                                        $record->student_number = $studentNumber;
                                        $record->save();
                                    }
                                    
                                    $user = User::create([
                                        'name' => $record->first_name . ' ' . $record->last_name,
                                        'email' => $record->email,
                                        'username' => $studentNumber, // Use student number as username
                                        'password' => bcrypt(Str::random(12)), // Random password
                                        'user_type' => 'student', // Explicitly set user type
                                    ]);
                                    
                                    // Assign student role if exists
                                    if ($role = Role::where('name', 'Student')->first()) {
                                        $user->assignRole($role);
                                    }
                                    
                                    // Create new student from applicant with all specified fields
                                    $student = Student::create([
                                        'user_id' => $user->id,
                                        'student_number' => $studentNumber,
                                        'first_name' => $record->first_name,
                                        'middle_name' => $record->middle_name,
                                        'last_name' => $record->last_name,
                                        'suffix' => $record->suffix,
                                        'sex' => $record->sex,
                                        'mobile_number' => $record->mobile,
                                        'email' => $record->email,
                                        'address' => $record->address,
                                        'province_id' => $record->province_id,
                                        'city_id' => $record->city_id,
                                        'barangay_id' => $record->barangay_id,
                                        'postal_code' => $record->zip,
                                        'campus_id' => $record->campus_id,
                                        'student_status' => 'active',
                                        // Avatar will be null by default unless added later
                                    ]);
                                }
                                
                                // Get current academic year
                                $currentAcademicYear = AcademicYear::where('is_active', true)->first();
                                
                                // Get program information from desired_program if program_id is not set
                                if (empty($record->program_id) && !empty($record->desired_program)) {
                                    [$programId, $programCode] = self::lookupProgramFromDesiredProgram($record->desired_program);
                                    if ($programId) {
                                        $record->program_id = $programId;
                                        // Save to keep the record updated with the resolved program
                                        $record->save();
                                    }
                                }
                                
                                // Create student enrollment if it doesn't exist
                                if (!$record->studentEnrollment) {
                                    $enrollment = StudentEnrollment::create([
                                        'student_id' => $student->id,
                                        'applicant_id' => $record->applicant_number,
                                        'program_id' => $record->program_id,
                                        'program_code' => $record->program ? $record->program->code : 
                                            (!empty($record->desired_program) ? (self::lookupProgramFromDesiredProgram($record->desired_program)[1]) : null),
                                        'campus_id' => $record->campus_id,
                                        'academic_year_id' => $currentAcademicYear?->id,
                                        'year_level' => 1,
                                        'semester' => 1,
                                        'status' => 'enrolled',
                                        'is_new_student' => true,
                                        'remarks' => 'Enrolled from application',
                                    ]);
                                    
                                    // Also update the student with program data if missing
                                    $studentUpdateData = [];
                                    if (empty($student->program_id) && !empty($enrollment->program_id)) {
                                        $studentUpdateData['program_id'] = $enrollment->program_id;
                                    }
                                    if (empty($student->program_code) && !empty($enrollment->program_code)) {
                                        $studentUpdateData['program_code'] = $enrollment->program_code;
                                    }
                                    if (empty($student->academic_year_id) && !empty($enrollment->academic_year_id)) {
                                        $studentUpdateData['academic_year_id'] = $enrollment->academic_year_id;
                                    }
                                    if (empty($student->year_level) && !empty($enrollment->year_level)) {
                                        $studentUpdateData['year_level'] = $enrollment->year_level;
                                    }
                                    if (empty($student->semester) && !empty($enrollment->semester)) {
                                        $studentUpdateData['semester'] = $enrollment->semester;
                                    }
                                    
                                    if (!empty($studentUpdateData)) {
                                        $student->update($studentUpdateData);
                                    }
                                }
                                
                                // Update applicant status to 'officially enrolled'
                                $record->status = 'officially enrolled';
                                $record->save();
                                
                                DB::commit();
                                
                                Notification::make()
                                    ->success()
                                    ->title('Enrollment Successful')
                                    ->body('The applicant has been enrolled as a student successfully.')
                                    ->send();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                
                                // Log any errors that occur
                                Log::error('Error during enrollment process: ' . $e->getMessage(), [
                                    'applicant_id' => $record->id,
                                    'exception' => $e
                                ]);

                                Notification::make()
                                    ->danger()
                                    ->title('Enrollment Error')
                                    ->body('An error occurred during enrollment: ' . $e->getMessage())
                                    ->send();
                            }
                        }),
                    Tables\Actions\ViewAction::make()
                        ->form([
                            Forms\Components\Section::make('Enrollment Information')
                                ->schema([
                                    Forms\Components\TextInput::make('full_name')
                                        ->label('Student Name')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('email')
                                        ->label('Email')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('mobile')
                                        ->label('Mobile')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('desired_program')
                                        ->label('Program')
                                        ->disabled(),
                                ])->columns(2),
                        ]),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_enroll')
                        ->label('Enroll Selected')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirm Bulk Enrollment')
                        ->modalDescription('Are you sure you want to enroll the selected applicants? This will create student records and enrollments for each.')
                        ->modalSubmitActionLabel('Confirm Enrollment')
                        ->action(function (Collection $records): void {
                            $enrolledCount = 0;
                            $failedCount = 0;
                            $currentAcademicYear = AcademicYear::where('is_active', true)->first();
                            
                            DB::beginTransaction();
                            try {
                                foreach ($records as $record) {
                                    // Skip if already enrolled
                                    if ($record->status === 'officially enrolled' || $record->studentEnrollment) {
                                        continue;
                                    }
                                    
                                    // Check if student already exists
                                    $student = Student::where('email', $record->email)->first();
                                    
                                    if (!$student) {
                                        // Generate the same identifier for both username and student_number
                                        // First check if the applicant already has a student_number
                                        $studentNumber = $record->student_number;
                                        
                                        if (empty($studentNumber)) {
                                            // Generate username/student_number in format: [CampusCode(alpha)][Year(2digits)][CampusCode(numeric)][SequentialNumber]
                                            $campus = $record->campus;
                                            $academicYear = AcademicYear::where('is_active', true)->first();
                                            
                                            // Get campus codes
                                            $campusCodeAlpha = substr($campus?->code ?? 'UN', 0, 2); // Default to 'UN' if no campus
                                            $campusCodeNumeric = str_pad($campus?->id ?? 0, 2, '0', STR_PAD_LEFT);
                                            
                                            // Get year portion (last 2 digits)
                                            $yearCode = substr($academicYear?->name ?? date('Y'), 2, 2);
                                            
                                            // Get latest sequential number and increment
                                            $latestUser = User::where('username', 'like', $campusCodeAlpha . $yearCode . $campusCodeNumeric . '%')->orderBy('id', 'desc')->first();
                                            
                                            $sequentialNumber = 1;
                                            if ($latestUser && preg_match('/^[A-Z]{2}\d{2}\d{2}(\d{4})$/', $latestUser->username, $matches)) {
                                                $sequentialNumber = intval($matches[1]) + 1;
                                            }
                                            
                                            $studentNumber = $campusCodeAlpha . $yearCode . $campusCodeNumeric . str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);
                                            
                                            // Update the applicant with this student number
                                            $record->student_number = $studentNumber;
                                            $record->save();
                                        }
                                        
                                        $user = User::create([
                                            'name' => $record->first_name . ' ' . $record->last_name,
                                            'email' => $record->email,
                                            'username' => $studentNumber, // Use student number as username
                                            'password' => bcrypt(Str::random(12)), // Random password
                                            'user_type' => 'student', // Explicitly set user type
                                        ]);
                                        
                                        // Assign student role if exists
                                        if ($role = Role::where('name', 'Student')->first()) {
                                            $user->assignRole($role);
                                        }
                                        
                                        // Create new student from applicant with all specified fields
                                        $student = Student::create([
                                            'user_id' => $user->id,
                                            'student_number' => $studentNumber,
                                            'first_name' => $record->first_name,
                                            'middle_name' => $record->middle_name,
                                            'last_name' => $record->last_name,
                                            'suffix' => $record->suffix,
                                            'sex' => $record->sex,
                                            'mobile_number' => $record->mobile,
                                            'email' => $record->email,
                                            'address' => $record->address,
                                            'province_id' => $record->province_id,
                                            'city_id' => $record->city_id,
                                            'barangay_id' => $record->barangay_id,
                                            'postal_code' => $record->zip,
                                            'campus_id' => $record->campus_id,
                                            'student_status' => 'active',
                                            // Avatar will be null by default unless added later
                                        ]);
                                    }
                                    
                                    // Get program information from desired_program if program_id is not set
                                    if (empty($record->program_id) && !empty($record->desired_program)) {
                                        [$programId, $programCode] = self::lookupProgramFromDesiredProgram($record->desired_program);
                                        if ($programId) {
                                            $record->program_id = $programId;
                                            // Save to keep the record updated with the resolved program
                                            $record->save();
                                        }
                                    }
                                    
                                    // Create student enrollment
                                    $enrollment = StudentEnrollment::create([
                                        'student_id' => $student->id,
                                        'applicant_id' => $record->applicant_number,
                                        'program_id' => $record->program_id,
                                        'program_code' => $record->program ? $record->program->code : 
                                            (!empty($record->desired_program) ? (self::lookupProgramFromDesiredProgram($record->desired_program)[1]) : null),
                                        'campus_id' => $record->campus_id,
                                        'academic_year_id' => $currentAcademicYear?->id,
                                        'year_level' => 1,
                                        'semester' => 1,
                                        'status' => 'enrolled',
                                        'is_new_student' => true,
                                        'remarks' => 'Enrolled from application',
                                    ]);
                                    
                                    // Also update the student with program data if missing
                                    $studentUpdateData = [];
                                    if (empty($student->program_id) && !empty($enrollment->program_id)) {
                                        $studentUpdateData['program_id'] = $enrollment->program_id;
                                    }
                                    if (empty($student->program_code) && !empty($enrollment->program_code)) {
                                        $studentUpdateData['program_code'] = $enrollment->program_code;
                                    }
                                    if (empty($student->academic_year_id) && !empty($enrollment->academic_year_id)) {
                                        $studentUpdateData['academic_year_id'] = $enrollment->academic_year_id;
                                    }
                                    if (empty($student->year_level) && !empty($enrollment->year_level)) {
                                        $studentUpdateData['year_level'] = $enrollment->year_level;
                                    }
                                    if (empty($student->semester) && !empty($enrollment->semester)) {
                                        $studentUpdateData['semester'] = $enrollment->semester;
                                    }
                                    
                                    if (!empty($studentUpdateData)) {
                                        $student->update($studentUpdateData);
                                    }
                                    
                                    // Update applicant status to 'officially enrolled'
                                    $record->status = 'officially enrolled';
                                    $record->save();
                                    
                                    $enrolledCount++;
                                }
                                
                                DB::commit();
                                
                                Notification::make()
                                    ->success()
                                    ->title('Bulk Enrollment Completed')
                                    ->body("{$enrolledCount} applicants have been enrolled as students. {$failedCount} failed.")
                                    ->send();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                
                                Log::error('Error during bulk enrollment: ' . $e->getMessage(), [
                                    'exception' => $e
                                ]);
                                
                                Notification::make()
                                    ->danger()
                                    ->title('Bulk Enrollment Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentEnrollments::route('/'),
        ];
    }
}
