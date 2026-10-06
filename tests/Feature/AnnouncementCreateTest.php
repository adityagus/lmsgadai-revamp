<?php

namespace Tests\Feature;

use App\Contracts\FileUploadServiceInterface;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Services\AnnouncementService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AnnouncementCreateTest extends TestCase
{
    private function payload(string $type = 'text'): array
    {
        return ['submenu_id' => 1, 'title' => 'Pengumuman uji', 'type' => $type,
            'content' => '<p>Informasi terbaru</p>', 'regionals_id' => ['0001'], 'kd_jabatan' => ['01']];
    }

    public function test_text_needs_content_and_pdf_needs_a_valid_file(): void
    {
        $rules = (new StoreAnnouncementRequest)->rules();
        $this->assertTrue(Validator::make($this->payload(), $rules)->passes());
        $this->assertTrue(Validator::make([...$this->payload(), 'content' => ''], $rules)->errors()->has('content'));
        $this->assertTrue(Validator::make($this->payload('pdf'), $rules)->errors()->has('dokumen'));
        $this->assertTrue(Validator::make([...$this->payload('pdf'), 'dokumen' => UploadedFile::fake()->create('test.pdf', 10, 'application/pdf')], $rules)->passes());
        $this->assertTrue(Validator::make([...$this->payload('pdf'), 'dokumen' => UploadedFile::fake()->create('test.txt', 10, 'text/plain')], $rules)->fails());
        $this->assertTrue(Validator::make([...$this->payload(), 'regionals_id' => [], 'kd_jabatan' => []], $rules)->fails());
    }

    public function test_text_is_saved_without_upload_and_keeps_its_audience(): void
    {
        Schema::create('submenu', function ($table) { $table->id(); $table->string('name'); });
        DB::table('submenu')->insert(['id' => 1, 'name' => 'Pengumuman']);
        Schema::create('documents', function ($table) {
            $table->id(); $table->integer('submenu_id'); $table->string('title');
            $table->string('type'); $table->text('content')->nullable(); $table->string('url')->nullable();
            $table->string('created_by')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('document_region', function ($table) { $table->integer('document_id'); $table->string('regional_id'); });
        Schema::create('document_position', function ($table) {
            $table->integer('document_id'); $table->string('kd_jbt'); $table->string('created_by');
        });
        $this->mock(FileUploadServiceInterface::class)->shouldNotReceive('upload');
        $document = app(AnnouncementService::class)->createAnnouncement($this->payload(), null);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'type' => 'text', 'url' => null]);
        $this->assertDatabaseHas('document_region', ['document_id' => $document->id, 'regional_id' => '0001']);
        $this->assertDatabaseHas('document_position', ['document_id' => $document->id, 'kd_jbt' => '01']);
    }
}
