<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PopularCoursesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('is_popular')->nullable();
            $table->text('students')->nullable();
            $table->text('details')->nullable();
        });
    }

    public function test_public_catalog_contains_all_and_only_popular_summaries(): void
    {
        config(['services.mix.img_url' => '/storage/aktif/']);
        foreach (range(1, 9) as $id) {
            DB::table('courses')->insert([
                'id' => $id, 'name' => 'Kelas '.$id, 'description' => 'Ringkasan '.$id,
                'thumbnail' => $id === 1 ? '/uploads/course.jpg' : null,
                'is_popular' => true, 'students' => 'private participants', 'details' => 'private details',
            ]);
        }
        DB::table('courses')->insert([
            ['name' => 'Nonpopuler', 'is_popular' => false],
            ['name' => 'Belum ditandai', 'is_popular' => null],
        ]);
        $response = $this->getJson('/api/popular-courses')->assertOk()->assertJsonCount(9, 'data');
        $this->assertSame(range(9, 1), array_column($response->json('data'), 'id'));
        $this->assertSame(['id', 'name', 'description', 'thumbnail_url'], array_keys($response->json('data.0')));
        $response->assertJsonPath('data.8.thumbnail_url', '/storage/aktif/uploads/course.jpg')
            ->assertJsonPath('data.0.thumbnail_url', null);
        $this->getJson('/api/courses')->assertUnauthorized();
        $this->withSession(['auth' => ['user' => 'audit-fixture', 'status' => 'login', 'idgrup' => 'JBT-020']])
            ->getJson('/api/popular-courses')->assertOk()->assertJsonCount(9, 'data');
        $this->getJson('/api/courses')->assertForbidden();
    }

    public function test_empty_popular_catalog_is_successful(): void
    {
        $this->getJson('/api/popular-courses')->assertOk()->assertExactJson(['data' => []]);
    }
}
