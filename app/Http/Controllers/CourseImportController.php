<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CourseImportController extends Controller
{
    /**
     * Show the import form
     */
    public function showImportForm()
    {
        return view('courses.import-form');
    }

    /**
     * Process the import
     */
    public function import(Request $request)
    {
        // Validate the upload
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        try {
            // Store the file
            $file = $request->file('csv_file');
            $path = $file->storeAs('imports', 'courses_' . time() . '.csv');
            $fullPath = Storage::path($path);
            
            // Run the import command
            $exitCode = Artisan::call('app:import-courses', [
                'file' => $fullPath
            ]);
            
            $output = Artisan::output();
            
            // Parse output to get statistics
            preg_match('/Courses created: (\d+)/', $output, $created);
            preg_match('/Courses updated: (\d+)/', $output, $updated);
            preg_match('/Courses skipped: (\d+)/', $output, $skipped);
            
            $stats = [
                'created' => $created[1] ?? 0,
                'updated' => $updated[1] ?? 0,
                'skipped' => $skipped[1] ?? 0,
                'total' => ($created[1] ?? 0) + ($updated[1] ?? 0) + ($skipped[1] ?? 0),
            ];
            
            if ($exitCode === 0) {
                if ($stats['skipped'] > 0) {
                    // Some rows had errors, but some were successful
                    return redirect()->route('filament.admin.resources.courses.index')
                        ->with('warning', 'Import completed with some issues. Created: ' . $stats['created'] . 
                        ', Updated: ' . $stats['updated'] . ', Skipped: ' . $stats['skipped']);
                } else {
                    // All successful
                    return redirect()->route('filament.admin.resources.courses.index')
                        ->with('success', 'Import completed successfully. Created: ' . $stats['created'] . 
                        ', Updated: ' . $stats['updated']);
                }
            } else {
                // Major error
                return back()->withErrors(['csv_file' => 'Import failed: ' . trim(explode("\n", $output)[0])]);
            }
        } catch (\Exception $e) {
            Log::error('Course import error: ' . $e->getMessage());
            return back()->withErrors(['csv_file' => 'Error processing file: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Download the template file
     */
    public function downloadTemplate()
    {
        $path = storage_path('app/templates/course_import_template.csv');
        
        if (!file_exists($path)) {
            return back()->withErrors(['template' => 'Template file not found.']);
        }
        
        return response()->download($path, 'course_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
