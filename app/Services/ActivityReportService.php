<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class ActivityReportService
{
    public function buildExcel(): string
    {
        $book = app(ActivityLogExcelExporter::class)->build();
        $stream = fopen('php://memory', 'w+b');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($stream);
            rewind($stream);
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
            $book->disconnectWorksheets();
        }
    }

    public function build(): string
    {
        $documents = DB::table('documents')->whereNull('deleted_at');
        $views = DB::table('document_views as v')->join('documents as d', 'd.id', '=', 'v.document_id')
            ->whereNull('d.deleted_at');
        $totalDocuments = (clone $documents)->count();
        $readers = (clone $views)->distinct()->count('v.username');
        $clicks = (clone $views)->sum('v.view_count');
        $readDocuments = (clone $views)->distinct()->count('v.document_id');
        $recent = (clone $views)->select('d.id', 'd.title', 'd.no_surat')
            ->selectRaw('COUNT(DISTINCT v.username) as readers, SUM(v.view_count) as clicks, MAX(v.last_viewed_at) as last_read')
            ->groupBy('d.id', 'd.title', 'd.no_surat')->orderByDesc('last_read')->orderBy('d.id')->limit(20)->get();

        $lines = [
            'LAPORAN AKTIVITAS LOG - LMS GADAI MULIA',
            'Dibuat: '.now('Asia/Jakarta')->format('d-m-Y H:i:s').' WIB',
            'Cakupan: seluruh riwayat pembacaan dokumen aktif (bukan log login atau error aplikasi).',
            'Jumlah pembukaan adalah kumulatif, bukan aktivitas harian.', '',
            'Dokumen aktif: '.$totalDocuments,
            'Dokumen pernah dibaca: '.$readDocuments,
            'Dokumen belum dibaca: '.($totalDocuments - $readDocuments),
            'Pembaca unik lintas dokumen: '.$readers,
            'Total pembukaan: '.$clicks, '',
            'Maksimal 20 dokumen dengan aktivitas terakhir terbaru:',
        ];
        foreach ($recent as $index => $row) {
            $title = preg_replace('/\s+/', ' ', strip_tags($row->title ?? 'Tanpa judul'));
            $number = preg_replace('/\s+/', ' ', $row->no_surat ?? '-');
            $lines[] = ($index + 1).'. '.$title.' ('.$number.')';
            $lines[] = '   Pembaca: '.$row->readers.' | Pembukaan kumulatif: '.$row->clicks.' | Terakhir: '.($row->last_read ?? '-').' (waktu tersimpan di database)';
        }
        if ($recent->isEmpty()) $lines[] = 'Belum ada aktivitas pembacaan dokumen.';
        $lines[] = '';
        $lines[] = 'Laporan dibuat dari snapshot data LMS saat diproses. Dokumen arsip tidak disertakan.';
        return implode("\n", $lines);
    }
}
