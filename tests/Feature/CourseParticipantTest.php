<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseParticipantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.db2' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('db2');
        $db = DB::connection('db2');
        $db->statement("ATTACH DATABASE ':memory:' AS auth");
        $db->statement("ATTACH DATABASE ':memory:' AS master");
        $db->statement('CREATE TABLE auth.users (username TEXT, full_name TEXT, employee_id INTEGER, is_active INTEGER)');
        $db->statement('CREATE TABLE master.employee (employee_id INTEGER, position_id INTEGER)');
        $db->statement('CREATE TABLE master.position (position_id INTEGER, position_code TEXT)');
        $db->table('master.position')->insert([['position_id' => 1, 'position_code' => 'ALLOWED'], ['position_id' => 2, 'position_code' => 'OTHER']]);
        $db->table('master.employee')->insert([['employee_id' => 1, 'position_id' => 2], ['employee_id' => 2, 'position_id' => 1]]);
        $db->table('auth.users')->insert([
            ['username' => 'budi', 'full_name' => 'Budi Karyawan', 'employee_id' => 1, 'is_active' => 1],
            ['username' => 'inactive', 'full_name' => 'Budi Nonaktif', 'employee_id' => 2, 'is_active' => 0],
        ]);
        Schema::create('categories', function ($t) { $t->id(); $t->string('name'); });
        Schema::create('courses', function ($t) { $t->id(); $t->string('name'); $t->integer('category_id')->nullable(); });
        Schema::create('course_details', function ($t) { $t->id(); $t->integer('course_id'); $t->string('type'); $t->integer('order'); $t->integer('quiz_id')->nullable(); });
        (require database_path('migrations/2026_09_25_000003_create_course_access_rules.php'))->up();
        (require database_path('migrations/2026_09_28_000001_create_course_content_progress_table.php'))->up();
        (require database_path('migrations/2026_09_29_000001_create_course_participants_table.php'))->up();
        DB::table('courses')->insert([['id' => 1, 'name' => 'Terbatas'], ['id' => 2, 'name' => 'Terbuka']]);
        DB::table('course_details')->insert(['id' => 1, 'course_id' => 1, 'type' => 'text', 'order' => 1]);
        DB::table('course_access_rules')->insert(['course_id' => 1, 'position_code' => 'ALLOWED']);
    }

    private function loginAs(string $username = 'manager', string $position = 'JBT-032'): static
    {
        return $this->withSession(['auth' => ['status' => 'login', 'user' => $username, 'idgrup' => $position]]);
    }

    public function test_existing_quiz_participants_are_imported_once_and_included_in_class_count(): void
    {
        Schema::create('lms_users', function ($t) { $t->id(); $t->string('username'); $t->string('role'); });
        Schema::create('quizzes', function ($t) { $t->id(); $t->integer('course_id')->nullable(); });
        Schema::create('quiz_students', function ($t) { $t->integer('quiz_id'); $t->integer('student_id'); $t->integer('additional_minutes'); });
        DB::table('lms_users')->insert(['id' => 1, 'username' => 'budi', 'role' => 'student']);
        DB::table('quizzes')->insert([['id' => 1, 'course_id' => 1], ['id' => 2, 'course_id' => 1], ['id' => 3, 'course_id' => null]]);
        DB::table('quiz_students')->insert([
            ['quiz_id' => 1, 'student_id' => 1, 'additional_minutes' => 10],
            ['quiz_id' => 2, 'student_id' => 1, 'additional_minutes' => 20],
            ['quiz_id' => 3, 'student_id' => 1, 'additional_minutes' => 30],
        ]);
        $this->loginAs()->getJson('/api/courses/1/participants')->assertJsonPath('total_students', 0);
        $migration = require database_path('migrations/2026_09_29_000002_import_quiz_participants_into_courses.php');
        $migration->up();
        $created = DB::table('course_participants')->value('created_at');
        $migration->up();
        $this->assertDatabaseCount('course_participants', 1);
        $this->assertDatabaseHas('course_participants', ['course_id' => 1, 'username' => 'budi', 'created_at' => $created]);
        $this->assertDatabaseCount('quiz_students', 3);
        $this->assertDatabaseHas('quiz_students', ['quiz_id' => 1, 'additional_minutes' => 10]);
        $this->getJson('/api/courses/1/participants')->assertOk()->assertJsonCount(1, 'participants')->assertJsonPath('total_students', 1);
        $this->loginAs('budi', 'OTHER')->getJson('/api/courses/1/progress')->assertOk();
    }

    public function test_empty_special_participants_and_removing_last_participant_return_valid_counts(): void
    {
        $this->loginAs();
        foreach (['/api', '/api/v1'] as $prefix) {
            // Selected position has no active employee: zero is a valid total.
            $this->getJson($prefix.'/courses/1/participants')->assertOk()
                ->assertJsonPath('participants', [])->assertJsonPath('total_students', 0);
            // An unrestricted class still counts active employees without special participants.
            $this->getJson($prefix.'/courses/2/participants')->assertOk()
                ->assertJsonPath('participants', [])->assertJsonPath('total_students', 1);
        }
        DB::connection('db2')->table('master.employee')->where('employee_id', 1)->update(['position_id' => 1]);
        $this->getJson('/api/courses/1/participants')->assertOk()
            ->assertJsonPath('participants', [])->assertJsonPath('total_students', 1);
        $this->postJson('/api/courses/1/participants', ['username' => 'budi'])->assertOk();
        $this->deleteJson('/api/courses/1/participants', ['username' => 'budi'])->assertOk();
        $this->getJson('/api/courses/1/participants')->assertOk()
            ->assertJsonPath('participants', [])->assertJsonPath('total_students', 1);
        $this->assertDatabaseCount('course_participants', 0);
    }

    public function test_student_total_adds_position_students_and_all_listed_participants_even_when_they_overlap(): void
    {
        $db = DB::connection('db2');
        $db->table('master.employee')->insert([['employee_id' => 3, 'position_id' => 1], ['employee_id' => 4, 'position_id' => 2]]);
        $db->table('auth.users')->insert([
            ['username' => 'position-user', 'full_name' => 'Position User', 'employee_id' => 3, 'is_active' => 1],
            ['username' => 'position-alias', 'full_name' => 'Position Alias', 'employee_id' => 3, 'is_active' => 1],
            ['username' => 'outside', 'full_name' => 'Outside User', 'employee_id' => 4, 'is_active' => 1],
        ]);
        $this->loginAs()->getJson('/api/courses/1/participants')->assertOk()->assertJsonPath('total_students', 1);
        $this->postJson('/api/courses/1/participants', ['username' => 'position-user'])->assertOk();
        $this->getJson('/api/courses/1/participants')->assertJsonPath('students_count', 1)
            ->assertJsonPath('participants_count', 1)->assertJsonPath('total_students', 2);
        $this->postJson('/api/courses/1/participants', ['username' => 'budi'])->assertOk();
        $this->getJson('/api/courses/1/participants')->assertJsonPath('total_students', 3);
        // Every entry returned in participants contributes to the displayed sum.
        DB::table('course_participants')->insert(['course_id' => 1, 'username' => 'inactive']);
        $this->getJson('/api/v1/courses/1/participants')->assertJsonPath('participants_count', 3)->assertJsonPath('total_students', 4);
        $this->deleteJson('/api/courses/1/participants', ['username' => 'budi'])->assertOk();
        $this->getJson('/api/courses/1/participants')->assertJsonPath('total_students', 3);
        $this->getJson('/api/courses/2/participants')->assertJsonPath('total_students', 3);
        DB::table('course_access_rules')->insert(['course_id' => 1, 'position_code' => 'OTHER']);
        $this->getJson('/api/courses/1/participants')->assertJsonPath('total_students', 5);
        $db->table('auth.users')->where('employee_id', 3)->update(['is_active' => 0]);
        $this->getJson('/api/courses/1/participants')->assertJsonPath('total_students', 4);
    }

    public function test_managers_add_active_employees_idempotently_and_revoke_only_the_target_course(): void
    {
        $this->getJson('/api/courses/1/participants')->assertUnauthorized();
        $this->loginAs('budi', 'OTHER');
        $this->getJson('/api/course-participant-options?q=bu')->assertForbidden();
        $this->getJson('/api/courses/1/participants')->assertForbidden();
        $this->postJson('/api/courses/1/participants', ['username' => 'budi'])->assertForbidden();
        $this->deleteJson('/api/courses/1/participants', ['username' => 'budi'])->assertForbidden();
        $this->loginAs();
        $this->getJson('/api/course-participant-options?q=BU')->assertOk()->assertJsonCount(1, 'employees')->assertJsonPath('employees.0.username', 'budi');
        $this->getJson('/api/course-participant-options?q=b')->assertOk()->assertJsonCount(0, 'employees');
        $this->postJson('/api/courses/999/participants', ['username' => 'budi'])->assertNotFound();
        foreach (['inactive', 'unknown'] as $username) {
            $this->postJson('/api/courses/1/participants', compact('username'))->assertUnprocessable();
        }
        $this->postJson('/api/courses/1/participants', ['username' => 'budi'])->assertOk();
        $this->postJson('/api/courses/1/participants', ['username' => 'budi'])->assertOk();
        $this->assertDatabaseCount('course_participants', 1);
        $this->getJson('/api/v1/courses/1/participants')->assertOk()->assertJsonPath('participants.0.name', 'Budi Karyawan');
        $this->postJson('/api/v1/courses/2/participants', ['username' => 'budi'])->assertOk();
        $this->deleteJson('/api/v1/courses/1/participants', ['username' => 'budi'])->assertOk();
        $this->assertDatabaseHas('course_participants', ['course_id' => 2, 'username' => 'budi']);
        $this->assertDatabaseMissing('course_participants', ['course_id' => 1, 'username' => 'budi']);
    }

    public function test_explicit_access_covers_list_content_and_progress_without_overriding_position_access(): void
    {
        $this->loginAs('budi', 'OTHER');
        $this->getJson('/api/courses')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/preview-course/1')->assertForbidden();
        $this->getJson('/contents/1')->assertForbidden();
        $this->getJson('/api/courses/1/progress')->assertForbidden();
        $this->loginAs()->postJson('/api/courses/1/participants', ['username' => 'budi'])->assertOk();
        $this->loginAs('budi', 'OTHER');
        $this->getJson('/api/courses')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/preview-course/1')->assertOk()->assertJsonPath('course.id', 1);
        $this->getJson('/api/courses/1/progress')->assertOk()->assertJsonPath('total', 1);
        $this->postJson('/api/courses/1/progress/1/complete')->assertOk();
        $this->loginAs()->deleteJson('/api/courses/1/participants', ['username' => 'budi'])->assertOk();
        $this->loginAs('budi', 'OTHER')->getJson('/api/courses/1/progress')->assertForbidden();
        $this->assertDatabaseCount('course_content_progress', 1);
        $this->loginAs('budi', 'ALLOWED')->getJson('/api/courses/1/progress')->assertOk()->assertJsonPath('completed_count', 1);
    }
}
