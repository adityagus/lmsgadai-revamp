<?php
namespace Tests\Feature;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentLifecycleTest extends TestCase
{
    public function test_archive_and_restore_move_the_document_file_with_its_record(): void
    {
        config(['filesystems.default' => 'local']);
        $disk = Storage::fake('local');
        Schema::create('documents', function ($table) {
            $table->id(); $table->string('title'); $table->string('url');
            $table->string('created_by')->nullable(); $table->string('updated_by')->nullable(); $table->string('deleted_by')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        $record = Announcement::create(['title' => 'Test document', 'url' => 'documents/example.pdf']);
        $disk->put('public/aktif/documents/example.pdf', 'test-content');
        $service = app(AnnouncementService::class);
        $service->deleteAnnouncement($record->id);
        $this->assertTrue($record->fresh()->trashed());
        $disk->assertMissing('public/aktif/documents/example.pdf');
        $disk->assertExists('public/tidakaktif/documents/example.pdf');
        $service->restoreAnnouncement($record->id);
        $this->assertFalse($record->fresh()->trashed());
        $disk->assertExists('public/aktif/documents/example.pdf');
        $disk->assertMissing('public/tidakaktif/documents/example.pdf');
    }
}
