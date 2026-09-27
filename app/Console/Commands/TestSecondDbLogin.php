<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSecondDbLogin extends Command
{
    protected $signature = 'test:second-db-login {email}';
    protected $description = 'Test login (user exists) on second MySQL database connection';

    public function handle()
    {
        try {
            $user = DB::connection('sam_pos_view')->table('users')->first();
            if ($user) {
                $this->info('Berhasil konek dan dapat data dari database kedua. Contoh data:');
                $this->line(json_encode($user));
            } else {
                $this->error('Koneksi berhasil, tapi tabel users kosong di database kedua.');
            }
        } catch (\Exception $e) {
            $this->error('Gagal konek atau query ke database kedua: ' . $e->getMessage());
        }
    }
}
