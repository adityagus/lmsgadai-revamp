<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPosition;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\AksesCabang;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MigrasiDataController extends Controller
{
    public function export()
    {
        $data = [
            'documents' => Announcement::all()->toArray(),
            'document_regions' => AksesCabang::all()->toArray(),
            'akses_jabatan' => DocumentPosition::all()->toArray(),
        ];

        return Excel::download(new class($data) implements WithMultipleSheets {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function sheets(): array {
                return [
                    new class($this->data['documents']) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                        private $arr; public function __construct($arr) { $this->arr = $arr; }
                        public function array(): array { return $this->arr; }
                        public function title(): string { return 'documents'; }
                    },
                    new class($this->data['document_regions']) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                        private $arr; public function __construct($arr) { $this->arr = $arr; }
                        public function array(): array { return $this->arr; }
                        public function title(): string { return 'document_regions'; }
                    },
                    new class($this->data['akses_jabatan']) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                        private $arr; public function __construct($arr) { $this->arr = $arr; }
                        public function array(): array { return $this->arr; }
                        public function title(): string { return 'akses_jabatan'; }
                    },
                ];
            }
        }, 'migrasi_data.xlsx', ExcelFormat::XLSX);
    }

    // IMPORT dari Excel
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:51200']);
        $data = Excel::toArray([], $request->file('file'));
        $idMap = [];

        // dd(array_slice($data[0], 1));
        // Sheet 0: documents
        foreach(array_slice($data[0], 1) as $row) {
            if (empty($row[0])) continue;
            $tgl_berlaku = null;
            if (!empty($row[4])) {
              if (is_numeric($row[4])) {
                $tgl_berlaku = Date::excelToDateTimeObject($row[4])->format('Y-m-d');
              } else {
                $tgl_berlaku = Carbon::parse($row[4])->format('Y-m-d');
              }
          }
            $announcemment = Announcement::updateOrCreate(['id' => $row[0]], [
                'submenu_id' => $row[1] == 'Memo' ? 1 : null,
                'title' => $row[2] ?? null,
                'no_surat' => $row[3] ?? null,
                'tgl_berlaku' => $tgl_berlaku ?? null,
                'url' => isset($row[2], $row[5]) ? $row[2] . '/' . $row[5] : null,
                'menu_order' => $row[6] ?? null,
                'created_by' => $row[7] ?? null,
                'created_at' => now(),
                'updated_by' => $row[9] ?? null,
                'updated_at' => $row[10] ?? null,
                'deleted_by' => $row[11] ?? null,
                'deleted_at' => $row[12] ?? null,
            ]);
            $idMap[$row[0]] = $announcemment->id;
        }

      foreach (array_slice($data[1], 1) as $row) {
        $newDocId = $idMap[$row[0]] ?? null;
        if ($newDocId) {
          if ($row[1] == 999) {
            foreach (DB::connection('db2')->table('master.branch')->where('is_active', true)->get() as $cabang) {
              AksesCabang::updateOrCreate([
                'id_document' => $newDocId,
                'id_cabang' => $cabang->branch_code,
                'akses' => 1,
              ]);
            }
          } else {
            AksesCabang::updateOrCreate([
              'id_document' => $newDocId,
              'id_cabang' => $row[1],
              'akses' => 1,
            ]);
          }
        }
      }

      foreach (array_slice($data[2], 1) as $row) {
        if (empty($row[0]))
          continue;
        if ($row[2] == 999) {
            foreach (DB::connection('db2')->table('master.position')->where('is_active', true)->get() as $jabatan) {
            DocumentPosition::updateOrCreate([
              'document_id' => $row[1] ?? null,
              'kd_jbt' => $jabatan->position_code,
              'user' => $row[3] ?? null,
              'akses' => 1,
            ]);
          }
        } else {
          DocumentPosition::updateOrCreate([
            'document_id' => $row[1] ?? null,
            'kd_jbt' => $row[2] ?? null,
            'user' => $row[3] ?? null,
            'akses' => 1,
          ]);
        }
      }
        
        
        // // Sheet 1: document_regions
        // foreach(array_slice($data[1], 1) as $row) {
        //     $newDocId = $idMap[$row[0]] ?? null;
        //     if($newDocId){
        //         DocumentRegion::updateOrCreate([
        //             'document_id' => $newDocId,
        //             'regional_id' => $row[1],
        //         ]);
        //     }
        // }
        // // Sheet 2: akses_jabatan
        // // dd(array_slice($data[2], 1));
        // foreach(array_slice($data[2], 1) as $row) {
        //     if (empty($row[0])) continue;
        //     dd($row);
            
        //     AksesJabatan::updateOrCreate(['id' => $row[0]], [
        //         'document_id' => $row[1] ?? null,
        //         'kd_jbt' => $row[2] ?? null,
        //         'user' => $row[3] ?? null,
        //         'akses' => 1,
        //     ]);
        // }
        return back()->with('success', 'Import selesai!');
    }
}
