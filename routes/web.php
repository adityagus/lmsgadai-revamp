<?php
use App\Http\Controllers\api\CourseController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\StoredFileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/storage/{path}', StoredFileController::class)->where('path', '.*');
require __DIR__.'/legacy.php';
Route::get('/api/popular-courses', [CourseController::class, 'popular']);
Route::get('/sign-in', fn () => session('auth.user') ? redirect('/overview') : Inertia::render('Auth/SignIn', ['title' => 'Masuk', 'fullscreen' => true]))->name('login');
Route::redirect('/lms', '/kelas');
Route::redirect('/report', '/report/documents');
Route::redirect('/roles', '/master/roles');
Route::redirect('/role-management', '/master/roles');
Route::get('/course/update-content/{contentId}', [PageController::class, 'editContent']);
Route::get('/course/update-contents/{contentId}', [PageController::class, 'editContent']);
Route::get('/student/courses/{id}', fn ($id) => redirect('/student/kelas/'.$id));
foreach (config('pages') as [$uri, $name, $page, $title, $access, $fullscreen]) {
    $route = Route::get($uri, [PageController::class, 'show'])->name($name)
        ->defaults('page', $page)->defaults('title', $title)
        ->defaults('access', $access)->defaults('fullscreen', $fullscreen);
    if ($access !== null) {
        $route->middleware('jabatan:'.$access);
    }
}
