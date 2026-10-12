<?php

namespace Tests\Feature;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Services\Quiz\QuizService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PositionAuthorizationTest extends TestCase
{
    use \Tests\EmployeeDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->createEmployeeDatabase();
        $this->withoutVite();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        Schema::create('lms_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('role');
            $table->timestamps();
        });

        Route::middleware(['web', 'jabatan:JBT-032'])->get('/_test/position', fn () => ['allowed' => true]);
        Route::middleware(['web', 'jabatan:unknown'])->get('/_test/unknown', fn () => ['allowed' => true]);
        Route::middleware(['web', 'jabatan:manager'])->get('/_test/quiz-role', fn () => [
            'role' => app(QuizService::class)->identity(request())->role,
        ]);
    }

    private function loginAs(?string $position, ?string $assignedRole = null): void
    {
        if ($assignedRole !== null) {
            DB::table('lms_users')->updateOrInsert(['username' => 'fixture'], ['name' => 'Fixture', 'role' => $assignedRole]);
        }
        $this->withSession(['auth' => ['user' => 'fixture', 'nama' => 'Fixture', 'status' => 'login', 'idgrup' => $position]]);
    }

    public function test_guests_and_incomplete_sessions_cannot_access_protected_routes(): void
    {
        $this->get('/_test/position')->assertRedirect('/sign-in');
        $this->getJson('/_test/position')->assertUnauthorized();
        $this->withSession(['auth' => ['status' => 'login', 'idgrup' => 'JBT-032']]);
        $this->getJson('/_test/position')->assertUnauthorized();
    }

    public function test_existing_admin_positions_allow_pages_and_both_api_prefixes(): void
    {
        foreach (['JBT-032'] as $position) {
            $this->loginAs($position, 'student');
            $this->getJson('/session')->assertOk()->assertJsonPath('auth.role', 'admin');
            $this->get('/information-document/create')->assertOk();
            $this->get('/kelas')->assertOk();
            // Validation is reached for managers, before any course write.
            $this->postJson('/api/courses', [])->assertUnprocessable();
            $this->postJson('/api/v1/courses', [])->assertUnprocessable();
        }
    }

    public function test_explicit_position_checks_do_not_grant_an_admin_bypass(): void
    {
        $this->loginAs('JBT-032');
        $this->getJson('/_test/position')->assertOk();
        $this->getJson('/_test/unknown')->assertForbidden();
        $this->loginAs('JBT-038');
        $this->getJson('/_test/position')->assertForbidden();
    }

    public function test_mapped_positions_receive_access_and_unmapped_positions_default_to_student(): void
    {
        $this->loginAs('JBT-OTHER');
        $this->get('/kelas')->assertForbidden();
        $this->get('/student/kelas')->assertOk();
        $this->postJson('/api/courses', [])->assertForbidden();
        $this->postJson('/api/v1/courses', [])->assertForbidden();
        $this->loginAs('JBT-019');
        $this->get('/kelas')->assertOk();
        $this->get('/information-document/create')->assertForbidden();
        $this->loginAs('JBT-037');
        $this->get('/information-document/create')->assertOk();
    }

    public function test_position_mapping_is_shared_with_quiz_without_using_assigned_roles(): void
    {
        config(['access.position_roles.JBT-TEST' => 'trainer']);
        $this->loginAs('JBT-TEST', 'student');
        $this->getJson('/session')->assertJsonPath('auth.role', 'trainer');
        $this->getJson('/_test/quiz-role')->assertOk()->assertJsonPath('role', 'trainer');
        $this->assertDatabaseHas('lms_users', ['username' => 'fixture', 'role' => 'student']);

        config(['access.position_roles.JBT-TEST' => 'student']);
        $this->get('/kelas')->assertForbidden();
        $this->getJson('/_test/quiz-role')->assertForbidden();
    }

    public function test_sop_can_load_document_reports_and_audit_data_but_students_cannot(): void
    {
        $this->partialMock(\App\Http\Controllers\api\DocumentReportController::class, function ($mock) {
            $mock->shouldReceive('getReport')->once()->andReturn(response()->json(['success' => true, 'data' => []]));
        });
        $this->mock(\App\Contracts\Services\DocumentViewServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getDocumentViewStats')->twice()->andReturn(collect());
            $mock->shouldReceive('getUsersWhoHaveViewed')->with(1)->twice()->andReturn(collect());
            $mock->shouldReceive('getUsersWhoHaveNotViewed')->with(1)->twice()->andReturn(collect());
        });
        $endpoints = ['/api/v1/report/documents'];
        foreach (['', '/api/v1'] as $prefix) {
            foreach (['stats', 'document/1/viewed', 'document/1/unviewed'] as $path) {
                $endpoints[] = $prefix.'/document-views/'.$path;
            }
        }
        foreach ($endpoints as $endpoint) $this->getJson($endpoint)->assertUnauthorized();
        $this->loginAs('JBT-037');
        $this->get('/report/documents')->assertOk();
        $this->get('/master/audit-logs')->assertOk();
        foreach ($endpoints as $endpoint) $this->getJson($endpoint)->assertOk()->assertJsonPath('success', true);
        $this->loginAs('JBT-OTHER');
        foreach ($endpoints as $endpoint) $this->getJson($endpoint)->assertForbidden();
        $this->postJson('/api/v1/report/documents/export-excel', [])->assertForbidden();
        $this->postJson('/api/v1/report/activity-logs/export-excel', [])->assertForbidden();
    }

    public function test_trainers_cannot_create_edit_delete_or_access_document_archives(): void
    {
        Schema::create('documents', fn (Blueprint $table) => $table->id());
        DB::table('documents')->insert(['id' => 1]);
        $this->partialMock(\App\Http\Controllers\api\AnnouncementController::class, function ($mock) {
            foreach (['index', 'detail', 'documentById', 'lastDocumentPreview'] as $method) {
                $mock->shouldReceive($method)->times(8)->andReturn(response()->json(['success' => true]));
            }
        });
        $this->partialMock(\App\Http\Controllers\InformationController::class, function ($mock) {
            $mock->shouldReceive('menu2')->times(8)->andReturn(response()->json([]));
        });
        $this->mock(\App\Contracts\Services\DocumentViewServiceInterface::class, function ($mock) {
            $mock->shouldReceive('recordView')->with('fixture', 1)->times(8);
        });
        foreach (['JBT-019', 'JBT-038', '042', 'JBT-TRAINER'] as $position) {
            $this->loginAs($position);
            $this->getJson('/session')->assertJsonPath('auth.role', 'trainer');
            $this->get('/kelas')->assertOk();
            $this->get('/quiz/manage')->assertOk();
            foreach (['/pengumuman', '/formulir', '/detail-pengumuman/1'] as $path) {
                $this->get($path)->assertOk();
            }
            foreach (['information-document/create?type=1', 'information-document/create?type=2', 'information-document/update/1', 'archive-pengumuman', 'archive-pengumuman?submenu_id=2'] as $page) {
                $this->get('/'.$page)->assertForbidden();
            }
            foreach (['', '/api/v1'] as $prefix) {
                foreach (['/announcements?category=1', '/announcement/1', '/announcements/document/1', '/announcements/document/preview/1', '/menu2'] as $path) {
                    $this->getJson($prefix.$path)->assertOk();
                }
                $this->postJson($prefix.'/document-views', ['document_id' => 1])->assertCreated();
                $this->getJson($prefix.'/announcements/trash')->assertForbidden();
                $this->postJson($prefix.'/announcements', [])->assertForbidden();
                $this->postJson($prefix.'/announcements/1', [])->assertForbidden();
                $this->patchJson($prefix.'/announcements/1/restore')->assertForbidden();
                $this->deleteJson($prefix.'/announcements/1')->assertForbidden();
            }
            $this->deleteJson('/announcements/hardDelete/1')->assertForbidden();
        }
        foreach (['JBT-032', 'JBT-037'] as $position) {
            $this->loginAs($position);
            $this->get('/information-document/create')->assertOk();
            $this->get('/archive-pengumuman')->assertOk();
        }
    }

    public function test_audit_staff_can_only_read_reports_and_activity(): void
    {
        $this->partialMock(\App\Http\Controllers\api\DocumentReportController::class, function ($mock) {
            $mock->shouldReceive('getReport')->once()->andReturn(response()->json(['success' => true, 'data' => []]));
            $mock->shouldReceive('getActivityLogs')->once()->andReturn(response()->json(['success' => true, 'data' => []]));
        });
        $this->mock(\App\Contracts\Services\DocumentViewServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getDocumentViewStats')->twice()->andReturn(collect());
            $mock->shouldReceive('getUsersWhoHaveViewed')->with(1)->twice()->andReturn(collect());
            $mock->shouldReceive('getUsersWhoHaveNotViewed')->with(1)->twice()->andReturn(collect());
        });
        $this->loginAs('JBT-020', 'admin');
        $this->getJson('/session')->assertJsonPath('auth.role', 'audit');
        $this->get('/overview')->assertRedirect('/report/documents');
        $this->get('/report')->assertRedirect('/report/documents');
        foreach (['/report/documents', '/master/audit-logs', '/api/v1/report/documents', '/api/v1/report/activity-logs'] as $path) {
            $this->get($path)->assertOk();
        }
        foreach (['', '/api/v1'] as $prefix) {
            foreach (['stats', 'document/1/viewed', 'document/1/unviewed'] as $path) {
                $this->getJson($prefix.'/document-views/'.$path)->assertOk()->assertJsonPath('success', true);
            }
            $this->getJson($prefix.'/announcements/trash')->assertForbidden();
            $this->postJson($prefix.'/announcements', [])->assertForbidden();
            $this->postJson($prefix.'/announcements/1', [])->assertForbidden();
            $this->patchJson($prefix.'/announcements/1/restore')->assertForbidden();
            $this->deleteJson($prefix.'/announcements/1')->assertForbidden();
            $this->postJson($prefix.'/document-views', ['document_id' => 1])->assertForbidden();
        }
        foreach (['/kelas', '/student/kelas', '/quiz/manage', '/batch-pelatihan', '/riwayat-belajar', '/pengumuman', '/formulir', '/information-document/create', '/information-document/update/1', '/archive-pengumuman', '/detail-course/1', '/api/preview-course/1', '/api/v1/preview-course/1', '/api/courses', '/menu2'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->postJson('/api/v1/report/documents/export-excel', [])->assertForbidden();
        $this->postJson('/api/v1/report/activity-logs/export-excel', [])->assertForbidden();
        $this->post('/logout')->assertRedirect('/sign-in')->assertSessionMissing('auth');
    }

    public function test_audit_login_redirects_to_reports_after_password_verification(): void
    {
        $this->mock(UserRepositoryInterface::class, fn ($mock) => $mock->shouldReceive('cekLogin')->twice()->andReturn(collect([
            (object) ['username' => 'fixture', 'full_name' => 'Audit Staff', 'password_hash' => password_hash('correct password', PASSWORD_BCRYPT, ['cost' => 4]), 'position_code' => 'JBT-020'],
        ])));
        $this->postJson('/login', ['user' => 'fixture', 'pass' => 'wrong'])->assertJsonPath('success', false)->assertSessionMissing('auth');
        $previousSession = session()->getId();
        $this->postJson('/login', ['user' => 'fixture', 'pass' => 'correct password'])
            ->assertJsonPath('redirect', '/report/documents')->assertSessionHas('auth.idgrup', 'JBT-020');
        $this->assertNotSame($previousSession, session()->getId());
    }

    public function test_client_supplied_role_or_position_cannot_elevate_access(): void
    {
        $this->withSession(['auth' => ['user' => 'fixture', 'status' => 'login', 'idgrup' => 'JBT-OTHER', 'role' => 'admin']]);
        $this->get('/kelas?idgrup=JBT-032&role=admin')->assertForbidden();
        $this->postJson('/api/courses', ['idgrup' => 'JBT-032', 'role' => 'admin'])->assertForbidden();
    }

    public function test_login_verifies_password_rotates_session_and_uses_employee_position(): void
    {
        $hash = password_hash('correct password', PASSWORD_BCRYPT, ['cost' => 4]);
        $hash = '$2a$'.substr($hash, 4);
        $this->mock(UserRepositoryInterface::class, fn ($mock) => $mock->shouldReceive('cekLogin')->twice()->andReturn(collect([
            (object) ['username' => 'fixture', 'full_name' => 'Fixture', 'password_hash' => $hash, 'position_code' => 'JBT-032'],
        ])));
        $this->postJson('/login', ['user' => 'fixture', 'pass' => 'wrong'])->assertJsonPath('success', false)->assertSessionMissing('auth');
        $previousSession = session()->getId();
        $this->postJson('/login', ['user' => 'fixture', 'pass' => 'correct password', 'idgrup' => 'JBT-OTHER'])
            ->assertJsonPath('success', true)->assertSessionHas('auth.idgrup', 'JBT-032');
        $this->assertNotSame($previousSession, session()->getId());
        $this->get('/information-document/create')->assertOk();
        $this->post('/logout')->assertRedirect('/sign-in')->assertSessionMissing('auth');
    }
}
