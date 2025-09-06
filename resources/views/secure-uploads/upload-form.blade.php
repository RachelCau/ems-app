<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents - {{ $applicant->first_name }} {{ $applicant->last_name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
    @php
        // Check if all documents are submitted
        $allSubmitted = true;
        foreach($documents as $document) {
            if($document->status !== 'Submitted' && $document->status !== 'Verified') {
                $allSubmitted = false;
                break;
            }
        }
    @endphp

    <div class="min-h-screen py-8" x-data="uploadApp({{ $allSubmitted ? 'true' : 'false' }})">
        <div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 text-white p-6">
                <h1 class="text-2xl font-bold">Document Upload Portal</h1>
                <p class="mt-1">Welcome, {{ $applicant->first_name }} {{ $applicant->last_name }}</p>
                <p class="text-sm mt-1 text-blue-100">Application ID: {{ $applicant->applicant_number }}</p>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <div class="mb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">Your Required Documents</h2>
                    <p class="text-gray-600 mb-4">Please upload the following documents to complete your application.</p>
                    
                    <!-- Document Status Summary -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h3 class="font-medium text-gray-700 mb-2">Document Status</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($documents as $document)
                            <div class="flex items-center border-l-4 p-3 rounded {{ $document->status === 'Submitted' || $document->status === 'Verified' ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50' }}">
                                <div>
                                    <p class="font-medium">{{ $document->document_type }}</p>
                                    <p class="text-sm">
                                        Status: 
                                        <span class="px-2 py-1 text-xs rounded-full {{ $document->status === 'Submitted' ? 'bg-blue-100 text-blue-800' : ($document->status === 'Verified' ? 'bg-green-100 text-green-800' : ($document->status === 'Missing' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                            {{ $document->status }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Upload Form -->
                    <template x-if="!allSubmitted">
                        <div class="border border-gray-200 rounded-lg p-5">
                            <h3 class="font-medium text-gray-800 mb-4">Upload Document</h3>
                            
                            <div x-show="successMessage" class="mb-4 p-3 bg-green-100 text-green-700 rounded-md" x-text="successMessage"></div>
                            <div x-show="errorMessage" class="mb-4 p-3 bg-red-100 text-red-700 rounded-md" x-text="errorMessage"></div>
                            
                            <form @submit.prevent="uploadDocument" class="space-y-4">
                                <div>
                                    <label for="document_type" class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                                    <div class="relative">
                                        <input type="text" id="document_type" x-model="documentType" @focus="showSuggestions = true" @blur="setTimeout(() => showSuggestions = false, 200)" 
                                            class="block w-full px-4 py-2 text-gray-900 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            placeholder="Enter document type">
                                        
                                        <!-- Document type suggestions -->
                                        <div x-show="showSuggestions" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            <ul class="py-1">
                                                @foreach($commonDocumentTypes as $type)
                                                <li>
                                                    <button type="button" @click="selectDocumentType('{{ $type }}')" 
                                                        class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 focus:outline-none">
                                                        {{ $type }}
                                                    </button>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                    <p x-show="errors.document_type" x-text="errors.document_type" class="mt-1 text-sm text-red-600"></p>
                                </div>
                                
                                <div>
                                    <label for="document_file" class="block text-sm font-medium text-gray-700 mb-1">Document File</label>
                                    <div class="flex items-center space-x-2">
                                        <label class="block w-full">
                                            <span class="sr-only">Choose file</span>
                                            <input type="file" id="document_file" @change="handleFileChange" 
                                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                        </label>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">Accepted formats: PDF, JPG, JPEG, PNG (max 6MB)</p>
                                    <p x-show="errors.document_file" x-text="errors.document_file" class="mt-1 text-sm text-red-600"></p>
                                </div>
                                
                                <div class="flex items-center justify-between pt-4">
                                    <div class="flex items-center">
                                        <div x-show="isUploading" class="mr-2">
                                            <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        <span x-show="isUploading" class="text-sm font-medium text-blue-600">Uploading...</span>
                                    </div>
                                    <button type="submit" :disabled="isUploading" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                        Upload Document
                                    </button>
                                </div>
                            </form>
                        </div>
                    </template>
                    
                    <!-- Message when all documents are submitted -->
                    <template x-if="allSubmitted">
                        <div class="border border-gray-200 rounded-lg p-5">
                            <h3 class="font-medium text-gray-800 mb-4">Upload Document</h3>
                            
                            <div class="mb-4 p-4 bg-green-50 text-green-800 rounded-md border border-green-200 flex items-start">
                                <svg class="h-5 w-5 mr-3 text-green-600 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="font-medium">All required documents have been submitted successfully!</p>
                                    <p class="mt-1 text-sm">Your application is now complete. The upload functionality has been disabled to ensure data consistency. If you need to make any changes, please contact the admissions office.</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <div class="pt-4 mt-6 border-t border-gray-200">
                    <p class="text-sm text-gray-500">
                        This is a secure upload portal. Your link will expire in 48 hours. If you have any issues, please contact the admissions office.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function uploadApp(allDocumentsSubmitted) {
            return {
                documentType: '',
                documentFile: null,
                isUploading: false,
                successMessage: '',
                errorMessage: '',
                errors: {},
                showSuggestions: false,
                allSubmitted: allDocumentsSubmitted,
                
                selectDocumentType(type) {
                    this.documentType = type;
                    this.showSuggestions = false;
                },
                
                handleFileChange(event) {
                    this.documentFile = event.target.files[0];
                },
                
                async uploadDocument() {
                    // If all documents are submitted, prevent upload
                    if (this.allSubmitted) {
                        this.errorMessage = 'All required documents have already been submitted.';
                        return;
                    }
                
                    this.errors = {};
                    this.successMessage = '';
                    this.errorMessage = '';
                    
                    // Validate form
                    if (!this.documentType) {
                        this.errors.document_type = 'Please specify the document type';
                    }
                    
                    if (!this.documentFile) {
                        this.errors.document_file = 'Please select a file to upload';
                    }
                    
                    if (Object.keys(this.errors).length > 0) {
                        return;
                    }
                    
                    this.isUploading = true;
                    
                    try {
                        const formData = new FormData();
                        formData.append('document_type', this.documentType);
                        formData.append('document_file', this.documentFile);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                        
                        const response = await fetch('{{ url("/secure-upload/$token") }}', {
                            method: 'POST',
                            body: formData,
                        });
                        
                        const result = await response.json();
                        
                        if (!response.ok) {
                            if (result.errors) {
                                this.errors = result.errors;
                            } else {
                                this.errorMessage = result.error || 'An error occurred during upload.';
                            }
                            return;
                        }
                        
                        // Success
                        this.successMessage = 'Document uploaded successfully!';
                        this.documentType = '';
                        this.documentFile = null;
                        document.getElementById('document_file').value = '';
                        
                        // Refresh page after 2 seconds to show updated document status
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                        
                    } catch (error) {
                        this.errorMessage = 'An unexpected error occurred. Please try again.';
                        console.error('Upload error:', error);
                    } finally {
                        this.isUploading = false;
                    }
                }
            }
        }
    </script>
</body>
</html> 