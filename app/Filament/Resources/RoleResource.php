<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    // protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('name', '!=', 'super admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Role Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('permissions')
                            ->relationship('permissions', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->searchDebounce(500)
                            ->placeholder('Select or search permissions')
                            ->helperText('Select multiple permissions by typing or clicking')
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('Role Information')
                        ->modalIcon('heroicon-o-key')
                        ->modalWidth('xl')
                        ->color('gray')
                        ->infolist(fn (Infolist $infolist): Infolist => $infolist
                            ->schema([
                                Infolists\Components\Section::make('Role Details')
                                    ->description('Role information and permissions')
                                    ->icon('heroicon-o-key')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Role Name')
                                            ->icon('heroicon-o-tag')
                                            ->weight('bold')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\TextEntry::make('permissions_count')
                                            ->label('Total Permissions')
                                            ->state(fn ($record) => $record->permissions->count())
                                            ->icon('heroicon-o-shield-check')
                                            ->badge()
                                            ->color('success')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\TextEntry::make('permissions.name')
                                            ->label('Assigned Permissions')
                                            ->listWithLineBreaks()
                                            ->bulleted()
                                            ->icon('heroicon-o-lock-closed')
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
} 