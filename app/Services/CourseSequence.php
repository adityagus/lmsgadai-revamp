<?php

namespace App\Services;

use App\Models\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourseSequence
{
    public function completed($contents, int $courseId, string $username)
    {
        $completed = Schema::hasTable('course_content_progress')
            ? DB::table('course_content_progress')->where('username', $username)->where('course_id', $courseId)
                ->whereIn('content_id', $contents->pluck('id'))->pluck('completed_at', 'content_id')
            : collect();
        $quizzes = $contents->whereNotNull('quiz_id');
        foreach ($quizzes as $content) $completed->forget($content->id);
        if ($quizzes->isNotEmpty() && Schema::hasTable('quiz_attempts') && Schema::hasTable('lms_users')) {
            $student = DB::table('lms_users')->where('username', $username)->value('id');
            if ($student) {
                $attempts = DB::table('quiz_attempts')->where('student_id', $student)
                    ->whereIn('quiz_id', $quizzes->pluck('quiz_id'))->where('status', '!=', 'IN_PROGRESS')->whereNotNull('submitted_at')
                    ->selectRaw('quiz_id, MIN(submitted_at) as completed_at')->groupBy('quiz_id')->pluck('completed_at', 'quiz_id');
                foreach ($quizzes as $content) {
                    if (isset($attempts[$content->quiz_id])) $completed[$content->id] = $attempts[$content->quiz_id];
                }
            }
        }
        return $completed;
    }

    public function assertUnlocked(int $courseId, int $contentId, string $username): void
    {
        abort_unless($this->isUnlocked($courseId, $contentId, $username), 403, 'Selesaikan materi atau kuis sebelumnya terlebih dahulu.');
    }

    public function isUnlocked(int $courseId, int $contentId, string $username): bool
    {
        $contents = Content::where('course_id', $courseId)->orderBy('order')->orderBy('id')->get();
        if (!$contents->contains('id', $contentId)) return false;
        $completed = $this->completed($contents, $courseId, $username);
        if (isset($completed[$contentId])) return true;
        foreach ($contents as $content) {
            if ((int) $content->id === $contentId) return true;
            if (!isset($completed[$content->id])) return false;
        }
        return false;
    }

    public function assertQuizUnlocked($quiz, string $username): void
    {
        abort_unless($this->quizUnlocked($quiz, $username), 403, 'Selesaikan materi atau kuis sebelumnya terlebih dahulu.');
    }

    public function quizUnlocked($quiz, string $username): bool
    {
        if (!$quiz->course_id) return true;
        $content = Content::where('course_id', $quiz->course_id)->where('quiz_id', $quiz->id)
            ->where('type', 'quiz')->orderBy('order')->orderBy('id')->first();
        return $content && $this->isUnlocked((int) $quiz->course_id, (int) $content->id, $username);
    }
}
