@php
    use Illuminate\Support\Facades\Storage;
    
    $url = $url ?? null;
    $label = $label ?? 'Document';
    
    if (empty($url)) {
        return;
    }
    
    try {
        // Get file extension
        $extension = pathinfo($url, PATHINFO_EXTENSION);
    } catch (\Exception $e) {
        $extension = '';
    }
@endphp

<div class="p-2 bg-white rounded-xl shadow-sm">
    <div class="flex items-center justify-between mb-2 px-2">
        <h3 class="text-sm font-medium text-gray-700">{{ $label }}</h3>
        <a href="{{ $url }}" target="_blank" class="inline-flex items-center justify-center px-3 py-1 text-xs font-medium rounded-lg text-primary-600 hover:text-primary-500">
            <span class="flex items-center gap-1">
                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                Open
            </span>
        </a>
    </div>
    
    @if (in_array(strtolower($extension), ['pdf']))
        <div class="flex flex-col items-center">
            <div class="w-full h-20 flex items-center justify-center bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-document-text class="w-6 h-6 text-primary-500" />
                    <span class="text-sm text-gray-600">PDF Document</span>
                </div>
            </div>
        </div>
    @elseif (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
        <div class="flex flex-col items-center">
            <img src="{{ $url }}" alt="{{ $label }}" class="w-full h-20 object-cover rounded-lg" />
        </div>
    @else
        <div class="flex flex-col items-center">
            <div class="w-full h-20 flex items-center justify-center bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-document class="w-6 h-6 text-primary-500" />
                    <span class="text-sm text-gray-600">Document</span>
                </div>
            </div>
        </div>
    @endif
</div> 