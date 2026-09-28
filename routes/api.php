<?php

use App\Http\Controllers\MigrasiDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\api\CourseController;
use App\Http\Controllers\api\MasterController;
use App\Http\Controllers\api\WizardController;
use App\Http\Controllers\api\ContentController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\api\FileUploadController;
use App\Http\Controllers\api\AnnouncementController;
use App\Http\Controllers\api\DocumentViewController;

/*
|--------------------------------------------------------------------------
| Web Routes (SPA + Session + CSRF)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

// Session & Auth
Route::middleware(['web', 'api'])->group(function () {
    Route::get('/session', function () {
        return response()->json([
            'auth' => session('auth'),
        ]);
    });

    Route::post('/login', [LoginController::class, 'aksiLogin'])->middleware('throttle:login');
    Route::post('/logout', [LoginController::class, 'logout']);
});

    // Information
    Route::get('/menu/{id}', [InformationController::class, 'menu']);
    Route::get('/menu2', [InformationController::class, 'menu2']);

    // File upload
    Route::post('/upload', [FileUploadController::class, 'upload'])->middleware('session.auth');

    // Courses
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/course-access-options', [CourseController::class, 'accessOptions'])->middleware('session.auth');
    Route::get('/courses/{id}/access', [CourseController::class, 'access'])->middleware('session.auth');
    Route::get('/courses/{id}', [CourseController::class, 'show'])->middleware('session.auth');
    Route::get('/preview-course/{id}', [CourseController::class, 'preview']);
    Route::post('/courses', [CourseController::class, 'store'])->middleware('session.auth');
    Route::put('/courses/{id}', [CourseController::class, 'update'])->middleware('session.auth');
    Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->middleware('session.auth');
    Route::get('/categories', [CourseController::class, 'categories']);
    Route::get('/search', [CourseController::class, 'search']);
    // Content
    Route::get('/course/contents/{courseId}', [ContentController::class, 'index']);
    Route::get('/contents/{id}', [ContentController::class, 'show'])->middleware('session.auth');
    Route::post('/contents', [ContentController::class, 'store'])->middleware('session.auth');
    Route::put('/contents/{id}', [ContentController::class, 'update'])->middleware('session.auth');
    Route::delete('/contents/{id}', [ContentController::class, 'destroy'])->middleware('session.auth');

    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/announcements/trash', [AnnouncementController::class, 'trash']);
    Route::get('/announcement/{menu_id}', [AnnouncementController::class, 'detail']);
    Route::get('/announcements/document/{document_id}', [AnnouncementController::class, 'documentById'])->middleware('session.auth');
    Route::get('/announcements/document/preview/{document_id}', [AnnouncementController::class, 'lastDocumentPreview']);
    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('session.auth');
    Route::post('/announcements/{document_id}', [AnnouncementController::class, 'update'])->middleware('session.auth');
    Route::patch('/announcements/{document_id}/restore', [AnnouncementController::class, 'restore'])->middleware('session.auth');
    Route::delete('/announcements/{document_id}', [AnnouncementController::class, 'destroy'])->middleware('session.auth');
    // Master data

    Route::prefix('master')->group(function () {
        Route::get('jabatan', [MasterController::class, 'getJabatan']);
        Route::get('areas', [MasterController::class, 'getAreas']);
        Route::get('types/{id_menu}', [MasterController::class, 'getTypesByIdMenu']);
        Route::get('wilayah', [MasterController::class, 'getWilayah']);
        Route::get('cabang', [MasterController::class, 'getCabang']);
    });

    // Wizard
    Route::post('/wizard/step/{step}', [WizardController::class, 'saveStep'])->middleware('session.auth');
    Route::get('/wizard/session', [WizardController::class, 'getSession'])->middleware('session.auth');
    Route::post('/wizard/finish', [WizardController::class, 'finish'])->middleware('session.auth');

    // Document View Tracker
    Route::post('/document-views', [DocumentViewController::class, 'store'])->middleware('session.auth');
    Route::get('/document-views/stats', [DocumentViewController::class, 'stats']);
    Route::get('/document-views/document/{document_id}/check', [DocumentViewController::class, 'check']);
    Route::get('/document-views/document/{document_id}/unviewed', [DocumentViewController::class, 'unviewedUsers']);
    Route::get('/document-views/document/{document_id}/viewed', [DocumentViewController::class, 'viewedUsers']);

// Report
Route::middleware(['web', 'api'])->group(function () {
    Route::get('/report/documents', [\App\Http\Controllers\api\DocumentReportController::class, 'getReport']);
    Route::get('/report/activity-logs', [\App\Http\Controllers\api\DocumentReportController::class, 'getActivityLogs']);
    Route::post('/report/activity-logs/export-excel', [\App\Http\Controllers\api\DocumentReportController::class, 'exportActivityLogsExcel']);
    Route::post('/report/documents/export-excel', [\App\Http\Controllers\api\DocumentReportController::class, 'exportExcel']);
});

});

// Fallback search route outside v1 prefix
Route::middleware(['web', 'api'])->get('/search', [CourseController::class, 'search']);

// migrasi data
Route::get('/migrasi-data', [MigrasiDataController::class, 'export'])->middleware('session.auth');
Route::post('/migrasi-data', [MigrasiDataController::class, 'import'])->middleware('session.auth');

// routes/api.php;
