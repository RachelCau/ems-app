<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use App\Models\AdmissionDocument;

class DocumentController extends Controller
{
    /**
     * Display the document file
     *
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function show($filename)
    {
        // Check multiple possible paths
        $possibleDisks = [
            'admissions',  // New disk at public/assets/documents/admission_documents
            'public',      // Legacy storage disk
        ];
        
        // Try to find the file in any of our disks
        $fileDisk = null;
        $filePath = null;
        
        // First, try exact filename match
        foreach ($possibleDisks as $disk) {
            if (Storage::disk($disk)->exists($filename)) {
                $fileDisk = $disk;
                $filePath = $filename;
                break;
            }
        }
        
        // If not found, try with admission_documents prefix
        if (!$filePath) {
            foreach ($possibleDisks as $disk) {
                if (Storage::disk($disk)->exists("admission_documents/{$filename}")) {
                    $fileDisk = $disk;
                    $filePath = "admission_documents/{$filename}";
                    break;
                }
            }
        }
        
        // If still not found, try direct file path
        if (!$filePath) {
            $possiblePaths = [
                public_path("assets/documents/admission_documents/{$filename}"),
                public_path("admission_documents/{$filename}")
            ];
            
            foreach ($possiblePaths as $path) {
                if (File::exists($path)) {
                    return response()->file($path);
                }
            }
        }
        
        // If file doesn't exist anywhere, return 404
        if (!$fileDisk || !$filePath) {
            abort(404, 'Document not found');
        }
        
        // Update the document record if the path isn't the expected one
        $document = AdmissionDocument::where('file_path', 'like', "%{$filename}")->first();
        if ($document && $document->file_path !== $filePath) {
            $document->update(['file_path' => $filePath]);
        }
        
        // Determine the file's MIME type
        $mimeType = Storage::disk($fileDisk)->mimeType($filePath);
        
        // Stream the file
        $file = Storage::disk($fileDisk)->get($filePath);
        return Response::make($file, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
} 