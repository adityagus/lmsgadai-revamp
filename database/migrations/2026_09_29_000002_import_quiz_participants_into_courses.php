<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $participants = DB::table('quiz_students as participants')
            ->join('quizzes as quizzes', 'quizzes.id', '=', 'participants.quiz_id')
            ->join('courses as courses', 'courses.id', '=', 'quizzes.course_id')
            ->join('lms_users as users', 'users.id', '=', 'participants.student_id')
            ->select('courses.id as course_id', 'users.username')->distinct()->get();
        foreach ($participants as $participant) {
            DB::table('course_participants')->insertOrIgnore([
                'course_id' => $participant->course_id, 'username' => $participant->username, 'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Preserve memberships: imported rows cannot be distinguished from independently granted access.
    }
};
