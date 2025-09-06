<div x-data="{ expanded: false }" class="w-full">
    <div 
        @mouseover="expanded = true" 
        @mouseleave="expanded = false"
        class="relative overflow-hidden transition-all duration-300 ease-in-out bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md group"
    >
        <!-- Main content -->
        <div class="px-6 py-5 flex items-center">
            <!-- Dynamic avatar with pulse effect -->
            <div class="flex-shrink-0 relative">
                @php
                    $avatarUrl = $this->getAvatarUrl();
                @endphp
                <div class="w-20 h-20 rounded-full overflow-hidden flex items-center justify-center bg-gradient-to-br from-emerald-400 to-teal-600 text-white text-xl font-bold shadow-sm transform transition-transform group-hover:scale-105">
                    @if (auth()->user()->employee && auth()->user()->employee->avatar)
                        <img src="{{ $avatarUrl }}" alt="{{ $this->getUserFullName() }}" class="w-full h-full object-cover">
                    @else
                        {{ $this->getInitial() }}
                    @endif
                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-green-500 border-2 border-white dark:border-gray-800"></span>
                </div>
            </div>
            
            <!-- User info with animated transitions -->
            <div class="ml-4 flex-grow">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white leading-tight flex items-center">
                    <span class="transition-all duration-300 group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        Welcome, {{ $this->getUserRole() }}
                    </span>
                </h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-0.5 flex items-center space-x-1">
                    <span>{{ $this->getUserFullName() }}</span>
                    <span class="inline-block mx-2 h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    <span>{{ $this->getCurrentDate() }}</span>
                </p>
            </div>

            <!-- Interactive detail icon -->
            <div 
                class="flex-shrink-0 transition-opacity duration-300"
                :class="{ 'opacity-100': expanded, 'opacity-30': !expanded }"
            >
                <button type="button" class="p-1 text-gray-400 hover:text-primary-500 dark:text-gray-500 dark:hover:text-primary-400 focus:outline-none transition-colors duration-200 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Expandable additional info (slides down) -->
        <div 
            x-show="expanded"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60"
        >
            <div class="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-primary-500 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Last login: {{ $this->getLastLoginTime() }}</span>
                </div>
            </div>
        </div>

        <!-- Decorative gradients -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-36 w-80 h-80 opacity-10 dark:opacity-5 bg-gradient-to-br from-primary-600 to-primary-400 rounded-full transform-gpu"></div>
            <div class="absolute -bottom-14 -left-20 w-40 h-40 opacity-10 dark:opacity-5 bg-gradient-to-br from-teal-400 to-emerald-600 rounded-full transform-gpu"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Any additional JavaScript could be added here
    document.addEventListener('alpine:init', () => {
        // Could add custom Alpine.js components here
    });
</script>
@endpush 