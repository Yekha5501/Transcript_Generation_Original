<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranscriptController;



Route::get('/', function () {
    return view('welcome');
});

Route::post('/transcript/batch', [TranscriptController::class, 'batch'])->name('transcript.batch');


// Transcript routes
Route::get('/transcript/{regNumber}', [TranscriptController::class, 'generate'])->name('transcript.generate');
Route::get('/transcript/preview/{regNumber}', [TranscriptController::class, 'preview'])->name('transcript.preview');



// Single transcript generation


// Batch transcript generation


// Download specific transcript file
Route::get('/transcript/download/{filename}', [TranscriptController::class, 'downloadFile'])->name('transcript.download');

// Download batch results page
Route::get('/transcript/batch/results', [TranscriptController::class, 'downloadBatchResults'])->name('transcript.batch.results');

// Preview student grades
// Clear batch results


Route::get('/transcript/clear', [TranscriptController::class, 'clearResults'])->name('transcript.clear');

// Batch processing routes
Route::post('/transcript/batch/queue', [TranscriptController::class, 'queueBatch'])->name('transcript.batch.queue');
Route::get('/transcript/batch/process/{queueId}/{index}', [TranscriptController::class, 'processQueueItem'])->name('transcript.batch.process');
Route::get('/transcript/batch/status/{queueId}', [TranscriptController::class, 'getQueueStatus'])->name('transcript.batch.status');
Route::get('/download-temp/{filename}', [TranscriptController::class, 'downloadTemp'])->name('download.temp');
// Batch routes
Route::get('/transcript/batch/list/{queueId}', [TranscriptController::class, 'showBatchList'])->name('transcript.batch.list');
Route::get('/transcript/batch/download/{queueId}/{studentId}', [TranscriptController::class, 'downloadBatchTranscript'])->name('transcript.batch.download');


// Batch routes
Route::post('/transcript/batch/queue', [TranscriptController::class, 'queueBatch'])->name('transcript.batch.queue');
Route::get('/transcript/batch/list/{queueId}', [TranscriptController::class, 'showBatchList'])->name('transcript.batch.list');
Route::get('/transcript/batch/download/{queueId}/{studentId}', [TranscriptController::class, 'downloadBatchTranscript'])->name('transcript.batch.download');
Route::get('/transcript/batch/status/{queueId}', [TranscriptController::class, 'getQueueStatus'])->name('transcript.batch.status');
Route::get('/download-temp/{filename}', [TranscriptController::class, 'downloadTemp'])->name('download.temp');