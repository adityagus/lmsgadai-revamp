<?php

namespace App\Export;

use App\Models\Document;
use App\Models\AksesCabang;
use App\Models\DocumentPosition;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DocumentExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'documents' => new DocumentsSheetExport(),
            'document_regions' => new DocumentRegionsSheetExport(),
            'akses_jabatan' => new AksesJabatanSheetExport(),
        ];
    }
}

// Sheet Exports
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DocumentsSheetExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Document::all();
    }
    public function headings(): array
    {
        return [
            'id',
            'submenu_id',
            'title',
            'no_surat',
            'url',
            'tgl_berlaku',
            'menu_order',
            'created_by',
            'created_at',
            'updated_by',
            'updated_at',
            'deleted_by',
            'deleted_at'
        ];
    }
}

class DocumentRegionsSheetExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return AksesCabang::all();
    }
    public function headings(): array
    {
        return ['id', 'id_document', 'id_cabang', 'user', 'akses'];
    }
}

class AksesJabatanSheetExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return DocumentPosition::all();
    }
    public function headings(): array
    {
        return ['id', 'document_id', 'kd_jbt', 'user', 'akses'];
    }
}
