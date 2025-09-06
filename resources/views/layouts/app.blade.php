<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'CSMS') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Styles -->
    @livewireStyles
    @stack('styles')
    
    <style>
        /* Dark theme styles for the application form page */
        body.dark-theme {
            background-color: #121212;
            color: #fff;
        }
        
        .dark-theme header {
            background-color: #1a1a1a !important;
            border-color: #333 !important;
        }
        
        .dark-theme nav a {
            color: #ddd !important;
        }
        
        .dark-theme footer {
            background-color: #1a1a1a !important;
        }
    </style>
</head>
<body class="{{ request()->routeIs('application.form') ? 'dark-theme' : '' }} font-sans antialiased bg-white text-gray-900">
    <div class="min-h-screen">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="/" class="text-blue-800 font-bold text-2xl">
                            <img src="{{ asset('images/csms-logo.png') }}" alt="CSMS Logo" class="h-10">
                        </a>
                    </div>
                    
                    <!-- Navigation -->
                    <nav class="flex items-center space-x-6">
                        <a href="/" class="text-blue-900 hover:text-blue-700">Home</a>
                        <a href="/about" class="text-blue-900 hover:text-blue-700">About</a>
                        <a href="/framework" class="text-blue-900 hover:text-blue-700">Framework</a>
                        <a href="/course" class="text-blue-900 hover:text-blue-700">Course</a>
                        <a href="/contact" class="text-blue-900 hover:text-blue-700">Contact</a>
                        
                        <a href="/login" class="border border-gray-300 rounded-full px-6 py-2 text-gray-700 hover:bg-gray-50">Login</a>
                        <a href="/register" class="border border-blue-500 rounded-full px-6 py-2 text-blue-500 hover:bg-blue-50">Enroll Now</a>
                    </nav>
                </div>
            </div>
        </header>
        
        <main>
            {{ $slot }}
        </main>
        
        <footer class="bg-blue-900 text-white py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="mb-2">&copy; Copyright CSMS All Rights Reserved</p>
                    <p class="text-sm">Designed by Arnaiz MG, Causing R, Cucharo MR, Olalia R, Torres J, Palero P.</p>
                </div>
                
                <!-- Back to top button -->
                <div class="flex justify-end">
                    <a href="#" class="bg-blue-500 rounded-full p-3 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        </footer>
    </div>
    
    @livewireScripts
    @stack('scripts')
</body>
</html> 