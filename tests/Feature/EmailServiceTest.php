<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['mail.default' => 'resend']);
        config(['services.resend.key' => 'test-only', 'services.resend.from' => 'onboarding@resend.dev']);
    }

    public function test_missing_key_prevents_delivery(): void
    {
        Http::fake();
        config(['services.resend.key' => null]);
        $this->artisan('email:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('Resend belum siap')->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_resend_attachment_bytes_are_base64_encoded(): void
    {
        Http::fake(['api.resend.com/emails' => Http::response(['id' => 'attachment-id'])]);
        app(\App\Services\EmailService::class)->sendText('recipient@example.com', 'Laporan Excel', 'Terlampir.', [
            ['filename' => 'laporan.xlsx', 'content' => "PK\x03\x04binary-test"],
        ]);
        Http::assertSent(fn ($request) => $request['attachments'][0]['filename'] === 'laporan.xlsx'
            && base64_decode($request['attachments'][0]['content'], true) === "PK\x03\x04binary-test");
    }

    public function test_smtp_uses_configured_mailer_without_resend(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.username' => 'sender@example.com', 'mail.mailers.smtp.password' => 'test-only',
            'mail.from.address' => 'sender@example.com']);
        Http::fake();
        $mailer = \Mockery::mock(\Illuminate\Mail\Mailer::class);
        $sent = \Mockery::mock(\Illuminate\Mail\SentMessage::class);
        $sent->shouldReceive('getMessageId')->once()->andReturn('smtp-test-id');
        \Illuminate\Support\Facades\Mail::shouldReceive('mailer')->once()->with('smtp')->andReturn($mailer);
        $mailer->shouldReceive('raw')->once()->withArgs(function ($body, $callback) {
            $message = new \Illuminate\Mail\Message(new \Symfony\Component\Mime\Email());
            $callback($message);
            return $message->getSymfonyMessage()->getTo()[0]->getAddress() === 'recipient@example.com'
                && $message->getSymfonyMessage()->getSubject() === 'Tes Email - LMS Gadai Mulia';
        })->andReturn($sent);
        $this->artisan('email:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('ID: smtp-test-id')->assertExitCode(0);
        Http::assertNothingSent();
    }

    public function test_log_mailer_cannot_report_real_delivery(): void
    {
        config(['mail.default' => 'log']);
        Http::fake();
        $this->artisan('email:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('Pilih MAIL_MAILER')->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_invalid_recipient_prevents_delivery(): void
    {
        Http::fake();
        $this->artisan('email:test', ['recipient' => 'invalid'])
            ->expectsOutputToContain('Alamat email tujuan tidak valid')->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_command_sends_one_resend_request_and_reports_id(): void
    {
        Http::fake(['api.resend.com/emails' => Http::response(['id' => 'test-email-id'])]);
        $this->artisan('email:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('ID: test-email-id')->assertExitCode(0);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://api.resend.com/emails'
            && $request->hasHeader('Authorization', 'Bearer test-only')
            && $request['from'] === config('services.resend.from')
            && $request['to'] === ['recipient@example.com']
            && $request['subject'] === 'Tes Email - LMS Gadai Mulia'
            && str_contains($request['text'], 'Tidak ada tindakan yang diperlukan.'));
    }

    public function test_provider_errors_do_not_expose_response_body(): void
    {
        Http::fake(['api.resend.com/emails' => Http::response(['message' => 'private-diagnostic'], 403)]);
        $this->artisan('email:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('HTTP 403')->doesntExpectOutputToContain('private-diagnostic')->assertExitCode(1);
        Http::assertSentCount(1);
    }

    public function test_missing_message_id_is_not_reported_as_success(): void
    {
        Http::fake(['api.resend.com/emails' => Http::response([])]);
        $this->artisan('email:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('tidak mengembalikan ID email')->assertExitCode(1);
    }

    public function test_restricted_test_sender_explains_required_domain_without_exposing_response(): void
    {
        Http::fake(['api.resend.com/emails' => Http::response(['message' => 'You can only send testing emails to your own email address private@example.com'], 403)]);
        $this->artisan('email:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('domain terverifikasi')->doesntExpectOutputToContain('private@example.com')->assertExitCode(1);
        Http::assertSentCount(1);
    }

    public function test_connection_failure_is_redacted_and_not_retried(): void
    {
        Http::fake(['api.resend.com/emails' => Http::failedConnection('private-diagnostic')]);
        $this->artisan('email:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('Koneksi Resend gagal')->doesntExpectOutputToContain('private-diagnostic')->assertExitCode(1);
    }
}
