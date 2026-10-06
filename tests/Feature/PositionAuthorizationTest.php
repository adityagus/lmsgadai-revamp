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
    protected function setUp(): void
    {
        parent::setUp();
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
        foreach (['JBT-032', 'JBT-038'] as $position) {
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
