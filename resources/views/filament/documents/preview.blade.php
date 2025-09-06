@php
    if (isset($document)) {
        // When used in a modal context, $document is passed directly
        $record = $document;
        $filePath = $record->file_path ?? null;
    } else {
        // When used in a form context, we use $getRecord()
        $record = $getRecord();
        $filePath = $record->file_path ?? null;
    }
    
    // Extract filename for use with our document controller
    $fileName = $filePath ? basename($filePath) : '';
    $documentUrl = $filePath ? route('documents.show', ['filename' => $fileName]) : null;
    
    // Check if the file actually exists in any of our storage locations
    $fileExists = false;
    if ($filePath) {
        // Check in custom admissions disk (new path)
        if (Storage::disk('admissions')->exists($fileName)) {
            $fileExists = true;
        }
        // Check in public disk (storage/app/public)
        elseif (Storage::disk('public')->exists($filePath)) {
            $fileExists = true;
        }
        elseif (Storage::disk('public')->exists("admission_documents/{$fileName}")) {
            $fileExists = true;
        }
        // Check directly in public directories
        elseif (file_exists(public_path("assets/documents/admission_documents/{$fileName}"))) {
            $fileExists = true;
        }
        elseif (file_exists(public_path("admission_documents/{$fileName}"))) {
            $fileExists = true;
        }
    }
    
    $isPdf = $filePath ? Str::endsWith(strtolower($filePath), '.pdf') : false;
@endphp

<div>
    <div class="p-2 dark:bg-gray-900">
        <h3 class="text-lg font-medium dark:text-gray-200">Applicant Documents</h3>
        
        <div class="mt-2 mb-4 border-b border-gray-800 pb-2">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $fileName }}</p>
        </div>
        
        <div class="dark:bg-gray-800 rounded p-10 flex flex-col items-center justify-center" style="min-height: 300px;">
            @if($fileExists)
                @if($isPdf)
                    <div class="w-full flex items-center justify-center">
                        <iframe src="{{ $documentUrl }}" class="w-full h-[400px] border-0" loading="lazy"></iframe>
                    </div>
                @else
                    <img src="{{ $documentUrl }}" alt="{{ $fileName }}" class="max-w-full h-auto max-h-[400px] object-contain">
                @endif
            @else
                <div class="flex items-center justify-center gap-4 text-gray-400 py-12">
                    <span class="text-blue-400">404</span> | <span>NOT FOUND</span>
                </div>
            @endif
        </div>
        
        <div class="mt-2 text-center">
            @if($fileExists)
                <a href="{{ $documentUrl }}" target="_blank" class="text-primary-500 hover:text-primary-600 text-sm transition">
                    Open Document in new tab
                </a>
            @endif
        </div>
    </div>
</div> 