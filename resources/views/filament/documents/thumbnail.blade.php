@php
    $record = $getRecord();
    $filePath = $record->file_path ?? null;
    $fileName = $filePath ? basename($filePath) : 'No document';
    
    // Improved file existence check for multiple storage locations
    $fileExists = false;
    if ($filePath) {
        // Check in custom admissions disk (assets/documents/admission_documents)
        if (Storage::disk('admissions')->exists($fileName)) {
            $fileExists = true;
            
            // Update record if path format has changed
            if ($record->file_path !== $fileName) {
                \App\Models\AdmissionDocument::where('id', $record->id)
                    ->update(['file_path' => $fileName]);
            }
        }
        // Check in public disk (storage/app/public)
        elseif (Storage::disk('public')->exists($filePath)) {
            $fileExists = true;
        }
        // Check for alternative path in public storage
        elseif (Storage::disk('public')->exists("admission_documents/{$fileName}")) {
            $fileExists = true;
            
            // Update the record with the correct path
            \App\Models\AdmissionDocument::where('id', $record->id)
                ->update(['file_path' => "admission_documents/{$fileName}"]);
        }
        // Check directly in public directories
        elseif (file_exists(public_path("assets/documents/admission_documents/{$fileName}"))) {
            $fileExists = true;
            
            // Update to use the new disk format
            \App\Models\AdmissionDocument::where('id', $record->id)
                ->update(['file_path' => $fileName]);
        }
        elseif (file_exists(public_path("admission_documents/{$fileName}"))) {
            $fileExists = true;
            
            // Update to use the new disk format
            \App\Models\AdmissionDocument::where('id', $record->id)
                ->update(['file_path' => $fileName]);
        }
    }
@endphp

<div class="flex items-center gap-2">
    @if($filePath)
        <div class="flex flex-col">
            <span class="truncate max-w-[150px]" title="{{ $fileName }}">{{ $fileName }}</span>
            @if(!$fileExists)
                <span class="text-danger-500 text-xs">File not found</span>
            @elseif(Str::endsWith(strtolower($filePath), '.pdf'))
                <span class="text-success-500 text-xs">PDF Document</span>
            @else
                <span class="text-info-500 text-xs">Image Document</span>
            @endif
        </div>
    @else
        <span class="text-gray-400">No document</span>
    @endif
</div> 