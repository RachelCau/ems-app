<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use App\Models\User;
use App\Models\Campus;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\ExportAction;
use App\Filament\Exports\StudentExport;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    // protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 1;

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(User::all()->map(function ($user) {
                                return [
                                    'id' => $user->id,
                                    'name' => $user->name ?? '',
                                ];
                            })->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->hidden(),
                        Forms\Components\TextInput::make('student_number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('middle_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('suffix')
                            ->maxLength(255),
                        Forms\Components\Select::make('sex')
                            ->options([
                                'male' => 'male',
                                'female' => 'female',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('mobile_number')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('campus_id')
                            ->label('Campus')
                            ->options(Campus::all()->map(function ($campus) {
                                return [
                                    'id' => $campus->id,
                                    'name' => $campus->name ?? '',
                                ];
                            })->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('program_code')
                            ->label('Program')
                            ->options(function () {
                                return \App\Models\Program::all()->pluck('code', 'code');
                            })
                            ->searchable()
                            ->helperText('Program code the student is enrolled in'),
                        Forms\Components\Select::make('student_status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'graduated' => 'Graduated',
                                'transferred' => 'Transferred',
                                'dropped' => 'Dropped',
                            ])
                            ->required()
                            ->default('active'),
                        Forms\Components\Select::make('academic_year_id')
                            ->label('Academic Year')
                            ->options(AcademicYear::pluck('name', 'id'))
                            ->required()
                            ->default(function () {
                                return AcademicYear::where('is_active', true)->first()?->id ??
                                    AcademicYear::latest('id')->first()?->id;
                            }),
                        Forms\Components\Select::make('year_level')
                            ->options([
                                1 => '1st Year',
                                2 => '2nd Year',
                                3 => '3rd Year',
                                4 => '4th Year',
                            ])
                            ->required()
                            ->default(1),
                        Forms\Components\Select::make('semester')
                            ->options([
                                1 => '1st Semester',
                                2 => '2nd Semester',
                                3 => 'Summer'
                            ])
                            ->required()
                            ->default(1),
                        Forms\Components\FileUpload::make('avatar')
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->image()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    \Illuminate\Support\Facades\Log::info('Avatar uploaded', [
                                        'filename' => $state,
                                        'state' => $state,
                                    ]);
                                }
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('first_name', $direction)
                            ->orderBy('last_name', $direction);
                    }),
                Tables\Columns\TextColumn::make('program_code')
                    ->label('Program')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->program) {
                            return $record->program->name . ' (' . $record->program_code . ')';
                        }
                        return $state ?: '—';
                    })
                    ->description(fn($record) => $record->program ? $record->program->code : null)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('academicYear.name')
                    ->label('Academic Year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('year_level')
                    ->label('Year Level')
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        1 => '1st Year',
                        2 => '2nd Year',
                        3 => '3rd Year',
                        4 => '4th Year',
                        default => $state . 'th Year',
                    }),
                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->formatStateUsing(fn(int $state): string => $state . (($state == 1) ? 'st' : 'nd') . ' Semester'),
                Tables\Columns\TextColumn::make('campus.name')
                    ->label('Campus')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('student_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'graduated' => 'info',
                        'transferred' => 'warning',
                        'dropped' => 'danger',
                    }),
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
                    Tables\Actions\Action::make('download_cor')
                        ->label('COR')
                        ->tooltip('Download Certificate of Registration')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->url(fn($record) => route('student.cor.pdf', ['id' => $record->id]))
                        ->openUrlInNewTab(),
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('Student Information')
                        ->modalIcon('heroicon-o-user')
                        ->modalWidth('2xl')
                        ->color('gray')
                        ->infolist(
                            fn(Infolist $infolist): Infolist => $infolist
                                ->schema([
                                    Infolists\Components\Section::make('Student Information')
                                        ->description('Personal details of the student')
                                        ->icon('heroicon-o-user')
                                        ->schema([
                                            Infolists\Components\TextEntry::make('student_number')
                                                ->label('Student ID')
                                                ->icon('heroicon-o-identification')
                                                ->copyable()
                                                ->copyMessage('Student ID copied')
                                                ->copyMessageDuration(1500)
                                                ->weight('bold')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                            Infolists\Components\TextEntry::make('full_name')
                                                ->label('Student Name')
                                                ->icon('heroicon-o-user')
                                                ->color('gray')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                            Infolists\Components\TextEntry::make('campus.name')
                                                ->label('Campus')
                                                ->icon('heroicon-o-building-library')
                                                ->color('gray')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                            Infolists\Components\TextEntry::make('student_status')
                                                ->label('Status')
                                                ->icon('heroicon-o-information-circle')
                                                ->badge()
                                                ->color(fn(string $state): string => match ($state) {
                                                    'active' => 'success',
                                                    'inactive' => 'gray',
                                                    'graduated' => 'info',
                                                    'transferred' => 'warning',
                                                    'dropped' => 'danger',
                                                })
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                        ])
                                        ->collapsible()
                                        ->extraAttributes(['class' => 'bg-gray-950 border border-gray-800 rounded-xl p-6 shadow-lg']),
                                ])
                                ->extraAttributes(['class' => 'p-0 bg-gray-950'])
                        ),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
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
            RelationManagers\EnrolledCoursesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
