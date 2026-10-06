<?php
namespace App\Services;

use App\Contracts\Repositories\MasterRepositoryInterface;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ActivityLogExcelExporter
{
    public function __construct(private MasterRepositoryInterface $masterRepository) {}

    public function build($docId = null): Spreadsheet
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(600);



        $query = DB::table('documents as d')
            ->leftJoin('submenu as sm', 'd.submenu_id', '=', 'sm.id')
            ->select('d.id', 'd.no_surat', 'd.title', 'sm.name as submenu_name', 'd.type', 'd.tgl_berlaku', 'd.created_at')
            ->whereNull('d.deleted_at');

        if ($docId) {
            $query->where('d.id', $docId);
        }

        $documents = $query->orderBy('d.created_at', 'desc')->get();

        if ($documents->isEmpty()) {
            throw new \RuntimeException('Data dokumen tidak ditemukan.');
        }

        $rawJabatans = $this->masterRepository->getJabatan();
        $jabatansMap = [];
        foreach ($rawJabatans as $j) {
            $jArr = (array) $j;
            $kd = (string) ($jArr['kd_jabatan'] ?? $jArr['position_code'] ?? '');
            if ($kd !== '') {
                $jabatansMap[$kd] = $jArr['nm_jabatan'] ?? $jArr['position_name'] ?? $kd;
            }
        }

        $users = [];
        try {
            $rawUsers = DB::connection('db2')
                ->table('auth.users as a')
                ->leftJoin('master.employee as b', 'a.employee_id', '=', 'b.employee_id')
                ->leftJoin('master.position as c', 'b.position_id', '=', 'c.position_id')
                ->leftJoin('master.branch as d', 'b.branch_id', '=', 'd.branch_id')
                ->leftJoin('master.company as e', 'd.company_id', '=', 'e.company_id')
                ->select(
                    'a.username',
                    'a.full_name',
                    'b.employee_code as nik',
                    'c.position_code as position_code',
                    'c.position_name as position_name',
                    'd.branch_name as branch_name',
                    'e.company_name as company_name'
                )
                ->where('a.is_active', true)
                ->get();
            foreach ($rawUsers as $u) {
                $users[$u->username] = $u;
            }
        } catch (\Exception $e) {
            // connection fallback for isolated test environments
        }

        $views = DB::table('document_views')->get()->keyBy(function ($item) {
            return $item->document_id . '_' . $item->username;
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($spreadsheet)->disableCalculationCache();

        // Set Default Font
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aktivitas Log');

        // Explicit Column Widths (Avoid scanning cells for auto-size)
        $columnWidths = [
            'A' => 16,
            'B' => 28,
            'C' => 35,
            'D' => 16,
            'E' => 16,
            'F' => 22,
            'G' => 18,
            'H' => 25,
            'I' => 22,
            'J' => 18,
            'K' => 18,
            'L' => 16,
            'M' => 22,
            'N' => 22,
            'O' => 16,
        ];
        foreach ($columnWidths as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $headers = [
            "Document ID\n(Kode unik dokumen\npada sistem Beladiri)",
            "Nomor Dokumen",
            "Perihal/Judul Dokumen",
            "Jenis Dokumen",
            "Tanggal Publish",
            "User ID / Identitas Unik\nUser by sistem beladiri",
            "NIK Karyawan",
            "Nama User",
            "Jabatan",
            "Wilayah/Area (contoh:\njabar, jaya, kepri)",
            "Cabang",
            "Status Baca",
            "First Read",
            "Last Read",
            "Jumlah Dibuka"
        ];

        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getRowDimension(1)->setRowHeight(40);

        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFE699'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $batchSize = 2000;
        $currentBatch = [];
        $currentRow = 2;
        $userCounter = 125;

        foreach ($documents as $doc) {
            $docCode = 'DOC-' . str_pad($doc->id, 3, '0', STR_PAD_LEFT);
            $noSurat = (string)($doc->no_surat ?? '-');
            $title = (string)($doc->title ?? '-');
            $jenis = (string)strtoupper($doc->submenu_name ?? $doc->type ?? 'PENGUMUMAN');
            $tglPublish = (string)($doc->tgl_berlaku ? date('d-M-y', strtotime($doc->tgl_berlaku)) : date('d-M-y', strtotime($doc->created_at)));

            if (!empty($users)) {
                foreach ($users as $username => $u) {
                    $key = $doc->id . '_' . $username;
                    $view = $views->get($key);
                    $hasViewed = $view !== null && (int)$view->view_count > 0;

                    $userIdCode = 'USR-' . str_pad($userCounter++, 6, '0', STR_PAD_LEFT);
                    $nik = (string)($u->nik ?? '-');
                    $namaUser = (string)($u->full_name ?? $username);
                    $jabatan = (string)($jabatansMap[$u->position_code] ?? $u->position_name ?? $u->position_code ?? '-');
                    $wilayah = (string)($u->company_name ?? '-');
                    $cabang = (string)($u->branch_name ?? '-');

                    $currentBatch[] = [
                        $docCode,
                        $noSurat,
                        $title,
                        $jenis,
                        $tglPublish,
                        $userIdCode,
                        $nik,
                        $namaUser,
                        $jabatan,
                        $wilayah,
                        $cabang,
                        $hasViewed ? 'Sudah Dibaca' : 'Belum Dibaca',
                        $hasViewed && $view->first_viewed_at ? date('j M Y, H.i', strtotime($view->first_viewed_at)) : '0',
                        $hasViewed && $view->last_viewed_at ? date('j M Y, H.i', strtotime($view->last_viewed_at)) : '0',
                        $hasViewed ? (int)$view->view_count : 0,
                    ];

                    if (count($currentBatch) >= $batchSize) {
                        $sheet->fromArray($currentBatch, null, 'A' . $currentRow);
                        $currentRow += count($currentBatch);
                        $currentBatch = [];
                    }
                }
            } else {
                $docViews = DB::table('document_views')->where('document_id', $doc->id)->get();
                if ($docViews->isNotEmpty()) {
                    foreach ($docViews as $dv) {
                        $userIdCode = 'USR-' . str_pad($userCounter++, 6, '0', STR_PAD_LEFT);
                        $currentBatch[] = [
                            $docCode,
                            $noSurat,
                            $title,
                            $jenis,
                            $tglPublish,
                            $userIdCode,
                            '-',
                            (string)$dv->username,
                            '-',
                            '-',
                            '-',
                            'Sudah Dibaca',
                            $dv->first_viewed_at ? date('j M Y, H.i', strtotime($dv->first_viewed_at)) : '0',
                            $dv->last_viewed_at ? date('j M Y, H.i', strtotime($dv->last_viewed_at)) : '0',
                            (int)$dv->view_count,
                        ];

                        if (count($currentBatch) >= $batchSize) {
                            $sheet->fromArray($currentBatch, null, 'A' . $currentRow);
                            $currentRow += count($currentBatch);
                            $currentBatch = [];
                        }
                    }
                }
            }
        }

        if (!empty($currentBatch)) {
            $sheet->fromArray($currentBatch, null, 'A' . $currentRow);
            $currentRow += count($currentBatch);
            $currentBatch = [];
        }

        $lastRow = $currentRow - 1;

        if ($lastRow >= 2) {
            $sheet->getStyle("A2:O{$lastRow}")->applyFromArray([
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            foreach (['A', 'E', 'F', 'G', 'L', 'M', 'N', 'O'] as $c) {
                $sheet->getStyle("{$c}2:{$c}{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
            foreach (['B', 'C', 'D', 'H', 'I', 'J', 'K'] as $c) {
                $sheet->getStyle("{$c}2:{$c}{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            }
        }

        return $spreadsheet;
    }
}
