<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampusResource\Pages;
use App\Filament\Resources\CampusResource\RelationManagers;
use App\Models\Campus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class CampusResource extends Resource
{
    protected static ?string $model = Campus::class;

    // protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'System Management';

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
                Forms\Components\Section::make('Campus Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255),
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
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('programs_count')
                    ->label('Programs')
                    ->counts('programs'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('Campus Information')
                        ->modalIcon('heroicon-o-building-office')
                        ->modalWidth('lg')
                        ->color('gray')
                        ->infolist(fn (Infolist $infolist): Infolist => $infolist
                            ->schema([
                                Infolists\Components\Section::make('Campus Details')
                                    ->description('Campus information and programs')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Campus Name')
                                            ->icon('heroicon-o-building-library')
                                            ->weight('bold')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\TextEntry::make('code')
                                            ->label('Campus Code')
                                            ->icon('heroicon-o-identification')
                                            ->copyable()
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\TextEntry::make('programs_count')
                                            ->label('Total Programs')
                                            ->state(fn ($record) => $record->programs->count())
                                            ->icon('heroicon-o-academic-cap')
                                            ->badge()
                                            ->color('info')
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
            RelationManagers\PrimaryEmployeesRelationManager::class,
            RelationManagers\ProgramsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampuses::route('/'),
            'create' => Pages\CreateCampus::route('/create'),
            'edit' => Pages\EditCampus::route('/{record}/edit'),
        ];
    }
}
