<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicantResource\Pages;
use App\Models\Applicant;
use App\Models\Campus;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Events\ApplicationStatusChanged;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\ApplicantResource\RelationManagers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\Mail;
use App\Services\ApplicantService;

class ApplicantResource extends Resource
{
    protected static ?string $model = Applicant::class;

    // protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Application Management';

    protected static ?string $navigationLabel = 'Applicants';

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $modelLabel = 'Applicant';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();

        // Super Admin can see all applicants from all campuses
        if (in_array('Super Admin', $userRoles)) {
            return $query;
        }
        
        // Get the employee record for the current user
        $employee = $user->employee;
        
        // First filter by campus for all roles (except Super Admin)
        if ($employee && $employee->campus_id) {
            $query->where('campus_id', $employee->campus_id);
        }
        
        // Regular Admin can see all statuses but only from their assigned campus
        if (in_array('Admin', $userRoles)) {
            return $query;
        }
        
        // Then filter by role-specific statuses
        
        // Program Head: only show applicants with "for interview" status
        if (in_array('Program Head', $userRoles)) {
            // Get the employee and their department
            $employee = $user->employee;
            if ($employee && $employee->department_id) {
                // Get program IDs that belong to this department
                $departmentProgramIds = \App\Models\Department::find($employee->department_id)
                    ->programs()
                    ->pluck('programs.id')
                    ->toArray();
                
                // Filter applicants by their desired program and status
                return $query->where('status', 'for interview')
                    ->where(function (Builder $query) use ($departmentProgramIds) {
                        // Match applicants where desired_program matches one of the department's programs
                        $query->whereIn('program_id', $departmentProgramIds)
                            ->orWhereHas('program', function (Builder $programQuery) use ($departmentProgramIds) {
                                $programQuery->whereIn('id', $departmentProgramIds);
                            });
                    });
            }
            
            return $query->where('status', 'for interview');
        }
        
        // Admission Officer: only show applicants with "pending" and "for entrance exam" statuses
        if (in_array('Admission Officer', $userRoles)) {
            return $query->whereIn('status', ['pending', 'for entrance exam']);
        }
        
