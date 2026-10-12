<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\{EmployeeDirectory, RoleAccess};
use App\Services\Quiz\{AttemptHistory, QuizService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningHistoryController extends Controller
{
    private function query(Request $r)
    {
        $query = DB::table('quiz_attempts as a')->join('lms_users as u', 'u.id', '=', 'a.student_id')
            ->join('quizzes as q', 'q.id', '=', 'a.quiz_id')
            ->leftJoin('learning_attempt_history as h', 'h.attempt_id', '=', 'a.id')
            ->leftJoin('learning_quiz_versions as v', 'v.id', '=', 'h.quiz_version_id');
        $username = $r->session()->get('auth.user');
        if (RoleAccess::isAdmin($r)) return $query;
        if (RoleAccess::isManager($r)) {
            $id = DB::table('lms_users')->where('username', $username)->value('id');
            return $query->where(function ($where) use ($username, $id) {
                $where->where('h.batch_owner', $username);
                if ($id) $where->orWhere('q.created_by', $id);
            });
        }
        return $query->where('u.username', $username);
    }

    public function index(Request $r, EmployeeDirectory $directory, QuizService $service)
    {
        $data = $r->validate(['batch_id' => 'nullable|integer|min:1', 'username' => 'nullable|string|max:150', 'page' => 'nullable|integer|min:1']);
        $query = $this->query($r);
        if (!empty($data['batch_id'])) $query->where('h.batch_id', $data['batch_id']);
        $canFilterStudents = RoleAccess::isManager($r);
        $participants = $canFilterStudents
            ? (clone $query)->select('u.username')->distinct()->orderBy('u.username')->pluck('u.username')
            : collect();
        if (!empty($data['username'])) $query->where('u.username', $data['username']);
        $page = $query->orderByDesc('a.id')->select('a.*', 'u.username', 'q.title', 'h.batch_name', 'h.batch_id', 'v.version as recorded_version', 'v.snapshot')->paginate(15);
        $names = $directory->names($participants->merge($page->getCollection()->pluck('username'))->unique()->all());
        $page->setCollection($page->getCollection()->map(function ($row) use ($r, $names, $service) {
            $quiz = $service->quiz($row->quiz_id);
            $visible = RoleAccess::isManager($r) || ($service->released($quiz, $row) && $quiz->show_score);
            $snapshot = $row->snapshot ? json_decode($row->snapshot) : null;
            return ['id' => $row->id, 'quiz_id' => $row->quiz_id, 'title' => $snapshot?->quiz?->title ?? $row->title,
                'username' => $row->username, 'name' => $names[$row->username] ?? $row->username,
                'batch_id' => $row->batch_id, 'batch_name' => $row->batch_name, 'recorded_version' => $row->recorded_version,
                'attempt_number' => $row->attempt_number, 'started_at' => $row->started_at, 'submitted_at' => $row->submitted_at,
                'status' => $row->status, 'pending_review' => (bool) $row->pending_review,
                'score_visible' => $visible, 'score' => $visible ? $row->score : null];
        }));
        return array_merge($page->toArray(), [
            'can_filter_students' => $canFilterStudents,
            'participants' => $participants->map(fn ($username) => [
                'username' => $username, 'name' => $names[$username] ?? $username,
            ])->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all(),
        ]);
    }

    public function show(Request $r, int $id, QuizService $service)
    {
        abort_unless($this->query($r)->where('a.id', $id)->exists(), 404);
        $attempt = DB::table('quiz_attempts')->find($id);
        abort_if($attempt->status === 'IN_PROGRESS', 409, 'Percobaan masih berlangsung. Riwayat jawaban tersedia setelah dikumpulkan.');
        $data = $service->payload($attempt, RoleAccess::isManager($r));
        $history = app(AttemptHistory::class)->read($id);
        $data['recorded_at'] = $history?->created_at;
        $data['materials'] = $history ? collect($history->course_snapshot->contents ?? [])
            ->filter(fn ($item) => RoleAccess::isManager($r) || in_array($item->id, $history->course_snapshot->completed_content_ids ?? []))
            ->map(fn ($item) => collect($item)->only(['id', 'title', 'type', 'content', 'lampiran'])->all())->values() : [];
        $data['grade_history'] = RoleAccess::isManager($r) || isset($data['score']) ? DB::table('learning_grade_audits')->where('attempt_id', $id)
            ->orderBy('id')->get(['previous_points', 'points', 'feedback', 'graded_by', 'created_at']) : [];
        $data['history_notice'] = $history
            ? 'Soal dan materi direkam saat percobaan dimulai. Nilai percobaan tetap tersimpan; penilaian essay dapat dilengkapi oleh trainer.'
            : 'Percobaan dibuat sebelum pencatatan versi. Nilai lama tetap tersimpan, tetapi isi asli sebelum perubahan dan batch asal tidak dapat dipastikan.';
        return $data;
    }
}
