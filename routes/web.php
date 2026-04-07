<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranscriptController;



Route::get('/', function () {
    return view('welcome');
});


// Transcript routes
Route::get('/transcript/{regNumber}', [TranscriptController::class, 'generate'])->name('transcript.generate');
Route::post('/transcript/batch', [TranscriptController::class, 'generateBatch'])->name('transcript.batch');
Route::get('/transcript/preview/{regNumber}', [TranscriptController::class, 'preview'])->name('transcript.preview');
