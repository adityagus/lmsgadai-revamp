<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\{DB, Schema};

class LearningHistoryTest extends TestCase
{
    use \Tests\EmployeeDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createEmployeeDatabase();
        Schema::create('courses', function ($t) { $t->id(); $t->string('name'); });
        Schema::create('course_details', function ($t) { $t->id(); $t->integer('course_id'); $t->string('title'); $t->string('type'); $t->text('content')->nullable(); $t->string('lampiran')->nullable(); $t->integer('order'); $t->integer('quiz_id')->nullable(); });
        require_once database_path('migrations/2026_09_23_000001_create_quiz_module.php');
        (new \CreateQuizModule)->up();
        require_once database_path('migrations/2026_09_23_000003_add_quiz_question_explanation.php');
        (new \AddQuizQuestionExplanation)->up();
        require_once database_path('migrations/2026_09_24_000001_add_quiz_no_schedule.php');
        (new \AddQuizNoSchedule)->up();
        foreach (['2026_10_07_000001_add_image_path_to_questions.php', '2026_09_28_000001_create_course_content_progress_table.php', '2026_10_08_000001_create_training_batches.php', '2026_10_08_000002_create_learning_history.php'] as $file) (require database_path('migrations/'.$file))->up();
        DB::table('courses')->insert(['id' => 1, 'name' => 'Dasar Gadai']);
        DB::table('lms_users')->insert([
            ['id' => 1, 'username' => 'trainer', 'name' => 'Trainer', 'role' => 'trainer'],
            ['id' => 2, 'username' => 'budi', 'name' => 'Budi', 'role' => 'student'],
            ['id' => 3, 'username' => 'other', 'name' => 'Other', 'role' => 'student'],
        ]);
        DB::table('question_banks')->insert(['id' => 1, 'name' => 'Dasar', 'created_by' => 1]);
        DB::table('questions')->insert(['id' => 1, 'bank_id' => 1, 'question' => 'Pertanyaan versi awal', 'type' => 'multiple_choice', 'weight' => 10, 'difficulty' => 'easy', 'explanation' => 'Pembahasan awal', 'created_by' => 1]);
        DB::table('question_options')->insert([['id' => 1, 'question_id' => 1, 'text' => 'Pilihan benar awal', 'is_correct' => true], ['id' => 2, 'question_id' => 1, 'text' => 'Pilihan salah awal', 'is_correct' => false]]);
        DB::table('quizzes')->insert(['id' => 1, 'title' => 'Kuis Awal', 'course_id' => 1, 'duration_minutes' => 30, 'question_count' => 1, 'passing_grade' => 70, 'maximum_attempt' => 1, 'grading_method' => 'highest', 'no_schedule' => true, 'available_from' => now()->subDay(), 'available_until' => now()->addWeek(), 'show_score' => true, 'show_correct_answer' => true, 'show_wrong_answer' => true, 'show_explanation' => true, 'show_passing_status' => true, 'status' => 'PUBLISHED', 'created_by' => 1]);
        DB::table('quiz_questions')->insert(['quiz_id' => 1, 'question_id' => 1, 'position' => 1, 'explanation' => 'Pembahasan kuis awal']);
        DB::table('course_details')->insert([
            ['id' => 1, 'course_id' => 1, 'title' => 'Materi awal', 'type' => 'text', 'content' => '<p>Isi materi awal</p>', 'order' => 1, 'quiz_id' => null],
            ['id' => 2, 'course_id' => 1, 'title' => 'Kuis', 'type' => 'quiz', 'content' => null, 'order' => 2, 'quiz_id' => 1],
            ['id' => 3, 'course_id' => 1, 'title' => 'Materi berikutnya', 'type' => 'text', 'content' => 'Materi belum dibuka', 'order' => 3, 'quiz_id' => null],
        ]);
        DB::table('course_content_progress')->insert(['course_id' => 1, 'content_id' => 1, 'username' => 'budi', 'completed_at' => now()]);
        foreach ([1, 2] as $id) {
            DB::table('training_batches')->insert(['id' => $id, 'name' => 'Batch '.$id, 'status' => 'active', 'starts_on' => today()->toDateString(), 'ends_on' => today()->addWeek()->toDateString(), 'created_by' => 'trainer']);
            DB::table('training_batch_members')->insert(['batch_id' => $id, 'username' => 'budi']);
            DB::table('training_batch_courses')->insert(['batch_id' => $id, 'course_id' => 1]);
        }
        $this->login();
    }

    private function login($username = 'budi', $position = 'STAFF'): static
    {
        return $this->withSession(['auth' => ['user' => $username, 'status' => 'login', 'idgrup' => $position]]);
    }

    private function start($batch = 1)
    {
        return $this->postJson('/api/quiz/1/start', ['batch_id' => $batch])->assertOk()->json();
    }

    private function answer($attempt, $option = 1)
    {
        $this->putJson('/api/quiz/attempts/'.$attempt['id'].'/answers/'.$attempt['questions'][0]['id'],
            ['selected_options' => [$option], 'essay' => '', 'flagged' => false, 'visited' => true, 'revision' => 0])->assertOk();
    }

    public function test_snapshot_controls_answers_grading_and_history_after_source_changes(): void
    {
        $attempt = $this->start();
        DB::table('questions')->where('id', 1)->update(['question' => 'Teks telah diganti', 'weight' => 99, 'type' => 'multiple_answer']);
        DB::table('question_options')->where('id', 1)->update(['text' => 'Teks baru', 'is_correct' => false]);
        DB::table('question_options')->where('id', 2)->update(['is_correct' => true]);
        DB::table('quiz_questions')->update(['explanation' => 'Pembahasan baru']);
        DB::table('quizzes')->where('id', 1)->update(['title' => 'Judul baru', 'passing_grade' => 99]);
        DB::table('course_details')->where('id', 1)->update(['content' => 'Materi baru']);
        $this->answer($attempt);
        $this->postJson('/api/quiz/attempts/'.$attempt['id'].'/submit')->assertOk()->assertJsonPath('score', 100)->assertJsonPath('passing_grade', 70);
        DB::table('training_batches')->update(['status' => 'completed']);
        DB::table('training_batch_members')->delete();
        $this->getJson('/api/learning-history/'.$attempt['id'])->assertOk()->assertJsonPath('title', 'Kuis Awal')
            ->assertJsonPath('batch_name', 'Batch 1')->assertJsonPath('recorded_version', 1)
            ->assertJsonPath('questions.0.question', 'Pertanyaan versi awal')->assertJsonPath('questions.0.weight', 10)
            ->assertJsonPath('questions.0.options.0.text', 'Pilihan benar awal')->assertJsonPath('questions.0.explanation', 'Pembahasan kuis awal')
            ->assertJsonPath('materials.0.content', '<p>Isi materi awal</p>')->assertJsonCount(1, 'materials');
        $this->getJson('/api/learning-history?batch_id=1')->assertOk()->assertJsonPath('data.0.score', 100)->assertJsonPath('data.0.title', 'Kuis Awal');
        $this->login('other')->getJson('/api/learning-history/'.$attempt['id'])->assertNotFound();
        $this->login('another-trainer', 'JBT-TRAINER')->getJson('/api/learning-history/'.$attempt['id'])->assertNotFound();
        $this->login('trainer', 'JBT-TRAINER')->getJson('/api/learning-history/'.$attempt['id'])->assertOk();
    }

    public function test_new_batch_has_its_own_attempt_limit_and_previous_score_is_preserved(): void
    {
        $first = $this->start(); $this->answer($first);
        $this->postJson('/api/quiz/attempts/'.$first['id'].'/submit')->assertOk();
        $this->postJson('/api/quiz/1/start', ['batch_id' => 1])->assertUnprocessable();
        DB::table('questions')->where('id', 1)->update(['question' => 'Versi kedua']);
        $this->getJson('/api/quiz/student?batch_id=2')->assertOk()->assertJsonPath('quizzes.0.can_start', true)->assertJsonCount(0, 'quizzes.0.attempts');
        $second = $this->start(2); $this->answer($second, 2);
        $this->postJson('/api/quiz/attempts/'.$second['id'].'/submit')->assertOk()->assertJsonPath('score', 0)->assertJsonPath('aggregate_score', 0);
        $this->getJson('/api/learning-history?batch_id=1')->assertJsonPath('data.0.score', 100)->assertJsonPath('data.0.recorded_version', 1);
        $this->getJson('/api/learning-history?batch_id=2')->assertJsonPath('data.0.score', 0)->assertJsonPath('data.0.recorded_version', 2);
        $this->assertDatabaseCount('quiz_attempts', 2);
        $this->assertDatabaseCount('learning_quiz_versions', 2);
    }

    public function test_release_controls_forged_batch_and_legacy_history_are_respected(): void
    {
        $this->postJson('/api/quiz/1/start', ['batch_id' => 999])->assertForbidden();
        $first = $this->start();
        $this->postJson('/api/quiz/1/start', ['batch_id' => 2])->assertStatus(409);
        $this->getJson('/api/learning-history/'.$first['id'])->assertStatus(409);
        $this->postJson('/api/quiz/attempts/'.$first['id'].'/submit')->assertOk();
        DB::table('quizzes')->where('id', 1)->update(['no_schedule' => false, 'release_after_close' => true, 'available_until' => now()->addWeek()]);
        $this->getJson('/api/learning-history')->assertOk()->assertJsonPath('data.0.score', null)->assertJsonPath('data.0.score_visible', false);
        $this->getJson('/api/learning-history/'.$first['id'])->assertOk()->assertJsonMissingPath('score')->assertJsonCount(0, 'questions');
        DB::table('learning_attempt_history')->where('attempt_id', $first['id'])->delete();
        $this->getJson('/api/learning-history/'.$first['id'])->assertOk()->assertJsonPath('recorded_version', null)->assertJsonCount(0, 'materials');
        $this->getJson('/api/learning-history?batch_id=1')->assertJsonCount(0, 'data');
    }

    public function test_essay_regrading_retains_an_audit_and_uses_recorded_weight(): void
    {
        DB::table('questions')->where('id', 1)->update(['type' => 'essay']);
        $attempt = $this->start();
        $question = $attempt['questions'][0]['id'];
        $this->putJson('/api/quiz/attempts/'.$attempt['id'].'/answers/'.$question,
            ['selected_options' => [], 'essay' => 'Jawaban peserta', 'flagged' => false, 'visited' => true, 'revision' => 0])->assertOk();
        $this->postJson('/api/quiz/attempts/'.$attempt['id'].'/submit')->assertOk()->assertJsonPath('pending_review', true);
        DB::table('questions')->where('id', 1)->update(['weight' => 1, 'type' => 'multiple_choice']);
        $this->login('trainer', 'JBT-TRAINER');
        $this->putJson('/api/quiz/results/'.$attempt['id'].'/grade/'.$question, ['points' => 7, 'feedback' => 'Penilaian awal'])->assertOk();
        $this->putJson('/api/quiz/results/'.$attempt['id'].'/grade/'.$question, ['points' => 8, 'feedback' => 'Koreksi penilaian'])->assertOk();
        $this->getJson('/api/learning-history/'.$attempt['id'])->assertOk()->assertJsonPath('score', 80)
            ->assertJsonCount(2, 'grade_history')->assertJsonPath('grade_history.1.previous_points', 7)
            ->assertJsonPath('questions.0.type', 'essay')->assertJsonPath('questions.0.weight', 10);
        $this->assertDatabaseCount('learning_grade_audits', 2);
    }

    public function test_participant_filters_keep_history_and_options_within_authorized_scope(): void
    {
        $attempt = $this->start();
        $this->postJson('/api/quiz/attempts/'.$attempt['id'].'/submit')->assertOk();
        $quiz = (array) DB::table('quizzes')->find(1);
        $quiz['id'] = 2;
        $quiz['created_by'] = 3;
        DB::table('quizzes')->insert($quiz);
        $otherAttempt = (array) DB::table('quiz_attempts')->find($attempt['id']);
        unset($otherAttempt['id']);
        $otherAttempt['quiz_id'] = 2;
        $otherAttempt['student_id'] = 3;
        DB::table('quiz_attempts')->insert($otherAttempt);

        $this->login('admin', 'JBT-032')->getJson('/api/learning-history?username=other')->assertOk()
            ->assertJsonPath('can_filter_students', true)->assertJsonCount(2, 'participants')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.username', 'other');
        $this->getJson('/api/learning-history?batch_id=1')->assertOk()
            ->assertJsonCount(1, 'participants')->assertJsonPath('participants.0.username', 'budi');
        $this->login('trainer', 'JBT-TRAINER')->getJson('/api/learning-history?username=other')->assertOk()
            ->assertJsonCount(0, 'data')->assertJsonCount(1, 'participants')
            ->assertJsonPath('participants.0.username', 'budi');
        $this->getJson('/api/learning-history?username=budi')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $attempt['id']);
        $this->login()->getJson('/api/learning-history?username=other')->assertOk()
            ->assertJsonPath('can_filter_students', false)->assertJsonCount(0, 'participants')->assertJsonCount(0, 'data');
    }

    public function test_material_versions_record_the_old_content_without_rewriting_it(): void
    {
        $service = app(\App\Services\Quiz\AttemptHistory::class);
        $original = DB::table('course_details')->find(1);
        $service->content($original); $service->content($original);
        DB::table('course_details')->where('id', 1)->update(['content' => 'Versi materi berikutnya']);
        $service->content(DB::table('course_details')->find(1));
        $this->assertDatabaseCount('learning_content_versions', 2);
        $this->assertSame('<p>Isi materi awal</p>', json_decode(DB::table('learning_content_versions')->orderBy('id')->value('snapshot'))->content);
    }
}
