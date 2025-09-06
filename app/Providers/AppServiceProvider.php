<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->checkStorageLink();
    }

    /**
     * Check if the storage link exists, create it if it doesn't
     */
    protected function checkStorageLink()
    {
        $publicStoragePath = public_path('storage');
        $actualStoragePath = storage_path('app/public');
    
        if (!file_exists($publicStoragePath)) {
            try {
                if (function_exists('symlink')) {
                    // Attempt to create symbolic link
                    app('files')->link($actualStoragePath, $publicStoragePath);
                } else {
                    // Log or handle gracefully
                    logger()->warning('symlink() is not available. Copying directory instead.');
                    File::copyDirectory($actualStoragePath, $publicStoragePath);
                }
            } catch (\Throwable $e) {
                // Catch broader Throwable to handle all possible errors
                logger()->error('Failed to create storage link: ' . $e->getMessage());
                // Fallback: Copy instead of symlink
                if (!file_exists($publicStoragePath)) {
                    File::copyDirectory($actualStoragePath, $publicStoragePath);
                }
            }
        }
    }
    
}
