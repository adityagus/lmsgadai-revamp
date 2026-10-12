<?php

namespace App\Services\Quiz;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QuizService
{
    public function identity($request)
    {
        $username = $request->session()->get('auth.user');
        abort_unless($username && $request->session()->get('auth.status') === 'login', 401);
        $user = app(EmployeeParticipants::class)->findActive($username);
        abort_unless($user, 403, 'Akun karyawan tidak aktif atau tidak ditemukan.');
        $user->role = app(\App\Services\LmsAccess::class)->role($request);
        $user->position = $request->session()->get('auth.idgrup');
        $user->branch = $request->session()->get('auth.cabang');
        return $user;
    }

    public function manage($user, $quiz = null)
    {
        abort_unless(in_array($user->role, ['admin', 'trainer', 'teacher'], true), 403);
        if ($quiz) {
            abort_unless($user->role === 'admin' || $quiz->created_by === $user->id, 403);
        }
    }

    public function quiz($id)
    {
        $quiz = DB::table('quizzes')->find($id);
        abort_unless($quiz, 404);
        return $quiz;
    }

    public function validatePassingGrade($grade, $field = 'passing_grade')
    {
        if (!is_numeric($grade) || !is_finite((float) $grade) || (float) $grade < 0 || (float) $grade > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Nilai minimal lulus harus antara 0 dan 100.',
            ]);
        }
    }

    public function settings($quiz, $student)
    {
        $override = DB::table('quiz_students')->where('quiz_id', $quiz->id)->where('student_id', $student)->first();
        $from = $override->available_from ?? (!empty($quiz->no_schedule) ? null : $quiz->available_from);
        $until = $override->available_until ?? (!empty($quiz->no_schedule) ? null : $quiz->available_until);
        return [
            'from' => $from ? Carbon::parse($from) : null,
            'until' => $until ? Carbon::parse($until) : null,
            'minutes' => $quiz->duration_minutes + ($override->additional_minutes ?? 0),
            'maximum' => $override->maximum_attempt ?? $quiz->maximum_attempt,
        ];
    }

    public function assigned($quiz, $user)
    {
        $student = $user->id;
        // Courses currently have no enrolment restriction: every signed-in LMS user can open them.
        // A linked quiz follows that same audience; legacy standalone quizzes keep their assignments.
        if ($quiz->course_id) {
            return DB::table('courses')->where('id', $quiz->course_id)->exists()
                && DB::getSchemaBuilder()->hasTable('course_details')
                && DB::table('course_details')->where('course_id', $quiz->course_id)->where('quiz_id', $quiz->id)->where('type', 'quiz')->exists()
                && app(\App\Services\CourseAudience::class)->allows((int) $quiz->course_id, request())
                && DB::table('lms_users')->where('id', $student)->exists();
        }
        if (!app(QuizAudience::class)->allows($quiz->id, $user)) return false;
        return DB::table('quiz_students')->where('quiz_id', $quiz->id)->where('student_id', $student)->exists()
            || DB::table('quiz_classes')->join('class_students', 'class_students.class_id', '=', 'quiz_classes.class_id')
                ->where('quiz_id', $quiz->id)->where('student_id', $student)->exists();
    }

    public function start($quiz, $user, $batchId = null)
    {
        return DB::transaction(function () use ($quiz, $user, $batchId) {
            // Serialize starts for a student, including the first attempt, across browser tabs.
            DB::table('lms_users')->where('id', $user->id)->lockForUpdate()->first();
            $quiz = DB::table('quizzes')->where('id', $quiz->id)->lockForUpdate()->first();
            abort_unless($quiz->status === 'PUBLISHED' && $this->assigned($quiz, $user), 403);
            if ($user->role === 'student') app(\App\Services\CourseSequence::class)->assertQuizUnlocked($quiz, $user->username);
            $history = app(AttemptHistory::class);
            $batch = $history->batch($quiz, $user->username, $batchId);
            $active = DB::table('quiz_attempts')->where('quiz_id', $quiz->id)->where('student_id', $user->id)->where('status', 'IN_PROGRESS')->first();
            if ($active) {
                $record = $history->read($active->id);
                abort_if((int) ($record->batch_id ?? 0) !== (int) ($batch?->id ?? 0), 409, 'Masih ada percobaan aktif dari penugasan lain. Selesaikan percobaan tersebut terlebih dahulu.');
                $this->expire($active->id);
                return $active->id;
            }
            $settings = $this->settings($quiz, $user->id);
            abort_if(($settings['from'] && now()->lt($settings['from'])) || ($settings['until'] && now()->gte($settings['until'])), 422, 'Quiz di luar periode pengerjaan.');
            $allAttempts = DB::table('quiz_attempts')->where('quiz_id', $quiz->id)->where('student_id', $user->id);
            $count = $history->scope(clone $allAttempts, $batch?->id)->count();
            $nextAttempt = 1 + (int) $allAttempts->max('attempt_number');
            abort_if($settings['maximum'] > 0 && $count >= $settings['maximum'], 422, 'Batas attempt sudah tercapai.');
            $questions = DB::table('quiz_questions')->where('quiz_id', $quiz->id)->orderBy('position')->pluck('question_id')->all();
            if ($quiz->randomize_question) {
                shuffle($questions);
            }
            $questions = array_slice($questions, 0, $quiz->question_count);
            abort_unless(count($questions) === $quiz->question_count && count($questions), 422, 'Jumlah soal belum mencukupi.');
            $id = DB::table('quiz_attempts')->insertGetId([
                'quiz_id' => $quiz->id,
                'student_id' => $user->id,
                'attempt_number' => $nextAttempt,
                'started_at' => now(),
                'expired_at' => $settings['until'] ? now()->addMinutes($settings['minutes'])->min($settings['until']) : now()->addMinutes($settings['minutes']),
                'status' => 'IN_PROGRESS',
            ]);
            $history->capture($id, $quiz, $user, $batch);
            foreach ($questions as $position => $question) {
                $options = DB::table('question_options')->where('question_id', $question)->orderBy('id')->pluck('id')->all();
                if ($quiz->randomize_answer) {
                    shuffle($options);
                }
                $aq = DB::table('quiz_attempt_questions')->insertGetId(['attempt_id' => $id, 'question_id' => $question, 'position' => $position + 1, 'option_order' => json_encode($options)]);
                DB::table('student_answers')->insert(['attempt_question_id' => $aq, 'created_at' => now(), 'updated_at' => now()]);
            }
            return $id;
        }, 3);
    }

    public function attempt($id, $user, $manager = false)
    {
        $attempt = DB::table('quiz_attempts')->find($id);
        abort_unless($attempt, 404);
        if ($manager) {
            $this->manage($user, $this->quiz($attempt->quiz_id));
        } else {
            abort_unless($attempt->student_id === $user->id, 403);
            $quiz = $this->quiz($attempt->quiz_id);
            if ($user->role === 'student' && $attempt->status === 'IN_PROGRESS' && !app(AttemptHistory::class)->read($attempt->id)) {
                app(\App\Services\CourseSequence::class)->assertQuizUnlocked($quiz, $user->username);
            }
            abort_unless($attempt->status !== 'IN_PROGRESS' || ($quiz->course_id
                ? app(\App\Services\CourseAudience::class)->allows((int) $quiz->course_id, request())
                : app(QuizAudience::class)->allows($attempt->quiz_id, $user)),
                403,
                'Anda tidak termasuk peserta yang diizinkan mengakses kuis ini.');
        }
        $this->expire($id);
        return DB::table('quiz_attempts')->find($id);
    }

    public function expire($id)
    {
        $a = DB::table('quiz_attempts')->find($id);
        if ($a && $a->status === 'IN_PROGRESS' && now()->gte(Carbon::parse($a->expired_at))) {
            $this->submit($id);
        }
    }

    public static function objectivePoints($selected, $correct, $weight)
    {
        sort($selected);
        sort($correct);
        return $selected === $correct ? (float) $weight : 0.0;
    }

    public function submit($id)
    {
        return DB::transaction(function () use ($id) {
            $a = DB::table('quiz_attempts')->where('id', $id)->lockForUpdate()->first();
            if ($a->status !== 'IN_PROGRESS') {
                return;
            }
            $expired = now()->gte(Carbon::parse($a->expired_at));
            foreach ($this->answerRows($id) as $row) {
                $selected = json_decode($row->selected_options ?? '[]', true);
                $points = $row->type === 'essay' ? (strlen(trim($row->essay ?? '')) ? null : 0) : self::objectivePoints($selected, $this->optionsForAttempt($id, $row->question_id)->where('is_correct', true)->pluck('id')->all(), $row->weight);
                DB::table('student_answers')->where('id', $row->answer_id)->update(['points' => $points]);
            }
            DB::table('quiz_attempts')->where('id', $id)->update(['status' => $expired ? 'AUTO_SUBMITTED' : 'SUBMITTED', 'submitted_at' => $expired ? $a->expired_at : now()]);
            $this->recalculate($id);
        }, 3);
    }

    public function answerRows($id)
    {
        $rows = DB::table('quiz_attempt_questions as aq')->join('questions as q', 'q.id', '=', 'aq.question_id')
            ->join('quiz_attempts as attempt', 'attempt.id', '=', 'aq.attempt_id')
            ->leftJoin('quiz_questions as qq', function ($join) { $join->on('qq.quiz_id', '=', 'attempt.quiz_id')->on('qq.question_id', '=', 'aq.question_id'); })
            ->join('student_answers as a', 'a.attempt_question_id', '=', 'aq.id')->where('aq.attempt_id', $id)->orderBy('aq.position')
            ->select('aq.*', 'q.question', 'q.image_path', 'q.type', 'q.weight', DB::raw('COALESCE(qq.explanation, q.explanation) as explanation'), 'q.difficulty', 'a.id as answer_id', 'a.selected_options', 'a.essay', 'a.flagged', 'a.visited', 'a.revision', 'a.points', 'a.feedback')->get();
        $snapshot = app(AttemptHistory::class)->read($id);
        if ($snapshot) {
            $questions = collect($snapshot->snapshot->questions)->keyBy('id');
            foreach ($rows as $row) {
                $question = $questions->get($row->question_id);
                abort_unless($question, 409, 'Rekaman versi soal tidak lengkap.');
                foreach (['question', 'image_path', 'type', 'weight', 'explanation', 'difficulty'] as $field) $row->$field = $question->$field ?? null;
            }
        }
        return $rows;
    }

    public function optionsForAttempt($attemptId, $questionId)
    {
        $history = app(AttemptHistory::class)->read($attemptId);
        if ($history) {
            $question = collect($history->snapshot->questions)->firstWhere('id', $questionId);
            abort_unless($question, 409, 'Rekaman versi soal tidak lengkap.');
            return collect($question->options);
        }
        return DB::table('question_options')->where('question_id', $questionId)->get();
    }

    public function recalculate($id)
    {
        $rows = $this->answerRows($id);
        $correct = 0;
        $wrong = 0;
        $unanswered = 0;
        $pending = false;
        foreach ($rows as $r) {
            $answered = $r->type === 'essay' ? strlen(trim($r->essay ?? '')) > 0 : count(json_decode($r->selected_options ?? '[]', true)) > 0;
            if (!$answered) {
                $unanswered++;
            } elseif ($r->points === null) {
                $pending = true;
            } elseif ((float) $r->points === (float) $r->weight) {
                $correct++;
            } else {
                $wrong++;
            }
        }
        DB::table('quiz_attempts')->where('id', $id)->update([
            'score' => $pending ? null : round($rows->sum('points') / max(0.01, $rows->sum('weight')) * 100, 2),
            'total_correct' => $correct,
            'total_wrong' => $wrong,
            'total_unanswered' => $unanswered,
            'pending_review' => $pending,
        ]);
    }

    public function payload($attempt, $manager = false)
    {
        $quiz = $this->quiz($attempt->quiz_id);
        $history = app(AttemptHistory::class)->read($attempt->id);
        if ($history) {
            // Release controls stay with the trainer; the historical assessment and grading scale are fixed.
            foreach (['title', 'description', 'course_id', 'passing_grade', 'grading_method'] as $field) {
                $quiz->$field = $history->snapshot->quiz->$field;
            }
        }
        $finished = $attempt->status !== 'IN_PROGRESS';
        $release = $this->released($quiz, $attempt);
        $data = [
            'id' => $attempt->id,
            'recorded_version' => $history?->version,
            'batch_id' => $history?->batch_id,
            'batch_name' => $history?->batch_name,
            'quiz_id' => $quiz->id,
            'course_id' => $quiz->course_id,
            'title' => $quiz->title,
            'student' => app(EmployeeParticipants::class)->names([$attempt->student_id])->get($attempt->student_id),
            'classes' => $quiz->course_id ? DB::table('courses')->where('id', $quiz->course_id)->pluck('name') : DB::table('classes')->join('class_students', 'class_students.class_id', '=', 'classes.id')->where('student_id', $attempt->student_id)->pluck('name'),
            'status' => $attempt->status,
            'attempt_number' => $attempt->attempt_number,
            'started_at' => Carbon::parse($attempt->started_at)->toIso8601String(),
            'expired_at' => Carbon::parse($attempt->expired_at)->toIso8601String(),
            'submitted_at' => $attempt->submitted_at ? Carbon::parse($attempt->submitted_at)->toIso8601String() : null,
            'server_now' => now()->toIso8601String(),
            'pending_review' => (bool) $attempt->pending_review,
            'question_count' => DB::table('quiz_attempt_questions')->where('attempt_id', $attempt->id)->count(),
        ];
        if ($manager || ($release && $quiz->show_score)) {
            $data['score'] = $attempt->score;
            $data['aggregate_score'] = $this->aggregate($quiz, $attempt->student_id, $history?->batch_id, true);
        }
        if ($manager || ($release && $quiz->show_total_correct)) {
            foreach (['total_correct', 'total_wrong', 'total_unanswered'] as $key) {
                $data[$key] = $attempt->$key;
            }
        }
        if ($manager || ($release && $quiz->show_passing_status)) {
            $data['passing_grade'] = $quiz->passing_grade;
            $data['passed'] = $attempt->score === null ? null : $attempt->score >= $quiz->passing_grade;
        }
        $data['questions'] = [];
        if (!$finished || $manager || ($release && ($quiz->show_correct_answer || $quiz->show_wrong_answer || $quiz->show_explanation))) {
            foreach ($this->answerRows($attempt->id) as $row) {
                $options = $this->optionsForAttempt($attempt->id, $row->question_id)->keyBy('id');
                $item = ['id' => $row->id, 'position' => $row->position, 'question' => $row->question, 'type' => $row->type, 'options' => [], 'selected_options' => json_decode($row->selected_options ?? '[]', true), 'essay' => $row->essay ?? '', 'flagged' => (bool) $row->flagged, 'visited' => (bool) $row->visited, 'revision' => $row->revision];
                $item['image_url'] = QuestionImage::url($row->image_path);
                foreach (json_decode($row->option_order, true) as $oid) {
                    $o = ['id' => $oid, 'text' => $options[$oid]->text];
                    if ($manager || ($release && $quiz->show_correct_answer)) {
                        $o['is_correct'] = (bool) $options[$oid]->is_correct;
                    }
                    if (($manager || ($release && $quiz->show_wrong_answer)) && in_array($oid, $item['selected_options'])) {
                        $o['answer_status'] = $options[$oid]->is_correct ? 'correct' : 'incorrect';
                    }
                    $item['options'][] = $o;
                }
                if ($manager || ($release && $quiz->show_explanation)) {
                    $item['explanation'] = $row->explanation;
                }
                if ($manager || ($release && $quiz->show_wrong_answer)) {
                    $item['points'] = $row->points;
                    $item['weight'] = $row->weight;
                    $item['feedback'] = $row->feedback;
                }
                $data['questions'][] = $item;
            }
        }
        return $data;
    }

    public function released($quiz, $attempt): bool
    {
        if ($attempt->status === 'IN_PROGRESS') return false;
        if (!empty($quiz->no_schedule) || !$quiz->release_after_close) return true;
        $until = $this->settings($quiz, $attempt->student_id)['until'];
        if ($quiz->available_until) $until = $until ? $until->max(Carbon::parse($quiz->available_until)) : Carbon::parse($quiz->available_until);
        return $until && now()->gte($until);
    }

    public function aggregate($quiz, $student, ?int $batchId = null, bool $scoped = false)
    {
        $query = DB::table('quiz_attempts')->where('quiz_id', $quiz->id)->where('student_id', $student)->where('status', '!=', 'IN_PROGRESS')->orderBy('attempt_number');
        $scores = ($scoped ? app(AttemptHistory::class)->scope($query, $batchId) : $query)->pluck('score');
        if (!$scores->count() || $scores->containsStrict(null)) {
            return null;
        }
        switch ($quiz->grading_method) {
            case 'first':
                return (float) $scores->first();
            case 'latest':
                return (float) $scores->last();
            case 'average':
                return round($scores->avg(), 2);
            default:
                return (float) $scores->max();
        }
    }
}
