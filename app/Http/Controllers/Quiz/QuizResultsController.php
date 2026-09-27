<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Services\Quiz\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuizResultsController extends Controller
{
    private $service;
    public function __construct(QuizService $service)
    {
        $this->service = $service;
    }

    public function index(Request $r, $quiz)
    {
        $user = $this->service->identity($r);
        $q = $this->service->quiz($quiz);
        $this->service->manage($user, $q);
        foreach (DB::table('quiz_attempts')->where('quiz_id', $quiz)->where('status', 'IN_PROGRESS')->pluck('id') as $id) {
            $this->service->expire($id);
        }
        $attempts = DB::table('quiz_attempts as a')->join('lms_users as u', 'u.id', '=', 'a.student_id')->where('quiz_id', $quiz)->orderBy('u.name')->orderBy('attempt_number')->select('a.*', 'u.name')->get();
        $studentIds = DB::table('quiz_students')->where('quiz_id', $quiz)->pluck('student_id')->merge(DB::table('class_students')->whereIn('class_id', DB::table('quiz_classes')->where('quiz_id', $quiz)->select('class_id'))->pluck('student_id'))->merge($attempts->pluck('student_id'))->unique();
        $course = $q->course_id ? DB::table('courses')->find($q->course_id) : null;
        if ($course) { $studentIds = DB::table('lms_users')->pluck('id'); }
        $scores = [];
        $classStats = [];
        foreach ($studentIds as $student) {
            $score = $this->service->aggregate($q, $student);
            if ($score !== null) {
                $scores[] = $score;
            }
            $classes = $course ? collect([$course]) : DB::table('classes')->join('class_students', 'classes.id', '=', 'class_students.class_id')->where('student_id', $student)->get(['classes.id', 'classes.name']);
            foreach ($classes as $class) {
                if (!isset($classStats[$class->id])) {
                    $classStats[$class->id] = ['name' => $class->name, 'participants' => 0, 'scores' => []];
                }
                $classStats[$class->id]['participants']++;
                if ($score !== null) {
                    $classStats[$class->id]['scores'][] = $score;
                }
            }
        }
        foreach ($attempts as $a) {
            $a->classes = $course ? collect([$course->name]) : DB::table('classes')->join('class_students', 'classes.id', '=', 'class_students.class_id')->where('student_id', $a->student_id)->pluck('name');
            $a->duration_seconds = $a->submitted_at ? Carbon::parse($a->started_at)->diffInSeconds(Carbon::parse($a->submitted_at)) : null;
            $a->aggregate_score = $this->service->aggregate($q, $a->student_id);
            foreach (['started_at', 'expired_at', 'submitted_at'] as $key) {
                $a->$key = $a->$key ? Carbon::parse($a->$key)->toIso8601String() : null;
            }
        }
        $questions = DB::table('quiz_attempt_questions as aq')->join('quiz_attempts as a', 'a.id', '=', 'aq.attempt_id')
            ->join('questions as q', 'q.id', '=', 'aq.question_id')->join('student_answers as sa', 'sa.attempt_question_id', '=', 'aq.id')
            ->where('a.quiz_id', $quiz)->where('a.status', '!=', 'IN_PROGRESS')->whereNotNull('sa.points')
            ->select('q.id', 'q.question', 'q.difficulty', DB::raw('COUNT(*) as responses'), DB::raw('SUM(CASE WHEN sa.points = q.weight THEN 1 ELSE 0 END) as correct'))
            ->groupBy('q.id', 'q.question', 'q.difficulty')->get()->map(function ($row) {
                $row->correct_rate = round($row->correct / max(1, $row->responses) * 100, 1);
                $row->wrong_rate = 100 - $row->correct_rate;
                return $row; })->sortBy('correct_rate')->values();
        return [
            'quiz' => $q,
            'attempts' => $attempts,
            'statistics' => [
                'participants' => $studentIds->count(),
                'completed' => $attempts->where('status', '!=', 'IN_PROGRESS')->pluck('student_id')->unique()->count(),
                'not_started' => $studentIds->diff($attempts->pluck('student_id'))->count(),
                'graded' => count($scores),
                'average_score' => count($scores) ? round(array_sum($scores) / count($scores), 2) : null,
                'highest_score' => count($scores) ? max($scores) : null,
                'lowest_score' => count($scores) ? min($scores) : null,
                'pass_rate' => count($scores) ? round(count(array_filter($scores, function ($s) use ($q) {
                    return $s >= $q->passing_grade; })) / count($scores) * 100, 1) : null,
                'average_completion_seconds' => round($attempts->whereNotNull('duration_seconds')->avg('duration_seconds') ?? 0),
            ],
            'questions' => $questions,
            'hardest_questions' => $questions->take(5)->values(),
            'classes' => array_values(array_map(function ($c) use ($q) {
                $scores = $c['scores'];
                unset($c['scores']);
                return $c + ['graded' => count($scores), 'average_score' => count($scores) ? round(array_sum($scores) / count($scores), 2) : null, 'pass_rate' => count($scores) ? round(count(array_filter($scores, function ($s) use ($q) {
                    return $s >= $q->passing_grade; })) / count($scores) * 100, 1) : null]; }, $classStats)),
        ];
    }

    public function show(Request $r, $attempt)
    {
        return $this->service->payload($this->service->attempt($attempt, $this->service->identity($r), true), true);
    }

    public function grade(Request $r, $attempt, $question)
    {
        $user = $this->service->identity($r);
        $a = $this->service->attempt($attempt, $user, true);
        $data = $r->validate(['points' => 'required|numeric|min:0', 'feedback' => 'nullable|string|max:10000']);
        return DB::transaction(function () use ($a, $question, $data, $user) {
            DB::table('quiz_attempts')->where('id', $a->id)->lockForUpdate()->first();
            abort_if($a->status === 'IN_PROGRESS', 422, 'Attempt belum selesai.');
            $row = $this->service->answerRows($a->id)->firstWhere('id', (int) $question);
            abort_unless($row && $row->type === 'essay', 422);
            abort_if($data['points'] > $row->weight, 422, 'Nilai melebihi bobot soal.');
            abort_if(!strlen(trim($row->essay ?? '')) && $data['points'] > 0, 422, 'Essay kosong harus bernilai nol.');
            DB::table('student_answers')->where('id', $row->answer_id)->update($data + ['graded_by' => $user->id, 'updated_at' => now()]);
            $this->service->recalculate($a->id);
            return ['message' => 'Nilai essay disimpan.'];
        }, 3);
    }
}
