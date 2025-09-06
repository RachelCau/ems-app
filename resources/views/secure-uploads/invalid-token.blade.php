<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invalid or Expired Token</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="max-w-md w-full bg-white shadow-md rounded-lg overflow-hidden">
            <div class="bg-red-600 text-white p-6">
                <h1 class="text-2xl font-bold">Invalid or Expired Link</h1>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <svg class="mx-auto h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    
                    <h2 class="mt-4 text-lg font-medium text-gray-900">
                        This upload link is invalid or has expired
                    </h2>
                    
                    <p class="mt-2 text-gray-600">
                        Please contact the admissions office to request a new upload link if you need to submit or update your documents.
                    </p>
                </div>
                
                <div class="mt-6 text-center">
                    <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Go to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 