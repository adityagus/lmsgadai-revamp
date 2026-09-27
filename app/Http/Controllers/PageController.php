<?php
namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\Content;
use App\Services\LmsAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show(Request $request)
    {
        $route = $request->route();
        $params = array_diff_key($route->parameters(), $route->defaults);
        $props = [
            'title' => $route->defaults['title'],
            'fullscreen' => $route->defaults['fullscreen'],
            'routeContext' => [
                'name' => $route->getName(), 'params' => $params, 'query' => $request->query(),
                'meta' => ['values' => $request->is('formulir') ? 2 : 1, 'quizPreview' => $route->getName() === 'quizPreview'],
            ],
        ];
        if ($route->defaults['page'] === 'Dashboard') {
            $role = app(LmsAccess::class)->role($request);
            $documents = Announcement::forUser(session('auth.idgrup'), session('auth.cabang'));
            $props['stats'] = [
                'courses' => Course::count(),
                'documents' => $role === 'trainer' ? null : (clone $documents)->count(),
                'categories' => DB::table('categories')->count(),
            ];
            $props['courses'] = Course::with('category')->latest()->limit(4)->get()->map(fn ($course) => [
                'id' => $course->id, 'name' => $course->name, 'category' => $course->category?->name,
                'thumbnail' => $course->thumbnail ? \Illuminate\Support\Facades\Storage::disk('aktif')->url($course->thumbnail) : null,
            ]);
            $props['documents'] = $role === 'trainer' ? [] : (clone $documents)->with('menu')->latest()->limit(5)->get(['id', 'title', 'submenu_id', 'created_at']);
        }
        return Inertia::render($route->defaults['page'], $props);
    }

    public function editContent(Request $request, $contentId)
    {
        abort_unless(in_array(app(LmsAccess::class)->role($request), ['admin', 'trainer', 'teacher'], true), 403);
        $content = Content::findOrFail($contentId);
        return redirect('/course/update-contents/'.$content->course_id.'/'.$content->id);
    }
}
