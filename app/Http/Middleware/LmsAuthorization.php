<?php
namespace App\Http\Middleware;

use App\Services\LmsAccess;
use Closure;
use Illuminate\Http\Request;

class LmsAuthorization
{
    public function handle(Request $request, Closure $next)
    {
        // Keep the existing public course-detail page and its preview contract.
        if ($request->isMethod('GET') && $request->is('api/preview-course/*', 'api/v1/preview-course/*')) {
            return $next($request);
        }
        if ($request->is('/', 'sign-in', 'login', 'session', 'csrf-token', 'up', 'api/v1/login', 'api/v1/session') || $request->is('detail-course/*')) {
            return $next($request);
        }
        if ($request->session()->get('auth.status') !== 'login' || !$request->session()->has('auth.user')) {
            return $request->expectsJson() && !$request->header('X-Inertia')
                ? response()->json(['message' => 'Unauthenticated'], 401)
                : redirect('/sign-in');
        }
        $role = app(LmsAccess::class)->role($request);
        $manager = in_array($role, ['admin', 'teacher', 'trainer'], true);
        $action = $request->route()->getActionName();
        [$controller, $method] = array_pad(explode('@', $action), 2, null);
        $short = class_basename($controller);
        $access = $request->route()->defaults['access'] ?? null;
        if ($access === 'manager') abort_unless($manager, 403);
        if ($access === 'admin') abort_unless($role === 'admin', 403);

        if (in_array($short, ['CourseController', 'ContentController', 'MasterController', 'WizardController', 'FileUploadController'], true) && !$request->isMethod('GET')) {
            abort_unless($manager, 403);
        }
        if ($short === 'AnnouncementController' && (!$request->isMethod('GET') || $method === 'trash')) {
            abort_unless($role === 'admin', 403);
        }
        if ($short === 'MigrasiDataController') abort_unless($role === 'admin', 403);
        if ($short === 'DocumentReportController' || ($short === 'DocumentViewController' && in_array($method, ['stats', 'viewedUsers', 'unviewedUsers'], true))) {
            abort_unless($manager, 403);
        }
        if ($role === 'trainer') {
            abort_if($request->is('pengumuman', 'formulir', 'detail-pengumuman/*', 'archive-pengumuman'), 403);
            abort_if($short === 'DocumentViewController' && !$request->isMethod('GET'), 403);
            abort_if(in_array($short, ['AnnouncementController', 'InformationController', 'MigrasiDataController', 'OverviewController'], true), 403);
            abort_if($short === 'MasterController' && !in_array($method, ['getCategories', 'addCategory', 'updateCategory', 'deleteCategory'], true), 403);
        }
        return $next($request);
    }
}
