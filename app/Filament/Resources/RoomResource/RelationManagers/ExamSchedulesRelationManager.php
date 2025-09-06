<?php

namespace App\Filament\Resources\RoomResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExamSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'examSchedules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('exam_date')
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->required()
                    ->seconds(false)
                    ->label('Start Time'),
                Forms\Components\TimePicker::make('end_time')
                    ->required()
                    ->seconds(false)
                    ->label('End Time')
                    ->after('start_time'),
                Forms\Components\TextInput::make('venue')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('remarks')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('venue')
            ->columns([
                Tables\Columns\TextColumn::make('exam_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i')
                    ->label('Start Time')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->time('H:i')
                    ->label('End Time')
                    ->sortable(),
                Tables\Columns\TextColumn::make('venue')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 