<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\MaxWidth;
use App\Filament\Widgets\AdmissionsStatsOverview;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = 1;
    
    // Make the title dynamic based on user role
    public function getTitle(): string 
    {
        $user = Auth::user();
        
        if ($user && $user->roles->contains('name', 'Admission Officer')) {
            return 'Admission Dashboard';
        }
        
        if ($user && $user->user_type === 'student') {
            return 'Student Dashboard';
        }
        
        return 'Dashboard';
    }
    
    protected function getHeaderWidgets(): array
    {
        $user = Auth::user();
        
        // For MIS Officer - show all statistics and widgets
        if ($user && $user->roles->contains('name', 'MIS Officer')) {
            return [
                \App\Filament\Widgets\AdmissionsStatsOverview::class,
                \App\Filament\Widgets\ChedProgramsChart::class,
                \App\Filament\Widgets\TesdaProgramsChart::class,
                \App\Filament\Widgets\EnrolledCoursesWidget::class,
            ];
        }
        
        // For Admission Officer role
        if ($user && $user->roles->contains('name', 'Admission Officer')) {
            return [
                \App\Filament\Widgets\AdmissionsStatsOverview::class,
            ];
        }
        
        // For Registrar role - show program charts and enrolled courses widget
        if ($user && $user->roles->contains('name', 'Registrar')) {
            return [
                \App\Filament\Widgets\ChedProgramsChart::class,
                \App\Filament\Widgets\TesdaProgramsChart::class,
                \App\Filament\Widgets\EnrolledCoursesWidget::class,
            ];
        }
        
        return [];
    }
    
    protected function getFooterWidgets(): array
    {
        return [];
    }
    
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    
    public function getEnrollments()
    {
        $user = Auth::user();
        
        if (!$user || $user->user_type !== 'student' || !$user->student) {
            return collect();
        }
        
        return StudentEnrollment::with(['academicYear', 'program', 'campus', 'enrolledCourses.course'])
            ->where('student_id', $user->student->id)
            ->latest()
            ->get();
    }
    
    protected function getHeaderActions(): array
    {
        return [];
    }
    
    protected function getViewData(): array
    {
        return [
            'currentEnrollment' => $this->getEnrollments()->first(),
            'enrollmentHistory' => $this->getEnrollments()->skip(1),
        ];
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        
        // Show for students, Admission Officers, Registrars, and MIS Officers
        if ($user && (
            $user->user_type === 'student' || 
            $user->roles->contains('name', 'Admission Officer') ||
            $user->roles->contains('name', 'Registrar') ||
            $user->roles->contains('name', 'MIS Officer')
        )) {
            return true;
        }
        
        return false;
    }
} 