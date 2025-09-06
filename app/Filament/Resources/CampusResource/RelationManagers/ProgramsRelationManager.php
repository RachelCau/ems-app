<?php

namespace App\Filament\Resources\CampusResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ProgramCategory;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProgramsRelationManager extends RelationManager
{
    protected static string $relationship = 'programs';
    
    protected static ?string $recordTitleAttribute = 'name';
    
    protected static ?string $title = 'Programs';
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set is_primary flag for newly created programs
        $data['is_primary'] = true;
        
        return $data;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('program_category_id')
                    ->label('Program Category')
                    ->options(ProgramCategory::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    protected function getTableRecordsPerPageSelectOptions(): array 
    {
        return [10, 25, 50];
    }
    
    protected function getTableRecordsCount(): ?int
    {
        return Program::whereJsonContains('campus_id', $this->getOwnerRecord()->id)->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\IconColumn::make('pivot.is_primary')
                    ->label('Primary')
                    ->boolean(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Indicate this is a primary campus when creating via the relation manager
                        $data['pivot'] = [
                            'is_primary' => true,
                        ];
                        
                        return $data;
                    }),
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primary Campus')
                            ->default(false),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
} 