<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('categories', function ($t) { $t->id(); $t->string('name'); });
        Schema::create('courses', function ($t) { $t->id(); $t->string('name'); $t->integer('category_id')->nullable(); });
        Schema::create('course_details', function ($t) {
            $t->id(); $t->integer('course_id'); $t->string('title'); $t->string('type'); $t->integer('quiz_id')->nullable(); $t->integer('order');
        });
        DB::table('courses')->insert([['id' => 1, 'name' => 'Kelas'], ['id' => 2, 'name' => 'Lainnya']]);
        foreach (range(1, 7) as $id) {
            DB::table('course_details')->insert(['id' => $id, 'course_id' => 1, 'title' => 'Materi '.$id, 'type' => $id === 6 ? 'quiz' : 'text', 'quiz_id' => $id === 6 ? 90 : null, 'order' => $id]);
        }
        DB::table('course_details')->insert(['id' => 8, 'course_id' => 2, 'title' => 'Lainnya', 'type' => 'pdf', 'order' => 1]);
    }

    private function manager(): static
    {
        return $this->withSession(['auth' => ['user' => 'manager', 'status' => 'login', 'idgrup' => 'JBT-032']]);
    }

    public function test_manager_reorders_all_pages_including_quiz_and_student_preview_follows_saved_order(): void
    {
        $this->manager()->getJson('/api/courses/1/content-order')->assertOk()->assertJsonCount(7, 'contents');
        $order = [6, 7, 1, 2, 3, 4, 5];
        $this->patchJson('/api/courses/1/content-order', ['content_ids' => $order, 'original_ids' => range(1, 7)])->assertOk();
        $this->assertSame($order, DB::table('course_details')->where('course_id', 1)->orderBy('order')->pluck('id')->all());
        $this->assertDatabaseHas('course_details', ['id' => 6, 'quiz_id' => 90, 'order' => 1]);
        $this->assertDatabaseHas('course_details', ['id' => 8, 'course_id' => 2, 'order' => 1]);
        $this->withSession(['auth' => ['user' => 'student', 'status' => 'login', 'idgrup' => 'STAFF']]);
        $this->getJson('/api/preview-course/1')->assertOk()->assertJsonPath('contents.0.id', 6)->assertJsonPath('contents.6.id', 5);
        $this->manager()->getJson('/api/courses/1?page=2')->assertOk()->assertJsonPath('contents.data.0.id', 4);
    }

    public function test_ordering_rejects_guests_students_foreign_duplicate_missing_and_stale_lists(): void
    {
        $payload = ['content_ids' => range(1, 7), 'original_ids' => range(1, 7)];
        $this->getJson('/api/courses/1/content-order')->assertUnauthorized();
        $this->patchJson('/api/courses/1/content-order', $payload)->assertUnauthorized();
        $this->withSession(['auth' => ['user' => 'student', 'status' => 'login', 'idgrup' => 'STAFF']]);
        $this->getJson('/api/courses/1/content-order')->assertForbidden();
        $this->patchJson('/api/courses/1/content-order', $payload)->assertForbidden();
        $this->manager();
        foreach ([[1, 2], [1, 2, 3, 4, 5, 6, 8], [1, 2, 3, 4, 5, 6, 6]] as $invalid) {
            $this->patchJson('/api/courses/1/content-order', array_replace($payload, ['content_ids' => $invalid]))->assertUnprocessable();
        }
        $this->patchJson('/api/v1/courses/1/content-order', array_replace($payload, ['original_ids' => []]))->assertStatus(409);
        $this->assertSame(range(1, 7), DB::table('course_details')->where('course_id', 1)->orderBy('order')->pluck('id')->all());
        $reversed = array_reverse(range(1, 7));
        $this->patchJson('/api/v1/courses/1/content-order', array_replace($payload, ['content_ids' => $reversed]))->assertOk();
        $this->patchJson('/api/courses/1/content-order', $payload)->assertStatus(409);
        $this->assertSame($reversed, DB::table('course_details')->where('course_id', 1)->orderBy('order')->pluck('id')->all());
        $this->patchJson('/api/courses/999/content-order', $payload)->assertNotFound();
        $this->getJson('/api/courses/999/content-order')->assertNotFound();
    }
}
