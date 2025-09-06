# Migration Reorganization Guide

## Overview

This directory contains Laravel migration files for the CSMS application. The migrations have been reorganized to:

1. Fix inconsistent naming patterns
2. Combine related migrations
3. Fix future-dated migrations (2025 dates)
4. Consolidate modifications to the same tables
5. Remove redundant add/update/delete migrations
6. Fix foreign key constraints by ensuring correct migration order

## Migration Order

The migrations are now organized in the following order:

1. Core Laravel tables (users, permissions, etc.)
2. Base tables (academic_years, campuses, departments, etc.)
3. Location-related tables (provinces, cities, barangays)
4. Program-related tables (program_categories, programs, courses)
5. Student and applicant tables
6. Enrollment and room tables
7. Supporting feature tables (exams, interviews, etc.)

## Key Consolidated Migrations

The following migrations have been consolidated:

1. `2014_10_12_000000_create_users_table.php` - Core Laravel user table
2. `2024_07_17_00000*_create_*_table.php` - Location tables (provinces, cities, barangays)
3. `2024_07_18_000000_create_programs_and_courses_tables.php` - Combined program and course tables
4. `2024_07_18_100000_create_students_table.php` - Combined all student-related fields
5. `2024_07_20_000000_create_applicants_table.php` - Combined all applicant-related fields
6. `2024_07_21_000000_create_student_enrollments_table.php` - Combined enrollment and related tables
7. `2024_07_21_100000_create_rooms_table.php` - Creates rooms for exams and classes
8. `2024_07_22_000000_create_exam_schedules_table.php` - Combined exam schedule related tables
9. `2024_07_23_000000_create_interview_schedules_table.php` - Consolidated interview schedules
10. `2024_07_24_000000_add_last_login_at_to_users_table.php` - Adds last login tracking
11. `2024_07_25_*` - System tables (notifications, jobs, imports, exports, etc.)

## Changes Made

1. Deleted 50+ individual migrations that modified existing tables
2. Renamed all migrations with 2025 dates to use proper sequential 2024 dates
3. Combined related tables into single migrations
4. Ensured proper relationship order to avoid foreign key issues
5. Standardized enum fields and column names
6. Fixed migration order to ensure proper foreign key constraint creation

## Execution Steps

1. Back up your database before running any migrations
2. If you're setting up a fresh installation, simply run:
   ```
   php artisan migrate
   ```
   
3. If you're updating an existing installation with previously run migrations:
   ```
   php artisan migrate:fresh
   ```
   (Note: This will drop all tables and re-create them - ensure you have a backup first)

## Potential Issues

- The consolidation may require adjustment of existing application code that references tables modified in this reorganization.
- Any Eloquent models may need to be updated to reflect the new table structure.
- Existing seeders may need to be updated to match the new schema.

## Additional Information

- The reorganization maintains all foreign key relationships
- Table and column comments have been preserved where relevant
- Enum options have been standardized across related tables 