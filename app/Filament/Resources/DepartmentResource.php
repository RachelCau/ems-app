<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use App\Models\Program;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\DB;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    // protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'Academic Management';
    
    protected static ?int $navigationSort = 5; 

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Department Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('programs')
                            ->relationship('programs', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->optionsLimit(100)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('program_category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\MultiSelect::make('campus_id')
                                    ->label('Campuses')
                                    ->options(function() {
                                        return \App\Models\Campus::pluck('name', 'id');
                                    })
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(1000),
                            ])
                            ->helperText('Select the programs that belong to this department')
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('program_count')
                    ->badge()
                    ->label('Programs')
                    ->getStateUsing(function ($record) {
                        return $record->programs->pluck('name')->all();
                    })
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->listWithLineBreaks(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('Department Information')
                        ->modalIcon('heroicon-o-building-office-2')
                        ->modalWidth('lg')
                        ->color('gray')
                        ->infolist(fn (Infolist $infolist): Infolist => $infolist
                            ->schema([
                                Infolists\Components\Section::make('Department Details')
                                    ->description('Department information')
                                    ->icon('heroicon-o-building-office-2')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Department Name')
                                            ->icon('heroicon-o-building-office-2')
                                            ->weight('bold')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\TextEntry::make('programs_list')
                                            ->label('Programs')
                                            ->getStateUsing(function ($record) {
                                                return $record->programs->pluck('name')->all();
                                            })
                                            ->listWithLineBreaks()
                                            ->limitList(5)
                                            ->expandableLimitedList()
                                            ->icon('heroicon-o-academic-cap')
                                            ->color('gray')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('programs')
            ->with('programs:id,name'); // Only select needed fields
    }
} 