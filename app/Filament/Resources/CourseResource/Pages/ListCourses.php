<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ExportAction;
use Illuminate\Support\Facades\Response;
use App\Models\Course;
use Filament\Forms;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\IconPosition;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New course')
                ->color('success')
                ->icon('heroicon-o-plus'),
                
            Actions\Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return CourseResource::getExportResponse();
                }),
                
            Actions\Action::make('import')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->size(ActionSize::Large)
                ->iconPosition(IconPosition::Before)
                ->modalWidth('xl')
                ->modalHeading('Import Courses')
                ->modalDescription('Upload a CSV file containing course information. All courses will be associated with the selected academic year.')
                ->modalIcon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\Group::make([
                        Forms\Components\Select::make('academic_year_id')
                            ->relationship('academicYear', 'name')
                            ->label('Academic Year')
                            ->default(fn () => \App\Models\AcademicYear::where('is_active', true)->first()?->id)
                            ->preload()
                            ->searchable()
                            ->required(),
                            
                        Forms\Components\FileUpload::make('csv_file')
                            ->label('CSV File')
                            ->disk('public')
                            ->directory('csv-imports')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                            ->required()
                            ->helperText('Required columns: Program, Code, Course, Unit, Type, Prerequisite, Level, Semester. Note: Courses will only be merged if they have identical CODE, COURSE, LEVEL, SEMESTER, and UNIT values. Otherwise, they will be created as separate entries.')
                            ->columnSpanFull(),
                            
                        Forms\Components\Tabs::make('import_help')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Format Guide')
                                    ->icon('heroicon-m-information-circle')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Placeholder::make('program')
                                                    ->label('PROGRAM')
                                                    ->content('Program code(s) with commas (BSIT,BSCS)'),
                                                    
                                                Forms\Components\Placeholder::make('code')
                                                    ->label('CODE')
                                                    ->content('Course code (e.g., IT101)'),
                                                    
                                                Forms\Components\Placeholder::make('course')
                                                    ->label('COURSE')
                                                    ->content('Full course name'),
                                                    
                                                Forms\Components\Placeholder::make('unit')
                                                    ->label('UNIT')
                                                    ->content('Number of units (1-10)'),
                                                    
                                                Forms\Components\Placeholder::make('type')
                                                    ->label('TYPE')
                                                    ->content('GEN_ED, TECH_SKILL, or CAP_OJT'),
                                                    
                                                Forms\Components\Placeholder::make('prereq')
                                                    ->label('PREREQUISITE')
                                                    ->content('Prereq course codes with commas'),
                                                    
                                                Forms\Components\Placeholder::make('level')
                                                    ->label('LEVEL')
                                                    ->content('Year level (1, 2, 3, or 4)'),
                                                    
                                                Forms\Components\Placeholder::make('semester')
                                                    ->label('SEMESTER')
                                                    ->content('Semester (1, 2, or summer)'),
                                            ]),
                                    ]),
                                    
                                Forms\Components\Tabs\Tab::make('Example')
                                    ->icon('heroicon-m-code-bracket')
                                    ->schema([
                                        Forms\Components\Placeholder::make('example')
                                            ->content('BSIT,BSCS,IT101,Introduction to Computing,3,GEN_ED,,1,1')
                                            ->extraAttributes(['class' => 'font-mono text-sm']),
                                            
                                        Forms\Components\Placeholder::make('explanation')
                                            ->content('This example creates a course "Introduction to Computing" with code "IT101" that belongs to both BSIT and BSCS programs, is a GEN_ED type course with 3 units in 1st year, 1st semester, with no prerequisites.'),
                                    ]),
                            ]),
                    ]),
                    
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('download_sample')
                            ->label('Download Sample CSV')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('gray')
                            ->button()
                            ->action(function () {
                                return CourseResource::getDownloadSampleResponse();
                            }),
                    ])->alignment('center'),
                ])
                ->action(function (array $data) {
                    return CourseResource::processImport($data);
                }),
        ];
    }
} 