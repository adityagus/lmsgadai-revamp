<?php
namespace App\Console\Commands;

use App\Services\ActivityReportService;
use App\Services\EmailService;
use Illuminate\Console\Command;
use RuntimeException;

class EmailActivityReport extends Command
{
    protected $signature = 'email:activity-report {recipient} {--preview : Tampilkan laporan tanpa pengiriman}';
    protected $description = 'Kirim langsung laporan aktivitas pembacaan dokumen';

    public function handle(ActivityReportService $reports, EmailService $email): int
    {
        $recipient = $this->argument('recipient');
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tujuan tidak valid.');
            return self::FAILURE;
        }
        if ($this->option('preview')) {
            $this->line($reports->build());
            return self::SUCCESS;
        }
        $attachment = [
            'filename' => 'Laporan-Aktivitas-Log-'.now('Asia/Jakarta')->format('Y-m-d-His').'.xlsx',
            'content' => $reports->buildExcel(),
        ];
        try {
            $id = $email->sendText($recipient, 'Laporan Aktivitas Log - LMS Gadai Mulia',
                'Terlampir laporan aktivitas log LMS Gadai Mulia dalam format Excel.', [$attachment]);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
        $this->info('Laporan diterima layanan pengiriman untuk '.$recipient.'. ID email: '.$id);
        $this->line('Periksa Inbox/Spam; penerimaan server belum menjamin email masuk inbox.');
        return self::SUCCESS;
    }
}
