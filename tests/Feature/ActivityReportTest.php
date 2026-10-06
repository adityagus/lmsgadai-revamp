<?php
namespace Tests\Feature;


use App\Services\ActivityReportService;
use App\Services\EmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

use Tests\TestCase;

class ActivityReportTest extends TestCase
{
    public function test_report_counts_unique_readers_and_excludes_archives(): void
    {
        Schema::create('documents', function ($table) {
            $table->id(); $table->string('title'); $table->string('no_surat')->nullable(); $table->softDeletes();
        });
        Schema::create('document_views', function ($table) {
            $table->id(); $table->integer('document_id'); $table->string('username');
            $table->integer('view_count'); $table->timestamp('last_viewed_at')->nullable();
        });
        DB::table('documents')->insert([
            ['id' => 1, 'title' => 'Dokumen A', 'deleted_at' => null],
            ['id' => 2, 'title' => 'Dokumen B', 'deleted_at' => null],
            ['id' => 3, 'title' => 'Dokumen Arsip', 'deleted_at' => now()],
            ['id' => 4, 'title' => 'Belum dibaca', 'deleted_at' => null],
        ]);
        DB::table('document_views')->insert([
            ['document_id' => 1, 'username' => 'reader', 'view_count' => 3, 'last_viewed_at' => now()],
            ['document_id' => 2, 'username' => 'reader', 'view_count' => 2, 'last_viewed_at' => now()],
            ['document_id' => 3, 'username' => 'archived', 'view_count' => 50, 'last_viewed_at' => now()],
        ]);
        $body = app(ActivityReportService::class)->build();
        $this->assertStringContainsString('Dokumen aktif: 3', $body);
        $this->assertStringContainsString('Dokumen belum dibaca: 1', $body);
        $this->assertStringContainsString('Pembaca unik lintas dokumen: 1', $body);
        $this->assertStringContainsString('Total pembukaan: 5', $body);
        $this->assertStringNotContainsString('Dokumen Arsip', $body);
    }

    public function test_command_sends_report_directly_without_queue(): void
    {
        Queue::fake();
        $reports = $this->mock(ActivityReportService::class);
        $reports->shouldReceive('buildExcel')->once()->andReturn('excel-test-bytes');
        $email = $this->mock(EmailService::class);
        $email->shouldReceive('sendText')->once()->withArgs(fn ($recipient, $subject, $body, $attachments) => $recipient === 'recipient@example.com' && $attachments[0]['content'] === 'excel-test-bytes' && str_ends_with($attachments[0]['filename'], '.xlsx') && !str_contains($body, 'Total pembukaan'))->andReturn('provider-test-id');
        $this->artisan('email:activity-report', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('ID email: provider-test-id')->assertExitCode(0);
        Queue::assertNothingPushed();
    }

    public function test_preview_never_sends(): void
    {
        $this->mock(ActivityReportService::class)->shouldReceive('build')->once()->andReturn('Laporan uji');
        $this->mock(EmailService::class)->shouldNotReceive('sendText');
        $this->artisan('email:activity-report', ['recipient' => 'recipient@example.com', '--preview' => true])
            ->expectsOutput('Laporan uji')->assertExitCode(0);
    }

    public function test_invalid_recipient_never_sends(): void
    {
        $this->mock(EmailService::class)->shouldNotReceive('sendText');
        $this->artisan('email:activity-report', ['recipient' => 'invalid'])->assertExitCode(1);
    }

    public function test_provider_failure_returns_failure(): void
    {
        $this->mock(ActivityReportService::class)->shouldReceive('buildExcel')->once()->andReturn('excel-test-bytes');
        $this->mock(EmailService::class)->shouldReceive('sendText')->once()->andThrow(new \RuntimeException('Provider menolak'));
        $this->artisan('email:activity-report', ['recipient' => 'recipient@example.com'])
            ->expectsOutput('Provider menolak')->assertExitCode(1);
    }
}
