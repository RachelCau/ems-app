<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AdmissionDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SecureDocumentUploadController extends Controller
{
    /**
     * Show the document upload form
     */
    public function showUploadForm(Request $request, $token)
    {
        $data = $this->validateToken($token);
        
        if (!$data) {
            return view('secure-uploads.invalid-token');
        }
        
        $applicant = Applicant::find($data['applicant_id']);
        
        if (!$applicant) {
            return view('secure-uploads.invalid-token');
        }
        
        // Get required documents and their status
        $documents = AdmissionDocument::where('applicant_id', $applicant->id)->get();
        
        // Get common document types for suggestions
        $commonDocumentTypes = $this->getCommonDocumentTypes();
        
        // Get common document rejection reasons
        $rejectionReasons = $this->getDocumentRejectionReasons();
        
        return view('secure-uploads.upload-form', [
            'applicant' => $applicant,
            'documents' => $documents,
            'token' => $token,
            'commonDocumentTypes' => $commonDocumentTypes,
            'rejectionReasons' => $rejectionReasons
        ]);
    }
    
    /**
     * Handle document upload
     */
    public function uploadDocument(Request $request, $token)
    {
        $data = $this->validateToken($token);
        
        if (!$data) {
            return response()->json(['error' => 'Invalid or expired token'], 403);
        }
        
        $applicant = Applicant::find($data['applicant_id']);
        
        if (!$applicant) {
            return response()->json(['error' => 'Applicant not found'], 404);
        }
        
        // Check if all documents are already submitted
        $documents = AdmissionDocument::where('applicant_id', $applicant->id)->get();
        $allSubmitted = true;
        foreach($documents as $document) {
            if($document->status !== 'Submitted' && $document->status !== 'Verified') {
                $allSubmitted = false;
                break;
            }
        }
        
        // If all documents are already submitted, prevent upload
        if ($allSubmitted) {
            return response()->json(['error' => 'All required documents have already been submitted.'], 403);
        }
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|max:255',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:6144',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $file = $request->file('document_file');
        $documentType = $request->input('document_type');
        
        // Create a sanitized filename
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::slug($applicant->applicant_number . '-' . $documentType) . '-' . time() . '.' . $extension;
        
        // Store the file - use empty path to store directly in the disk root
        $path = $file->storeAs('', $fileName, 'admissions');
        
        // Update or create the document record
        $document = AdmissionDocument::updateOrCreate(
            [
                'applicant_id' => $applicant->id,
                'document_type' => $documentType,
            ],
            [
                'status' => 'Submitted',
                'remarks' => 'Uploaded via secure link',
                'submitted_at' => now(),
                'file_path' => $fileName,
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'document' => $document
        ]);
    }
    
    /**
     * Validate the secure token
     */
    private function validateToken($token)
    {
        $cacheKey = "upload_token_{$token}";
        $data = Cache::get($cacheKey);
        
        if (!$data) {
            return null;
        }
        
        if (now()->isAfter($data['expires_at'])) {
            Cache::forget($cacheKey);
            return null;
        }
        
        return $data;
    }
    
    /**
     * Get common document types
     */
    private function getCommonDocumentTypes()
    {
        return [
            'formal_picture',
            'report_card_back',
            'report_card_front',
            'birth_certificate',
        ];
    }
    
    /**
     * Get common document rejection reasons
     */
    public function getDocumentRejectionReasons()
    {
        return [
            'Form 137' => [
                'reason' => 'Form 137 Submitted. Need for Registrar\'s Office Approval',
                'subtext' => 'Need to visit Registrar\'s Office'
            ],
            'Card Front' => [
                'reason' => 'Unreadable/Unrecognizable Text/Image of Front View of the Card',
                'subtext' => 'Please resubmit a clear, high-quality image'
            ],
            'Card Back' => [
                'reason' => 'Unreadable/Unrecognizable Text/Image of Back View of the Card',
                'subtext' => 'Please resubmit a clear, high-quality image'
            ],
            'Grade 11 Card' => [
                'reason' => 'Grade 11 Card',
                'subtext' => 'Please submit your complete grades record'
            ],
            'PSA Unreadable' => [
                'reason' => 'Unreadable/Unrecognizable Text/Image of PSA',
                'subtext' => 'Please resubmit a clear, high-quality image'
            ],
            'Municipal Copy' => [
                'reason' => 'Municipal Copy of Live Birth was submitted instead of PSA Copy',
                'subtext' => 'Please obtain and submit the PSA authenticated copy'
            ],
            'Informal Picture' => [
                'reason' => 'Picture not in Formal Attire',
                'subtext' => 'Please submit a photo in proper formal attire'
            ]
        ];
    }
} 