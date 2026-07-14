<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranscriptController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home route - use the TranscriptController index method
Route::get('/', [TranscriptController::class, 'index'])->name('home');

// ============================================
// TRANSCRIPT GENERATOR ROUTES
// ============================================

// Main transcript generator page (also accessible via /transcript)
Route::get('/transcript', [TranscriptController::class, 'index'])->name('transcript.index');

// Single student transcript generation
Route::get('/transcript/{regNumber}', [TranscriptController::class, 'generate'])->name('transcript.generate');

// Preview student grades (AJAX)
Route::get('/transcript/preview/{regNumber}', [TranscriptController::class, 'preview'])->name('transcript.preview');

// ============================================
// BATCH TRANSCRIPT ROUTES
// ============================================

// Queue batch transcripts (upload file)
Route::post('/transcript/batch/queue', [TranscriptController::class, 'queueBatch'])->name('transcript.batch.queue');

// Show batch list with all students
Route::get('/transcript/batch/list/{queueId}', [TranscriptController::class, 'showBatchList'])->name('transcript.batch.list');

// Download a single transcript from batch
Route::get('/transcript/batch/download/{queueId}/{studentId}', [TranscriptController::class, 'downloadBatchTranscript'])->name('transcript.batch.download');

// Get queue status (AJAX polling)
Route::get('/transcript/batch/status/{queueId}', [TranscriptController::class, 'getQueueStatus'])->name('transcript.batch.status');

// ============================================
// TEMPLATE MANAGEMENT ROUTES
// ============================================

// Get available templates (AJAX)
Route::get('/api/templates', [TranscriptController::class, 'getTemplates'])->name('api.templates');

// ============================================
// FILE DOWNLOAD ROUTES
// ============================================

// Download temporary transcript file
Route::get('/download-temp/{filename}', [TranscriptController::class, 'downloadTemp'])->name('download.temp');

// Download specific transcript file
Route::get('/transcript/download/{filename}', [TranscriptController::class, 'downloadFile'])->name('transcript.download');

// ============================================
// UTILITY ROUTES
// ============================================

// Clear batch results
Route::get('/transcript/clear', [TranscriptController::class, 'clearResults'])->name('transcript.clear');

// Download batch results page (legacy - keep for backward compatibility)
Route::get('/transcript/batch/results', [TranscriptController::class, 'downloadBatchResults'])->name('transcript.batch.results');

// Legacy batch processing (keep for backward compatibility)
Route::post('/transcript/batch', [TranscriptController::class, 'batch'])->name('transcript.batch');
Route::get('/transcript/batch/process/{queueId}/{index}', [TranscriptController::class, 'processQueueItem'])->name('transcript.batch.process');