<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamQuestionResource\Pages;
use App\Models\ExamQuestion;
use App\Models\ExamSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Str;

/**
 * Exam Questions Feature
 * 
 * NOTE: This feature is currently disabled and scheduled for future updates.
 * The exam question functionality will be implemented in a future release.
 */
class ExamQuestionResource extends Resource
{
    protected static ?string $model = ExamQuestion::class;

    protected static ?string $navigationGroup = 'Application Management';

    protected static ?int $navigationSort = 5;
    
    // Remove the hardcoded label
    //protected static ?string $navigationLabel = 'Exam Questions (Coming Soon)';

    // Add this method to control navigation display based on feature flag
    public static function shouldRegisterNavigation(): bool
    {
        // If Feature facade isn't available yet (during app bootstrap), default to true
        if (!class_exists('\App\Facades\Feature')) {
            return true;
        }
        
        return true; // Always register, but we'll show it as disabled if the feature is disabled
    }
    
    // Override the navigation item to show the feature status
    public static function getNavigationItems(): array
    {
        // If Feature facade isn't available yet, use parent method
        if (!class_exists('\App\Facades\Feature')) {
            return parent::getNavigationItems();
        }
        
        // Create navigation using our feature utility
        return [
            \App\Filament\Utilities\FeatureNavigation::createItem(
                'exam_questions',
                static::getNavigationLabel(),
                [
                    'icon' => static::getNavigationIcon(),
                    'isActiveWhen' => fn (): bool => request()->routeIs(static::getRouteBaseName() . '.*'),
                    'badge' => static::getNavigationBadge(),
                    'badgeColor' => static::getNavigationBadgeColor(),
                    'group' => static::getNavigationGroup(),
                ]
            )
        ];
    }
    
    // Update the badge method to show count when enabled
    public static function getNavigationBadge(): ?string
    {
        if (class_exists('\App\Facades\Feature') && \App\Facades\Feature::isEnabled('exam_questions')) {
            return static::$model::count();
        }
        
        return 'Coming Soon';
    }
    
