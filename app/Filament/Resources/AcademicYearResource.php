<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicYearResource\Pages;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class AcademicYearResource extends Resource
{
    protected static ?string $model = AcademicYear::class;

    // protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
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
                Forms\Components\Section::make('Academic Year Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Auto-generated from start and end dates')
                            ->dehydrated()
                            ->readonly()
                            ->unique(ignoreRecord: true)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                            }),
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Get $get, Forms\Set $set) {
                                if ($operation !== 'create' || !$state || !$get('end_date')) {
                                    return;
                                }
                                
                                $startYear = date('Y', strtotime($state));
                                $endYear = date('Y', strtotime($get('end_date')));
                                
                                $set('name', "{$startYear}-{$endYear}");
                            }),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->after('start_date')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Get $get, Forms\Set $set) {
                                if ($operation !== 'create' || !$state || !$get('start_date')) {
                                    return;
                                }
                                
                                $startYear = date('Y', strtotime($get('start_date')));
                                $endYear = date('Y', strtotime($state));
                                
                                $set('name', "{$startYear}-{$endYear}");
                            }),
                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->helperText('Only one academic year can be active at a time. Setting this to active will deactivate other academic years.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
           
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('Academic Year Information')
                        ->modalIcon('heroicon-o-calendar')
                        ->modalWidth('lg')
                        ->color('gray')
                        ->infolist(fn (Infolist $infolist): Infolist => $infolist
                            ->schema([
                                Infolists\Components\Section::make('Academic Year Details')
                                    ->description('Academic year information')
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Academic Year')
                                            ->icon('heroicon-o-calendar')
                                            ->weight('bold')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('start_date')
                                                    ->label('Start Date')
                                                    ->date()
                                                    ->icon('heroicon-o-calendar-days')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                    
                                                Infolists\Components\TextEntry::make('end_date')
                                                    ->label('End Date')
                                                    ->date()
                                                    ->icon('heroicon-o-calendar-days')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            ]),
                                            
                                        Infolists\Components\TextEntry::make('is_active')
                                            ->label('Status')
                                            ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                                            ->icon('heroicon-o-circle-stack')
                                            ->badge()
                                            ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\TextEntry::make('duration')
                                            ->label('Duration')
                                            ->state(function ($record): string {
                                                $start = \Carbon\Carbon::parse($record->start_date);
                                                $end = \Carbon\Carbon::parse($record->end_date);
                                                $months = $start->diffInMonths($end);
                                                return $months . ' ' . str('month')->plural($months);
                                            })
                                            ->icon('heroicon-o-clock')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                    ])
                                    ->collapsible()
                                    ->extraAttributes(['class' => 'bg-gray-950 border border-gray-800 rounded-xl p-6 shadow-lg']),
                            ])
                            ->extraAttributes(['class' => 'p-0 bg-gray-950'])
                        ),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->successNotificationTitle(fn(AcademicYear $record): string => "Academic year '{$record->name}' has been deleted successfully"),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotificationTitle("Selected academic years have been deleted successfully"),
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
            'index' => Pages\ListAcademicYears::route('/'),
            'create' => Pages\CreateAcademicYear::route('/create'),
            'edit' => Pages\EditAcademicYear::route('/{record}/edit'),
        ];
    }
} 