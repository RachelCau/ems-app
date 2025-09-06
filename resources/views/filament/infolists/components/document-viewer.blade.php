@php
    use Illuminate\Support\Facades\Storage;
    
    $state = $getState();
    
    if (empty($state)) {
        return;
    }

    try {
        $url = $state;
        
        // Check if it's a path rather than a URL
        if (!filter_var($state, FILTER_VALIDATE_URL)) {
            // If the path doesn't start with storage/ or public/, assume it's a path relative to the public disk
            if (strpos($state, 'storage/') !== 0 && strpos($state, 'public/') !== 0) {
                $url = asset('storage/' . $state);
            } else {
                $url = Storage::url($state);
            }
        }

        // Get file extension
        $extension = pathinfo($state, PATHINFO_EXTENSION);
    } catch (\Exception $e) {
        $extension = '';
        $url = '';
    }
@endphp

@if(empty($url))
    <div class="flex flex-col items-center py-4">
        <div class="rounded-full bg-danger-50 text-danger-500 p-3 mb-3">
            <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
        </div>
        <p class="text-sm text-gray-600">Document unavailable or invalid</p>
        
        @if(app()->environment('local') || app()->environment('development'))
            <div class="mt-4 p-3 bg-gray-100 rounded text-xs font-mono text-gray-700 w-full overflow-auto">
                <strong>Debug Info:</strong><br>
                State: {{ var_export($state, true) }}<br>
                URL: {{ var_export($url ?? null, true) }}<br>
                Extension: {{ var_export($extension ?? null, true) }}
            </div>
        @endif
    </div>
@else
    <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-medium text-gray-900">{{ $getLabel() }}</h3>
            <div class="flex items-center space-x-2">
                <a href="{{ $url }}" target="_blank" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium tracking-tight text-white bg-primary-600 rounded-lg hover:bg-primary-500 focus:outline-none focus:bg-primary-700">
                    <x-heroicon-o-eye class="w-5 h-5 mr-1" />
                    View Full Document
                </a>
                <a href="{{ $url }}" download class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium tracking-tight border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                    <x-heroicon-o-arrow-down-tray class="w-5 h-5 mr-1" />
                    Download
                </a>
            </div>
        </div>
        
        @if (in_array(strtolower($extension), ['pdf']))
            <div class="relative pt-[56.25%] w-full rounded-lg overflow-hidden bg-gray-100 mb-3">
                <iframe 
                    src="{{ $url }}" 
                    class="absolute inset-0 w-full h-full border-0"
                    allow="autoplay; encrypted-media"
                    allowfullscreen>
                </iframe>
            </div>
            <div class="px-4 py-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <x-heroicon-o-document-text class="w-5 h-5 text-primary-500 mr-2" />
                    <span class="text-sm text-gray-700">PDF Document</span>
                </div>
            </div>
        @elseif (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
            <div class="flex justify-center bg-gray-100 rounded-lg p-2 mb-3">
                <img src="{{ $url }}" alt="{{ $getLabel() }}" class="max-w-full rounded max-h-[600px] object-contain" />
            </div>
            <div class="px-4 py-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <x-heroicon-o-photo class="w-5 h-5 text-primary-500 mr-2" />
                    <span class="text-sm text-gray-700">Image Document ({{ strtoupper($extension) }})</span>
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center bg-gray-100 rounded-lg p-8 mb-3">
                <div class="rounded-full bg-primary-50 p-4 mb-3">
                    <x-heroicon-o-document class="w-8 h-8 text-primary-500" />
                </div>
                <p class="text-gray-700 font-medium">Document Preview Not Available</p>
                <p class="text-sm text-gray-500 mt-1">Download the document to view its contents</p>
            </div>
            <div class="px-4 py-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <x-heroicon-o-document class="w-5 h-5 text-primary-500 mr-2" />
                    <span class="text-sm text-gray-700">Document ({{ strtoupper($extension) ?: 'Unknown Type' }})</span>
                </div>
            </div>
        @endif
        
        @if(app()->environment('local') || app()->environment('development'))
            <div class="mt-4 p-2 bg-gray-50 rounded text-xs font-mono text-gray-500 truncate">
                Path: {{ $state }}<br>
                URL: {{ $url }}
            </div>
        @endif
    </div>
@endif 