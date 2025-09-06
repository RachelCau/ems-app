<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\AcademicYear;
// use App\Models\Admission;
use App\Models\AdmissionDocument;
use App\Models\Applicant;
use App\Models\Campus;
use App\Models\Course;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EnrolledCourse;
use App\Models\ExamSchedule;
use App\Models\Office;
use App\Models\Program;
use App\Models\ProgramCategory;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Models\ExamQuestion;
use App\Policies\AcademicYearPolicy;
use App\Policies\AdmissionDocumentPolicy;
// use App\Policies\AdmissionPolicy;
use App\Policies\ApplicantPolicy;
use App\Policies\CampusPolicy;
use App\Policies\CoursePolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\EnrolledCoursePolicy;
use App\Policies\ExamSchedulePolicy;
use App\Policies\OfficePolicy;
use App\Policies\ProgramCategoryPolicy;
use App\Policies\ProgramPolicy;
use App\Policies\RolePolicy;
use App\Policies\StudentEnrollmentPolicy;
use App\Policies\StudentPolicy;
use App\Policies\ExamQuestionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AcademicYear::class => AcademicYearPolicy::class,
        // Admission::class => AdmissionPolicy::class,
        AdmissionDocument::class => AdmissionDocumentPolicy::class,
        Applicant::class => ApplicantPolicy::class,
        Campus::class => CampusPolicy::class,
        Course::class => CoursePolicy::class,
        Department::class => DepartmentPolicy::class,
        Employee::class => EmployeePolicy::class,
        EnrolledCourse::class => EnrolledCoursePolicy::class,
        ExamSchedule::class => ExamSchedulePolicy::class,
        ExamQuestion::class => ExamQuestionPolicy::class,
        Office::class => OfficePolicy::class,
        Program::class => ProgramPolicy::class,
        ProgramCategory::class => ProgramCategoryPolicy::class,
        Role::class => RolePolicy::class,
        Student::class => StudentPolicy::class,
        StudentEnrollment::class => StudentEnrollmentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        
        // Register the super-admin role with all permissions
        Gate::before(function (User $user, $ability) {
            if ($user->hasRole('super admin')) {
                return true;
            }
            
            return null;
        });
    }
}
