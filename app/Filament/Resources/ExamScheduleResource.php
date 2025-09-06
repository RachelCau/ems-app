<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamScheduleResource\Pages;
use App\Filament\Resources\ExamScheduleResource\RelationManagers;
use App\Models\ExamSchedule;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\ApplicantExamSchedule;
use Illuminate\Support\Facades\DB;
use App\Models\Applicant;

class ExamScheduleResource extends Resource
{
    protected static ?string $model = ExamSchedule::class;

    protected static ?string $navigationGroup = 'Application Management';

    protected static ?int $navigationSort = 3;

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        $minDate = Carbon::now()->addDays(2);

        return $form
            ->schema([
                Forms\Components\Section::make('Exam Schedule Information')
                    ->schema([
                        Forms\Components\DatePicker::make('exam_date')
                            ->required()
                            ->minDate($minDate)
                            ->helperText('You can only schedule exams starting from 2 days from today')
                            ->live(),

                        Forms\Components\TimePicker::make('start_time')
                            ->required()
                            ->unique()
                            ->seconds(false)
                            ->label('Start Time')
                            ->before('end_time')
                            ->rules(['after_or_equal:08:00', 'before:20:00'])
                            ->validationMessages([
                                'after_or_equal' => 'Exams can only be scheduled after 8:00 AM',
                                'before' => 'Exams cannot be scheduled after 8:00 PM',
                            ])
                            ->live(),

                        Forms\Components\TimePicker::make('end_time')
                            ->required()
                            ->unique()
                            ->seconds(false)
                            ->label('End Time')
                            ->after('start_time')
                            ->rules(['after:start_time', 'before_or_equal:20:00'])
                            ->validationMessages([
                                'after' => 'End time must be after start time',
                                'before_or_equal' => 'Exams must end by 8:00 PM',
                            ])
                            ->live(),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('room_id')
                                    ->label('Examination Room')
                                    ->options(function () {
                                        return Room::where('is_available', true)
                                            ->where('is_active', true)
                                            ->get()
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $room = Room::find($state);
                                            if ($room) {
                                                $set('room_capacity', $room->capacity);
                                                $set('capacity', $room->capacity);
                                            } else {
                                                $set('room_capacity', null);
                                                $set('capacity', null);
                                            }
                                        } else {
                                            $set('room_capacity', null);
                                            $set('capacity', null);
                                        }
                                    })
                                    ->relationship('room', 'name')
                                    ->helperText('Select an examination room for this schedule'),

                                Forms\Components\TextInput::make('room_capacity')
                                    ->label('Maximum Capacity')
                                    ->disabled()
                                    ->suffix('applicants')
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('capacity')
                                    ->label('Available Slots')
                                    ->integer()
                                    ->minValue(1)
                                    ->required()
                                    ->helperText('Total applicants allowed for this exam schedule'),
                            ])
                            ->columns(3)
                            ->columnSpan(3),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exam_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('g:i A')
                    ->label('Start Time')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->time('g:i A')
                    ->label('End Time')
                    ->sortable(),
                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->state(function (ExamSchedule $record): string {
                        // Force a fresh query to get the latest count
                        $usedCapacity = ApplicantExamSchedule::where('exam_schedule_id', $record->id)->count();
                        $totalCapacity = $record->capacity ?: ($record->room->capacity ?? 0);

                        return "{$usedCapacity} / {$totalCapacity}";
                    })
                    ->description(function (ExamSchedule $record): ?string {
                        // Force a fresh query to get the latest count
                        $usedCapacity = ApplicantExamSchedule::where('exam_schedule_id', $record->id)->count();
                        $remainingSlots = ($record->capacity ?: ($record->room->capacity ?? 0)) - $usedCapacity;
                        if ($remainingSlots <= 0) {
                            return 'No slots available';
                        }
                        return "{$remainingSlots} slots available";
                    })
                    ->color(function (ExamSchedule $record): string {
                        // Force a fresh query to get the latest count
                        $usedCapacity = ApplicantExamSchedule::where('exam_schedule_id', $record->id)->count();
                        $totalCapacity = $record->capacity ?: ($record->room->capacity ?? 0);

                        if ($usedCapacity >= $totalCapacity) {
                            return 'danger';
                        }

                        if ($usedCapacity >= ($totalCapacity * 0.8)) {
                            return 'warning';
                        }

                        return 'success';
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('View Exam Schedule')
                        ->form([
                            Forms\Components\Section::make('Exam Schedule Information')
                                ->schema([
                                    Forms\Components\DatePicker::make('exam_date')
                                        ->disabled(),
                                    Forms\Components\TimePicker::make('start_time')
                                        ->seconds(false)
                                        ->label('Start Time')
                                        ->disabled(),
                                    Forms\Components\TimePicker::make('end_time')
                                        ->seconds(false)
                                        ->label('End Time')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('room')
                                        ->label('Room')
                                        ->formatStateUsing(function ($record) {
                                            return $record->room->name ?? 'No room assigned';
                                        })
                                        ->disabled(),
                                    Forms\Components\TextInput::make('capacity')
                                        ->label('Available Slots')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('approved_applicants_count')
                                        ->label('Assigned Applicants')
                                        ->disabled()
                                        ->formatStateUsing(function ($record) {
                                            return ApplicantExamSchedule::where('exam_schedule_id', $record->id)->count();
                                        }),
                                ])->columns(2),
                        ]),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Exam Schedule Deleted')
                                ->body('The exam schedule has been deleted successfully.')
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Exam Schedules Deleted')
                                ->body('The selected exam schedules have been deleted successfully.')
                        ),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ApplicantExamSchedulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamSchedules::route('/'),
            'create' => Pages\CreateExamSchedule::route('/create'),
            'edit' => Pages\EditExamSchedule::route('/{record}/edit'),
            'assign-applicants' => Pages\AssignApplicants::route('/{record}/assign-applicants'),
        ];
    }

    // Add custom validation to check for overlapping exam schedules
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        static::validateRoomAvailability($data);
        return $data;
    }

    public static function mutateFormDataBeforeUpdate(array $data, $record): array
    {
        static::validateRoomAvailability($data, $record->id);
        return $data;
    }

    protected static function validateRoomAvailability(array $data, $recordId = null): void
    {
        if (empty($data['exam_date']) || empty($data['start_time']) || empty($data['end_time']) || empty($data['room_id'])) {
            return;
        }

        // Check for overlapping exam schedules in the same room
        $query = ExamSchedule::query()
            ->where('room_id', $data['room_id'])
            ->where('exam_date', $data['exam_date'])
            ->where(function (Builder $query) use ($data) {
                // Find schedules where time periods overlap
                $query->where(function (Builder $query) use ($data) {
                    $query->where('start_time', '<', $data['end_time'])
                        ->where('end_time', '>', $data['start_time']);
                });
            });

        // Exclude the current record if editing
        if ($recordId) {
            $query->where('id', '!=', $recordId);
        }

        // Check if there's an overlapping schedule
        $overlappingSchedule = $query->first();

        if ($overlappingSchedule) {
            throw new \Exception('This room is already booked during this time period on the selected date');
        }
    }
}
