<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseProgressTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('courses', fn ($t) => $t->id());
        Schema::create('course_details', function ($t) {
            $t->id(); $t->unsignedBigInteger('course_id'); $t->string('type');
            $t->text('content')->nullable(); $t->integer('order'); $t->unsignedBigInteger('quiz_id')->nullable();
        });
        (require database_path('migrations/2026_09_28_000001_create_course_content_progress_table.php'))->up();
        DB::table('courses')->insert([['id' => 1], ['id' => 2]]);
        DB::table('course_details')->insert([
            ['id' => 1, 'course_id' => 1, 'type' => 'text', 'order' => 1],
            ['id' => 2, 'course_id' => 1, 'type' => 'pdf', 'order' => 2],
            ['id' => 3, 'course_id' => 2, 'type' => 'video', 'order' => 1],
        ]);
    }

    private function loginAs(string $username = 'employee-a'): static
    {
        return $this->withSession(['auth' => ['status' => 'login', 'user' => $username, 'idgrup' => 'STAFF']]);
    }

    public function test_completion_persists_is_idempotent_and_isolated_per_employee(): void
    {
        $this->loginAs()->getJson('/api/courses/1/progress')->assertOk()
            ->assertJsonPath('completed_count', 0)->assertJsonPath('resume_content_id', 1);
        $this->assertDatabaseCount('course_content_progress', 0);
        $first = $this->postJson('/api/courses/1/progress/1/complete', ['username' => 'employee-b'])->assertOk()
            ->assertJsonPath('completed_count', 1)->assertJsonPath('resume_content_id', 2)->json('items.0.completed_at');
        $this->travel(1)->hours();
        $this->postJson('/api/courses/1/progress/1/complete')->assertOk()->assertJsonPath('items.0.completed_at', $first);
        $this->assertDatabaseCount('course_content_progress', 1);
        $this->loginAs('employee-b')->getJson('/api/courses/1/progress')->assertJsonPath('completed_count', 0);
        $this->loginAs()->getJson('/api/v1/courses/1/progress')->assertJsonPath('completed_count', 1);
        $this->postJson('/api/v1/courses/1/progress/2/complete')->assertOk()->assertJsonPath('is_complete', true);
    }

    public function test_skipping_to_last_lesson_does_not_complete_the_course(): void
    {
        $this->loginAs()->postJson('/api/courses/1/progress/2/complete')->assertForbidden();
        $this->getJson('/contents/2')->assertForbidden();
        $this->assertDatabaseCount('course_content_progress', 0);
        DB::table('course_details')->where('id', 2)->delete();
        $this->getJson('/api/courses/1/progress')->assertJsonPath('total', 1)->assertJsonPath('completed_count', 0);
    }

    public function test_guest_wrong_course_and_restricted_course_are_rejected(): void
    {
        $this->getJson('/api/courses/1/progress')->assertUnauthorized();
        $this->postJson('/api/courses/1/progress/1/complete')->assertUnauthorized();
        $this->loginAs()->postJson('/api/courses/1/progress/3/complete')->assertNotFound();
        $this->getJson('/api/courses/999/progress')->assertNotFound();
        Schema::create('course_access_rules', function ($t) { $t->integer('course_id'); $t->string('position_code'); });
        DB::table('course_access_rules')->insert(['course_id' => 1, 'position_code' => 'OTHER']);
        $this->getJson('/api/courses/1/progress')->assertForbidden();
        $this->postJson('/api/courses/1/progress/1/complete')->assertForbidden();
        $this->assertDatabaseCount('course_content_progress', 0);
    }

    public function test_native_quiz_requires_submitted_attempt_and_empty_course_is_not_complete(): void
    {
        DB::table('course_details')->where('id', 2)->update(['type' => 'quiz', 'quiz_id' => 7]);
        $this->loginAs()->postJson('/api/courses/1/progress/2/complete')->assertUnprocessable();
        Schema::create('lms_users', function ($t) { $t->id(); $t->string('username'); $t->string('role'); });
        Schema::create('quiz_attempts', function ($t) {
            $t->id(); $t->integer('student_id'); $t->integer('quiz_id'); $t->string('status'); $t->timestamp('submitted_at')->nullable();
        });
        DB::table('lms_users')->insert(['id' => 1, 'username' => 'employee-a', 'role' => 'student']);
        DB::table('quiz_attempts')->insert(['student_id' => 1, 'quiz_id' => 7, 'status' => 'IN_PROGRESS']);
        $this->getJson('/api/courses/1/progress')->assertJsonPath('completed_count', 0);
        DB::table('quiz_attempts')->update(['status' => 'SUBMITTED', 'submitted_at' => now()]);
        $this->getJson('/api/courses/1/progress')->assertJsonPath('completed_count', 1);
        DB::table('course_details')->where('course_id', 2)->delete();
        $this->getJson('/api/courses/2/progress')->assertJsonPath('total', 0)->assertJsonPath('is_complete', false);
    }
}