        // Registrar: only show applicants with "for enrollment" status
        if (in_array('Registrar', $userRoles)) {
            return $query->where('status', 'for enrollment');
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Applicant Information')
                    ->description('Enter the personal details of the applicant')
                    ->icon('heroicon-o-user')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('campus_id')
                                    ->relationship('campus', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('academic_year_id')
                                    ->relationship('academicYear', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(1),
                            ])->columns(2),
                        Forms\Components\TextInput::make('applicant_number')
                            ->required()
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-identification'),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('middle_name')
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('last_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),
                            ])->columns(3),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-envelope'),
                        Forms\Components\Select::make('program_id')
                            ->relationship('program', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Program')
                            ->prefixIcon('heroicon-o-academic-cap'),
                        Forms\Components\TextInput::make('desired_program')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-academic-cap'),
                        Forms\Components\Select::make('status')
                            ->options(function () {
                                $options = [
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'for entrance exam' => 'For Entrance Exam',
                                    'for interview' => 'For Interview',
                                    'for enrollment' => 'For Enrollment',
                                    'declined' => 'Declined',
                                ];
                                
                                // Add 'Officially Enrolled' option only for Registrar role
                                $user = auth()->user();
                                if ($user && $user->roles->contains('name', 'Registrar')) {
                                    $options['Officially Enrolled'] = 'Officially Enrolled';
                                }
                                
                                return $options;
                            })
                            ->required()
                            ->searchable()
                            ->prefixIcon('heroicon-o-document-check')
                            ->hidden(fn() => $form->getOperation() === 'edit'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('applicant_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-identification'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'middle_name', 'last_name', 'suffix'])
                    ->sortable(['first_name']),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('campus.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('academicYear.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('desired_program')
                    ->label('Program')
                    ->formatStateUsing(function (Applicant $record, $state) {
                        // Always show program data regardless of status
                        if ($record->program) {
                            return "{$record->program->name} ({$record->program->code})";
                        }
                        // Fall back to desired_program if program relationship not available
                        return $record->desired_program ?? 'Not specified';
                    })
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->icon(function (string $state): string {
                        return match ($state) {
                            'pending' => 'heroicon-o-clock',
                            'approved' => 'heroicon-o-check-circle',
                            'for entrance exam' => 'heroicon-o-academic-cap',
                            'for interview' => 'heroicon-o-chat-bubble-left-right',
                            'for enrollment' => 'heroicon-o-clipboard-document-check',
                            'Officially Enrolled' => 'heroicon-o-academic-cap',
                            'declined' => 'heroicon-o-x-circle',
                            default => 'heroicon-o-question-mark-circle',
                        };
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'approved' => 'success',
                        'for entrance exam' => 'info',
                        'for interview' => 'warning',
                        'for enrollment' => 'success',
                        'Officially Enrolled' => 'success',
                        'declined' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campus')
                    ->relationship('campus', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Campus'),
                Tables\Filters\SelectFilter::make('academic_year')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Academic Year'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(function () {
                        $options = [
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'for entrance exam' => 'For Entrance Exam',
                            'for interview' => 'For Interview',
                            'for enrollment' => 'For Enrollment',
                            'declined' => 'Declined',
                        ];
                        
                        // Add 'Officially Enrolled' option only for Registrar role
                        $user = auth()->user();
                        if ($user && $user->roles->contains('name', 'Registrar')) {
                            $options['Officially Enrolled'] = 'Officially Enrolled';
                        }
                        
                        return $options;
                    })
                    ->indicator('Status'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicator('Date Range'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalWidth('2xl')
                        ->color('gray')
                        ->infolist(
                            fn(Infolist $infolist): Infolist => $infolist
                                ->schema([
                                    Infolists\Components\Section::make('Applicant Information')
                                        ->description('Personal details of the applicant')
                                        ->icon('heroicon-o-user')
                                        ->schema([
                                            Infolists\Components\Grid::make(3)
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('applicant_number')
                                                        ->label('Applicant number')
                                                        ->icon('heroicon-o-identification')
                                                        ->copyable()
                                                        ->copyMessage('Applicant number copied')
                                                        ->copyMessageDuration(1500)
                                                        ->weight('bold')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50'])
                                                        ->columnSpan(3),

                                                    Infolists\Components\TextEntry::make('campus.name')
                                                        ->label('Campus')
                                                        ->icon('heroicon-o-building-office')
                                                        ->color('gray')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                                    Infolists\Components\TextEntry::make('academicYear.name')
                                                        ->label('Academic year')
                                                        ->icon('heroicon-o-calendar')
                                                        ->color('gray')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                ]),

                                            Infolists\Components\Grid::make(4)
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('first_name')
                                                        ->label('First name')
                                                        ->icon('heroicon-o-user')
                                                        ->color('gray')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                                    Infolists\Components\TextEntry::make('middle_name')
                                                        ->label('Middle name')
                                                        ->default('N/A')
                                                        ->color('gray')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                                    Infolists\Components\TextEntry::make('last_name')
                                                        ->label('Last name')
                                                        ->icon('heroicon-o-user')
                                                        ->color('gray')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                                    Infolists\Components\TextEntry::make('suffix')
                                                        ->label('Suffix')
                                                        ->default('N/A')
                                                        ->color('gray')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                ]),

                                            Infolists\Components\TextEntry::make('email')
                                                ->label('Email')
                                                ->icon('heroicon-o-envelope')
                                                ->copyable()
                                                ->copyMessage('Email copied')
                                                ->copyMessageDuration(1500)
                                                ->color('gray')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                                
                                            Infolists\Components\TextEntry::make('program_category')
                                                ->label('Program category')
                                                ->icon('heroicon-o-academic-cap')
                                                ->formatStateUsing(function ($state) {
                                                    // Check if the value is an ID
                                                    if (is_numeric($state)) {
                                                        // Try to find the program category by ID
                                                        $category = \App\Models\ProgramCategory::find($state);
                                                        return $category ? $category->name : $state;
                                                    }

                                                    // If it's already a string or other value, return as is
                                                    return $state;
                                                })
                                                ->color('gray')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                            Infolists\Components\TextEntry::make('desired_program')
                                                ->label('Desired program')
                                                ->icon('heroicon-o-academic-cap')
                                                ->color('gray')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                            Infolists\Components\Grid::make(2)
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('status')
                                                        ->label('Application status')
                                                        ->badge()
                                                        ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                                        ->icon(function (string $state): string {
                                                            return match ($state) {
                                                                'pending' => 'heroicon-o-clock',
                                                                'approved' => 'heroicon-o-check-circle',
                                                                'for entrance exam' => 'heroicon-o-academic-cap',
                                                                'for interview' => 'heroicon-o-chat-bubble-left-right',
                                                                'for enrollment' => 'heroicon-o-clipboard-document-check',
                                                                'Officially Enrolled' => 'heroicon-o-academic-cap',
                                                                'declined' => 'heroicon-o-x-circle',
                                                                default => 'heroicon-o-question-mark-circle',
                                                            };
                                                        })
                                                        ->color(fn(string $state): string => match ($state) {
                                                            'pending' => 'gray',
                                                            'approved' => 'success',
                                                            'for entrance exam' => 'info',
                                                            'for interview' => 'warning',
                                                            'for enrollment' => 'success',
                                                            'Officially Enrolled' => 'success',
                                                            'declined' => 'danger',
                                                            default => 'gray',
                                                        })
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                                    Infolists\Components\TextEntry::make('created_at')
                                                        ->label('Applied on')
                                                        ->dateTime()
                                                        ->icon('heroicon-o-clock')
                                                        ->color('gray')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                ])
                                        ])
                                        ->collapsible()
                                        ->extraAttributes(['class' => 'bg-gray-950 border border-gray-800 rounded-xl p-6 shadow-lg']),
                                ])
                                ->extraAttributes(['class' => 'p-0 bg-gray-950'])
                        ),
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->visible(function () {
                            $userRoles = Auth::user()->roles->pluck('name')->toArray();
                            return in_array('Admin', $userRoles) ||
                                (!in_array('Admission Officer', $userRoles) &&
                                    !in_array('Program Head', $userRoles) &&
                                    !in_array('Registrar', $userRoles));
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->visible(function () {
                            $userRoles = Auth::user()->roles->pluck('name')->toArray();
                            return in_array('Admin', $userRoles) ||
                                (!in_array('Admission Officer', $userRoles) &&
                                    !in_array('Program Head', $userRoles) &&
                                    !in_array('Registrar', $userRoles));
                        })
                        ->before(function (Applicant $applicant) {
                            // Check if the applicant status is "Officially Enrolled"
                            if ($applicant->status === 'Officially Enrolled') {
                                // Find the student by matching student number pattern
                                $studentExists = Student::where(function($query) use ($applicant) {
                                    // Get campus for generating number pattern
                                    $campus = $applicant->campus;
                                    if ($campus) {
                                        $campusAlphaCode = strtoupper(substr($campus->name, 0, 2));
                                        $academicYear = substr(date('Y'), -2);
                                        $campusNumericCode = str_pad($campus->id, 2, '0', STR_PAD_LEFT);
                                        
                                        // Look for student with matching student number pattern
                                        $pattern = $campusAlphaCode . $academicYear . $campusNumericCode;
                                        $query->where('student_number', 'LIKE', $pattern . '%');
                                    }
                                })->exists();
                                
                                if ($studentExists) {
                                    // Prevent deletion with a notification
                                    Notification::make()
                                        ->title('Cannot Delete Enrolled Applicant')
                                        ->body('This applicant has been enrolled as a student and cannot be deleted.')
                                        ->danger()
                                        ->send();
                                        
                                    // Cancel the deletion
                                    return false;
                                }
                            }
                        }),
                    Tables\Actions\Action::make('for_interview')
                        ->label('For Interview')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('warning')
                        ->visible(function (Applicant $record) {
                            $user = Auth::user();
                            $userRoles = $user->roles->pluck('name')->toArray();
                            
                            // Only Program Heads can set applicants to interview status
                            if (!in_array('Program Head', $userRoles)) {
                                return false;
                            }
                            
                            // Check if the applicant belongs to the same campus as the employee
                            $employee = $user->employee;
                            if (!$employee || $employee->campus_id != $record->campus_id) {
                                return false;
                            }
                            
                            // Applicable statuses that can be moved to interview
                            $validPreviousStatuses = ['approved'];
                            
                            return in_array($record->status, $validPreviousStatuses);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Set For Interview')
                        ->modalDescription('Are you sure you want to set this application for interview? The applicant will be notified via email.')
                        ->modalSubmitActionLabel('Yes, set for interview')
                        ->action(function (Applicant $record): void {
                            $oldStatus = $record->status;
                            $record->status = 'for interview';
                            $record->save();

                            event(new ApplicationStatusChanged($record, $oldStatus, 'for interview'));

                            Notification::make()
                                ->success()
                                ->title('Set for Interview')
                                ->body('The applicant has been set for interview and notified via email.')
                                ->send();
                        }),
                    Tables\Actions\Action::make('for_enrollment')
                        ->label('For Enrollment')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('success')
                        ->visible(function (Applicant $record) {
                            $user = Auth::user();
                            $userRoles = $user->roles->pluck('name')->toArray();
                            
                            // Only Registrars can set applicants to enrollment status
                            if (!in_array('Registrar', $userRoles)) {
                                return false;
                            }
                            
                            // Check if the applicant belongs to the same campus as the employee
                            $employee = $user->employee;
                            if (!$employee || $employee->campus_id != $record->campus_id) {
                                return false;
                            }
                            
                            // Applicable statuses that can be moved to enrollment
                            $validPreviousStatuses = ['for interview', 'approved'];
                            
                            return in_array($record->status, $validPreviousStatuses);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Set For Enrollment')
                        ->modalDescription('Are you sure you want to set this application for enrollment? The applicant will be notified via email.')
                        ->modalSubmitActionLabel('Yes, set for enrollment')
                        ->action(function (Applicant $record): void {
                            $oldStatus = $record->status;
                            $record->status = 'for enrollment';
                            $record->save();

                            event(new ApplicationStatusChanged($record, $oldStatus, 'for enrollment'));

                            Notification::make()
                                ->success()
                                ->title('Set for Enrollment')
                                ->body('The applicant has been set for enrollment and notified via email.')
                                ->send();
                        }),
                    Tables\Actions\Action::make('enroll')
                        ->label('Enroll as Student')
                        ->color('success')
                        ->icon('heroicon-o-academic-cap')
                        ->requiresConfirmation()
                        ->visible(fn (Applicant $record) => 
                            auth()->user() && 
                            auth()->user()->roles->contains('name', 'Registrar') && 
                            $record->status !== 'Officially Enrolled'
                        )
                        ->action(function (Applicant $record) {
                            $applicantService = app(ApplicantService::class);
                            
                            try {
                                $result = $applicantService->updateStatus($record, 'Officially Enrolled');
                                
                                if ($result) {
                                    Notification::make()
                                        ->title('Student Enrolled')
                                        ->body('Applicant has been enrolled as a student successfully.')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Enrollment Failed')
                                        ->body('Failed to enroll applicant as student.')
                                        ->danger()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Enrollment Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enroll_students')
                        ->label('Enroll as Students')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user() && auth()->user()->roles->contains('name', 'Registrar'))
                        ->action(function (Collection $records) {
                            $enrolledCount = 0;
                            $failedCount = 0;
                            $applicantService = app(ApplicantService::class);
                            
                            DB::beginTransaction();
                            try {
                                foreach ($records as $applicant) {
                                    // Only proceed if not already enrolled
                                    if ($applicant->status !== 'Officially Enrolled') {
                                        $result = $applicantService->updateStatus($applicant, 'Officially Enrolled');
                                        
                                        if ($result) {
                                            $enrolledCount++;
                                        } else {
                                            $failedCount++;
                                        }
                                    }
                                }
                                
                                DB::commit();
                                
                                Notification::make()
                                    ->title('Enrollment Completed')
                                    ->body("{$enrolledCount} applicants have been enrolled as students. {$failedCount} failed.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                
                                Notification::make()
                                    ->title('Enrollment Failed')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Moved to ViewApplicant page
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplicants::route('/'),
            'create' => Pages\CreateApplicant::route('/create'),
            'view' => Pages\ViewApplicant::route('/{record}'),
            'edit' => Pages\EditApplicant::route('/{record}/edit'),
        ];
    }
}
