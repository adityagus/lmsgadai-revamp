<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use Inertia\Inertia;

require __DIR__.'/legacy.php';
Route::get('/', fn () => redirect(session('auth.user') ? '/overview' : '/sign-in'));
Route::get('/sign-in', fn () => session('auth.user') ? redirect('/overview') : Inertia::render('Auth/SignIn', ['title' => 'Masuk', 'fullscreen' => true]))->name('login');
Route::redirect('/lms', '/kelas');
Route::redirect('/report', '/report/documents');
Route::get('/course/update-content/{contentId}', [PageController::class, 'editContent']);
Route::get('/course/update-contents/{contentId}', [PageController::class, 'editContent']);
Route::get('/student/courses/{id}', fn ($id) => redirect('/student/kelas/'.$id));
foreach (config('pages') as [$uri, $name, $page, $title, $access, $fullscreen]) {
    Route::get($uri, [PageController::class, 'show'])->name($name)
        ->defaults('page', $page)->defaults('title', $title)
        ->defaults('access', $access)->defaults('fullscreen', $fullscreen);
}
