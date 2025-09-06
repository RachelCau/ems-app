<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InterviewScheduleResource\Pages;
use App\Filament\Resources\InterviewScheduleResource\RelationManagers;
use App\Models\InterviewSchedule;
use App\Models\Applicant;
use App\Models\ApplicantInterviewSchedule;
use App\Events\ApplicationStatusChanged;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class InterviewScheduleResource extends Resource
{
    protected static ?string $model = InterviewSchedule::class;

    protected static ?string $navigationGroup = 'Application Management';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        $userRoles = $user->roles->pluck('name')->toArray();
        
        // Super Admin can see all interview schedules
        if (in_array('Super Admin', $userRoles)) {
            return $query;
        }
        
        // Get the employee record for the current user
        $employee = $user->employee;
        
        // Filter by user's campus for all roles except Super Admin
        if ($employee && $employee->campus_id) {
            $query->where('campus_id', $employee->campus_id);
        }
        
        // Program Head: only show interview schedules related to their department's programs
        if (in_array('Program Head', $userRoles) && $employee && $employee->department_id) {
            // Get program IDs that belong to this department
            $departmentProgramIds = \App\Models\Department::find($employee->department_id)
                ->programs()
                ->pluck('programs.id')
                ->toArray();
            
            // Only include interview schedules that have applicants with these program IDs
            return $query->whereHas('applicantInterviewSchedules.applicant', function (Builder $applicantQuery) use ($departmentProgramIds) {
                $applicantQuery->whereIn('program_id', $departmentProgramIds)
                    ->orWhereHas('program', function (Builder $programQuery) use ($departmentProgramIds) {
                        $programQuery->whereIn('id', $departmentProgramIds);
                    });
            });
        }
        
        return $query;
    }

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    /**
     * Check if a given date is a holiday
     */
    private static function isHoliday(Carbon $date): bool
    {
        // List of fixed holidays for Philippines (adjust for your country)
        $fixedHolidays = [
            '01-01', // New Year's Day
            '04-09', // Araw ng Kagitingan (Day of Valor)
            '05-01', // Labor Day
            '06-12', // Independence Day
            '08-21', // Ninoy Aquino Day
            '08-30', // National Heroes Day (last Monday of August - simplified)
            '11-01', // All Saints' Day
            '11-30', // Bonifacio Day
            '12-25', // Christmas Day
            '12-30', // Rizal Day
            '12-31', // New Year's Eve
        ];
        
        // Check if the date matches any fixed holiday
        $monthDay = $date->format('m-d');
        if (in_array($monthDay, $fixedHolidays)) {
            return true;
        }
        
        // Good Friday (Easter-based holiday) - simplified approach for demo
        // In a real app, you might want to use a more accurate calculation or API
        $easterSunday = Carbon::createFromTimestamp(easter_date($date->year));
        $goodFriday = $easterSunday->copy()->subDays(2);
        
        if ($date->isSameDay($goodFriday)) {
            return true;
        }
        
        // Special non-working holidays (add any special holidays for current year)
        $specialHolidays = [
            // Format: 'YYYY-MM-DD'
            '2023-02-25', // EDSA People Power Revolution Anniversary
            '2023-11-27', // Special Holiday (add relevant ones)
            // Add more as needed for each year
            '2024-02-25', // EDSA People Power Revolution Anniversary
            '2024-11-25', // Special Holiday
        ];
        
        if (in_array($date->format('Y-m-d'), $specialHolidays)) {
            return true;
        }
        
        return false;
    }

    public static function form(Form $form): Form
    {
        $minDate = Carbon::now();

        return $form
            ->schema([
                Forms\Components\Section::make('Interview Schedule Details')
                    ->description('Set up interview date, time and capacity')
                    ->schema([
                        Forms\Components\Select::make('campus_id')
                            ->relationship('campus', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Campus'),
                            
                        Forms\Components\DatePicker::make('interview_date')
                            ->required()
                            ->minDate($minDate)
                            ->helperText('Interviews can only be scheduled starting from today')
                            ->live()
                            ->validationAttribute('Interview date')
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('start_time', null);
                                $set('end_time', null);
                            }),

                        Forms\Components\TimePicker::make('start_time')
                            ->required()
                            ->unique()
                            ->seconds(false)
                            ->label('Start Time')
                            ->format('h:i A')
                            ->displayFormat('h:i A')
                            ->hoursStep(1)
                            ->minutesStep(1)
                            ->validationAttribute('Start time')
                            ->live()
                            ->closeOnDateSelection(false),

                        Forms\Components\TimePicker::make('end_time')
                            ->required()
                            ->unique()
                            ->seconds(false)
                            ->label('End Time')
                            ->format('h:i A')
                            ->displayFormat('h:i A')
                            ->hoursStep(1)
                            ->minutesStep(1)
                            ->validationAttribute('End time')
                            ->live()
                            ->closeOnDateSelection(false),

                        Forms\Components\TextInput::make('capacity')
                            ->label('Available Slots')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(30)
                            ->required()
                            ->validationAttribute('Capacity')
                            ->helperText('Total applicants allowed for this interview schedule'),

                        Forms\Components\TextInput::make('venue')
                            ->required()
                            ->label('Venue'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('interview_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->time('g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->state(function (InterviewSchedule $record): string {
                        // Get the number of applicants assigned to this schedule
                        $usedCapacity = $record->applicantInterviewSchedules()->count();
                        $totalCapacity = $record->capacity;
                        
                        return "{$usedCapacity} / {$totalCapacity}";
                    })
                    ->description(function (InterviewSchedule $record): ?string {
                        // Calculate remaining slots
                        $usedCapacity = $record->applicantInterviewSchedules()->count();
                        $remainingSlots = $record->capacity - $usedCapacity;
                        
                        if ($remainingSlots <= 0) {
                            return 'No slots available';
                        }
                        
                        return "{$remainingSlots} slots available";
                    })
                    ->color(function (InterviewSchedule $record): string {
                        // Color based on capacity usage
                        $usedCapacity = $record->applicantInterviewSchedules()->count();
                        $totalCapacity = $record->capacity;
                        
                        if ($usedCapacity >= $totalCapacity) {
                            return 'danger'; // Red when full
                        }
                        
                        if ($usedCapacity >= ($totalCapacity * 0.8)) {
                            return 'warning'; // Yellow when almost full (80% or more)
                        }
                        
                        return 'success'; // Green when plenty of slots available
                    })
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('venue')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('View Interview Schedule')
                        ->form([
                            Forms\Components\Section::make('Interview Schedule Information')
                                ->schema([
                                    Forms\Components\DatePicker::make('interview_date')
                                        ->disabled(),
                                    Forms\Components\TimePicker::make('start_time')
                                        ->seconds(false)
                                        ->label('Start Time')
                                        ->disabled(),
                                    Forms\Components\TimePicker::make('end_time')
                                        ->seconds(false)
                                        ->label('End Time')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('venue')
                                        ->label('Venue')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('capacity')
                                        ->label('Available Slots')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('assigned_applicants_count')
                                        ->label('Assigned Applicants')
                                        ->disabled()
                                        ->formatStateUsing(function ($record) {
                                            return \App\Models\ApplicantInterviewSchedule::where('interview_schedule_id', $record->id)->count();
                                        }),
                                ])->columns(2),
                        ]),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Interview Schedule Deleted')
                                ->body('The interview schedule has been deleted successfully.')
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ApplicantInterviewSchedulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterviewSchedules::route('/'),
            'create' => Pages\CreateInterviewSchedule::route('/create'),
            'edit' => Pages\EditInterviewSchedule::route('/{record}/edit'),
        ];
    }
    
    /**
     * Assign queued applicants to the newly created interview schedule
     * 
     * @param InterviewSchedule $schedule The newly created schedule
     * @return int Number of applicants assigned
     */
    public static function assignQueuedApplicants(InterviewSchedule $schedule): int
    {
        // Find all applicants with status 'for interview' that don't have an interview schedule
        $queuedApplicants = Applicant::where('status', 'for interview')
            ->whereDoesntHave('applicantInterviewSchedules', function ($query) use ($schedule) {
                $query->where('interview_schedule_id', $schedule->id);
            })
            ->get();
            
        // Count how many slots are available
        $usedCapacity = ApplicantInterviewSchedule::where('interview_schedule_id', $schedule->id)->count();
        $availableSlots = $schedule->capacity - $usedCapacity;
        
        if ($availableSlots <= 0) {
            return 0; // No slots available
        }
        
        // Determine how many we can assign
        $applicantsToAssign = min($queuedApplicants->count(), $availableSlots);
        $assignedCount = 0;
        
        // Assign applicants up to the capacity limit
        foreach ($queuedApplicants->take($availableSlots) as $applicant) {
            // Check if the applicant already has another interview schedule
            $existingSchedule = ApplicantInterviewSchedule::where('applicant_id', $applicant->id)->first();
            
            if ($existingSchedule) {
                // Update the existing record
                $existingSchedule->interview_schedule_id = $schedule->id;
                $existingSchedule->status = 'Scheduled';
                $existingSchedule->save();
            } else {
                // Create new record
                ApplicantInterviewSchedule::create([
                    'applicant_id' => $applicant->id,
                    'interview_schedule_id' => $schedule->id,
                    'status' => 'Scheduled'
                ]);
            }
            
            $assignedCount++;
            
            // Notify the applicant about the interview schedule
            Notification::make()
                ->title('Applicant Assigned to Interview')
                ->body($applicant->first_name . ' ' . $applicant->last_name . ' has been assigned to an interview on ' . $schedule->interview_date->format('M d, Y') . '.')
                ->sendToDatabase($applicant->user);
        }
        
        return $assignedCount;
    }
}
