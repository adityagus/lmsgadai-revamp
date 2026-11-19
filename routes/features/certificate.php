<?php

use App\Http\Controllers\api\CourseCertificateController;
use Illuminate\Support\Facades\Route;

// Course completion certificates (templates + issued certificates).
Route::get('/courses/{id}/certificate-status', [CourseCertificateController::class, 'status'])->whereNumber('id');
Route::post('/courses/{id}/certificate/issue', [CourseCertificateController::class, 'issue'])->whereNumber('id');
Route::get('/courses/{id}/certificate-template', [CourseCertificateController::class, 'getTemplate'])->whereNumber('id');
Route::post('/courses/{id}/certificate-template', [CourseCertificateController::class, 'saveTemplate'])->whereNumber('id');
Route::post('/courses/{id}/certificate/preview-issue', [CourseCertificateController::class, 'previewIssue'])->whereNumber('id');
Route::get('/certificate-templates/{templateId}/file', [CourseCertificateController::class, 'streamTemplateFile'])->whereNumber('templateId');
