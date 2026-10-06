<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Illuminate\Http\Client\ConnectionException;
use InvalidArgumentException;
use RuntimeException;

class EmailService
{

    
    public function sendText(string $recipient, string $subject, string $body, array $attachments = []): string
    {
        
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Alamat email tujuan tidak valid.');
        }
        if (trim($subject) === '' || preg_match('/[\r\n]/', $subject) || trim($body) === '') {
            throw new InvalidArgumentException('Subjek dan isi email wajib diisi; subjek harus satu baris.');
        }

        if (config('mail.default') === 'smtp') {
            return $this->sendSmtp($recipient, $subject, $body, $attachments);
        }
        if (config('mail.default') !== 'resend') {
            throw new RuntimeException('Pilih MAIL_MAILER=smtp atau resend untuk pengiriman email nyata.');
        }

        $key = config('services.resend.key');
        $from = config('services.resend.from');
        if (empty($key) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Resend belum siap. Isi RESEND_API_KEY dan RESEND_FROM_ADDRESS di .env.');
        }

        try {
            $response = Http::withToken($key)->acceptJson()->connectTimeout(10)->timeout(20)
                ->withoutRedirecting()->post('https://api.resend.com/emails', [
                    'from' => $from, 'to' => [$recipient], 'subject' => $subject, 'text' => $body,
                    'attachments' => array_map(fn ($attachment) => [
                        'filename' => $attachment['filename'], 'content' => base64_encode($attachment['content']),
                    ], $attachments),
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Koneksi Resend gagal atau timeout. Periksa log pengiriman di Resend sebelum mencoba ulang.');
        }
        if (!$response->successful()) {
            $message = (string) $response->json('message', '');
            if ($response->status() === 403 && str_contains($message, 'own email address')) {
                throw new RuntimeException('Resend membatasi pengirim onboarding@resend.dev ke email pemilik akun. Tujuan ini tidak diizinkan; gunakan RESEND_FROM_ADDRESS dari domain terverifikasi untuk mengirim ke alamat tersebut.');
            }
            if ($response->status() === 403 && str_contains($message, 'not verified')) {
                throw new RuntimeException('Domain pengirim belum terverifikasi di Resend. Verifikasi domain RESEND_FROM_ADDRESS terlebih dahulu.');
            }
            throw new RuntimeException('Resend menolak pengiriman (HTTP '.$response->status().'). Periksa API key, verifikasi domain/penerima uji, dan kuota di dashboard Resend.');
        }
        $id = $response->json('id');
        if (!is_string($id) || trim($id) === '') {
            throw new RuntimeException('Resend tidak mengembalikan ID email. Periksa dashboard sebelum mencoba ulang.');
        }
        return $id;
    }

    private function sendSmtp(string $recipient, string $subject, string $body, array $attachments = []): string
    {
        $smtp = config('mail.mailers.smtp');
        if (empty($smtp['host']) || empty($smtp['username']) || empty($smtp['password'])
            || !filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('SMTP belum siap. Periksa MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, dan MAIL_FROM_ADDRESS.');
        }
        try {
            $sent = Mail::mailer('smtp')->raw($body, function (Message $message) use ($recipient, $subject, $attachments) {
                $message->to($recipient)->subject($subject);
                foreach ($attachments as $attachment) {
                    $message->attachData($attachment['content'], $attachment['filename']);
                }
            });
        } catch (TransportExceptionInterface $exception) {
            throw new RuntimeException('SMTP gagal atau belum mengonfirmasi pengiriman. Periksa koneksi dan App Password Gmail; cek folder Terkirim sebelum mencoba ulang.');
        }
        if ($sent === null) {
            throw new RuntimeException('Pengiriman dibatalkan sebelum diterima server SMTP.');
        }
        return $sent->getMessageId();
    }
}
