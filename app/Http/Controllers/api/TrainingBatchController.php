<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\{EmployeeDirectory, RoleAccess};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TrainingBatchController extends Controller
{
    private function visible(Request $r)
    {
        $query = DB::table('training_batches');
        if (RoleAccess::isAdmin($r)) return $query;
        if (RoleAccess::isManager($r)) return $query->where('created_by', $r->session()->get('auth.user'));
        return $query->where('status', '!=', 'draft')->whereIn('id', DB::table('training_batch_members')
            ->where('username', $r->session()->get('auth.user'))->select('batch_id'));
    }

    public function index(Request $r)
    {
        $rows = $this->visible($r)->orderByDesc('id')->get();
        $members = DB::table('training_batch_members')->selectRaw('batch_id, count(*) as total')->groupBy('batch_id')->pluck('total', 'batch_id');
        $courses = DB::table('training_batch_courses')->selectRaw('batch_id, count(*) as total')->groupBy('batch_id')->pluck('total', 'batch_id');
        return ['batches' => $rows->map(function ($b) use ($members, $courses) {
            $b->member_count = $members[$b->id] ?? 0; $b->course_count = $courses[$b->id] ?? 0; return $b;
        }), 'can_manage' => RoleAccess::isManager($r)];
    }

    public function options(Request $r, EmployeeDirectory $directory)
    {
        RoleAccess::assertManager($r);
        $data = $r->validate(['q' => 'nullable|string|max:100']);
        return ['employees' => $directory->search(trim($data['q'] ?? '')),
            'courses' => DB::table('courses')->orderBy('name')->get(['id', 'name'])];
    }

    public function learning(Request $r)
    {
        $username = (string) $r->session()->get('auth.user');
        $batches = DB::table('training_batches')->where('status', '!=', 'draft')
            ->whereIn('id', DB::table('training_batch_members')->where('username', $username)->select('batch_id'))
            ->orderByDesc('id')->get(['id', 'name', 'description', 'status', 'starts_on', 'ends_on']);
        $links = DB::table('training_batch_courses')->whereIn('batch_id', $batches->pluck('id'))->get();
        $courses = DB::table('courses')->whereIn('id', $links->pluck('course_id'))->get(['id', 'name']);
        $contents = DB::table('course_details')->whereIn('course_id', $courses->pluck('id'))->orderBy('order')->orderBy('id')->get()->groupBy('course_id');
        $summaries = $courses->mapWithKeys(function ($course) use ($r, $username, $contents) {
            $items = $contents->get($course->id, collect());
            $completed = app(\App\Services\CourseSequence::class)->completed($items, $course->id, $username)->count();
            return [$course->id => ['id' => $course->id, 'name' => $course->name, 'total' => $items->count(),
                'completed' => $completed, 'can_open' => app(\App\Services\CourseAudience::class)->allows($course->id, $r)]];
        });
        return ['batches' => $batches->map(function ($batch) use ($links, $summaries) {
            $today = today()->toDateString();
            $batch->state = $batch->status === 'completed' ? 'completed' : ($batch->starts_on > $today ? 'scheduled' : ($batch->ends_on < $today ? 'expired' : 'ready'));
            $batch->courses = $links->where('batch_id', $batch->id)->map(fn ($link) => $summaries->get($link->course_id))->filter()->values();
            return $batch;
        })];
    }

    public function show(Request $r, int $id, EmployeeDirectory $directory)
    {
        $batch = $this->visible($r)->where('id', $id)->first(); abort_unless($batch, 404);
        $members = DB::table('training_batch_members')->where('batch_id', $id)->pluck('username');
        if (!RoleAccess::isManager($r)) $members = $members->filter(fn ($u) => $u === $r->session()->get('auth.user'))->values();
        $names = $directory->names($members->all());
        $courses = DB::table('courses')->whereIn('id', DB::table('training_batch_courses')->where('batch_id', $id)->select('course_id'))->get(['id', 'name']);
        $contents = DB::table('course_details')->whereIn('course_id', $courses->pluck('id'))->get()->groupBy('course_id');
        $completed = Schema::hasTable('course_content_progress') ? DB::table('course_content_progress')
            ->whereIn('username', $members)->whereIn('course_id', $courses->pluck('id'))->whereNotNull('completed_at')
            ->get(['username', 'content_id'])->groupBy('username')->map(fn ($rows) => $rows->pluck('content_id')->flip()) : collect();
        $submitted = collect();
        if (Schema::hasTable('quiz_attempts') && Schema::hasTable('lms_users')) {
            $quizIds = $contents->flatten(1)->pluck('quiz_id')->filter()->unique();
            $submitted = DB::table('quiz_attempts as a')->join('lms_users as u', 'u.id', '=', 'a.student_id')
                ->whereIn('u.username', $members)->whereIn('a.quiz_id', $quizIds)
                ->where('a.status', '!=', 'IN_PROGRESS')->whereNotNull('a.submitted_at')
                ->distinct()->get(['u.username', 'a.quiz_id'])->groupBy('username')->map(fn ($rows) => $rows->pluck('quiz_id')->flip());
        }
        $progress = [];
        foreach ($members as $username) foreach ($courses as $course) {
            $items = $contents->get($course->id, collect());
            $done = $items->filter(fn ($item) => $item->quiz_id !== null
                ? isset($submitted[$username][$item->quiz_id]) : isset($completed[$username][$item->id]))->count();
            $progress[] = ['username' => $username, 'name' => $names[$username] ?? $username,
                'course_id' => $course->id, 'course_name' => $course->name, 'completed' => $done,
                'total' => $items->count(), 'percent' => $items->isEmpty() ? 0 : (int) round($done / $items->count() * 100)];
        }
        return ['batch' => $batch, 'members' => $members->map(fn ($u) => ['username' => $u, 'name' => $names[$u] ?? $u]),
            'courses' => $courses, 'progress' => $progress,
            'accessible_course_ids' => $courses->filter(fn ($course) => app(\App\Services\CourseAudience::class)->allows($course->id, $r))->pluck('id')->values()];
    }

    public function save(Request $r, EmployeeDirectory $directory, ?int $id = null)
    {
        RoleAccess::assertManager($r);
        $existing = $id ? $this->visible($r)->where('id', $id)->first() : null;
        if ($id) abort_unless($existing, 404);
        $data = $r->validate([
            'name' => 'required|string|max:255', 'description' => 'nullable|string|max:2000',
            'starts_on' => 'required|date_format:Y-m-d', 'ends_on' => 'required|date_format:Y-m-d|after_or_equal:starts_on',
            'status' => 'required|in:draft,active,completed', 'version' => $id ? 'required|integer|min:1' : 'nullable|integer',
            'usernames' => 'present|array|max:200', 'usernames.*' => 'required|string|max:150|distinct',
            'course_ids' => 'present|array|max:20', 'course_ids.*' => 'required|integer|distinct|exists:courses,id',
        ]);
        $old = $id ? DB::table('training_batch_members')->where('batch_id', $id)->pluck('username')->all() : [];
        $new = array_values(array_diff($data['usernames'], $old));
        $valid = $directory->accounts($new)->pluck('username')->all();
        if (array_diff($new, $valid)) throw ValidationException::withMessages(['usernames' => 'Pilih peserta dari akun karyawan aktif.']);
        if ($data['status'] === 'active' && (!$data['usernames'] || !$data['course_ids'])) {
            throw ValidationException::withMessages(['status' => 'Tambahkan minimal satu peserta dan satu kelas sebelum mengaktifkan batch.']);
        }
        $id = DB::transaction(function () use ($r, $id, $data) {
            $values = collect($data)->except(['usernames', 'course_ids', 'version'])->all();
            $values['updated_at'] = now();
            if ($id) {
                $row = DB::table('training_batches')->where('id', $id)->lockForUpdate()->first();
                abort_unless($row && (int) $row->version === $data['version'], 409, 'Batch telah berubah. Muat ulang sebelum menyimpan.');
                DB::table('training_batches')->where('id', $id)->update($values + ['version' => $row->version + 1]);
            } else {
                $id = DB::table('training_batches')->insertGetId($values + ['created_by' => $r->session()->get('auth.user'), 'created_at' => now()]);
            }
            DB::table('training_batch_members')->where('batch_id', $id)->delete();
            DB::table('training_batch_courses')->where('batch_id', $id)->delete();
            foreach ($data['usernames'] as $username) DB::table('training_batch_members')->insert(['batch_id' => $id, 'username' => $username]);
            foreach ($data['course_ids'] as $course) DB::table('training_batch_courses')->insert(['batch_id' => $id, 'course_id' => $course]);
            return $id;
        });
        return ['id' => $id, 'message' => 'Batch pelatihan berhasil disimpan.'];
    }
}
