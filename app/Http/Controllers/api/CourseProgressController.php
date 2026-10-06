<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseAudience;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseProgressController extends Controller
{
    private function contents(Request $request, int $id)
    {
        app(CourseAudience::class)->assertAllowed($id, $request);
        return Course::findOrFail($id)->contents()->orderBy('order')->orderBy('id')->get();
    }

    private function snapshot(Request $request, int $id, $contents): array
    {
        $username = (string) $request->session()->get('auth.user');
        $completed = app(\App\Services\CourseSequence::class)->completed($contents, $id, $username);

        $items = $contents->map(fn ($content) => [
            'content_id' => $content->id,
            'completed_at' => isset($completed[$content->id]) ? Carbon::parse($completed[$content->id])->toIso8601String() : null,
        ])->values();
        $count = $items->whereNotNull('completed_at')->count();

        return ['items' => $items, 'completed_count' => $count, 'total' => $contents->count(),
            'resume_content_id' => $items->firstWhere('completed_at', null)['content_id'] ?? $contents->last()?->id,
            'is_complete' => $contents->isNotEmpty() && $count === $contents->count()];
    }

    public function show(Request $request, int $id)
    {
        return $this->snapshot($request, $id, $this->contents($request, $id));
    }

    public function complete(Request $request, int $id, int $contentId)
    {
        $contents = $this->contents($request, $id);
        $content = $contents->firstWhere('id', $contentId);
        abort_unless($content, 404);
        $legacyForm = $content->type === 'quiz' && !$content->quiz_id
            && preg_match('~https://(?:docs\.google\.com/forms/|forms\.gle/)~i', $content->content ?? '');
        abort_if($content->quiz_id || ($content->type === 'quiz' && !$legacyForm), 422, 'Selesaikan quiz melalui halaman pengerjaan.');
        app(\App\Services\CourseSequence::class)->assertUnlocked($id, $contentId, (string) $request->session()->get('auth.user'));

        // A unique row and upsert make retries safe while preserving the first completion time.
        DB::table('course_content_progress')->upsert([[
            'username' => (string) $request->session()->get('auth.user'),
            'course_id' => $id, 'content_id' => $contentId, 'completed_at' => now(),
        ]], ['username', 'course_id', 'content_id'], ['content_id']);

        return $this->snapshot($request, $id, $contents);
    }
}
