<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\EnrolledCourse;

class EnrolledCoursesWidget extends TableWidget
{
    // Make widget appear high on the dashboard
    protected static ?int $sort = 5;

    protected static ?string $pollingInterval = '120s';

    // Eager load for better UX
    protected static bool $isLazy = false;

    // Make it full width with higher priority
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 'full',
        'lg' => 'full',
        'xl' => 'full',
    ];

    // Eager load relationships when widget is initialized
    public function mount(): void
    {
        // Get the authenticated user
        $user = Auth::user();
        
        // Ensure employee and role relationship is loaded
        if ($user && $user->user_type === 'employee') {
            // Get a fresh instance with loaded relationships
            $user = \App\Models\User::with('employee.role')->find($user->id);
            
            // Update the auth instance with the loaded relationships
            Auth::setUser($user);
        }
    }

    protected function getTableHeading(): string
    {
        return 'Enrolled Courses';
    }

    protected function getTableSubheading(): ?string
    {
        if (Auth::user()->user_type === 'admin' || Auth::user()->user_type === 'super admin') {
            return 'View all student course enrollments';
        } elseif (Auth::user()->user_type === 'employee') {
            return 'View all student enrollments';
        } else {
            return 'Your current course enrollments';
        }
    }

    public function table(Table $table): Table
    {
        $table = $table
            ->query($this->getTableQuery())
            ->defaultPaginationPageOption(10)
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-book-open')
            ->emptyStateHeading('No enrolled courses found')
            ->emptyStateDescription(
                Auth::user()->user_type === 'student'
                    ? 'Contact your registrar if you believe this is an error'
                    : null
            );

        // Determine which columns to show based on user type
        $columns = [
            Tables\Columns\TextColumn::make('course.code')
                ->label('Course Code')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('course.name')
                ->label('Course Name')
                ->sortable()
                ->searchable()
                ->wrap(),
        ];

        // Add student columns for admin and registrar users
        if (in_array(Auth::user()->user_type, ['admin', 'super admin', 'employee'])) {
            $columns[] = Tables\Columns\TextColumn::make('student_number')
                ->label('Student Number')
                ->sortable()
                ->searchable()
                ->fontFamily('mono');

            $columns[] = Tables\Columns\TextColumn::make('student.first_name')
                ->label('Student Name')
                ->formatStateUsing(function ($state, EnrolledCourse $record) {
                    if ($record->student) {
                        return $record->student->first_name . ' ' . $record->student->last_name;
                    }
                    return 'N/A';
                })
                ->searchable();
        }

        // Add status and grade columns
        $columns[] = Tables\Columns\TextColumn::make('status')
            ->badge()
            ->color(fn (string $state): string => match ($state) {
                'Active' => 'success',
                'Dropped' => 'danger',
                'Incomplete' => 'warning',
                default => 'info',
            })
            ->formatStateUsing(fn ($state) => $state ?? 'Pending')
            ->searchable();

        $table->columns($columns);

        // Add filters
        $table->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'Active' => 'Active',
                    'Dropped' => 'Dropped',
                    'Incomplete' => 'Incomplete',
                    'Pending' => 'Pending',
                ]),
        ]);

        return $table;
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();

        // For Admin or Registrar (show all courses)
        if (
            in_array($user->user_type, ['admin', 'super admin']) ||
            ($user->user_type === 'employee' && $this->isRegistrar($user))
        ) {
            return EnrolledCourse::with(['course', 'student']);
        }

        // For Students (filter by student number)
        if ($user->user_type === 'student' && $user->student) {
            return EnrolledCourse::with('course')
                ->where('student_number', $user->student->student_number);
        }

        // Default empty query if no permission
        return EnrolledCourse::query()->whereRaw('1 = 0');
    }

    /**
     * Check if the user is a registrar.
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    protected function isRegistrar($user)
    {
        if (!$user || !$user->employee || !$user->employee->role) {
            return false;
        }
        
        $roleName = strtolower($user->employee->role->name);
        return $roleName === 'registrar' ||
            $roleName === 'college registrar' ||
            $roleName === 'school registrar';
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        // Allow admin users to view the widget
        if ($user->user_type === 'admin' || $user->user_type === 'super admin') {
            return true;
        }

        // For Registrar (Employee user type with registrar role)
        if ($user->user_type === 'employee' && $user->employee && $user->employee->role) {
            $roleName = strtolower($user->employee->role->name);
            return $roleName === 'registrar' ||
                $roleName === 'college registrar' ||
                $roleName === 'school registrar';
        }

          // For Registrar (Employee user type with registrar role)
          if ($user->user_type === 'employee' && $user->employee && $user->employee->role) {
            $roleName = strtolower($user->employee->role->name);
            return $roleName === 'instructor';
        }
        
        // For Students
        return $user->user_type === 'student';
    }
}
