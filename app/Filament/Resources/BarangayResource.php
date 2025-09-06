<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangayResource\Pages;
use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BarangayResource extends Resource
{
    protected static ?string $model = Barangay::class;
    
    protected static ?string $navigationGroup = 'System Management';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('province_id')
                    ->label('Province')
                    ->options(Province::pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('city_id', null)),
                
                Forms\Components\Select::make('city_id')
                    ->label('City/Municipality')
                    ->options(function (callable $get) {
                        $provinceId = $get('province_id');
                        
                        if (!$provinceId) {
                            return City::pluck('name', 'id');
                        }
                        
                        return City::where('province_id', $provinceId)->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable(),
                
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('code')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('city.province.name')
                    ->label('Province')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('city.name')
                    ->label('City/Municipality')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                    
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
                Tables\Filters\SelectFilter::make('city')
                    ->relationship('city.province', 'name')
                    ->label('Province'),
                
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('City/Municipality')
                    ->options(City::pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBarangays::route('/'),
            'create' => Pages\CreateBarangay::route('/create'),
            'view' => Pages\ViewBarangay::route('/{record}'),
            'edit' => Pages\EditBarangay::route('/{record}/edit'),
        ];
    }
} 