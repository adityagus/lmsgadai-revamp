<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Course;
use App\Services\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseContentStructureController extends Controller
{
    public function index(Request $request, int $id)
    {
        RoleAccess::assertLearningManager($request);
        Course::findOrFail($id);

        $contents = DB::table('course_details')
            ->where('course_id', $id)
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'course_id', 'parent_id', 'title', 'type', 'quiz_id', 'content', 'order']);

        return response()->json(['contents' => $contents]);
    }

    public function storeChapter(Request $request, int $id)
    {
        RoleAccess::assertLearningManager($request);
        Course::findOrFail($id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
        ], [
            'title.required' => 'Judul Bab / Modul wajib diisi.',
            'title.max' => 'Judul Bab / Modul maksimal 150 karakter.',
        ]);

        $nextOrder = (int) (DB::table('course_details')->where('course_id', $id)->max('order') ?? 0) + 1;

        $chapter = Content::create([
            'course_id' => $id,
            'parent_id' => null,
            'title' => trim($data['title']),
            'type' => 'parent',
            'order' => $nextOrder,
        ]);

        return response()->json([
            'message' => 'Bab / Modul berhasil ditambahkan.',
            'data' => $chapter,
        ], 201);
    }

    public function updateChapter(Request $request, int $id, int $chapterId)
    {
        RoleAccess::assertLearningManager($request);
        Course::findOrFail($id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
        ], [
            'title.required' => 'Judul Bab / Modul wajib diisi.',
            'title.max' => 'Judul Bab / Modul maksimal 150 karakter.',
        ]);

        $chapter = Content::where('course_id', $id)
            ->where('id', $chapterId)
            ->where('type', 'parent')
            ->firstOrFail();

        $chapter->update(['title' => trim($data['title'])]);

        return response()->json([
            'message' => 'Nama Bab / Modul berhasil diperbarui.',
            'data' => $chapter,
        ]);
    }

    public function updateStructure(Request $request, int $id)
    {
        RoleAccess::assertLearningManager($request);

        $data = $request->validate([
            'items' => ['present', 'array', 'list'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.parent_id' => ['nullable', 'integer'],
            'original_ids' => ['present', 'array', 'list'],
            'original_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $items = $data['items'];
        $original = array_map('intval', $data['original_ids']);

        DB::transaction(function () use ($id, $items, $original) {
            Course::whereKey($id)->lockForUpdate()->firstOrFail();

            $rows = DB::table('course_details')
                ->where('course_id', $id)
                ->orderBy('order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'type']);

            $current = $rows->pluck('id')->map(fn ($v) => (int) $v)->all();
            abort_unless($current === $original, 409, 'Daftar materi telah berubah. Muat ulang sebelum menyimpan urutan.');

            $submittedIds = array_map(fn ($item) => (int) $item['id'], $items);
            $expectedSorted = $current;
            $submittedSorted = $submittedIds;
            sort($expectedSorted);
            sort($submittedSorted);

            if ($expectedSorted !== $submittedSorted) {
                throw ValidationException::withMessages([
                    'items' => 'Urutan harus memuat seluruh materi dan bab tepat satu kali.',
                ]);
            }

            $typeMap = $rows->pluck('type', 'id')->all();
            $parentIds = collect($rows)->where('type', 'parent')->pluck('id')->map(fn ($v) => (int) $v)->all();

            foreach ($items as $index => $item) {
                $itemId = (int) $item['id'];
                $parentId = isset($item['parent_id']) && $item['parent_id'] !== null ? (int) $item['parent_id'] : null;

                if (($typeMap[$itemId] ?? '') === 'parent' && $parentId !== null) {
                    throw ValidationException::withMessages([
                        'items' => 'Bab / Modul tidak boleh berada di dalam Bab lain.',
                    ]);
                }

                if ($parentId !== null && !in_array($parentId, $parentIds, true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Bab tujuan tidak valid untuk kelas ini.',
                    ]);
                }

                DB::table('course_details')
                    ->where('course_id', $id)
                    ->where('id', $itemId)
                    ->update([
                        'parent_id' => $parentId,
                        'order' => $index + 1,
                    ]);
            }
        });

        return response()->json([
            'message' => 'Urutan bab dan materi berhasil disimpan.',
        ]);
    }
}
