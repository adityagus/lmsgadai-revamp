<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\api\CourseController;
use App\Http\Controllers\api\CourseProgressController;
use App\Http\Controllers\api\CourseParticipantController;
use App\Http\Controllers\api\CourseContentOrderController;
use App\Http\Controllers\api\MasterController;
use App\Http\Controllers\api\WizardController;
use App\Http\Controllers\api\ContentController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\migrasiDataController;
use App\Http\Controllers\api\FileUploadController;
use App\Http\Controllers\api\AnnouncementController;
use App\Http\Controllers\api\DocumentViewController;

/*
|--------------------------------------------------------------------------
| Web Routes (SPA + Session + CSRF)
|--------------------------------------------------------------------------
*/

// 🔹 Route test session
Route::get('/session', function () {
    $auth = session('auth');
    if ($auth) {
        $auth['role'] = app(\App\Services\LmsAccess::class)->role(request());
    }
    return response()->json([
        'auth' => $auth,
    ]);
});

// error 503 maintenance
Route::get('/maintenance', function () {
    return view('errors/503');
})->middleware('session.auth');

// 🔹 Auth
Route::post('/login', [LoginController::class, 'aksiLogin'])->middleware('throttle:login');
Route::post('/logout', [LoginController::class, 'logout']);

// 🔹 Information
Route::get('/menu/{id}', [InformationController::class, 'menu']);
Route::get('/menu2', [InformationController::class, 'menu2']);

// 🔹 File upload
Route::post('/upload', [FileUploadController::class, 'upload'])->middleware('session.auth');

// 🔹 Courses
Route::middleware(['web', 'api'])->prefix('api')->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/course-access-options', [CourseController::class, 'accessOptions'])->middleware('session.auth');
    Route::get('/courses/{id}/access', [CourseController::class, 'access'])->middleware('session.auth');
    Route::get('/courses/{id}', [CourseController::class, 'show'])->middleware('session.auth');
    Route::get('/courses/{id}/content-order', [CourseContentOrderController::class, 'index'])->middleware('session.auth');
    Route::patch('/courses/{id}/content-order', [CourseContentOrderController::class, 'update'])->middleware('session.auth');
    Route::get('/courses/{id}/progress', [CourseProgressController::class, 'show'])->middleware('session.auth');
    Route::post('/courses/{id}/progress/{contentId}', [CourseProgressController::class, 'complete'])->middleware('session.auth');
    Route::post('/courses/{id}/progress/{contentId}/complete', [CourseProgressController::class, 'complete'])->middleware('session.auth');
    Route::get('/courses/{id}/participants', [CourseParticipantController::class, 'index'])->middleware('session.auth');
    Route::post('/courses/{id}/participants', [CourseParticipantController::class, 'store'])->middleware('session.auth');
    Route::delete('/courses/{id}/participants', [CourseParticipantController::class, 'destroy'])->middleware('session.auth');
    Route::get('/course-participant-options', [CourseParticipantController::class, 'options'])->middleware('session.auth');
    Route::get('/preview-course/{id}', [CourseController::class, 'preview']);
    Route::post('/courses', [CourseController::class, 'store'])->middleware('session.auth');
    Route::put('/courses/{id}', [CourseController::class, 'update'])->middleware('session.auth');
    Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->middleware('session.auth');
    Route::get('/search', [CourseController::class, 'search']);
    // Overview endpoint
    Route::get('/user-overview', [OverviewController::class, 'userOverview']);
    Route::get('/admin-overview', [OverviewController::class, 'adminOverview']);

    // 🔹 Categories
    Route::get('/categories', [MasterController::class, 'getCategories']);
    Route::post('/categories', [MasterController::class, 'addCategory'])->middleware('session.auth');
    Route::put('/categories/{id}', [MasterController::class, 'updateCategory'])->middleware('session.auth');
    Route::delete('/categories/{id}', [MasterController::class, 'deleteCategory'])->middleware('session.auth');
});




// 🔹 Content
Route::get('/course/contents/{courseId}', [ContentController::class, 'index']);
Route::get('/contents/{id}', [ContentController::class, 'show'])->middleware('session.auth');
Route::post('/contents', [ContentController::class, 'store'])->middleware('session.auth');
Route::put('/contents/{id}', [ContentController::class, 'update'])->middleware('session.auth');
Route::delete('/contents/{id}', [ContentController::class, 'destroy'])->middleware('session.auth');

// 🔹 Announcements
Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::get('/announcements/trash', [AnnouncementController::class, 'trash']);
Route::get('/announcement/{menu_id}', [AnnouncementController::class, 'detail']);
Route::get('/announcements/document/{document_id}', [AnnouncementController::class, 'documentById'])->middleware('session.auth');
Route::get('/announcements/document/preview/{document_id}', [AnnouncementController::class, 'lastDocumentPreview']);
Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('session.auth');
Route::post('/announcements/{document_id}', [AnnouncementController::class, 'update'])->middleware('session.auth');
Route::patch('/announcements/{document_id}/restore', [AnnouncementController::class, 'restore'])->middleware('session.auth');
Route::delete('/announcements/{document_id}', [AnnouncementController::class, 'destroy'])->middleware('session.auth');
Route::delete('/announcements/hardDelete/{document_id}', [AnnouncementController::class, 'hardDelete']);

// 🔹 Master data
Route::prefix('master')->group(function () {
    Route::get('jabatan', [MasterController::class, 'getJabatan']);
    Route::get('areas', [MasterController::class, 'getAreas']);
    Route::get('types/{id_menu}', [MasterController::class, 'getTypesByIdMenu']);
    Route::get('wilayah', [MasterController::class, 'getWilayah']);
    Route::get('cabang', [MasterController::class, 'getCabang']);
});

// 🔹 Wizard
Route::post('/wizard/step/{step}', [WizardController::class, 'saveStep'])->middleware('session.auth');
Route::get('/wizard/session', [WizardController::class, 'getSession'])->middleware('session.auth');
Route::post('/wizard/finish', [WizardController::class, 'finish'])->middleware('session.auth');

// 🔹 CSRF Token
Route::get('csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

// 🔹 Document View Tracker
Route::middleware(['web'])->group(function () {
    Route::post('/document-views', [DocumentViewController::class, 'store'])->middleware('session.auth');
    Route::get('/document-views/stats', [DocumentViewController::class, 'stats']);
    Route::get('/document-views/document/{document_id}/check', [DocumentViewController::class, 'check']);
    Route::get('/document-views/document/{document_id}/unviewed', [DocumentViewController::class, 'unviewedUsers']);
    Route::get('/document-views/document/{document_id}/viewed', [DocumentViewController::class, 'viewedUsers']);
});

// 🔹 Catch-all for SPA (Vue/React/Next.js front-end)
// ⚠️ TARUH PALING BAWAH
require __DIR__ . '/quiz.php';
