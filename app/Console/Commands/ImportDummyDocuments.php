<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\AdmissionDocument;

class ImportDummyDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:import-dummy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import sample documents for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating sample documents for testing...');
        
        // Ensure directories exist
        Storage::disk('public')->makeDirectory('admission_documents');
        
        // Create a sample PDF
        $this->createSamplePdf();
        
        // Create a sample image
        $this->createSampleImage();
        
        // Update broken document records to point to new sample files
        $this->updateDocumentRecords();
        
        $this->info('Sample documents created successfully!');
        
        return 0;
    }
    
    /**
     * Create a sample PDF file
     */
    private function createSamplePdf()
    {
        // Create a simple PDF file
        $pdfContent = '%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Resources<<>>/Contents 4 0 R/Parent 2 0 R>>endobj
4 0 obj<</Length 73>>stream
BT
/F1 24 Tf
100 700 Td
(Sample Document - For Testing Only) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f
0000000009 00000 n
0000000056 00000 n
0000000111 00000 n
0000000212 00000 n
trailer<</Size 5/Root 1 0 R>>
startxref
334
%%EOF';

        $pdfPath = 'admission_documents/sample_document.pdf';
        Storage::disk('public')->put($pdfPath, $pdfContent);
        $this->info("Created sample PDF at: {$pdfPath}");
    }
    
    /**
     * Create a sample image file
     */
    private function createSampleImage()
    {
        // Create a simple PNG image (1x1 pixel black image)
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        
        $imagePath = 'admission_documents/sample_image.png';
        Storage::disk('public')->put($imagePath, $imageContent);
        $this->info("Created sample image at: {$imagePath}");
    }
    
    /**
     * Update document records to point to new sample files
     */
    private function updateDocumentRecords()
    {
        $documents = AdmissionDocument::all();
        $pdfPath = 'admission_documents/sample_document.pdf';
        $imagePath = 'admission_documents/sample_image.png';
        
        $updated = 0;
        
        foreach ($documents as $document) {
            if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
                // Determine which sample to use based on original file extension
                $originalExt = pathinfo($document->file_path ?? '', PATHINFO_EXTENSION);
                $newPath = (strtolower($originalExt) === 'pdf') ? $pdfPath : $imagePath;
                
                $document->update(['file_path' => $newPath]);
                $updated++;
                
                $this->info("Updated document ID {$document->id} to use {$newPath}");
            }
        }
        
        $this->info("Updated {$updated} document records");
    }
} 