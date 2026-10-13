<?php

namespace Tests\Feature;

use App\Services\MinioFileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MinioStorageTest extends TestCase
{
    public function test_minio_disks_share_legacy_prefixes_and_private_visibility(): void
    {
        $config = require base_path('config/filesystems.php');
        $this->assertSame('s3', $config['default']);
        $this->assertArrayNotHasKey('local', $config['disks']);
        $this->assertArrayNotHasKey('local', config('filesystems.disks'));
        $this->assertSame([], $config['links']);
        $this->assertInstanceOf(MinioFileUploadService::class, app(\App\Contracts\FileUploadServiceInterface::class));
        $this->assertSame('public', $config['disks']['public']['root']);
        $this->assertSame('public/aktif', $config['disks']['aktif']['root']);
        foreach (['s3', 'public', 'aktif'] as $disk) {
            $this->assertSame('s3', $config['disks'][$disk]['driver']);
            $this->assertSame('private', $config['disks'][$disk]['visibility']);
            $this->assertTrue($config['disks'][$disk]['throw']);
        }
        // Instantiate the real adapter without making a network request.
        $adapter = Storage::build([...$config['disks']['aktif'],
            'key' => 'test', 'secret' => 'test', 'bucket' => 'lms-test',
            'endpoint' => 'http://localhost:9000', 'use_path_style_endpoint' => true]);
        $this->assertInstanceOf(\Illuminate\Filesystem\AwsS3V3Adapter::class, $adapter);
        $this->assertStringContainsString('/lms-test/public/aktif/test.pdf', $adapter->url('test.pdf'));
    }

    public function test_upload_archive_restore_and_delete_keep_the_same_object_paths(): void
    {
        $root = Storage::fake('s3');
        config(['filesystems.default' => 's3']);
        Storage::set('aktif', Storage::build(['driver' => 'local', 'root' => $root->path('public/aktif')]));
        $service = new MinioFileUploadService;
        $path = $service->upload(UploadedFile::fake()->createWithContent('test.pdf', 'test PDF'), 'SOP', 'test.pdf');
        $this->assertSame('SOP/test.pdf', $path);
        $root->assertExists('public/aktif/SOP/test.pdf');
        $this->assertTrue($service->move('public/aktif/'.$path, 'public/tidakaktif/'.$path));
        $root->assertMissing('public/aktif/'.$path);
        $root->assertExists('public/tidakaktif/'.$path);
        $this->assertTrue($service->move('public/tidakaktif/'.$path, 'public/aktif/'.$path));
        $this->assertTrue($service->delete('public/aktif/'.$path));
        $root->assertMissing('public/aktif/'.$path);
    }

    public function test_existing_storage_url_streams_private_files_for_signed_in_users(): void
    {
        Storage::fake('public')->put('aktif/SOP/test.pdf', 'test PDF');
        $this->withSession(['auth' => ['status' => 'login', 'user' => 'test', 'idgrup' => 'JBT-032']])
            ->get('/storage/aktif/SOP/test.pdf')->assertOk()->assertStreamedContent('test PDF')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_bucket_root_layout_supports_upload_archive_restore_and_delete(): void
    {
        $root = Storage::fake('s3');
        config(['filesystems.default' => 's3', 'filesystems.minio_public_prefix' => '']);
        Storage::set('aktif', Storage::build(['driver' => 'local', 'root' => $root->path('aktif')]));
        $service = new MinioFileUploadService;
        $path = $service->upload(UploadedFile::fake()->createWithContent('test.pdf', 'test PDF'), 'SOP', 'test.pdf');
        $root->assertExists('aktif/SOP/test.pdf');
        $this->assertTrue($service->move('public/aktif/'.$path, 'public/tidakaktif/'.$path));
        $root->assertMissing('aktif/'.$path);
        $root->assertExists('tidakaktif/'.$path);
        $this->assertTrue($service->move('public/tidakaktif/'.$path, 'public/aktif/'.$path));
        $this->assertTrue($service->delete('public/aktif/'.$path));
        $root->assertMissing('aktif/'.$path);
    }

    public function test_guests_cannot_read_private_bucket_files(): void
    {
        Storage::fake('public')->put('aktif/test.pdf', 'private PDF');
        $this->get('/storage/aktif/test.pdf')->assertRedirect('/sign-in');
    }

    public function test_missing_files_return_404(): void
    {
        Storage::fake('public');
        $this->withSession(['auth' => ['status' => 'login', 'user' => 'test', 'idgrup' => 'JBT-032']])
            ->get('/storage/aktif/missing.pdf')->assertNotFound();
    }

    public function test_public_course_preview_can_still_load_thumbnail_images(): void
    {
        Storage::fake('public')->put('aktif/uploads/courses/thumbnail.png', 'image');
        $this->get('/storage/aktif/uploads/courses/thumbnail.png')->assertOk()->assertStreamedContent('image');
        $this->get('/storage/aktif/uploads/courses/private.pdf')->assertRedirect('/sign-in');
    }
}
