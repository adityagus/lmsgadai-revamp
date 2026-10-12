<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Services\Quiz\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentQuizController extends Controller
{
    private $service;
    public function __construct(QuizService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $user = $this->service->identity($request);
        $context = $request->validate(['batch_id' => 'nullable|integer|min:1']);
        $batchId = $context['batch_id'] ?? null;
        $batchCourses = null;
        if ($batchId) {
            abort_unless(DB::table('training_batch_members')->where('batch_id', $batchId)->where('username', $user->username)->exists(), 403);
            $batchCourses = DB::table('training_batch_courses')->where('batch_id', $batchId)->pluck('course_id');
        }
        $quizzes = DB::table('quizzes')->where('status', '!=', 'DRAFT')->where(function ($q) use ($user) {
            $q->whereIn('course_id', DB::table('courses')->select('id'))
                ->orWhere(function ($legacy) use ($user) {
                    $legacy->whereNull('course_id')->where(function ($assigned) use ($user) {
                        $assigned->whereIn('id', DB::table('quiz_students')->where('student_id', $user->id)->select('quiz_id'))
                            ->orWhereIn('id', DB::table('quiz_classes')->whereIn('class_id', DB::table('class_students')->where('student_id', $user->id)->select('class_id'))->select('quiz_id'));
                    });
                });
        })->orderByDesc('id')->get();
        $items = [];
        foreach ($quizzes as $quiz) {
            if ($batchCourses !== null && !$batchCourses->contains($quiz->course_id)) continue;
            if (!$this->service->assigned($quiz, $user)) {
                continue;
            }
            foreach (DB::table('quiz_attempts')->where('quiz_id', $quiz->id)->where('student_id', $user->id)->where('status', 'IN_PROGRESS')->pluck('id') as $id) {
                $this->service->expire($id);
            }
            $attemptQuery = DB::table('quiz_attempts')->where('quiz_id', $quiz->id)->where('student_id', $user->id)->orderByDesc('attempt_number');
            $attempts = app(\App\Services\Quiz\AttemptHistory::class)->scope($attemptQuery, $batchId)->get(['id', 'status', 'attempt_number']);
            $settings = $this->service->settings($quiz, $user->id);
            $active = $attempts->firstWhere('status', 'IN_PROGRESS');
            $remaining = !$settings['maximum'] || $attempts->count() < $settings['maximum'];
            $sequenceLocked = $user->role === 'student' && !app(\App\Services\CourseSequence::class)->quizUnlocked($quiz, $user->username);
            $status = $active ? 'Sedang Dikerjakan' : ($attempts->count() && !$remaining ? 'Selesai' : (($settings['from'] && now()->lt($settings['from'])) ? 'Belum Dimulai' : (($settings['until'] && now()->gte($settings['until'])) || $quiz->status === 'CLOSED' ? 'Expired' : 'Tersedia')));
            $items[] = [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'course_id' => $quiz->course_id,
                'course' => $quiz->course_id ? DB::table('courses')->where('id', $quiz->course_id)->value('name') : null,
                'classes' => $quiz->course_id ? DB::table('courses')->where('id', $quiz->course_id)->pluck('name') : DB::table('classes')->whereIn('id', DB::table('quiz_classes')->where('quiz_id', $quiz->id)->select('class_id'))->pluck('name'),
                'duration_minutes' => $settings['minutes'],
                'question_count' => $quiz->question_count,
                'passing_grade' => $quiz->passing_grade,
                'no_schedule' => !$settings['from'] && !$settings['until'],
                'available_from' => $settings['from'] ? $settings['from']->toIso8601String() : null,
                'available_until' => $settings['until'] ? $settings['until']->toIso8601String() : null,
                'status' => $status,
                'can_start' => !$sequenceLocked && $status === 'Tersedia',
                'sequence_locked' => $sequenceLocked,
                'active_attempt' => $active->id ?? null,
                'maximum_attempt' => $settings['maximum'],
                'attempts' => $attempts,
            ];
        }
        return ['user' => $user, 'quizzes' => $items, 'server_now' => now()->toIso8601String()];
    }

    public function start(Request $r, $quiz)
    {
        $user = $this->service->identity($r);
        $data = $r->validate(['batch_id' => 'nullable|integer|min:1']);
        $id = $this->service->start($this->service->quiz($quiz), $user, $data['batch_id'] ?? null);
        return $this->service->payload($this->service->attempt($id, $user));
    }

    public function show(Request $r, $attempt)
    {
        $a = $this->service->attempt($attempt, $this->service->identity($r));
        if ($r->boolean('heartbeat') && $a->status === 'IN_PROGRESS') {
            return ['status' => $a->status, 'server_now' => now()->toIso8601String(), 'expired_at' => Carbon::parse($a->expired_at)->toIso8601String()];
        }
        return $this->service->payload($a);
    }

    public function save(Request $r, $attempt, $question)
    {
        $a = $this->service->attempt($attempt, $this->service->identity($r));
        $data = $r->validate([
            'selected_options' => 'present|array|max:30',
            'selected_options.*' => 'integer|distinct',
            'essay' => 'nullable|string|max:20000',
            'flagged' => 'required|boolean',
            'visited' => 'required|boolean',
            'revision' => 'required|integer|min:0',
        ]);
        return DB::transaction(function () use ($a, $question, $data) {
            $locked = DB::table('quiz_attempts')->where('id', $a->id)->lockForUpdate()->first();
            if ($locked->status !== 'IN_PROGRESS' || now()->gte(Carbon::parse($locked->expired_at))) {
                $this->service->expire($a->id);
                return response()->json(['message' => 'Waktu habis atau quiz sudah dikumpulkan.'], 409);
            }
            $aq = DB::table('quiz_attempt_questions')->where('attempt_id', $a->id)->where('id', $question)->first();
            abort_unless($aq, 404);
            $q = $this->service->answerRows($a->id)->firstWhere('id', $aq->id);
            $allowed = $this->service->optionsForAttempt($a->id, $aq->question_id)->pluck('id')->all();
            abort_if(count(array_diff($data['selected_options'], $allowed)), 422, 'Pilihan jawaban tidak valid.');
            abort_if($q->type !== 'multiple_answer' && count($data['selected_options']) > 1, 422);
            abort_if($q->type === 'essay' && count($data['selected_options']), 422);
            $answer = DB::table('student_answers')->where('attempt_question_id', $question)->first();
            if ((int) $answer->revision !== (int) $data['revision']) {
                return response()->json(['message' => 'Jawaban berubah di tab lain. Muat ulang sebelum melanjutkan.'], 409);
            }
            DB::table('student_answers')->where('id', $answer->id)->update([
                'selected_options' => json_encode(array_map('intval', $data['selected_options'])),
                'essay' => $q->type === 'essay' ? ($data['essay'] ?? '') : null,
                'flagged' => $data['flagged'],
                'visited' => $data['visited'],
                'revision' => $answer->revision + 1,
                'updated_at' => now(),
            ]);
            return ['revision' => $answer->revision + 1, 'server_now' => now()->toIso8601String()];
        }, 3);
    }

    public function submit(Request $r, $attempt)
    {
        $user = $this->service->identity($r);
        $this->service->attempt($attempt, $user);
        $this->service->submit($attempt);
        return $this->service->payload($this->service->attempt($attempt, $user));
    }
}
