<?php

use App\Http\Controllers\api\DocumentDownloadController;
use Illuminate\Support\Facades\Route;

// Document download tracking for the audit activity log.
Route::post('/document-downloads', [DocumentDownloadController::class, 'store']);
Route::get('/document-downloads/document/{document_id}/users', [DocumentDownloadController::class, 'downloadedUsers'])
    ->whereNumber('document_id');