    // Make the navigation item look different to indicate it's disabled
    public static function getNavigationBadgeColor(): ?string
    {
        if (class_exists('\App\Facades\Feature') && \App\Facades\Feature::isEnabled('exam_questions')) {
            return 'success';
        }
        
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Select::make('exam_type')
                            ->label('Exam Type')
                            ->options([
                                'multiple_choice' => 'Multiple Choice',
                                'true_false' => 'True/False',
                                'identification' => 'Identification',
                                'essay' => 'Essay Question',
                            ])
                            ->required()
                            ->default('multiple_choice')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // When exam type changes, update all existing questions
                                $questions = $get('questions') ?? [];
                                $updatedQuestions = [];

                                foreach ($questions as $index => $question) {
                                    // Update the type for each question
                                    $question['type'] = $state;

                                    // Reset choices based on the new type
                                    if ($state === 'multiple_choice') {
                                        $question['choices'] = [
                                            ['option' => '', 'is_correct' => false, 'letter' => 'A'],
                                            ['option' => '', 'is_correct' => false, 'letter' => 'B'],
                                            ['option' => '', 'is_correct' => false, 'letter' => 'C'],
                                            ['option' => '', 'is_correct' => false, 'letter' => 'D']
                                        ];
                                    } elseif ($state === 'true_false') {
                                        $question['choices'] = [
                                            ['option' => 'True', 'is_correct' => false, 'letter' => 'A'],
                                            ['option' => 'False', 'is_correct' => false, 'letter' => 'B']
                                        ];
                                    } elseif ($state === 'identification') {
                                        // For identification, set empty answer
                                        $question['answer'] = '';
                                    } elseif ($state === 'essay') {
                                        // For essay, set empty guidelines
                                        $question['answer_guidelines'] = '';
                                    }

                                    // Reset the correct answer
                                    $question['correct_answer'] = '';

                                    $updatedQuestions[$index] = $question;
                                }

                                if (!empty($updatedQuestions)) {
                                    $set('questions', $updatedQuestions);
                                }
                            })
                            ->columnSpan(1),

                        TextInput::make('no_items')
                            ->label('No. of Items')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Ensure we have a numeric value before calculations
                                $items = is_numeric($state) ? (int)$state : 1;

                                // Recalculate passing score based on percentage
                                $gradePercentage = $get('grade_percentage');
                                $gradePercentage = is_numeric($gradePercentage) ? (float)$gradePercentage : 75;

                                // Calculate passing score safely
                                $passingScore = ceil(($gradePercentage / 100) * $items);
                                $set('passing_score', $passingScore);
                                $set('grade', $gradePercentage / 100);

                                // Get exam type
                                $examType = $get('exam_type') ?? 'multiple_choice';

                                // Generate questions based on number of items
                                if ($items > 0) {
                                    $questions = [];
                                    for ($i = 0; $i < $items; $i++) {
                                        $questionData = [
                                            'question_number' => $i + 1,
                                            'question' => '',
                                            'type' => $examType,
                                            'correct_answer' => '',
                                        ];

                                        if ($examType === 'multiple_choice') {
                                            $questionData['choices'] = [
                                                ['option' => '', 'is_correct' => false, 'letter' => 'A'],
                                                ['option' => '', 'is_correct' => false, 'letter' => 'B'],
                                                ['option' => '', 'is_correct' => false, 'letter' => 'C'],
                                                ['option' => '', 'is_correct' => false, 'letter' => 'D']
                                            ];
                                        } elseif ($examType === 'true_false') {
                                            $questionData['choices'] = [
                                                ['option' => 'True', 'is_correct' => false, 'letter' => 'A'],
                                                ['option' => 'False', 'is_correct' => false, 'letter' => 'B']
                                            ];
                                        } elseif ($examType === 'identification') {
                                            $questionData['answer'] = '';
                                        } elseif ($examType === 'essay') {
                                            $questionData['answer_guidelines'] = '';
                                        }

                                        $questions[] = $questionData;
                                    }
                                    $set('questions', $questions);
                                } else {
                                    $set('questions', []);
                                }
                            })
                            ->columnSpan(1),

                        TextInput::make('grade_percentage')
                            ->label('Passing Grade Percentage')
                            ->numeric()
                            ->suffix('%')
                            ->default(75)
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Ensure we have a numeric value
                                $percentage = is_numeric($state) ? (float)$state : 75;

                                // Update grade (stored as decimal)
                                $set('grade', $percentage / 100);

                                // Update passing score based on percentage
                                $noItems = $get('no_items');
                                $noItems = is_numeric($noItems) ? (int)$noItems : 1;

                                $passingScore = ceil(($percentage / 100) * $noItems);
                                $set('passing_score', $passingScore);
                            })
                            ->afterStateHydrated(function (TextInput $component, callable $get) {
                                $grade = $get('grade');

                                // Set a default value if grade is not numeric or is empty
                                if (!is_numeric($grade) || $grade === null) {
                                    $component->state(75);
                                } else {
                                    $component->state((float)$grade * 100);
                                }
                            })
                            ->columnSpan(1),

                        TextInput::make('passing_score')
                            ->label('Passing Score')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->helperText('Auto-calculated: Passing Grade % × No. of Items')
                            ->columnSpan(1),

                        Hidden::make('grade')
                            ->default(0.75)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Status')
                            ->default(true)
                            ->helperText('Enable or disable this exam question')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('exam_schedule_id')
                            ->label('Exam Schedule')
                            ->relationship('examSchedule', 'exam_date', function ($query) {
                                return $query->orderBy('exam_date', 'desc');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1)
                            ->createOptionForm([
                                Forms\Components\DatePicker::make('exam_date')
                                    ->required(),
                                Forms\Components\TimePicker::make('exam_time')
                                    ->required(),
                                Forms\Components\TextInput::make('venue')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('remarks')
                                    ->maxLength(1000),
                            ]),
                    ])->columns(3),

                Section::make('Questions')
                    ->schema([
                        Repeater::make('questions')
                            ->label(false)
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                // Ensure each question has a unique question_number
                                if (is_array($state)) {
                                    foreach ($state as $index => $question) {
                                        $set("questions.{$index}.question_number", $index + 1);
                                    }
                                }
                            })
                            ->schema([
                                Hidden::make('question_number')
                                    ->default(function (callable $get, $record, $state) {
                                        // Get all questions
                                        $allQuestions = $get('../../questions') ?? [];

                                        // Find the index of the current question
                                        foreach ($allQuestions as $index => $question) {
                                            if ($question === $state) {
                                                return $index + 1;
                                            }
                                        }

                                        return 1; // Fallback
                                    }),

                                Hidden::make('type')
                                    ->default(function (callable $get) {
                                        return $get('../../exam_type') ?? 'multiple_choice';
                                    }),

                                TextInput::make('question')
                                    ->label(function (callable $get) {
                                        // Use the question_number field for the label
                                        $number = $get('question_number') ?: 1;
                                        return 'Question ' . $number;
                                    })
                                    ->required(),

                                // Multiple Choice Questions
                                Group::make()
                                    ->schema([
                                        Section::make('Multiple Choice Options')
                                            ->label('Multiple Choice Options')
                                            ->schema([
                                                Repeater::make('choices')
                                                    ->label('Options')
                                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                        // Get the correct answer for this question
                                                        $correctAnswer = $get('../correct_answer');

                                                        if ($correctAnswer && is_array($state)) {
                                                            // Check each choice to see if it matches the correct answer
                                                            foreach ($state as $index => $choice) {
                                                                if (isset($choice['letter']) && isset($choice['option'])) {
                                                                    $choiceAnswer = $choice['letter'] . '. ' . $choice['option'];
                                                                    if ($choiceAnswer === $correctAnswer) {
                                                                        $set("{$index}.is_correct", true);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    })
                                                    ->schema([
                                                        Hidden::make('letter'),

                                                        TextInput::make('option')
                                                            ->label(function ($get, $record, $context) {
                                                                // Use the letter field for labeling
                                                                $letter = $get('letter');
                                                                return $letter . '. Option';
                                                            })
                                                            ->required()
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get, $context) {
                                                                // If this option is marked as correct, update the correct_answer field
                                                                if ($get("is_correct")) {
                                                                    $set("../../correct_answer", $get('letter') . '. ' . (string)$state);
                                                                }
                                                            }),

                                                        Forms\Components\Checkbox::make('is_correct')
                                                            ->label('Correct Answer')
                                                            ->live(onBlur: true)
                                                            ->dehydrated(true)
                                                            ->afterStateHydrated(function ($state, Forms\Components\Checkbox $component, callable $set, callable $get) {
                                                                // Check if this option matches the correct answer
                                                                $correctAnswer = $get('../../correct_answer');
                                                                if ($correctAnswer) {
                                                                    $letter = $get('letter');
                                                                    $option = $get('option');
                                                                    if ($option) {
                                                                        $expectedAnswer = $letter . '. ' . $option;

                                                                        // If this is the correct answer, check the box
                                                                        if ($correctAnswer === $expectedAnswer) {
                                                                            $component->state(true);
                                                                        }
                                                                    }
                                                                }
                                                            })
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get, $context) {
                                                                if ($state) {
                                                                    // Uncheck other options
                                                                    $choices = $get('../../choices');
                                                                    foreach ($choices as $index => $choice) {
                                                                        if ($index !== $context) {
                                                                            $set("../../choices.{$index}.is_correct", false);
                                                                        }
                                                                    }

                                                                    // Update the correct_answer field
                                                                    $option = $get('option');
                                                                    if ($option) {
                                                                        $set("../../correct_answer", $get('letter') . '. ' . (string)$option);
                                                                    }
                                                                }
                                                            }),
                                                    ])
                                                    ->columns(2)
                                                    ->itemLabel(function ($get, $context) {
                                                        // Use the letter field we added
                                                        return $get('letter');
                                                    })
                                                    ->maxItems(4)
                                                    ->minItems(4)
                                                    ->disableItemCreation()
                                                    ->disableItemDeletion()
                                                    ->disableItemMovement(),

                                                TextInput::make('correct_answer')
                                                    ->label('Correct Answer')
                                                    ->disabled()
                                                    ->dehydrated(true)
                                                    ->helperText('This will be automatically updated when you select a correct option above'),
                                            ]),
                                    ])
                                    ->visible(function (callable $get) {
                                        $questionType = $get('type');
                                        $examType = $get('../../exam_type');
                                        return ($questionType === 'multiple_choice' && $examType === 'multiple_choice');
                                    }),

                                // True/False Questions    
                                Group::make()
                                    ->schema([
                                        Section::make('True/False Question')
                                            ->schema([
                                                Select::make('correct_answer')
                                                    ->label('Select Correct Answer')
                                                    ->options([
                                                        'A. True' => 'True',
                                                        'B. False' => 'False',
                                                    ])
                                                    ->required()
                                                    ->live()
                                                    ->dehydrated(true)
                                            ]),
                                    ])
                                    ->visible(function (callable $get) {
                                        $questionType = $get('type');
                                        $examType = $get('../../exam_type');
                                        return ($questionType === 'true_false' && $examType === 'true_false');
                                    }),

                                // Identification Questions
                                Group::make()
                                    ->schema([
                                        Section::make('Identification Question')
                                            ->schema([
                                                TextInput::make('answer')
                                                    ->label('Correct Answer')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->dehydrated(true)
                                            ]),
                                    ])
                                    ->visible(function (callable $get) {
                                        $questionType = $get('type');
                                        $examType = $get('../../exam_type');
                                        return ($questionType === 'identification' && $examType === 'identification');
                                    }),

                                // Essay Questions
                                Group::make()
                                    ->schema([
                                        Section::make('Essay Question')
                                            ->schema([
                                                Textarea::make('answer_guidelines')
                                                    ->label('Answer Guidelines/Rubric')
                                                    ->placeholder('Enter guidelines for scoring this essay question')
                                                    ->rows(3)
                                            ]),
                                    ])
                                    ->visible(function (callable $get) {
                                        $questionType = $get('type');
                                        $examType = $get('../../exam_type');
                                        return ($questionType === 'essay' && $examType === 'essay');
                                    }),
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->collapsible()
                            ->itemLabel(function (array $state) {
                                $questionNumber = $state['question_number'] ?? '?';
                                $answer = $state['correct_answer'] ?? $state['answer'] ?? '';
                                $answerText = $answer ? ' - Answer: ' . $answer : '';
                                return 'Question ' . $questionNumber . $answerText;
                            })
                            ->columns(1),
                    ])->columnSpanFull(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('examSchedule.exam_date')
                    ->label('Exam Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('examSchedule.venue')
                    ->label('Venue')
                    ->searchable(),

                Tables\Columns\TextColumn::make('exam_type')
                    ->label('Exam Type')
                    ->formatStateUsing(
                        fn(string $state): string =>
                        match ($state) {
                            'multiple_choice' => 'Multiple Choice',
                            'true_false' => 'True/False',
                            'identification' => 'Identification',
                            'essay' => 'Essay Question',
                            default => ucfirst($state)
                        }
                    )
                    ->badge()
                    ->color(
                        fn(string $state): string =>
                        match ($state) {
                            'multiple_choice' => 'primary',
                            'true_false' => 'success',
                            'identification' => 'warning',
                            'essay' => 'info',
                            default => 'gray'
                        }
                    ),

                Tables\Columns\TextColumn::make('no_items')
                    ->label('Number of Items')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade')
                    ->label('Passing Grade')
                    ->numeric(2)
                    ->formatStateUsing(fn(float $state): string => ($state * 100) . '%')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Questions')
                    ->getStateUsing(fn(ExamQuestion $record): int => is_array($record->questions) ? count($record->questions) : 0)
                    ->badge(),

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
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('Exam Questions')
                        ->modalWidth('xl'),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Exam Schedule Information')
                    ->description('Details about this exam schedule')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('examSchedule.exam_date')
                                        ->label('Exam Date')
                                        ->icon('heroicon-o-calendar')
                                        ->date()
                                        ->weight('bold'),
                                    Infolists\Components\TextEntry::make('examSchedule.exam_time')
                                        ->label('Exam Time')
                                        ->icon('heroicon-o-clock')
                                        ->time()
                                        ->color('primary'),
                                ]),

                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('examSchedule.venue')
                                        ->label('Venue')
                                        ->icon('heroicon-o-map-pin')
                                        ->weight('medium'),
                                    Infolists\Components\TextEntry::make('exam_type')
                                        ->label('Exam Type')
                                        ->formatStateUsing(
                                            fn(string $state): string =>
                                            match ($state) {
                                                'multiple_choice' => 'Multiple Choice',
                                                'true_false' => 'True/False',
                                                'identification' => 'Identification',
                                                'essay' => 'Essay Question',
                                                default => ucfirst($state)
                                            }
                                        )
                                        ->icon('heroicon-o-document-text')
                                        ->badge()
                                        ->color(
                                            fn(string $state): string =>
                                            match ($state) {
                                                'multiple_choice' => 'primary',
                                                'true_false' => 'success',
                                                'identification' => 'warning',
                                                'essay' => 'info',
                                                default => 'gray'
                                            }
                                        ),
                                ]),

                                Infolists\Components\Group::make([
                                    Infolists\Components\TextEntry::make('no_items')
                                        ->label('Number of Items')
                                        ->icon('heroicon-o-list-bullet')
                                        ->badge()
                                        ->size('lg')
                                        ->color('gray'),
                                    Infolists\Components\TextEntry::make('grade')
                                        ->label('Passing Grade')
                                        ->icon('heroicon-o-academic-cap')
                                        ->formatStateUsing(fn(float $state): string => ($state * 100) . '%')
                                        ->color('success')
                                        ->weight('bold'),
                                ]),
                            ]),

                        Infolists\Components\Group::make([
                            Infolists\Components\IconEntry::make('is_active')
                                ->label('Status')
                                ->icon('heroicon-o-check-circle')
                                ->boolean()
                                ->color(fn(bool $state): string => $state ? 'success' : 'danger')
                                ->size('xl'),
                        ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(false),

                Infolists\Components\Section::make('Questions')
                    ->description('All questions for this exam')
                    ->icon('heroicon-o-question-mark-circle')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('questions')
                            ->schema([
                                Infolists\Components\Grid::make(1)
                                    ->schema([
                                        Infolists\Components\Group::make([
                                            Infolists\Components\TextEntry::make('question_number')
                                                ->label('')
                                                ->formatStateUsing(fn($state) => "Question {$state}")
                                                ->icon('heroicon-o-queue-list')
                                                ->size('lg')
                                                ->weight('bold')
                                                ->color('primary'),

                                            Infolists\Components\TextEntry::make('question')
                                                ->label('')
                                                ->extraAttributes(['class' => 'text-lg px-4 py-2 my-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700']),

                                            Infolists\Components\TextEntry::make('type')
                                                ->label('Type')
                                                ->formatStateUsing(
                                                    fn(string $state): string =>
                                                    match ($state) {
                                                        'multiple_choice' => 'Multiple Choice',
                                                        'true_false' => 'True/False',
                                                        'identification' => 'Identification',
                                                        'essay' => 'Essay Question',
                                                        default => ucfirst($state)
                                                    }
                                                )
                                                ->badge()
                                                ->color(
                                                    fn(string $state): string =>
                                                    match ($state) {
                                                        'multiple_choice' => 'primary',
                                                        'true_false' => 'success',
                                                        'identification' => 'warning',
                                                        'essay' => 'info',
                                                        default => 'gray'
                                                    }
                                                )
                                                ->extraAttributes(['class' => 'mb-4']),
                                        ])
                                            ->extraAttributes(['class' => 'mb-4']),

                                        // Multiple Choice Options
                                        Infolists\Components\Group::make([
                                            Infolists\Components\RepeatableEntry::make('choices')
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('option')
                                                        ->label(fn($record) => $record['letter'] ?? '')
                                                        ->formatStateUsing(function ($state, $record) {
                                                            return ($record['is_correct'] ?? false)
                                                                ? "✓ {$state}"
                                                                : $state;
                                                        })
                                                        ->weight(fn($record) => ($record['is_correct'] ?? false) ? 'bold' : 'normal')
                                                        ->color(fn($record) => ($record['is_correct'] ?? false) ? 'success' : 'gray')
                                                        ->extraAttributes(fn($record) => [
                                                            'class' => ($record['is_correct'] ?? false)
                                                                ? 'px-3 py-2 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700'
                                                                : 'px-3 py-2'
                                                        ]),
                                                ])
                                                ->columns(2)
                                                ->extraAttributes(['class' => 'px-4 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700']),
                                        ])
                                            ->visible(fn($record) => ($record['type'] ?? '') === 'multiple_choice')
                                            ->extraAttributes(['class' => 'mt-2 mb-6']),

                                        // True/False Options
                                        Infolists\Components\Group::make([
                                            Infolists\Components\TextEntry::make('correct_answer')
                                                ->label('Correct Answer')
                                                ->icon('heroicon-o-check-circle')
                                                ->color('success')
                                                ->weight('bold')
                                                ->extraAttributes(['class' => 'px-4 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700']),
                                        ])
                                            ->visible(fn($record) => ($record['type'] ?? '') === 'true_false')
                                            ->extraAttributes(['class' => 'mt-2 mb-6']),

                                        // Identification Options
                                        Infolists\Components\Group::make([
                                            Infolists\Components\TextEntry::make('answer')
                                                ->label('Correct Answer')
                                                ->icon('heroicon-o-check-circle')
                                                ->color('warning')
                                                ->weight('bold')
                                                ->extraAttributes(['class' => 'px-4 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700']),
                                        ])
                                            ->visible(fn($record) => ($record['type'] ?? '') === 'identification')
                                            ->extraAttributes(['class' => 'mt-2 mb-6']),

                                        // Essay Options
                                        Infolists\Components\Group::make([
                                            Infolists\Components\TextEntry::make('answer_guidelines')
                                                ->label('Answer Guidelines')
                                                ->icon('heroicon-o-document-text')
                                                ->markdown()
                                                ->extraAttributes(['class' => 'px-4 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700']),
                                        ])
                                            ->visible(fn($record) => ($record['type'] ?? '') === 'essay')
                                            ->extraAttributes(['class' => 'mt-2 mb-6']),
                                    ]),
                            ])
                            ->extraAttributes(['class' => 'space-y-6 divide-y divide-gray-200 dark:divide-gray-800']),
                    ])
                    ->collapsible()
                    ->persistCollapsed(false),
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
            'index' => Pages\ListExamQuestions::route('/'),
            'create' => Pages\CreateExamQuestion::route('/create'),
            'view' => Pages\ViewExamQuestion::route('/{record}'),
            'edit' => Pages\EditExamQuestion::route('/{record}/edit'),
        ];
    }
}
