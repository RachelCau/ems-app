<?php

namespace App\Filament\Resources\ApplicantResource\Pages;

use App\Filament\Resources\ApplicantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ApplicantResource\RelationManagers;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewApplicant extends ViewRecord
{
    protected static string $resource = ApplicantResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Applicant Information')
                    ->description('Personal details of the applicant')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('applicant_number')
                                    ->label('Applicant number')
                                    ->icon('heroicon-o-identification')
                                    ->copyable()
                                    ->copyMessage('Applicant number copied')
                                    ->copyMessageDuration(1500)
                                    ->weight('bold')
                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50'])
                                    ->columnSpan(3),

                                Infolists\Components\TextEntry::make('campus.name')
                                    ->label('Campus')
                                    ->icon('heroicon-o-building-office')
                                    ->color('gray')
                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                Infolists\Components\TextEntry::make('academicYear.name')
                                    ->label('Academic year')
                                    ->icon('heroicon-o-calendar')
                                    ->color('gray')
                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                            ]),

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

                                Infolists\Components\TextEntry::make('suffix')
                                    ->label('Suffix')
                                    ->default('N/A')
                                    ->color('gray')
                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                            ]),

                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->copyMessage('Email copied')
                            ->copyMessageDuration(1500)
                            ->color('gray')
                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                            
                            Infolists\Components\Grid::make(2)
                            ->schema([
                            Infolists\Components\TextEntry::make('program_category')
                            ->label('Program category')
                            ->icon('heroicon-o-academic-cap')
                            ->formatStateUsing(function ($state) {
                                // Check if the value is an ID
                                if (is_numeric($state)) {
                                    // Try to find the program category by ID
                                    $category = \App\Models\ProgramCategory::find($state);
                                    return $category ? $category->name : $state;
                                }
                                
                                // If it's already a string or other value, return as is
                                return $state;
                            })
                            ->color('gray')
                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                        Infolists\Components\TextEntry::make('desired_program')
                            ->label('Desired program') 
                            ->icon('heroicon-o-academic-cap')
                            ->color('gray')
                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                        ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Application status')
                                    ->badge()
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                    ->icon(function (string $state): string {
                                        return match ($state) {
                                            'pending' => 'heroicon-o-clock',
                                            'approved' => 'heroicon-o-check-circle',
                                            'for entrance exam' => 'heroicon-o-academic-cap',
                                            'for interview' => 'heroicon-o-chat-bubble-left-right',
                                            'for enrollment' => 'heroicon-o-clipboard-document-check',
                                            'declined' => 'heroicon-o-x-circle',
                                            default => 'heroicon-o-question-mark-circle',
                                        };
                                    })
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'gray',
                                        'approved' => 'success',
                                        'for entrance exam' => 'info',
                                        'for interview' => 'warning',
                                        'for enrollment' => 'success',
                                        'declined' => 'danger',
                                        default => 'gray',
                                    })
                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Applied on')
                                    ->dateTime()
                                    ->icon('heroicon-o-clock')
                                    ->color('gray')
                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                            ])
                    ])
                    ->collapsible()
                    ->extraAttributes(['class' => 'bg-gray-950 border border-gray-800 rounded-xl p-6 shadow-lg']),
            ])
            ->extraAttributes(['class' => 'p-0 bg-gray-950']);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getRelationManagers(): array
    {
        return [
            RelationManagers\AdmissionDocumentsRelationManager::class,
        ];
    }
}
