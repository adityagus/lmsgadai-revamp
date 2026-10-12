<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Quiz\StudentQuizController as Student;
use App\Http\Controllers\Quiz\ManageQuizController as Manage;
use App\Http\Controllers\Quiz\QuizResultsController as Results;

Route::prefix('api/quiz')->middleware(['session.auth', \App\Http\Middleware\QuizRequestGuard::class])->group(function () {
    Route::get('student', [Student::class, 'index']);
    Route::post('{quiz}/start', [Student::class, 'start'])->whereNumber('quiz');
    Route::get('attempts/{attempt}', [Student::class, 'show']);
    Route::put('attempts/{attempt}/answers/{question}', [Student::class, 'save']);
    Route::post('attempts/{attempt}/submit', [Student::class, 'submit']);
    Route::get('manage', [Manage::class, 'index']);
    Route::get('audience-options', [Manage::class, 'audienceOptions']);
    Route::get('metadata', [Manage::class, 'metadata']);
    Route::post('users', [Manage::class, 'saveUser']);
    Route::post('classes', [Manage::class, 'saveClass']);
    Route::post('banks', [Manage::class, 'bank']);
    Route::get('questions', [Manage::class, 'questions']);
    Route::post('questions', [Manage::class, 'saveQuestion']);
    Route::post('question-images', [Manage::class, 'uploadQuestionImage']);
    Route::get('question-images/{path}', [Manage::class, 'showQuestionImage'])
        ->where('path', '[0-9]+/[A-Za-z0-9]+\.(?:jpg|jpeg|png|webp)')->name('quiz.question-image');
    Route::post('questions/replace-one', [Manage::class, 'replaceOneQuestion']);
    Route::post('questions/bulk', [Manage::class, 'saveQuestionsBulk']);
    Route::put('questions/{id}', [Manage::class, 'saveQuestion']);
    Route::post('generate', [Manage::class, 'generate']);
    Route::post('manage', [Manage::class, 'save']);
    Route::get('manage/{quiz}', [Manage::class, 'show']);
    Route::put('manage/{quiz}', [Manage::class, 'save']);
    Route::post('manage/{quiz}/duplicate', [Manage::class, 'duplicate']);
    Route::post('manage/{quiz}/overrides', [Manage::class, 'override']);
    Route::patch('manage/{quiz}/visibility', [Manage::class, 'visibility']);
    Route::patch('manage/{quiz}/questions/{question}/explanation', [Manage::class, 'explanation']);
    Route::get('manage/{quiz}/preview', [Manage::class, 'preview']);
    Route::get('manage/{quiz}/results', [Results::class, 'index']);
    Route::get('results/{attempt}', [Results::class, 'show']);
    Route::put('results/{attempt}/grade/{question}', [Results::class, 'grade']);
});
