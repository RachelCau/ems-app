<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\Office;
use App\Models\Role;
use App\Models\Campus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    // protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'User Management';
    
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
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Hidden::make('user_id'),
                        Forms\Components\TextInput::make('employee_number')
                            ->label('Employee ID')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('This will be auto-generated after selecting a campus')
                            ->hiddenOn('create')
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\EmployeeResource\Pages\EditEmployee),
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
                            ->label('Email Address')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // Get email from related User model if record exists
                                if ($record && $record->user) {
                                    $component->state($record->user->email);
                                }
                            })
                            ->dehydrated(true),
                        Forms\Components\Select::make('sex')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female'
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('mobile_number')
                            ->required()
                            ->maxLength(11),
                        Forms\Components\Textarea::make('address'),
                    ])->columns(2),

                Forms\Components\Section::make('Employment Details')
                    ->schema([
                        Forms\Components\Select::make('employee_type')
                            ->options([
                                'permanent' => 'Permanent',
                                'casual' => 'Casual',
                                'cos' => 'COS',
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
                            ])
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('xl');
                            }),
                        Forms\Components\Select::make('office_id')
                            ->relationship('office', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('xl');
                            }),
                        Forms\Components\Select::make('role_id')
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
                            ])
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('xl');
                            }),
                        Forms\Components\Select::make('campus_id')
                            ->relationship('campus', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('xl');
                            }),
                        Forms\Components\Select::make('employee_status')
                            ->options([
                                'active' => 'Active',
                                'resigned' => 'Resigned',
                                'retired' => 'Retired',
                                'terminated' => 'Terminated',
                            ])
                            ->default('active')
                            ->required()
                            ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\EmployeeResource\Pages\EditEmployee),
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->disk('avatars')
                            ->directory('employees')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_number')
                    ->searchable()
                    ->sortable()
                    ->label('Employee ID'),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Name')
                    ->formatStateUsing(fn (string $state, $record): string => 
                        $record->last_name . ', ' . $record->first_name)
                    ->searchable(['last_name', 'first_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('campus.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee_type')
                    ->label('Employee Type')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee_status')
                    ->label('Employee Status')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'resigned' => 'warning',
                        'retired' => 'gray',
                        'terminated' => 'danger',
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name'),
                Tables\Filters\SelectFilter::make('campus')
                    ->relationship('campus', 'name'),
                Tables\Filters\SelectFilter::make('employee_type')
                    ->options([
                        'permanent' => 'Permanent',
                        'casual' => 'Casual',
                        'cos' => 'COS',
                    ]),
                Tables\Filters\SelectFilter::make('employee_status')
                    ->options([
                        'active' => 'Active',
                        'resigned' => 'Resigned',
                        'retired' => 'Retired',
                        'terminated' => 'Terminated',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('Employee Information')
                        ->modalIcon('heroicon-o-user')
                        ->modalWidth('2xl')
                        ->color('gray')
                        ->infolist(fn (Infolist $infolist): Infolist => $infolist
                            ->schema([
                                Infolists\Components\Section::make('Employee Information')
                                    ->description('Personal details of the employee')
                                    ->icon('heroicon-o-user')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('employee_number')
                                            ->label('Employee ID')
                                            ->icon('heroicon-o-identification')
                                            ->copyable()
                                            ->copyMessage('Employee ID copied')
                                            ->copyMessageDuration(1500)
                                            ->weight('bold')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
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
                                                    
                                                Infolists\Components\TextEntry::make('sex')
                                                    ->label('Sex')
                                                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            ]),
                                            
                                        Infolists\Components\TextEntry::make('user.email')
                                            ->label('Email Address')
                                            ->icon('heroicon-o-envelope')
                                            ->copyable()
                                            ->copyMessage('Email copied')
                                            ->copyMessageDuration(1500)
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\TextEntry::make('mobile_number')
                                            ->label('Mobile Number')
                                            ->icon('heroicon-o-device-phone-mobile')
                                            ->copyable()
                                            ->copyMessage('Mobile number copied')
                                            ->copyMessageDuration(1500)
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\TextEntry::make('address')
                                            ->label('Address')
                                            ->icon('heroicon-o-map-pin')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                    ])
                                    ->collapsible(),
                                    
                                Infolists\Components\Section::make('Employment Details')
                                    ->description('Work-related information')
                                    ->icon('heroicon-o-briefcase')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('department.name')
                                                    ->label('Department')
                                                    ->icon('heroicon-o-building-office-2')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                    
                                                Infolists\Components\TextEntry::make('office.name')
                                                    ->label('Office')
                                                    ->icon('heroicon-o-building-office')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                    
                                                Infolists\Components\TextEntry::make('role.name')
                                                    ->label('Role')
                                                    ->icon('heroicon-o-key')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                
                                                Infolists\Components\TextEntry::make('campus.name')
                                                    ->label('Campus')
                                                    ->icon('heroicon-o-building-library')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            ]),
                                            
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('employee_type')
                                                    ->label('Employee Type')
                                                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                                                    ->icon('heroicon-o-user-group')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                    
                                                Infolists\Components\TextEntry::make('employee_status')
                                                    ->label('Status')
                                                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                                                    ->badge()
                                                    ->icon('heroicon-o-circle-stack')
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'active' => 'success',
                                                        'resigned' => 'warning',
                                                        'retired' => 'gray',
                                                        'terminated' => 'danger',
                                                        default => 'gray',
                                                    })
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            ]),
                                            
                                        Infolists\Components\ImageEntry::make('avatar')
                                            ->label('Profile Image')
                                            ->disk('avatars')
                                            ->height(150)
                                            ->circular()
                                            ->defaultImageUrl(function ($record) {
                                                return 'https://ui-avatars.com/api/?name=' . urlencode($record->first_name . ' ' . $record->last_name) . '&color=FFFFFF&background=111827';
                                            })
                                            ->extraAttributes(['class' => 'flex justify-center']),
                                    ])
                                    ->collapsible(),
                            ])
                            ->extraAttributes(['class' => 'p-0 bg-gray-950'])
                        ),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            // Delete the associated user if it exists
                            if ($record->user) {
                                $record->user->delete();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Delete the associated users if they exist
                            foreach ($records as $record) {
                                if ($record->user) {
                                    $record->user->delete();
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }
} 