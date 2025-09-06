<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create academic years
        $academicYears = [
            [
                'name' => '2023-2024',
                'start_date' => Carbon::create(2023, 8, 1),
                'end_date' => Carbon::create(2024, 7, 31),
                'is_active' => false,
            ],
            [
                'name' => '2024-2025',
                'start_date' => Carbon::create(2024, 8, 1),
                'end_date' => Carbon::create(2025, 7, 31),
                'is_active' => true, // Current active academic year
            ],
            [
                'name' => '2025-2026',
                'start_date' => Carbon::create(2025, 8, 1),
                'end_date' => Carbon::create(2026, 7, 31),
                'is_active' => false,
            ],
        ];

        // Insert each academic year into the database
        foreach ($academicYears as $yearData) {
            AcademicYear::updateOrCreate(
                ['name' => $yearData['name']],
                $yearData
            );
        }
    }
} 