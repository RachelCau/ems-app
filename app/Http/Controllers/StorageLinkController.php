<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class StorageLinkController extends Controller
{
    /**
     * Create the storage symbolic link
     *
     * @return \Illuminate\Http\Response
     */
    public function createLink(Request $request)
    {
        // Optional: Add some security to prevent unauthorized access
        if (!app()->environment('local') && !$request->has('secret_key')) {
            abort(403, 'Unauthorized action in non-local environment');
        }
        
        // Check if the link already exists
        if (file_exists(public_path('storage'))) {
            if (is_link(public_path('storage'))) {
                return response()->json([
                    'success' => true,
                    'message' => 'Storage link already exists!'
                ]);
            } else {
                // The directory exists but is not a symlink - try to remove it
                try {
                    if ($request->has('auto_remove') || app()->environment('local')) {
                        File::deleteDirectory(public_path('storage'));
                        // Continue with link creation
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'The path public/storage exists but is not a symbolic link. Add ?auto_remove=1 to automatically remove it.'
                        ], 400);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to remove existing directory: ' . $e->getMessage()
                    ], 500);
                }
            }
        }
        
        // Execute the storage:link command
        try {
            $exitCode = Artisan::call('storage:link');
            
            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Storage link created successfully! Public storage is now available at: /storage'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create storage link. Exit code: ' . $exitCode
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
} 