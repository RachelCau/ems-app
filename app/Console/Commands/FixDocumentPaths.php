<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdmissionDocument;
use Illuminate\Support\Facades\Storage;

class FixDocumentPaths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:fix-paths';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix document paths to ensure they can be accessed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting document path correction...');
        
        // Get all admission documents
        $documents = AdmissionDocument::all();
        
        $this->info("Found {$documents->count()} documents to check");
        
        $fixed = 0;
        $missing = 0;
        
        foreach ($documents as $document) {
            if (!$document->file_path) {
                $this->warn("Document ID {$document->id} has no file path");
                continue;
            }
            
            $originalPath = $document->file_path;
            $fileName = basename($originalPath);
            
            // Check if file exists at original path
            if (Storage::disk('public')->exists($originalPath)) {
                $this->info("Document ID {$document->id} exists at original path: {$originalPath}");
                continue;
            }
            
            // Check alternative path
            $alternativePath = "admission_documents/{$fileName}";
            if (Storage::disk('public')->exists($alternativePath)) {
                // Update path
                $document->update(['file_path' => $alternativePath]);
                $this->info("Fixed document ID {$document->id}: {$originalPath} -> {$alternativePath}");
                $fixed++;
                continue;
            }
            
            // If file doesn't exist in original path and can't be found in alternative path,
            // let's try to copy it from storage/app/ to storage/app/public/admission_documents/
            if (Storage::exists("admission_documents/{$fileName}")) {
                // Copy file to public directory
                Storage::copy(
                    "admission_documents/{$fileName}", 
                    "public/admission_documents/{$fileName}"
                );
                
                // Update path
                $document->update(['file_path' => "admission_documents/{$fileName}"]);
                $this->info("Copied and fixed document ID {$document->id} to: admission_documents/{$fileName}");
                $fixed++;
                continue;
            }
            
            $this->error("Document ID {$document->id} file not found: {$originalPath}");
            $missing++;
        }
        
        $this->info("Completed! Fixed: {$fixed}, Missing: {$missing}");
        
        return 0;
    }
} 