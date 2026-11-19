<?php

use App\Http\Controllers\api\CourseContentStructureController;
use Illuminate\Support\Facades\Route;

// Course material structure (Bab/Modul accordion, grouping and ordering).
Route::get('/courses/{id}/content-structure', [CourseContentStructureController::class, 'index'])->whereNumber('id');
Route::put('/courses/{id}/content-structure', [CourseContentStructureController::class, 'updateStructure'])->whereNumber('id');
Route::post('/courses/{id}/chapters', [CourseContentStructureController::class, 'storeChapter'])->whereNumber('id');
Route::patch('/courses/{id}/chapters/{chapterId}', [CourseContentStructureController::class, 'updateChapter'])->whereNumber(['id', 'chapterId']);
