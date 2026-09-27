<?php

namespace Tests\Feature;

use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\Quiz\QuizService;

class QuizTest extends TestCase
{
    private $teacher;
    private $student;
    private $other;
    private $quiz;
    private $question;
    private $correct;
    private $wrong;
    private $class;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        Schema::create('courses', function (Blueprint $t) { $t->id(); $t->string('name'); });
        require_once database_path('migrations/2026_09_23_000001_create_quiz_module.php');
        (new \CreateQuizModule)->up();
        require_once database_path('migrations/2026_09_24_000001_add_quiz_no_schedule.php');
        (new \AddQuizNoSchedule)->up();
        require_once database_path('migrations/2026_09_23_000003_add_quiz_question_explanation.php');
        (new \AddQuizQuestionExplanation)->up();
        (require database_path('migrations/2026_09_25_000002_create_quiz_access_rules.php'))->up();
        Carbon::setTestNow(Carbon::parse('2026-09-23 09:00:00', config('app.timezone')));
        $this->teacher = DB::table('lms_users')->insertGetId(['username' => 'trainer', 'name' => 'Trainer', 'role' => 'teacher']);
        $this->student = DB::table('lms_users')->insertGetId(['username' => 'budi', 'name' => 'Budi', 'role' => 'student']);
        $this->other = DB::table('lms_users')->insertGetId(['username' => 'other', 'name' => 'Other', 'role' => 'student']);
        $this->class = DB::table('classes')->insertGetId(['name' => 'Batch 01', 'created_by' => $this->teacher]);
        DB::table('class_students')->insert(['class_id' => $this->class, 'student_id' => $this->student]);
        $bank = DB::table('question_banks')->insertGetId(['name' => 'Produk Gadai', 'created_by' => $this->teacher]);
        $this->question = DB::table('questions')->insertGetId(['bank_id' => $bank, 'question' => 'Pilih jawaban', 'type' => 'multiple_choice', 'weight' => 2.5, 'difficulty' => 'easy', 'explanation' => 'SECRET EXPLANATION', 'created_by' => $this->teacher]);
        $this->correct = DB::table('question_options')->insertGetId(['question_id' => $this->question, 'text' => 'Benar', 'is_correct' => true]);
        $this->wrong = DB::table('question_options')->insertGetId(['question_id' => $this->question, 'text' => 'Salah', 'is_correct' => false]);
        $this->quiz = DB::table('quizzes')->insertGetId(['title' => 'Quiz Gadai', 'duration_minutes' => 45, 'question_count' => 1, 'passing_grade' => 75, 'maximum_attempt' => 1, 'randomize_question' => true, 'randomize_answer' => true, 'available_from' => now()->subHour(), 'available_until' => now()->addHours(3), 'status' => 'PUBLISHED', 'created_by' => $this->teacher]);
        DB::table('quiz_classes')->insert(['quiz_id' => $this->quiz, 'class_id' => $this->class]);
        DB::table('quiz_questions')->insert(['quiz_id' => $this->quiz, 'question_id' => $this->question, 'position' => 0]);
        $this->loginAs('budi');
    }

    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }
    private function loginAs($name, $position = null) { $this->withSession(['auth' => ['user' => $name, 'nama' => $name, 'status' => 'login', 'idgrup' => $position]]); }
    private function start() { return $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertOk()->json(); }
    private function answer($a, $options, $revision = 0) { return $this->putJson('/api/quiz/attempts/'.$a['id'].'/answers/'.$a['questions'][0]['id'], ['selected_options' => $options, 'essay' => '', 'flagged' => true, 'visited' => true, 'revision' => $revision, 'student_id' => $this->other, 'score' => 100]); }

    public function test_quiz_audience_requires_both_position_and_branch_and_blocks_direct_attempt_access()
    {
        app(\App\Services\Quiz\QuizAudience::class)->save($this->quiz, ['positions' => ['JBT-001'], 'branches' => ['0001']]);
        $this->withSession(['auth' => ['user' => 'budi', 'status' => 'login', 'idgrup' => 'JBT-001', 'cabang' => '0002']]);
        $this->getJson('/api/quiz/student')->assertOk()->assertJsonCount(0, 'quizzes');
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertForbidden();
        $this->withSession(['auth' => ['user' => 'budi', 'status' => 'login', 'idgrup' => 'JBT-002', 'cabang' => '0001']]);
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertForbidden();
        $this->withSession(['auth' => ['user' => 'budi', 'status' => 'login', 'idgrup' => 'JBT-001', 'cabang' => '0001']]);
        $this->getJson('/api/quiz/student')->assertOk()->assertJsonCount(1, 'quizzes');
        $a = $this->start();
        app(\App\Services\Quiz\QuizAudience::class)->save($this->quiz, ['positions' => ['JBT-002'], 'branches' => []]);
        $this->getJson('/api/quiz/attempts/'.$a['id'])->assertForbidden();
        $this->answer($a, [$this->correct])->assertForbidden();
        $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertForbidden();
        // Missing employee branch must not pass a branch restriction.
        app(\App\Services\Quiz\QuizAudience::class)->save($this->quiz, ['positions' => [], 'branches' => ['0001']]);
        $this->loginAs('budi');
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertForbidden();
        app(\App\Services\Quiz\QuizAudience::class)->save($this->quiz, ['positions' => [], 'branches' => []]);
        $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk();
    }

    public function test_manager_can_save_read_clear_and_duplicate_audience_without_changing_attempts()
    {
        $this->mock(\App\Services\Quiz\AudienceDirectory::class, function ($mock) {
            $mock->shouldReceive('positions')->andReturn([(object) ['value' => 'JBT-001', 'label' => 'Kasir']]);
            $mock->shouldReceive('branches')->andReturn([(object) ['value' => '0001', 'label' => 'Cabang Satu']]);
        });
        $a = $this->start();
        $this->getJson('/api/quiz/audience-options')->assertForbidden();
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['audience' => ['positions' => [], 'branches' => []]])->assertForbidden();
        $this->loginAs('trainer');
        $this->getJson('/api/quiz/audience-options')->assertOk()->assertJsonPath('branches.0.value', '0001');
        $audience = ['positions' => ['JBT-001'], 'branches' => ['0001']];
        $url = '/api/quiz/manage/'.$this->quiz;
        $this->patchJson($url.'/visibility', ['audience' => $audience])->assertOk();
        $this->getJson($url)->assertOk()->assertJsonPath('audience', $audience);
        $this->patchJson($url.'/visibility', ['audience' => ['positions' => ['UNKNOWN'], 'branches' => []]])->assertUnprocessable();
        $this->patchJson($url.'/visibility', ['audience' => ['positions' => [], 'branches' => ['UNKNOWN']]])->assertUnprocessable();
        $this->patchJson($url.'/visibility', ['show_score' => true])->assertOk();
        $this->getJson($url)->assertJsonPath('audience', $audience);
        $copy = $this->postJson($url.'/duplicate')->assertOk()->json('id');
        $this->getJson('/api/quiz/manage/'.$copy)->assertOk()->assertJsonPath('audience', $audience);
        $this->patchJson($url.'/visibility', ['audience' => ['positions' => [], 'branches' => []]])->assertOk();
        $this->getJson($url)->assertJsonPath('audience.positions', [])->assertJsonPath('audience.branches', []);
        $this->assertSame($a['id'], DB::table('quiz_attempts')->value('id'));
        $this->assertSame(1, DB::table('quiz_attempts')->count());
    }

    public function test_assignment_and_role_boundaries()
    {
        $this->getJson('/api/quiz/manage')->assertForbidden();
        $this->getJson('/api/quiz/questions')->assertForbidden();
        $this->postJson('/api/quiz/users', ['username' => 'budi', 'name' => 'Budi', 'role' => 'admin'])->assertForbidden();
        $this->loginAs('other');
        $this->getJson('/api/quiz/student')->assertOk()->assertJsonCount(0, 'quizzes');
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertForbidden();
        $this->loginAs('budi'); $a = $this->start();
        $this->loginAs('other');
        $this->getJson('/api/quiz/attempts/'.$a['id'])->assertForbidden();
        $this->answer($a, [$this->correct])->assertForbidden();
        $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertForbidden();
        $this->loginAs('another-trainer');
        DB::table('lms_users')->insert(['username' => 'another-trainer', 'name' => 'Other teacher', 'role' => 'teacher']);
        $this->getJson('/api/quiz/manage/'.$this->quiz)->assertForbidden();
        $this->getJson('/api/quiz/results/'.$a['id'])->assertForbidden();
    }

    public function test_trainer_role_is_separate_and_cannot_manage_other_owners_or_roles()
    {
        $this->loginAs('administrator', 'JBT-032');
        $this->postJson('/api/quiz/users', ['username' => 'trainer', 'name' => 'Trainer', 'role' => 'trainer'])->assertOk();
        $this->loginAs('trainer');
        $this->getJson('/api/quiz/metadata')->assertOk()->assertJsonPath('user.role', 'trainer');
        $this->getJson('/api/quiz/manage/'.$this->quiz)->assertOk();
        $this->postJson('/api/quiz/users', ['username' => 'trainer', 'name' => 'Trainer', 'role' => 'admin'])->assertForbidden();
        DB::table('quizzes')->where('id', $this->quiz)->update(['created_by' => $this->other]);
        $this->getJson('/api/quiz/manage')->assertOk()->assertJsonCount(0);
        $this->getJson('/api/quiz/manage/'.$this->quiz)->assertForbidden();
        $this->getJson('/api/quiz/manage/'.$this->quiz.'/results')->assertForbidden();
    }

    public function test_trainer_access_is_limited_to_learning_and_its_master_data()
    {
        DB::table('lms_users')->where('id', $this->teacher)->update(['role' => 'trainer']);
        $this->loginAs('trainer');
        $this->getJson('/session')->assertOk()->assertJsonPath('auth.role', 'trainer');
        $this->getJson('/api/quiz/metadata')->assertOk();
        $this->getJson('/announcements')->assertForbidden();
        $this->getJson('/api/admin-overview')->assertForbidden();
        $this->getJson('/api/v1/announcements')->assertForbidden();
        $this->getJson('/master/jabatan')->assertForbidden();
        $this->postJson('/api/categories', [])->assertStatus(422);
        $this->postJson('/api/courses', [])->assertStatus(422);
        $this->postJson('/contents', [])->assertStatus(422);
        $this->postJson('/announcements', [])->assertForbidden();
        $this->postJson('/document-views', [])->assertForbidden();
        $this->partialMock(\App\Http\Controllers\api\DocumentReportController::class, function ($mock) {
            $mock->shouldReceive('getReport')->once()->andReturn(response()->json(['success' => true]));
            $mock->shouldReceive('getMiddleware')->andReturn([]);
        });
        $this->partialMock(\App\Http\Controllers\api\DocumentViewController::class, function ($mock) {
            $mock->shouldReceive('stats')->once()->andReturn(response()->json(['success' => true]));
            $mock->shouldReceive('getMiddleware')->andReturn([]);
        });
        $this->getJson('/api/v1/report/documents')->assertOk();
        $this->getJson('/document-views/stats')->assertOk();
    }

    public function test_resume_autosave_random_order_and_answer_key_redaction()
    {
        $a = $this->start();
        $this->assertArrayNotHasKey('is_correct', $a['questions'][0]['options'][0]);
        $this->assertArrayNotHasKey('explanation', $a['questions'][0]);
        $this->assertArrayNotHasKey('score', $a);
        $this->answer($a, [$this->correct])->assertOk()->assertJsonPath('revision', 1);
        $this->answer($a, [$this->wrong])->assertStatus(409);
        $this->answer($a, [999999], 1)->assertStatus(422);
        Carbon::setTestNow(now()->addMinutes(10));
        $b = $this->start();
        $this->assertSame($a['id'], $b['id']);
        $this->assertSame($a['expired_at'], $b['expired_at']);
        $this->assertSame($a['questions'][0]['options'], $b['questions'][0]['options']);
        $this->assertSame([$this->correct], $b['questions'][0]['selected_options']);
        $this->assertTrue($b['questions'][0]['flagged']);
        $this->assertSame($this->student, DB::table('quiz_attempts')->value('student_id'));
    }

    public function test_server_scoring_idempotent_submit_hidden_results_and_attempt_limit()
    {
        $a = $this->start(); $this->answer($a, [$this->correct])->assertOk();
        $url = '/api/quiz/attempts/'.$a['id'].'/submit';
        $result = $this->postJson($url)->assertOk()->assertJsonPath('status', 'SUBMITTED')->json();
        $this->assertArrayNotHasKey('score', $result); $this->assertSame([], $result['questions']);
        $this->assertEquals(100, DB::table('quiz_attempts')->value('score'));
        $this->postJson($url)->assertOk();
        $this->answer($a, [$this->wrong], 1)->assertStatus(409);
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertStatus(422);
        DB::table('quizzes')->where('id', $this->quiz)->update(['show_score' => true, 'show_passing_status' => true, 'show_total_correct' => true]);
        $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk()->assertJsonPath('aggregate_score', 100)->assertJsonPath('passed', true)->assertJsonPath('total_correct', 1);
    }

    public function test_late_answers_are_rejected_and_scheduler_submits_without_browser()
    {
        $a = $this->start(); Carbon::setTestNow(now()->addMinutes(46));
        $this->artisan('quiz:expire')->assertExitCode(0);
        $this->answer($a, [$this->correct])->assertStatus(409);
        $row = DB::table('quiz_attempts')->first();
        $this->assertSame('AUTO_SUBMITTED', $row->status); $this->assertEquals(0, $row->score); $this->assertSame($row->expired_at, $row->submitted_at);
        $this->assertSame(0.0, app(QuizService::class)->aggregate(app(QuizService::class)->quiz($this->quiz), $this->student));
    }

    public function test_override_schedule_duration_and_additional_attempt()
    {
        DB::table('quizzes')->where('id', $this->quiz)->update(['available_from' => now()->addHour()]);
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertStatus(422);
        DB::table('quiz_students')->insert(['quiz_id' => $this->quiz, 'student_id' => $this->student, 'additional_minutes' => 15, 'maximum_attempt' => 2, 'available_from' => now()->subMinute(), 'available_until' => now()->addHours(2)]);
        $a = $this->start(); $this->assertSame(60.0, Carbon::parse($a['started_at'])->diffInMinutes(Carbon::parse($a['expired_at'])));
        $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertOk();
        $b = $this->start(); $this->assertNotSame($a['id'], $b['id']); $this->assertSame(2, $b['attempt_number']);
    }

    public function test_essay_manual_grading_and_result_release()
    {
        DB::table('questions')->where('id', $this->question)->update(['type' => 'essay']);
        DB::table('question_options')->where('question_id', $this->question)->delete();
        DB::table('quizzes')->where('id', $this->quiz)->update(['show_score' => true, 'release_after_close' => true]);
        $a = $this->start();
        $this->putJson('/api/quiz/attempts/'.$a['id'].'/answers/'.$a['questions'][0]['id'], ['selected_options' => [], 'essay' => 'Jawaban essay', 'flagged' => false, 'visited' => true, 'revision' => 0])->assertOk();
        $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertOk()->assertJsonPath('pending_review', true);
        $this->assertNull(DB::table('quiz_attempts')->value('score'));
        $this->loginAs('trainer');
        $path = '/api/quiz/results/'.$a['id'].'/grade/'.$a['questions'][0]['id'];
        $this->putJson($path, ['points' => 3])->assertStatus(422);
        $this->putJson($path, ['points' => 2, 'feedback' => 'Cukup baik'])->assertOk();
        $this->assertEquals(80, DB::table('quiz_attempts')->value('score'));
        $this->loginAs('budi');
        $this->assertArrayNotHasKey('score', $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk()->json());
        Carbon::setTestNow(now()->addHours(4));
        $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk()->assertJsonPath('pending_review', false)->assertJsonPath('aggregate_score', 80);
    }

    public function test_management_generator_duplicate_and_analytics()
    {
        $this->loginAs('trainer');
        $this->getJson('/api/quiz/metadata')->assertOk();
        $this->getJson('/api/quiz/manage/'.$this->quiz.'/preview')->assertOk()->assertJsonCount(1, 'questions');
        $this->assertSame(0, DB::table('quiz_attempts')->count());
        $this->postJson('/api/quiz/generate', ['bank_id' => 1, 'buckets' => [['difficulty' => 'easy', 'count' => 2]]])->assertStatus(422);
        $this->postJson('/api/quiz/generate', ['bank_id' => 1, 'buckets' => [['difficulty' => 'easy', 'count' => 1]]])->assertOk()->assertJsonPath('question_ids.0', $this->question);
        $copy = $this->postJson('/api/quiz/manage/'.$this->quiz.'/duplicate')->assertOk()->json('id');
        $this->assertSame('DRAFT', DB::table('quizzes')->where('id', $copy)->value('status'));
        $this->assertSame(0, DB::table('quiz_classes')->where('quiz_id', $copy)->count());
        $this->assertSame(1, DB::table('quiz_questions')->where('quiz_id', $copy)->count());
        $this->loginAs('budi'); $a = $this->start(); $this->answer($a, [$this->correct])->assertOk(); $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertOk();
        $this->loginAs('trainer');
        $this->getJson('/api/quiz/manage/'.$this->quiz.'/results')->assertOk()->assertJsonPath('statistics.participants', 1)->assertJsonPath('statistics.completed', 1)->assertJsonPath('statistics.average_score', 100)->assertJsonPath('questions.0.correct_rate', 100);
    }

    public function test_multi_answer_requires_exact_option_ids()
    {
        $this->assertSame(2.5, QuizService::objectivePoints([3,1], [1,3], 2.5));
        $this->assertSame(0.0, QuizService::objectivePoints([1], [1,3], 2.5));
        $this->assertSame(0.0, QuizService::objectivePoints([1,2,3], [1,3], 2.5));
    }

    public function test_used_quiz_explanation_is_editable_without_changing_scores_or_shared_questions()
    {
        DB::table('quizzes')->where('id', $this->quiz)->update(['show_explanation' => true]);
        $a = $this->start();
        $this->answer($a, [$this->correct])->assertOk();
        $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertOk();
        $before = (array) DB::table('quiz_attempts')->find($a['id']);
        $answer = (array) DB::table('student_answers')->first();
        $url = '/api/quiz/manage/'.$this->quiz.'/questions/'.$this->question.'/explanation';
        $this->patchJson($url, ['explanation' => 'Manipulated'])->assertForbidden();
        $this->loginAs('trainer');
        $this->patchJson($url, ['explanation' => str_repeat('x', 20001)])->assertStatus(422);
        $this->patchJson($url, ['explanation' => ' Pembahasan diperbarui ', 'weight' => 99, 'question' => 'Changed'])->assertOk();
        $this->assertSame($before, (array) DB::table('quiz_attempts')->find($a['id']));
        $this->assertSame($answer, (array) DB::table('student_answers')->first());
        $this->assertSame('SECRET EXPLANATION', DB::table('questions')->find($this->question)->explanation);
        $this->getJson('/api/quiz/manage/'.$this->quiz)->assertOk()->assertJsonPath('question_explanations.'.$this->question, 'Pembahasan diperbarui');
        $this->loginAs('budi');
        $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk()->assertJsonPath('questions.0.explanation', 'Pembahasan diperbarui');
        $this->loginAs('trainer');
        $this->patchJson($url, ['explanation' => ''])->assertOk();
        $this->loginAs('budi');
        $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk()->assertJsonPath('questions.0.explanation', '');
        DB::table('quizzes')->where('id', $this->quiz)->update(['show_explanation' => false]);
        $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk()->assertJsonCount(0, 'questions');
    }

    public function test_explanation_and_answer_feedback_only_appear_after_authorized_result_release()
    {
        DB::table('quizzes')->where('id', $this->quiz)->update(['show_correct_answer' => true, 'show_wrong_answer' => true, 'show_explanation' => true]);
        $a = $this->start();
        $this->assertArrayNotHasKey('explanation', $a['questions'][0]);
        foreach ($a['questions'][0]['options'] as $option) {
            $this->assertArrayNotHasKey('answer_status', $option);
            $this->assertArrayNotHasKey('is_correct', $option);
        }
        $this->answer($a, [$this->wrong])->assertOk();
        $result = $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertOk()->json();
        $this->assertSame('SECRET EXPLANATION', $result['questions'][0]['explanation']);
        $options = collect($result['questions'][0]['options'])->keyBy('id');
        $this->assertTrue($options[$this->correct]['is_correct']);
        $this->assertSame('incorrect', $options[$this->wrong]['answer_status']);
        DB::table('quizzes')->where('id', $this->quiz)->update(['show_correct_answer' => false]);
        $result = $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk()->json();
        foreach ($result['questions'][0]['options'] as $option) {
            $this->assertArrayNotHasKey('is_correct', $option);
            if ($option['id'] === $this->correct) { $this->assertArrayNotHasKey('answer_status', $option); }
        }
        DB::table('quizzes')->where('id', $this->quiz)->update(['release_after_close' => true]);
        $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk()->assertJsonCount(0, 'questions');
    }

    public function test_attempt_limit_can_change_after_submission_without_changing_past_results()
    {
        $a = $this->start();
        $this->answer($a, [$this->correct])->assertOk();
        $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertOk();
        $original = (array) DB::table('quiz_attempts')->find($a['id']);
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertStatus(422);
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['maximum_attempt' => 2])->assertForbidden();
        $this->loginAs('trainer');
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['maximum_attempt' => -1])->assertStatus(422);
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['maximum_attempt' => 2, 'duration_minutes' => 999])->assertOk();
        $this->assertSame(45, DB::table('quizzes')->find($this->quiz)->duration_minutes);
        $this->loginAs('budi');
        $second = $this->start();
        $this->assertNotSame($a['id'], $second['id']);
        $this->assertSame($original, (array) DB::table('quiz_attempts')->find($a['id']));
        $this->loginAs('trainer');
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['maximum_attempt' => 1])->assertOk();
        $this->loginAs('budi');
        $this->assertSame($second['id'], $this->start()['id']);
        $this->postJson('/api/quiz/attempts/'.$second['id'].'/submit')->assertOk();
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertStatus(422);
        $this->loginAs('trainer');
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['maximum_attempt' => 0])->assertOk();
        $this->loginAs('budi');
        $this->start();
    }

    public function test_short_titles_are_trimmed_and_draft_saves_preserve_assignments()
    {
        $this->loginAs('trainer');
        $form = $this->getJson('/api/quiz/manage/'.$this->quiz)->assertOk()->json();
        $form['status'] = 'DRAFT'; $form['title'] = ' K3 ';
        $form['class_ids'] = [];
        $form['course_id'] = DB::table('courses')->insertGetId(['name' => 'K3']);
        $form['overrides'] = [['student_id' => $this->student, 'additional_minutes' => 10, 'maximum_attempt' => 3]];
        $created = $this->postJson('/api/quiz/manage', $form)->assertOk()->json('id');
        $this->assertSame('K3', DB::table('quizzes')->find($created)->title);
        $form['title'] = ' SOP ';
        $this->putJson('/api/quiz/manage/'.$created, $form)->assertOk();
        $this->assertSame(3, DB::table('quiz_students')->where('quiz_id', $created)->value('maximum_attempt'));
        $this->mock(\App\Services\Quiz\AudienceDirectory::class, function ($mock) {
            $mock->shouldReceive('positions')->andReturn([(object) ['value' => 'JBT-001']]);
            $mock->shouldReceive('branches')->andReturn([(object) ['value' => '0001']]);
        });
        $form['audience'] = ['positions' => ['JBT-001'], 'branches' => ['0001']];
        $this->putJson('/api/quiz/manage/'.$created, $form)->assertOk();
        $this->getJson('/api/quiz/manage/'.$created)->assertJsonPath('audience', $form['audience']);
        $new = $this->postJson('/api/quiz/manage', $form)->assertOk()->json('id');
        $this->getJson('/api/quiz/manage/'.$new)->assertJsonPath('audience', $form['audience']);

        $form['title'] = '   ';
        $this->putJson('/api/quiz/manage/'.$created, $form)->assertStatus(422)->assertJsonValidationErrors('title');
        $form['title'] = str_repeat('a', 151);
        $this->putJson('/api/quiz/manage/'.$created, $form)->assertStatus(422)->assertJsonValidationErrors('title');
        $form['title'] = 'UAS'; $form['status'] = 'PUBLISHED';
        $this->putJson('/api/quiz/manage/'.$created, $form)->assertOk();
        $this->assertSame('PUBLISHED', DB::table('quizzes')->find($created)->status);
    }

    public function test_used_quiz_settings_save_overrides_atomically_and_keep_their_priority()
    {
        $a = $this->start();
        $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertOk();
        $this->loginAs('trainer');
        $o = ['student_id' => $this->student, 'additional_minutes' => 0, 'maximum_attempt' => 1, 'available_from' => null, 'available_until' => null];
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['maximum_attempt' => 2, 'overrides' => [$o]])->assertOk();
        $this->loginAs('budi');
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertStatus(422);
        $this->loginAs('trainer');
        $invalid = $o; $invalid['available_from'] = now()->addDay()->toIso8601String();
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['maximum_attempt' => 3, 'overrides' => [$invalid]])->assertStatus(422);
        $this->assertSame(2, DB::table('quizzes')->find($this->quiz)->maximum_attempt);
        $o['maximum_attempt'] = null;
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['overrides' => [$o]])->assertOk();
        $this->loginAs('budi');
        $this->start();
    }

    public function test_bulk_questions_validate_keys_and_bank_ownership_atomically()
    {
        $this->loginAs('trainer');
        $q = ['question' => 'Jaminan?', 'type' => 'multiple_choice', 'weight' => 1, 'difficulty' => 'easy', 'options' => [['text' => 'Emas', 'is_correct' => true], ['text' => 'Buku', 'is_correct' => false]]];
        $foreignBank = DB::table('question_banks')->insertGetId(['name' => 'Bank lain', 'created_by' => $this->other]);
        $this->postJson('/api/quiz/questions/bulk', ['bank_id' => $foreignBank, 'questions' => [$q]])->assertForbidden();
        $invalid = $q; $invalid['options'][0]['is_correct'] = false;
        $this->postJson('/api/quiz/questions/bulk', ['bank_id' => 1, 'questions' => [$q, $invalid]])->assertStatus(422);
        $this->assertSame(1, DB::table('questions')->count());
        $this->postJson('/api/quiz/questions/bulk', ['bank_id' => 1, 'questions' => [$q]])->assertOk()->assertJsonPath('questions.0.difficulty', 'easy');
        $this->assertSame(2, DB::table('questions')->count());
    }

    public function test_random_question_selection_respects_ownership_and_filters()
    {
        $this->loginAs('trainer');
        $this->postJson('/api/quiz/generate', ['buckets' => [['count' => 1]]])->assertOk()->assertJsonPath('question_ids.0', $this->question);
        $this->postJson('/api/quiz/questions/replace-one', ['exclude_ids' => [], 'difficulty' => 'hard'])->assertStatus(422);
        $this->postJson('/api/quiz/questions/replace-one', ['exclude_ids' => [$this->question]])->assertStatus(422);
        $this->postJson('/api/quiz/questions/replace-one', ['exclude_ids' => [], 'difficulty' => 'easy'])->assertOk()->assertJsonPath('question.id', $this->question);
    }

    public function test_publish_requires_one_course_and_not_class_assignments()
    {
        $this->loginAs('trainer');
        DB::table('quizzes')->where('id', $this->quiz)->update(['status' => 'DRAFT']);
        $form = $this->getJson('/api/quiz/manage/'.$this->quiz)->assertOk()->json();
        $form['class_ids'] = [];
        $this->postJson('/api/quiz/manage', $form)->assertStatus(422)->assertJsonValidationErrors('course_id');
        $form['status'] = 'PUBLISHED'; $form['class_ids'] = []; $form['overrides'] = [];
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertStatus(422)->assertJsonValidationErrors('course_id');
        $this->assertSame('DRAFT', DB::table('quizzes')->where('id', $this->quiz)->value('status'));
        $form['question_count'] = 10;
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertStatus(422)->assertJsonValidationErrors('course_id');
        $form['question_count'] = 1;
        $course = DB::table('courses')->insertGetId(['name' => 'Produk Gadai']);
        $form['course_id'] = [$course];
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertStatus(422)->assertJsonValidationErrors('course_id');
        $form['course_id'] = $course; $form['class_ids'] = [$this->class];
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertStatus(422)->assertJsonValidationErrors('class_ids');
        $form['class_ids'] = [];
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertOk();
        $this->assertSame('PUBLISHED', DB::table('quizzes')->where('id', $this->quiz)->value('status'));
        $this->assertSame(0, DB::table('quiz_classes')->where('quiz_id', $this->quiz)->count());
        $this->loginAs('other');
        $this->getJson('/api/quiz/student')->assertOk()->assertJsonCount(0, 'quizzes');
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertForbidden();
        Schema::create('course_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('course_id'); $t->unsignedBigInteger('quiz_id'); $t->string('type');
        });
        DB::table('course_details')->insert(['course_id' => $course, 'quiz_id' => $this->quiz, 'type' => 'quiz']);
        app(\App\Services\Quiz\QuizAudience::class)->save($this->quiz, ['positions' => ['JBT-001'], 'branches' => []]);
        $this->getJson('/api/quiz/student')->assertOk()->assertJsonCount(0, 'quizzes');
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertForbidden();
        app(\App\Services\Quiz\QuizAudience::class)->save($this->quiz, ['positions' => [], 'branches' => []]);
        $this->getJson('/api/quiz/student')->assertOk()->assertJsonPath('quizzes.0.course_id', $course);
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertOk()->assertJsonPath('status', 'IN_PROGRESS');
        $this->loginAs('trainer');
        $this->getJson('/api/quiz/manage/'.$this->quiz.'/results')->assertOk()->assertJsonPath('classes.0.name', 'Produk Gadai')->assertJsonPath('statistics.participants', 3);
    }

    public function test_passing_grade_must_cover_total_question_weight()
    {
        $this->loginAs('trainer');
        $form = $this->getJson('/api/quiz/manage/'.$this->quiz)->assertOk()->json();
        $form['course_id'] = DB::table('courses')->insertGetId(['name' => 'Bobot']);
        $form['class_ids'] = [];
        DB::table('questions')->where('id', $this->question)->update(['weight' => 100]);
        $form['passing_grade'] = 99;
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertStatus(422)->assertJsonValidationErrors('passing_grade');
        $form['passing_grade'] = 100;
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertOk();
        $question = $this->getJson('/api/quiz/questions')->assertOk()->json('0');
        DB::table('quizzes')->where('id', $this->quiz)->update(['status' => 'DRAFT']);
        $question['weight'] = 101;
        $this->putJson('/api/quiz/questions/'.$this->question, $question)->assertStatus(422);
        $this->assertEquals(100, DB::table('questions')->where('id', $this->question)->value('weight'));
        DB::table('questions')->where('id', $this->question)->update(['weight' => 101]);
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertStatus(422)->assertJsonValidationErrors('passing_grade');
    }

    public function test_quiz_without_dates_can_be_published_and_started_later_with_a_timer()
    {
        $this->loginAs('trainer');
        $form = $this->getJson('/api/quiz/manage/'.$this->quiz)->assertOk()->json();
        $course = DB::table('courses')->insertGetId(['name' => 'Tanpa Jadwal']);
        $form = array_merge($form, ['course_id' => $course, 'class_ids' => [], 'no_schedule' => true,
            'available_from' => null, 'available_until' => null, 'release_after_close' => true, 'show_score' => true]);
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertOk();
        $this->assertEquals(1, DB::table('quizzes')->where('id', $this->quiz)->value('no_schedule'));
        $this->assertEquals(0, DB::table('quizzes')->where('id', $this->quiz)->value('release_after_close'));
        Schema::create('course_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('course_id'); $t->unsignedBigInteger('quiz_id'); $t->string('type');
        });
        DB::table('course_details')->insert(['course_id' => $course, 'quiz_id' => $this->quiz, 'type' => 'quiz']);
        $this->loginAs('budi');
        Carbon::setTestNow(now()->addYears(2));
        $this->getJson('/api/quiz/student')->assertOk()->assertJsonPath('quizzes.0.no_schedule', true)
            ->assertJsonPath('quizzes.0.available_until', null)->assertJsonPath('quizzes.0.can_start', true);
        $attempt = $this->start();
        $this->assertSame(45.0, Carbon::parse($attempt['started_at'])->diffInMinutes(Carbon::parse($attempt['expired_at'])));
        $this->postJson('/api/quiz/attempts/'.$attempt['id'].'/submit')->assertOk()->assertJsonStructure(['score']);
        DB::table('quizzes')->where('id', $this->quiz)->update(['status' => 'CLOSED']);
        $this->postJson('/api/quiz/'.$this->quiz.'/start')->assertForbidden();
    }

    public function test_course_content_links_native_quiz_and_preserves_legacy_forms()
    {
        Schema::create('course_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('course_id'); $t->string('title'); $t->string('type'); $t->text('content')->nullable(); $t->integer('order'); $t->timestamps();
        });
        require_once database_path('migrations/2026_09_23_000002_link_course_content_quizzes.php');
        (new \LinkCourseContentQuizzes)->up();
        $course = DB::table('courses')->insertGetId(['name' => 'Produk Gadai']);
        $otherCourse = DB::table('courses')->insertGetId(['name' => 'SOP']);
        DB::table('quizzes')->where('id', $this->quiz)->update(['course_id' => $course]);
        $payload = ['course_id' => $course, 'title' => 'Quiz Produk Gadai', 'type' => 'quiz', 'quiz_id' => $this->quiz, 'order' => 1];
        $this->postJson('/contents', $payload)->assertForbidden();
        $this->loginAs('trainer');
        $this->postJson('/contents', array_merge($payload, ['course_id' => $otherCourse]))->assertStatus(422);
        $saved = $this->postJson('/contents', $payload)->assertOk()->assertJsonPath('data.quiz_id', $this->quiz)->json('data.id');
        $this->assertNull(DB::table('course_details')->where('id', $saved)->value('content'));
        $form = $this->getJson('/api/quiz/manage/'.$this->quiz)->assertOk()->assertJsonPath('course_locked', true)->json();
        $form['class_ids'] = []; $form['course_id'] = $otherCourse;
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertStatus(422);
        $this->assertSame($course, DB::table('quizzes')->find($this->quiz)->course_id);
        $this->putJson('/contents/'.$saved, ['title' => 'Quiz Revisi'])->assertOk()->assertJsonPath('data.quiz_id', $this->quiz);
        $this->loginAs('budi');
        $this->putJson('/contents/'.$saved, ['type' => 'text', 'quiz_id' => null])->assertForbidden();
        $this->deleteJson('/contents/'.$saved)->assertForbidden();
        $this->loginAs('trainer');
        $this->putJson('/contents/'.$saved, ['type' => 'quiz', 'quiz_id' => null, 'content' => 'https://docs.google.com/forms/example'])->assertOk()->assertJsonPath('data.quiz_id', null)->assertJsonPath('data.content', 'https://docs.google.com/forms/example');
        $legacy = array_merge($payload, ['quiz_id' => null, 'content' => 'https://docs.google.com/forms/legacy']);
        $this->postJson('/contents', $legacy)->assertOk()->assertJsonPath('data.content', 'https://docs.google.com/forms/legacy');
    }

    public function test_authoring_publish_validation_and_immutable_used_questions()
    {
        $this->loginAs('trainer');
        $q = ['bank_id' => 1, 'question' => 'Emas dapat menjadi jaminan?', 'type' => 'true_false', 'weight' => 1, 'difficulty' => 'medium', 'options' => [['text' => 'Benar', 'is_correct' => true], ['text' => 'Salah', 'is_correct' => false]]];
        $newQuestion = $this->postJson('/api/quiz/questions', $q)->assertOk()->json('id');
        $form = $this->getJson('/api/quiz/manage/'.$this->quiz)->assertOk()->json();
        foreach (['randomize_question','randomize_answer','show_score','show_correct_answer','show_wrong_answer','show_explanation','show_total_correct','show_passing_status','release_after_close'] as $key) { $form[$key] = (bool) $form[$key]; }
        $form['question_ids'] = [$newQuestion]; $form['question_count'] = 2;
        $form['class_ids'] = [];
        $form['course_id'] = DB::table('courses')->insertGetId(['name' => 'Produk Gadai']);
        $this->postJson('/api/quiz/manage', $form)->assertStatus(422);
        $form['question_count'] = 1;
        $created = $this->postJson('/api/quiz/manage', $form)->assertOk()->json('id');
        $this->assertSame(0, DB::table('quiz_classes')->where('quiz_id', $created)->count());
        $this->putJson('/api/quiz/questions/'.$newQuestion, $q)->assertStatus(422);
        $this->loginAs('budi'); $a = $this->start();
        $this->answer($a, [])->assertOk();
        $heartbeat = $this->getJson('/api/quiz/attempts/'.$a['id'].'?heartbeat=1')->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->assertArrayNotHasKey('questions', $heartbeat->json());
        $this->loginAs('trainer');
        $this->putJson('/api/quiz/manage/'.$this->quiz, $form)->assertStatus(422);
        $this->patchJson('/api/quiz/manage/'.$this->quiz.'/visibility', ['show_score' => true, 'status' => 'CLOSED'])->assertOk();
    }

    public function test_randomized_pool_is_persisted_and_attempts_keep_separate_answers()
    {
        for ($i = 0; $i < 12; $i++) {
            $q = DB::table('questions')->insertGetId(['bank_id' => 1, 'question' => 'Pertanyaan '.$i, 'type' => 'multiple_choice', 'weight' => 1, 'difficulty' => 'easy', 'created_by' => $this->teacher]);
            DB::table('question_options')->insert([['question_id' => $q, 'text' => 'A', 'is_correct' => true], ['question_id' => $q, 'text' => 'B', 'is_correct' => false]]);
            DB::table('quiz_questions')->insert(['quiz_id' => $this->quiz, 'question_id' => $q, 'position' => $i + 1]);
        }
        DB::table('quizzes')->where('id', $this->quiz)->update(['question_count' => 10, 'maximum_attempt' => 2]);
        $a = $this->start(); $this->assertCount(10, $a['questions']);
        $this->assertSame(range(1, 10), array_column($a['questions'], 'position'));
        $b = $this->getJson('/api/quiz/attempts/'.$a['id'])->assertOk()->json();
        $this->assertSame($a['questions'], $b['questions']);
        $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertOk();
        $c = $this->start();
        $this->assertNotSame($a['id'], $c['id']);
        $this->assertSame([], $c['questions'][0]['selected_options']);
        $this->assertSame(20, DB::table('quiz_attempt_questions')->count());
    }

    public function test_all_score_aggregation_policies_and_deadline_cap()
    {
        DB::table('quizzes')->where('id', $this->quiz)->update(['maximum_attempt' => 0, 'available_until' => now()->addMinutes(5)]);
        $a = $this->start();
        $this->assertSame(5.0, Carbon::parse($a['started_at'])->diffInMinutes(Carbon::parse($a['expired_at'])));
        $this->postJson('/api/quiz/attempts/'.$a['id'].'/submit')->assertOk();
        $b = $this->start(); $this->answer($b, [$this->correct])->assertOk(); $this->postJson('/api/quiz/attempts/'.$b['id'].'/submit')->assertOk();
        $service = app(QuizService::class); $quiz = $service->quiz($this->quiz);
        foreach (['first' => 0, 'latest' => 100, 'highest' => 100, 'average' => 50] as $method => $score) { $quiz->grading_method = $method; $this->assertEquals($score, $service->aggregate($quiz, $this->student)); }
    }
}
