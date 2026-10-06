<?php
namespace Tests\Feature;

use App\Contracts\Repositories\MasterRepositoryInterface;
use App\Http\Controllers\api\DocumentReportController;
use App\Services\ActivityLogExcelExporter;
use App\Services\ActivityReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ActivityLogExcelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('submenu', function ($t) { $t->id(); $t->string('name'); });
        Schema::create('documents', function ($t) {
            $t->id(); $t->string('title'); $t->string('no_surat'); $t->integer('submenu_id')->nullable();
            $t->string('type')->nullable(); $t->date('tgl_berlaku')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('document_views', function ($t) {
            $t->id(); $t->integer('document_id'); $t->string('username'); $t->integer('view_count');
            $t->timestamp('first_viewed_at')->nullable(); $t->timestamp('last_viewed_at')->nullable();
        });
        DB::table('documents')->insert([
            ['id' => 1, 'title' => 'Dokumen aktif', 'no_surat' => 'DOC-01', 'created_at' => '2026-09-01 00:00:00', 'deleted_at' => null],
            ['id' => 2, 'title' => 'Dokumen arsip', 'no_surat' => 'DOC-02', 'created_at' => '2026-09-01 00:00:00', 'deleted_at' => '2026-09-02 00:00:00'],
        ]);
        DB::table('document_views')->insert(['document_id' => 1, 'username' => 'reader', 'view_count' => 3,
            'first_viewed_at' => '2026-09-01 12:00:00', 'last_viewed_at' => '2026-09-02 12:00:00']);
        config(['database.connections.db2' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('db2');
        $db = DB::connection('db2');
        $db->statement("ATTACH DATABASE ':memory:' AS auth");
        $db->statement("ATTACH DATABASE ':memory:' AS master");
        $db->statement('CREATE TABLE auth.users (username TEXT, full_name TEXT, employee_id INTEGER, is_active INTEGER)');
        $db->statement('CREATE TABLE master.employee (employee_id INTEGER, employee_code TEXT, position_id INTEGER, branch_id INTEGER)');
        $db->statement('CREATE TABLE master.position (position_id INTEGER, position_code TEXT, position_name TEXT)');
        $db->statement('CREATE TABLE master.branch (branch_id INTEGER, branch_name TEXT, company_id INTEGER)');
        $db->statement('CREATE TABLE master.company (company_id INTEGER, company_name TEXT)');
        $db->table('auth.users')->insert([
            ['username' => 'reader', 'full_name' => 'Pembaca Uji', 'employee_id' => 1, 'is_active' => 1],
            ['username' => 'unread', 'full_name' => 'Belum Membaca', 'employee_id' => 2, 'is_active' => 1],
            ['username' => 'inactive', 'full_name' => 'Nonaktif', 'employee_id' => 3, 'is_active' => 0],
        ]);
        $db->table('master.employee')->insert(['employee_id' => 1, 'employee_code' => '12345', 'position_id' => 1, 'branch_id' => 1]);
        $db->table('master.position')->insert(['position_id' => 1, 'position_code' => 'POS1', 'position_name' => 'Jabatan Uji']);
        $db->table('master.branch')->insert(['branch_id' => 1, 'branch_name' => 'Cabang Uji', 'company_id' => 1]);
        $db->table('master.company')->insert(['company_id' => 1, 'company_name' => 'Wilayah Uji']);
        $this->mock(MasterRepositoryInterface::class)->shouldReceive('getJabatan')->andReturn(collect());
    }

    public function test_attachment_matches_button_export_cells_and_format(): void
    {
        $response = app(DocumentReportController::class)->exportActivityLogsExcel(Request::create('/export', 'POST'));
        $buttonFile = $response->getFile()->getPathname();
        $attachmentFile = tempnam(sys_get_temp_dir(), 'audit-mail-');
        try {
            file_put_contents($attachmentFile, app(ActivityReportService::class)->buildExcel());
            $button = IOFactory::load($buttonFile);
            $attachment = IOFactory::load($attachmentFile);
            $sheet = $attachment->getActiveSheet();
            $this->assertSame(1, $attachment->getSheetCount());
            $this->assertSame('Aktivitas Log', $sheet->getTitle());
            $this->assertSame($button->getActiveSheet()->toArray(), $sheet->toArray());
            $this->assertSame(3, $sheet->getHighestRow());
            $this->assertSame('O', $sheet->getHighestColumn());
            $this->assertSame('FFE699', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
            $this->assertSame('Calibri', $attachment->getDefaultStyle()->getFont()->getName());
            $this->assertSame('Pembaca Uji', $sheet->getCell('H2')->getValue());
            $this->assertSame('Sudah Dibaca', $sheet->getCell('L2')->getValue());
            $this->assertSame(3, $sheet->getCell('O2')->getValue());
            $this->assertSame('Belum Dibaca', $sheet->getCell('L3')->getValue());
            $button->disconnectWorksheets();
            $attachment->disconnectWorksheets();
        } finally {
            unlink($buttonFile);
            unlink($attachmentFile);
        }
    }

    public function test_document_filter_keeps_no_data_response(): void
    {
        $response = app(DocumentReportController::class)->exportActivityLogsExcel(Request::create('/export', 'POST', ['document_id' => 2]));
        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }
}
