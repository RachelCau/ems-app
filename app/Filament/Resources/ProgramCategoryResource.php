<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramCategoryResource\Pages;
use App\Models\ProgramCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ProgramCategoryResource extends Resource
{
    protected static ?string $model = ProgramCategory::class;

    // protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 4;

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Program Category Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000),
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
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
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
                        ->modalHeading('Program Category Information')
                        ->modalIcon('heroicon-o-tag')
                        ->modalWidth('lg')
                        ->color('gray')
                        ->infolist(
                            fn(Infolist $infolist): Infolist => $infolist
                                ->schema([
                                    Infolists\Components\Section::make('Program Category Details')
                                        ->description('Program category information')
                                        ->icon('heroicon-o-tag')
                                        ->schema([
                                            Infolists\Components\Grid::make(2)
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('name')
                                                        ->label('Category Name')
                                                        ->icon('heroicon-o-tag')
                                                        ->weight('bold')
                                                        ->color('gray')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                                    Infolists\Components\TextEntry::make('programs_count')
                                                        ->label('Total Programs')
                                                        ->state(fn($record) => $record->programs->count())
                                                        ->icon('heroicon-o-academic-cap')
                                                        ->badge()
                                                        ->color('info')
                                                        ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50'])
                                                ]),

                                            Infolists\Components\TextEntry::make('description')
                                                ->label('Description')
                                                ->icon('heroicon-o-document-text')
                                                ->markdown()
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
            'index' => Pages\ListProgramCategories::route('/'),
            'create' => Pages\CreateProgramCategory::route('/create'),
            'edit' => Pages\EditProgramCategory::route('/{record}/edit'),
        ];
    }
}
