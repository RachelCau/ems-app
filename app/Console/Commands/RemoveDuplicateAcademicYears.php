<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;

class RemoveDuplicateAcademicYears extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-duplicate-academic-years';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate academic years keeping only the oldest entry for each name';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding duplicate academic years...');
        
        // Get all academic years grouped by name
        $academicYears = DB::table('academic_years')
            ->select('name', DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();
            
        if ($academicYears->isEmpty()) {
            $this->info('No duplicate academic years found.');
            return 0;
        }
        
        $this->info('Found ' . $academicYears->count() . ' duplicate names. Removing duplicates...');
        
        // Process each duplicate name
        foreach ($academicYears as $year) {
            $this->info("Processing duplicate name: {$year->name}");
            
            // Get all entries with this name, ordered by ID (oldest first)
            $duplicates = DB::table('academic_years')
                ->where('name', $year->name)
                ->orderBy('id')
                ->get();
                
            // Keep the first/oldest record
            $keepId = $duplicates->first()->id;
            
            // Delete all other records
            $deleteCount = DB::table('academic_years')
                ->where('name', $year->name)
                ->where('id', '!=', $keepId)
                ->delete();
                
            $this->info("Kept ID: {$keepId}, Deleted: {$deleteCount} records");
        }
        
        $this->info('Duplicate academic years removed successfully.');
        return 0;
    }
}
