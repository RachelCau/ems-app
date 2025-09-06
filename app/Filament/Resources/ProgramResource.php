<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramResource\Pages;
use App\Filament\Resources\ProgramResource\RelationManagers;
use App\Models\Program;
use App\Models\Campus;
use App\Models\ProgramCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    // protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?int $navigationSort = 2;

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Program Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(225),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('campus_id')
                            ->label('Campuses')
                            ->relationship('campuses', 'name')
                            ->multiple()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(10),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return Campus::create($data)->id;
                            })
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('xl');
                            }),
                        Forms\Components\Select::make('program_category_id')
                            ->label('Program Category')
                            ->options(ProgramCategory::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return ProgramCategory::create($data)->id;
                            })
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('xl');
                            }),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('campuses.name')
                    ->label('Campuses')
                    ->listWithLineBreaks()
                    ->limitList(3),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('Program Information')
                        ->modalIcon('heroicon-o-academic-cap')
                        ->modalWidth('2xl')
                        ->color('gray')
                        ->infolist(
                            fn(Infolist $infolist): Infolist => $infolist
                                ->schema([
                                    Infolists\Components\Section::make('Program Details')
                                        ->description('Program information')
                                        ->icon('heroicon-o-academic-cap')
                                        ->schema([
                                            Infolists\Components\TextEntry::make('campuses.name')
                                                ->label('Campuses')
                                                ->listWithLineBreaks()
                                                ->bulleted()
                                                ->icon('heroicon-o-building-library')
                                                ->color('gray')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                                Infolists\Components\Grid::make(2)
                                                ->schema([
                                            Infolists\Components\TextEntry::make('category.name')
                                                ->label('Program Category')
                                                ->icon('heroicon-o-tag')
                                                ->color('gray')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                            Infolists\Components\TextEntry::make('code')
                                                ->label('Program Code')
                                                ->icon('heroicon-o-identification')
                                                ->copyable()
                                                ->color('gray')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50'])
                                                ]),

                                            Infolists\Components\TextEntry::make('name')
                                                ->label('Program Name')
                                                ->icon('heroicon-o-academic-cap')
                                                ->weight('bold')
                                                ->color('gray')
                                                ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

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
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }
}
