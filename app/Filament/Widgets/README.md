# Filament Widgets

This directory contains the Filament dashboard widgets used in the application.

## AdmissionsStatsOverview Widget

The AdmissionsStatsOverview widget displays statistics about the admissions process. Access to these statistics is controlled by user permissions as follows:

### Admission Officer (Edit Applicants Permission)
Can view:
- Applicants For Entrance Exam
- Approved Applicants
- Pending Applicants
- Declined Applicants

### Program Head (View Programs Permission)
Can view:
- Applicants For Entrance Exam
- Applicants For Interview

### MIS and Admin (View Students Permission)
Can view all statistics, including:
- Enrolled Students

### Permission Requirements
- To view the widget at all: Need either `view admissions` AND (`view applicants` OR `view students`)
- For Admission Officer stats: Need `edit applicants` permission
- For Program Head stats: Need `view programs` permission
- For Enrolled Students stats: Need `view students` permission

## Chart Widgets

There are two chart widgets in the dashboard that show program enrollment statistics:

### ChedProgramsChart 
Shows enrollment distribution across CHED programs.

### TesdaProgramsChart
Shows enrollment distribution across TESDA programs.

Both chart widgets can be viewed by:
- Program Head (view programs permission)
- MIS and Admin users (view admissions and view applicants/students permission)

## Implementation Notes

1. The widgets use Laravel's Gate facade to check permissions in the `canView()` method and within the `getStats()` method.

2. For Program Head users, a special direct return is implemented to ensure both stats cards are displayed properly.

3. Care is taken to avoid duplicating stats when a user has multiple roles/permissions.

4. The Admin and MIS users have access to all statistics, ensuring they have complete oversight. 