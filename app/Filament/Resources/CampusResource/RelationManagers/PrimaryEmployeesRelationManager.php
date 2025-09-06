<?php

namespace App\Filament\Resources\CampusResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Office;
use App\Events\EmployeeCreated;
use Filament\Notifications\Notification;

class PrimaryEmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'primaryEmployees';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('employee_number')
                            ->required()
                            ->maxLength(7)
                            ->label('Employee ID'),
                        Forms\Components\Hidden::make('user_id')
                            ->dehydrated(true)
                            ->default(function () {
                                return User::max('id') + 1;
                            }),
                        Forms\Components\Hidden::make('user_password')
                            ->dehydrated(false)
                            ->default(function () {
                                return Str::random(8);
                            }),
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('middle_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('sex')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female'
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mobile_number')
                            ->required()
                            ->maxLength(11),
                        Forms\Components\Textarea::make('address')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\Select::make('employee_type')
                            ->options([
                                'teaching' => 'Teaching',
                                'non-teaching' => 'Non-Teaching',
                                'both' => 'Both',
                            ])
                            ->required(),
                        Forms\Components\Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Select::make('office_id')
                            ->relationship('office', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Select::make('role_id')
                            ->label('Role')
                            ->relationship('role', 'name', function (Builder $query) {
                                return $query->where('name', '!=', 'super admin');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('permissions')
                                    ->relationship('permissions', 'name')
                                    ->multiple()
                                    ->preload(),
                            ]),
                        Forms\Components\Select::make('employee_status')
                            ->options([
                                'active' => 'Active',
                                'resigned' => 'Resigned',
                                'retired' => 'Retired',
                                'terminated' => 'Terminated',
                            ])
                            ->required()
                            ->default('active')
                            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\RelationManagers\RelationManager && !$livewire->isCreating()),
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->directory('employees-avatars')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Create a new user with the generated password
        $password = \Illuminate\Support\Str::random(8);
        $email = $data['email'];
        
        $user = \App\Models\User::create([
            'email' => $email,
            'password' => bcrypt($password),
            'user_type' => 'employee',
        ]);
        
        // Set the user_id on the employee record
        $data['user_id'] = $user->id;
        
        // Force employee_status to active
        $data['employee_status'] = 'active';
        
        // Store password temporarily to access in afterCreate
        session()->flash('temp_password', $password);
        
        return $data;
    }
    
    protected function afterCreate(): void 
    {
        if (session()->has('temp_password')) {
            $password = session('temp_password');
            // Fresh load the employee with its relations
            $employee = $this->record->fresh(['user']);
            
            // Log before dispatching
            \Illuminate\Support\Facades\Log::info('About to dispatch EmployeeCreated event from RelationManager', [
                'employee_id' => $employee->id,
                'email' => $employee->user->email,
            ]);
            
            try {
                // Dispatch the event
                event(new \App\Events\EmployeeCreated($employee, $password));
                
                \Illuminate\Support\Facades\Log::info('EmployeeCreated event dispatched successfully from RelationManager');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to dispatch EmployeeCreated event from RelationManager', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            \Filament\Notifications\Notification::make()
                ->title('Employee Created')
                ->body('Employee created successfully. An email with credentials has been sent to the employee.')
                ->success()
                ->send();
                
            // Clear the password from the session
            session()->forget('temp_password');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->defaultImageUrl(fn () => asset('images/placeholder.jpg'))
                    ->circular(),
                Tables\Columns\TextColumn::make('employee_number')
                    ->searchable()
                    ->sortable()
                    ->label('Employee ID'),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee_type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'on-leave' => 'warning',
                        'resigned' => 'danger',
                        'retired' => 'gray',
                        'terminated' => 'danger',
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name'),
                Tables\Filters\SelectFilter::make('employee_type')
                    ->options([
                        'teaching' => 'Teaching',
                        'non-teaching' => 'Non-Teaching',
                        'both' => 'Both',
                    ]),
                Tables\Filters\SelectFilter::make('employee_status')
                    ->options([
                        'active' => 'Active',
                        'on-leave' => 'On Leave',
                        'resigned' => 'Resigned',
                        'retired' => 'Retired',
                        'terminated' => 'Terminated',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
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
} 