<?php
namespace Tests\Feature;

use App\Contracts\FileUploadServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    public function test_course_list_pagination_and_crud_ignore_incompatible_cached_objects(): void
    {
        Schema::create('categories', function ($t) { $t->id(); $t->string('name'); });
        Schema::create('courses', function ($t) {
            $t->id();
            foreach (['name','slug','thumbnail','tagline','description','students','details'] as $field) $t->text($field)->nullable();
            $t->unsignedBigInteger('category_id')->nullable(); $t->boolean('is_popular')->default(false); $t->timestamps();
        });
        Schema::create('course_details', function ($t) { $t->id(); $t->unsignedBigInteger('course_id'); $t->integer('order'); });
        DB::table('categories')->insert(['id' => 1, 'name' => 'Pelatihan']);
        foreach (range(1, 6) as $i) DB::table('courses')->insert(['name' => 'Kelas '.$i, 'category_id' => 1]);
        Cache::put('courses.page.1', unserialize('O:8:"stdClass":0:{}', ['allowed_classes' => false]), 60);
        $this->withSession(['auth' => ['user' => 'manager', 'status' => 'login', 'idgrup' => 'JBT-032']]);
        $this->getJson('/api/courses')->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.total', 6);
        $this->getJson('/api/courses?page=2')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Kelas 1');
        $this->mock(FileUploadServiceInterface::class, fn ($mock) => $mock->shouldReceive('upload')->once()->andReturn('uploads/courses/test.png'));
        $data = ['name' => 'Kelas Baru', 'tagline' => 'Pelatihan', 'description' => 'Deskripsi', 'category_id' => 1, 'is_popular' => false];
        $file = UploadedFile::fake()->createWithContent('thumbnail.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4V8AAAAASUVORK5CYII='));
        $id = $this->postJson('/api/courses', $data + ['thumbnail' => $file])->assertOk()->json('data.id');
        $this->getJson('/api/courses')->assertJsonPath('data.0.id', $id);
        $data['name'] = 'Kelas Diperbarui';
        $this->putJson('/api/courses/'.$id, $data)->assertOk();
        $this->getJson('/api/courses/'.$id)->assertOk()->assertJsonPath('course.name', 'Kelas Diperbarui');
        $this->getJson('/api/courses')->assertJsonPath('data.0.name', 'Kelas Diperbarui');
        $this->deleteJson('/api/courses/'.$id)->assertOk();
        $this->getJson('/api/courses')->assertJsonPath('meta.total', 6);
    }
}
