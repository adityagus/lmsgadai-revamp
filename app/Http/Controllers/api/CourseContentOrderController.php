<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\LmsAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseContentOrderController extends Controller
{
    private function authorizeManager(Request $request): void
    {
        abort_unless(in_array(app(LmsAccess::class)->role($request), ['admin', 'teacher', 'trainer'], true), 403);
    }

    public function index(Request $request, int $id)
    {
        $this->authorizeManager($request);
        Course::findOrFail($id);

        return ['contents' => DB::table('course_details')->where('course_id', $id)
            ->orderBy('order')->orderBy('id')->get(['id', 'title', 'type', 'quiz_id'])];
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'content_ids' => 'present|array|list',
            'content_ids.*' => 'required|integer|distinct',
            'original_ids' => 'present|array|list',
            'original_ids.*' => 'required|integer|distinct',
        ]);
        $order = array_map('intval', $data['content_ids']);
        $original = array_map('intval', $data['original_ids']);

        DB::transaction(function () use ($id, $order, $original) {
            Course::whereKey($id)->lockForUpdate()->firstOrFail();
            $current = DB::table('course_details')->where('course_id', $id)
                ->orderBy('order')->orderBy('id')->lockForUpdate()->pluck('id')->map(fn ($value) => (int) $value)->all();
            abort_unless($current === $original, 409, 'Daftar materi telah berubah. Muat ulang sebelum menyimpan urutan.');

            $expected = $current;
            $submitted = $order;
            sort($expected);
            sort($submitted);
            if ($expected !== $submitted) {
                throw ValidationException::withMessages(['content_ids' => 'Urutan harus memuat seluruh materi kelas tepat satu kali.']);
            }

            foreach ($order as $index => $contentId) {
                DB::table('course_details')->where('course_id', $id)->where('id', $contentId)->update(['order' => $index + 1]);
            }
        });

        return ['message' => 'Urutan materi dan kuis berhasil disimpan.'];
    }
}
