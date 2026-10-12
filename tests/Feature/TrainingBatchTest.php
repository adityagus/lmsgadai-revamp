<?php

namespace Tests\Feature;

use App\Services\CourseAudience;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class TrainingBatchTest extends TestCase
{
    use \Tests\EmployeeDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->createEmployeeDatabase();
        Schema::create('categories', function ($t) { $t->id(); $t->string('name'); });
        Schema::create('courses', function ($t) { $t->id(); $t->string('name'); $t->integer('category_id')->nullable(); });
        Schema::create('course_details', function ($t) { $t->id(); $t->integer('course_id'); $t->string('type'); $t->integer('order'); $t->integer('quiz_id')->nullable(); });
        (require database_path('migrations/2026_10_08_000001_create_training_batches.php'))->up();
        (require database_path('migrations/2026_09_25_000003_create_course_access_rules.php'))->up();
        (require database_path('migrations/2026_09_28_000001_create_course_content_progress_table.php'))->up();
        DB::table('courses')->insert(['id' => 1, 'name' => 'Dasar Gadai']);
        DB::table('course_details')->insert(['id' => 1, 'course_id' => 1, 'type' => 'text', 'order' => 1]);
        DB::table('course_access_rules')->insert(['course_id' => 1, 'position_code' => 'OTHER']);
    }

    private function login(string $user = 'trainer', string $position = 'JBT-TRAINER'): static
    {
        return $this->withSession(['auth' => ['user' => $user, 'status' => 'login', 'idgrup' => $position]]);
    }

    private function data(array $changes = []): array
    {
        return array_replace(['name' => 'Oktober', 'description' => 'Orientasi', 'starts_on' => today()->toDateString(),
            'ends_on' => today()->addDays(7)->toDateString(), 'status' => 'active', 'course_ids' => [1], 'usernames' => ['budi', 'other']], $changes);
    }

    public function test_manager_creates_batch_and_student_access_follows_period_and_membership(): void
    {
        $this->getJson('/api/training-batches')->assertUnauthorized();
        $this->login()->get('/batch-pelatihan')->assertOk();
        $id = $this->postJson('/api/training-batches', $this->data())->assertOk()->json('id');
        $this->getJson('/api/training-batches')->assertJsonPath('batches.0.member_count', 2)->assertJsonPath('batches.0.course_count', 1);
        $this->login('budi', 'STAFF');
        $this->getJson('/api/training-batches')->assertJsonCount(1, 'batches')->assertJsonPath('can_manage', false);
        $this->getJson('/api/training-batches/'.$id)->assertOk()->assertJsonCount(1, 'members')->assertJsonPath('members.0.username', 'budi');
        $this->getJson('/api/courses')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/courses/1/progress')->assertOk();
        $this->postJson('/api/courses/1/progress/1/complete')->assertOk();
        $this->postJson('/api/training-batches', $this->data())->assertForbidden();
        $this->getJson('/api/training-batches/options?q=budi')->assertForbidden();
        $this->travel(8)->days();
        $this->getJson('/api/courses/1/progress')->assertForbidden();
        $this->getJson('/api/courses')->assertJsonPath('meta.total', 0);
        $this->assertDatabaseCount('course_content_progress', 1);
        $this->travelBack();
        $this->login('new-employee', 'STAFF')->getJson('/api/training-batches/'.$id)->assertNotFound();
        $this->login('another-trainer')->putJson('/api/training-batches/'.$id, $this->data(['version' => 1]))->assertNotFound();
    }

    public function test_report_keeps_prior_progress_and_inactive_history_and_updates_are_versioned(): void
    {
        DB::table('course_content_progress')->insert(['course_id' => 1, 'content_id' => 1, 'username' => 'budi', 'completed_at' => now()->subMonth()]);
        $id = $this->login()->postJson('/api/training-batches', $this->data())->assertOk()->json('id');
        $this->getJson('/api/training-batches/'.$id)->assertOk()->assertJsonPath('progress.0.percent', 100)->assertJsonCount(2, 'members');
        DB::connection('db2')->table('auth.users')->where('username', 'budi')->update(['is_active' => false]);
        $this->putJson('/api/training-batches/'.$id, $this->data(['version' => 1, 'status' => 'completed']))->assertOk();
        $this->putJson('/api/training-batches/'.$id, $this->data(['version' => 1]))->assertStatus(409);
        $this->getJson('/api/training-batches/'.$id)->assertJsonPath('batch.status', 'completed')->assertJsonPath('progress.0.percent', 100);
        $this->login('budi', 'STAFF')->getJson('/api/courses/1/progress')->assertForbidden();
        DB::table('course_access_rules')->delete();
        $this->getJson('/api/courses/1/progress')->assertOk();
        $this->assertDatabaseCount('course_content_progress', 1);
    }

    public function test_invalid_members_courses_dates_and_empty_activation_are_rejected(): void
    {
        $this->login();
        foreach ([['usernames' => ['inactive']], ['usernames' => ['forged']], ['course_ids' => [999]], ['course_ids' => []], ['usernames' => []], ['ends_on' => today()->subDay()->toDateString()]] as $changes) {
            $this->postJson('/api/training-batches', $this->data($changes))->assertUnprocessable();
        }
        $this->assertDatabaseCount('training_batches', 0);
        $id = $this->postJson('/api/training-batches', $this->data(['status' => 'draft', 'course_ids' => [], 'usernames' => []]))->assertOk()->json('id');
        $this->login('budi', 'STAFF')->getJson('/api/training-batches/'.$id)->assertNotFound();
        $this->getJson('/api/training-batches')->assertJsonCount(0, 'batches');
        $this->login()->getJson('/api/training-batches/options?q=budi')->assertOk()->assertJsonPath('employees.0.name', 'Budi Karyawan');
        $this->login('administrator', 'JBT-032')->getJson('/api/training-batches/'.$id)->assertOk();
    }

    public function test_quiz_completion_and_overlapping_batch_access_preserve_history(): void
    {
        Schema::create('lms_users', function ($t) { $t->id(); $t->string('username'); });
        Schema::create('quiz_attempts', function ($t) { $t->id(); $t->integer('student_id'); $t->integer('quiz_id'); $t->string('status'); $t->timestamp('submitted_at')->nullable(); });
        DB::table('lms_users')->insert(['id' => 7, 'username' => 'budi']);
        DB::table('course_details')->insert(['id' => 2, 'course_id' => 1, 'type' => 'quiz', 'order' => 2, 'quiz_id' => 9]);
        DB::table('quiz_attempts')->insert(['student_id' => 7, 'quiz_id' => 9, 'status' => 'IN_PROGRESS']);
        $id = $this->login()->postJson('/api/training-batches', $this->data())->assertOk()->json('id');
        $otherId = $this->postJson('/api/training-batches', $this->data(['name' => 'Kedua']))->assertOk()->json('id');
        $this->getJson('/api/training-batches/'.$id)->assertJsonPath('progress.0.completed', 0);
        DB::table('quiz_attempts')->update(['status' => 'SUBMITTED', 'submitted_at' => now()]);
        $this->getJson('/api/training-batches/'.$id)->assertJsonPath('progress.0.completed', 1)->assertJsonPath('progress.0.percent', 50);
        $this->putJson('/api/training-batches/'.$id, $this->data(['version' => 1, 'usernames' => ['other']]))->assertOk();
        $this->login('budi', 'STAFF')->getJson('/api/courses/1/progress')->assertOk();
        $this->getJson('/api/training-batches/'.$id)->assertNotFound();
        $this->login()->putJson('/api/training-batches/'.$otherId, $this->data(['version' => 1, 'starts_on' => today()->addDay()->toDateString()]))->assertOk();
        $this->login('budi', 'STAFF')->getJson('/api/courses/1/progress')->assertForbidden();
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_learning_feed_explains_closed_and_scheduled_batches_without_granting_access(): void
    {
        $closed = $this->login()->postJson('/api/training-batches', $this->data(['status' => 'completed']))->assertOk()->json('id');
        $scheduled = $this->postJson('/api/training-batches', $this->data(['starts_on' => today()->addDay()->toDateString()]))->assertOk()->json('id');
        $this->postJson('/api/training-batches', $this->data(['status' => 'draft']))->assertOk();
        $this->login('budi', 'STAFF')->getJson('/api/training-batches/my-learning')->assertOk()
            ->assertJsonCount(2, 'batches')->assertJsonPath('batches.0.state', 'scheduled')
            ->assertJsonPath('batches.0.courses.0.can_open', false)
            ->assertJsonPath('batches.1.state', 'completed')->assertJsonPath('batches.1.courses.0.can_open', false);
        $this->login('new-employee', 'STAFF')->getJson('/api/training-batches/my-learning')->assertJsonCount(0, 'batches');
        $this->login()->putJson('/api/training-batches/'.$closed, $this->data(['version' => 1, 'status' => 'active']))->assertOk();
        DB::table('course_content_progress')->insert(['username' => 'budi', 'course_id' => 1, 'content_id' => 1, 'completed_at' => now()]);
        $this->login('budi', 'STAFF')->getJson('/api/training-batches/my-learning')->assertOk()
            ->assertJsonPath('batches.1.state', 'ready')->assertJsonPath('batches.1.courses.0.can_open', true)
            ->assertJsonPath('batches.1.courses.0.completed', 1)->assertJsonPath('batches.1.courses.0.total', 1);
        $this->getJson('/api/courses')->assertJsonPath('meta.total', 1);
        $this->travel(8)->days();
        $this->getJson('/api/training-batches/my-learning')->assertJsonPath('batches.0.state', 'expired')
            ->assertJsonPath('batches.1.courses.0.can_open', false);
        DB::table('course_access_rules')->delete();
        $this->getJson('/api/training-batches/my-learning')->assertJsonPath('batches.1.courses.0.can_open', true);
    }

    public function test_batch_learning_courses_are_visible_independently_of_catalog_pagination(): void
    {
        foreach (range(2, 7) as $id) DB::table('courses')->insert(['id' => $id, 'name' => 'Kelas '.$id]);
        $this->login()->postJson('/api/training-batches', $this->data())->assertOk();
        $this->login('budi', 'STAFF')->getJson('/api/courses')->assertOk()->assertJsonPath('meta.last_page', 2)
            ->assertJsonMissing(['id' => 1, 'name' => 'Dasar Gadai']);
        $this->getJson('/api/training-batches/my-learning')->assertOk()->assertJsonPath('batches.0.courses.0.id', 1)
            ->assertJsonPath('batches.0.courses.0.can_open', true);
        $this->getJson('/api/courses/1/progress')->assertOk();
    }
}
