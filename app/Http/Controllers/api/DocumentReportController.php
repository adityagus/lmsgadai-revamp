<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Contracts\Repositories\MasterRepositoryInterface;

class DocumentReportController extends Controller
{
    protected $masterRepository;

    public function __construct(MasterRepositoryInterface $masterRepository)
    {
        $this->masterRepository = $masterRepository;
    }

    public function getReport(Request $request)
    {
        // 1. Fetch Master Jabatan
        $rawJabatans = $this->masterRepository->getJabatan();
        $jabatansMap = [];
        foreach ($rawJabatans as $j) {
            $jArr = (array) $j;
            $kd = (string) ($jArr['kd_jabatan'] ?? $jArr['position_code'] ?? '');
            if ($kd !== '') {
                $jabatansMap[$kd] = $jArr['nm_jabatan'] ?? $jArr['position_name'] ?? $kd;
            }
        }

        // 2. Fetch Master Wilayah & Cabang
        $wilayahList = $this->masterRepository->getWilayah();
        $wilayahMap = [];
        $branchToWilayahName = [];

        foreach ($wilayahList as $w) {
            $wArr = (array) $w;
            $kdW = (string) ($wArr['kd_wilayah'] ?? '');
            $nmW = $wArr['nm_wilayah'] ?? '';
            if ($kdW !== '') {
                $wilayahMap[$kdW] = $nmW;
            }
            if (isset($wArr['branches']) && is_array($wArr['branches'])) {
                foreach ($wArr['branches'] as $bCode) {
                    $branchToWilayahName[(string) $bCode] = $nmW;
                }
            }
        }

        // 3. Group Regional Document mappings
        $regionsGrouped = DB::table('document_region')
            ->get()
            ->groupBy('document_id');

        // 4. Fetch Documents
        $rows = DB::table('documents as d')
            ->leftJoin('submenu as sm', 'd.submenu_id', '=', 'sm.id')
            ->leftJoin('document_position as dp', 'd.id', '=', 'dp.document_id')
            ->select(
                'd.id', 'd.title', 'd.no_surat', 'd.tgl_berlaku', 'd.updated_at', 'd.created_at', 'd.deleted_at',
                'sm.name as ketentuan_dokumen', 'd.type',
                'dp.kd_jbt'
            )
            ->orderBy('d.created_at', 'desc')
            ->orderBy('dp.id', 'asc')
            ->get();

        // 5. Map Regional PT names
        $ptsCache = [];
        foreach ($regionsGrouped as $docId => $regs) {
            $mappedPts = [];
            foreach ($regs as $reg) {
                $regId = trim((string) $reg->regional_id);
                if (empty($regId)) continue;

                $lower = strtolower($regId);
                if ($lower === 'all') {
                    $mappedPts[] = 'All';
                    continue;
                }

                $name = $wilayahMap[$regId] ?? null;

                if (!$name && isset($branchToWilayahName[$regId])) {
                    $name = $branchToWilayahName[$regId];
                }

                if (!$name && strlen($regId) >= 2) {
                    $secondChar = $regId[1];
                    if (isset($wilayahMap[$secondChar])) {
                        $name = $wilayahMap[$secondChar];
                    }
                }

                if (!$name) {
                    $name = $regId;
                }

                $nameLower = strtolower($name);
                if ($nameLower === 'all') {
                    $mappedPts[] = 'All';
                } elseif ($nameLower === 'jakarta' || strpos($nameLower, 'jaya') !== false) {
                    $mappedPts[] = 'Jaya';
                } elseif ($nameLower === 'jawa barat' || strpos($nameLower, 'jabar') !== false) {
                    $mappedPts[] = 'Jabar';
                } elseif ($nameLower === 'kepri' || strpos($nameLower, 'kepri') !== false) {
                    $mappedPts[] = 'Kepri';
                } else {
                    $mappedPts[] = ucwords($nameLower);
                }
            }
            $ptsCache[$docId] = implode(', ', array_filter(array_unique($mappedPts)));
        }

        // 6. Format Response
        $formattedDocs = [];
        foreach ($rows as $row) {
            $docId = $row->id;
            $pts = $ptsCache[$docId] ?? '';
            if (empty($pts)) {
                $pts = 'All';
            }

            $kdJbt = (string) $row->kd_jbt;
            $position = '-';
            if ($kdJbt !== '') {
                $position = $jabatansMap[$kdJbt] ?? $kdJbt;
            }

            $formattedDocs[] = [
                'position' => $position,
                'pt' => $pts,
                'tanggal_upload' => $row->updated_at ?? $row->created_at,
                'ketentuan_dokumen' => $row->ketentuan_dokumen ?? $row->type ?? '-',
                'tanggal_berlaku' => $row->tgl_berlaku,
                'nomor_surat' => $row->no_surat,
                'perihal_judul' => $row->title,
                'status' => is_null($row->deleted_at) ? 'Active' : 'Inactive'
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $formattedDocs
        ]);
    }

    public function exportExcel(Request $request)
    {
        $data = $request->input('data', []);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Headers
        $headers = ["Jabatan", "PT.", "Tanggal Upload", "Ketentuan Dokumen", "Tanggal Berlaku", "Nomor Surat", "Perihal/Judul", "Status"];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // Set Data
        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['position'] ?? '-');
            $sheet->setCellValue('B' . $row, $item['pt'] ?? '-');

            $tglUpload = (isset($item['tanggal_upload']) && $item['tanggal_upload']) ? date('d M Y', strtotime($item['tanggal_upload'])) : '-';
            $sheet->setCellValue('C' . $row, $tglUpload);

            $sheet->setCellValue('D' . $row, $item['ketentuan_dokumen'] ?? '-');

            $tglBerlaku = (isset($item['tanggal_berlaku']) && $item['tanggal_berlaku']) ? date('d M Y', strtotime($item['tanggal_berlaku'])) : '-';
            $sheet->setCellValue('E' . $row, $tglBerlaku);

            $sheet->setCellValue('F' . $row, $item['nomor_surat'] ?? '-');
            $sheet->setCellValue('G' . $row, $item['perihal_judul'] ?? '-');
            $sheet->setCellValue('H' . $row, $item['status'] ?? '-');
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $fileName = 'laporan_dokumen_' . time() . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
