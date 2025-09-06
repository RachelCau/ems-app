<?php

use App\Livewire\HomePage;
use App\Livewire\MultiStepForm;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorageLinkController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SecureDocumentUploadController;
use App\Http\Controllers\CourseImportController;
use App\Http\Controllers\StudentPdfController;
use App\Http\Controllers\StudentCorController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', HomePage::class)->name('home');


Route::get('/register', MultiStepForm::class)->name('form');
Route::view('/register', 'register')->name('register.view');
Route::view('/registration-success', 'registration-success')->name('registration.success');

// Storage link creation route
Route::get('/create-storage-link', [StorageLinkController::class, 'createLink']);

// Add document routes
Route::get('documents/{filename}', [DocumentController::class, 'show'])
    ->name('documents.show')
    ->where('filename', '.*');

// Secure document upload routes 
Route::get('/secure-upload/{token}', [SecureDocumentUploadController::class, 'showUploadForm'])->name('secure.upload.form');
Route::post('/secure-upload/{token}', [SecureDocumentUploadController::class, 'uploadDocument'])->name('secure.upload.store');

// Course Import Routes
Route::get('/courses/import', [CourseImportController::class, 'showImportForm'])
    ->name('courses.import-form')
    ->middleware(['auth']);

Route::post('/courses/import', [CourseImportController::class, 'import'])
    ->name('courses.import')
    ->middleware(['auth']);

Route::get('/courses/download-template', [CourseImportController::class, 'downloadTemplate'])
    ->name('courses.download-template')
    ->middleware(['auth']);

// Student COR PDF Download Route
Route::get('/student/{id}/cor-pdf', [StudentCorController::class, 'downloadPdf'])->name('student.cor.pdf');