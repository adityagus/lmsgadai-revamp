<?php

namespace App\Services\Quiz;

use Illuminate\Support\Facades\{DB, Schema};

class AttemptHistory
{
    public function enabled(): bool
    {
        return Schema::hasTable('learning_attempt_history');
    }

    public function scope($query, ?int $batchId = null)
    {
        if (!$this->enabled()) return $query;
        $tagged = DB::table('learning_attempt_history')->whereNotNull('batch_id');
        return $batchId
            ? $query->whereIn('id', (clone $tagged)->where('batch_id', $batchId)->select('attempt_id'))
            : $query->whereNotIn('id', $tagged->select('attempt_id'));
    }

    public function batch($quiz, $username, $batchId)
    {
        if (!$batchId) return null;
        $batch = DB::table('training_batches as b')->join('training_batch_members as m', 'm.batch_id', '=', 'b.id')
            ->join('training_batch_courses as c', 'c.batch_id', '=', 'b.id')
            ->where('b.id', $batchId)->where('m.username', $username)->where('c.course_id', $quiz->course_id)
            ->where('b.status', 'active')->where('b.starts_on', '<=', today()->toDateString())
            ->where('b.ends_on', '>=', today()->toDateString())->select('b.*')->first();
        abort_unless($batch, 403, 'Batch tidak aktif, di luar periode, atau Anda tidak terdaftar pada kelas batch ini.');
        return $batch;
    }

    public function capture($attemptId, $quiz, $user, $batch): void
    {
        if (!$this->enabled()) return;
        // Caller holds the quiz row lock, serializing version allocation with quiz editing and starts.
        $questions = DB::table('quiz_questions as link')->join('questions as q', 'q.id', '=', 'link.question_id')
            ->where('link.quiz_id', $quiz->id)->orderBy('link.position')->select('q.*', DB::raw('COALESCE(link.explanation, q.explanation) as explanation'))->lockForUpdate()->get();
        $options = DB::table('question_options')->whereIn('question_id', $questions->pluck('id'))->orderBy('id')->get()->groupBy('question_id');
        $questions = $questions->map(function ($q) use ($options) {
            unset($q->created_at, $q->updated_at);
            $q->options = $options->get($q->id, collect())->map(fn ($o) => collect($o)->only(['id', 'text', 'is_correct'])->all())->values()->all();
            return $q;
        });
        $quizData = collect($quiz)->except(['created_at', 'updated_at'])->all();
        $snapshot = json_encode(['quiz' => $quizData, 'questions' => $questions], JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $snapshot);
        $version = DB::table('learning_quiz_versions')->where('quiz_id', $quiz->id)->where('fingerprint', $hash)->first();
        if (!$version) {
            $id = DB::table('learning_quiz_versions')->insertGetId(['quiz_id' => $quiz->id,
                'version' => 1 + (int) DB::table('learning_quiz_versions')->where('quiz_id', $quiz->id)->max('version'),
                'fingerprint' => $hash, 'snapshot' => $snapshot, 'created_at' => now()]);
            $version = DB::table('learning_quiz_versions')->find($id);
        }
        $course = $quiz->course_id ? DB::table('courses')->find($quiz->course_id) : null;
        $contents = $course && Schema::hasTable('course_details') ? DB::table('course_details')->where('course_id', $course->id)->orderBy('order')->orderBy('id')->get() : collect();
        $completedIds = $course && Schema::hasTable('course_content_progress') ? DB::table('course_content_progress')
            ->where('course_id', $course->id)->where('username', $user->username)->whereNotNull('completed_at')->pluck('content_id')->all() : [];
        DB::table('learning_attempt_history')->insert(['attempt_id' => $attemptId, 'quiz_version_id' => $version->id,
            'username' => $user->username, 'batch_id' => $batch?->id, 'batch_name' => $batch?->name, 'batch_owner' => $batch?->created_by,
            'course_id' => $course?->id, 'course_snapshot' => json_encode(['course' => $course, 'contents' => $contents, 'completed_content_ids' => $completedIds], JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }

    public function read(int $attemptId): ?object
    {
        $key = 'learning.history.'.$attemptId;
        if (request()->attributes->has($key)) return request()->attributes->get($key);
        if (!$this->enabled()) return null;
        $row = DB::table('learning_attempt_history as h')->join('learning_quiz_versions as v', 'v.id', '=', 'h.quiz_version_id')
            ->where('h.attempt_id', $attemptId)->select('h.*', 'v.version', 'v.snapshot')->first();
        if ($row) {
            $row->snapshot = json_decode($row->snapshot);
            $row->course_snapshot = json_decode($row->course_snapshot);
            // A snapshot is immutable. Reuse it across questions within this request only.
            request()->attributes->set($key, $row);
        }
        return $row;
    }

    public function content($content): void
    {
        if (!Schema::hasTable('learning_content_versions')) return;
        $data = collect($content instanceof \Illuminate\Database\Eloquent\Model ? $content->getAttributes() : $content)->except(['created_at', 'updated_at'])->all();
        $snapshot = json_encode($data, JSON_THROW_ON_ERROR);
        DB::table('learning_content_versions')->insertOrIgnore(['content_id' => $data['id'], 'course_id' => $data['course_id'],
            'fingerprint' => hash('sha256', $snapshot), 'snapshot' => $snapshot,
            'recorded_by' => request()->hasSession() ? request()->session()->get('auth.user') : null, 'created_at' => now()]);
    }
}
