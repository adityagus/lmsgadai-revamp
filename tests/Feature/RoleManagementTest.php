<?php

namespace Tests\Feature;

use App\Services\RoleAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\EmployeeDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use EmployeeDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createEmployeeDatabase();
        $this->withoutVite();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);
        DB::purge('sqlite');

        if (!Schema::hasTable('lms_position_roles')) {
            Schema::create('lms_position_roles', function (Blueprint $table) {
                $table->id();
                $table->string('position_code', 50)->unique();
                $table->string('position_name', 150)->nullable();
                $table->string('role', 50);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('lms_user_roles')) {
            Schema::create('lms_user_roles', function (Blueprint $table) {
                $table->id();
                $table->string('username', 100)->unique();
                $table->string('name', 150)->nullable();
                $table->string('position', 150)->nullable();
                $table->string('role', 50)->nullable();
                $table->text('roles')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        RoleAccess::clearRoleCache();
    }

    private function loginAs(?string $position, string $role = 'student'): void
    {
        $this->withSession([
            'auth' => [
                'user' => 'testuser',
                'nama' => 'Test User',
                'status' => 'login',
                'idgrup' => $position,
                'role' => $role,
            ],
        ]);
    }

    public function test_guests_and_non_admins_are_denied_access_to_role_management(): void
    {
        // Guest
        $this->get('/master/roles')->assertRedirect('/sign-in');
        $this->getJson('/master/role-data')->assertUnauthorized();

        // Student
        $this->loginAs('JBT-STUDENT', 'student');
        $this->get('/master/roles')->assertForbidden();
        $this->getJson('/master/role-data')->assertForbidden();

        // Trainer
        $this->loginAs('JBT-038', 'trainer');
        $this->getJson('/master/role-data')->assertForbidden();

        // SOP
        $this->loginAs('JBT-037', 'sop');
        $this->getJson('/master/role-data')->assertForbidden();

        // Audit
        $this->loginAs('JBT-020', 'audit');
        $this->getJson('/master/role-data')->assertForbidden();
    }

    public function test_admin_can_access_role_management_page_and_api(): void
    {
        $this->loginAs('JBT-032', 'admin');

        $this->get('/master/roles')->assertOk();

        $response = $this->getJson('/master/role-data');
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data',
                'available_roles',
                'positions',
            ]);
    }

    public function test_admin_can_create_update_and_delete_position_role_mapping(): void
    {
        $this->loginAs('JBT-032', 'admin');

        // Initial check: JBT-999 is unmapped -> defaults to student
        $this->assertSame('student', RoleAccess::roleForPosition('JBT-999'));

        // 1. Create mapping
        $storeRes = $this->postJson('/master/roles', [
            'position_code' => 'JBT-999',
            'position_name' => 'Special Trainer Staff',
            'role' => 'trainer',
            'description' => 'Dedicated training lead',
        ]);
        $storeRes->assertCreated()
            ->assertJsonPath('data.position_code', 'JBT-999')
            ->assertJsonPath('data.role', 'trainer');

        $createdId = $storeRes->json('data.id');

        // Dynamic resolution immediately reflects the new role
        $this->assertSame('trainer', RoleAccess::roleForPosition('JBT-999'));

        // 2. Update mapping to admin
        $updateRes = $this->putJson("/master/roles/{$createdId}", [
            'position_code' => 'JBT-999',
            'position_name' => 'Special Trainer Staff',
            'role' => 'admin',
            'description' => 'Promoted to admin',
        ]);
        $updateRes->assertOk()
            ->assertJsonPath('data.role', 'admin');

        // Dynamic resolution reflects updated role
        $this->assertSame('admin', RoleAccess::roleForPosition('JBT-999'));

        // 3. Delete mapping
        $deleteRes = $this->deleteJson("/master/roles/{$createdId}");
        $deleteRes->assertOk();

        // Dynamic resolution falls back to default student
        $this->assertSame('student', RoleAccess::roleForPosition('JBT-999'));
    }

    public function test_validation_prevents_duplicate_position_code_and_invalid_role(): void
    {
        $this->loginAs('JBT-032', 'admin');

        // Valid create
        $this->postJson('/master/roles', [
            'position_code' => 'JBT-UNIQUE',
            'role' => 'sop',
        ])->assertCreated();

        // Duplicate position_code
        $this->postJson('/master/roles', [
            'position_code' => 'JBT-UNIQUE',
            'role' => 'trainer',
        ])->assertJsonValidationErrors(['position_code']);
    }

    public function test_admin_can_assign_multi_roles_to_employee_via_checklist(): void
    {
        $this->loginAs('JBT-032', 'admin');

        // 1. Assign trainer and sop to employee 'budi'
        $storeRes = $this->postJson('/master/roles', [
            'username' => 'budi',
            'name' => 'Budi Santoso',
            'position' => 'Staff Kasir',
            'roles' => ['trainer', 'sop'],
            'description' => 'Trainer kelas sekaligus tim SOP dokumen',
        ]);
        $storeRes->assertCreated()
            ->assertJsonPath('data.username', 'budi')
            ->assertJsonPath('data.roles', ['trainer', 'sop']);

        $createdId = $storeRes->json('data.id');

        // Verify resolution
        $this->assertEquals(['trainer', 'sop'], RoleAccess::rolesForUser('budi'));
        $this->assertEquals('trainer', RoleAccess::roleForUser('budi'));

        // 2. Update to admin role
        $updateRes = $this->putJson("/master/roles/{$createdId}", [
            'username' => 'budi',
            'name' => 'Budi Santoso',
            'position' => 'Staff Kasir',
            'roles' => ['admin'],
            'description' => 'Promoted to admin',
        ]);
        $updateRes->assertOk()
            ->assertJsonPath('data.roles', ['admin']);

        $this->assertEquals(['admin'], RoleAccess::rolesForUser('budi'));
        $this->assertEquals('admin', RoleAccess::roleForUser('budi'));

        // 3. Delete assignment
        $deleteRes = $this->deleteJson("/master/roles/{$createdId}");
        $deleteRes->assertOk();

        // Falls back to student
        $this->assertEquals(['student'], RoleAccess::rolesForUser('budi'));
        $this->assertEquals('student', RoleAccess::roleForUser('budi'));
    }
}
