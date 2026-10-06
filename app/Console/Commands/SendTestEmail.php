<?php

namespace App\Console\Commands;

use App\Services\EmailService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class SendTestEmail extends Command
{
    protected $signature = 'email:test {recipient : Alamat email penerima}';
    protected $description = 'Kirim satu email uji LMS melalui mailer yang dikonfigurasi';

    public function handle(EmailService $email): int
    {
        try {
            $id = $email->sendText(
                $this->argument('recipient'),
                'Tes Email - LMS Gadai Mulia',
                "Halo,\n\nIni adalah email pengujian service email LMS Gadai Mulia (Beladiri).\n"
                ."Email ini dikirim atas permintaan pengujian layanan email. Tidak ada tindakan yang diperlukan.\n\n"
                ."Salam,\nLMS Gadai Mulia",
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Email diterima layanan pengiriman untuk '.$this->argument('recipient').'. ID: '.$id.'. Periksa Inbox/Spam; penerimaan server belum menjamin email masuk inbox.');
        return self::SUCCESS;
    }
}
