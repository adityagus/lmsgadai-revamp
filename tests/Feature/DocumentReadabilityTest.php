<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentReadabilityTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cachePath = storage_path('framework/testing/readability-'.bin2hex(random_bytes(6)));
        config(['cache.default' => 'file', 'cache.stores.file.path' => $this->cachePath,
            'cache.serializable_classes' => false,
            'database.connections.db2' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('db2');
        $db = DB::connection('db2');
        $db->statement("ATTACH DATABASE ':memory:' AS auth");
        $db->statement('CREATE TABLE auth.users (username TEXT, full_name TEXT, is_active INTEGER)');
        $db->table('auth.users')->insert([
            ['username' => 'reader', 'full_name' => 'Pembaca', 'is_active' => 1],
            ['username' => 'unread', 'full_name' => 'Belum Membaca', 'is_active' => 1],
            ['username' => 'inactive', 'full_name' => 'Nonaktif', 'is_active' => 0],
        ]);
        Schema::create('document_views', function ($table) {
            $table->id(); $table->integer('document_id'); $table->string('username');
            $table->integer('view_count'); $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
        });
        DB::table('document_views')->insert([
            ['document_id' => 7, 'username' => 'reader', 'view_count' => 3,
                'first_viewed_at' => '2026-09-01 10:00:00', 'last_viewed_at' => '2026-09-02 11:00:00'],
            ['document_id' => 7, 'username' => 'removed', 'view_count' => 1,
                'first_viewed_at' => null, 'last_viewed_at' => null],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->cachePath);
        parent::tearDown();
    }

    public function test_detail_endpoints_work_on_cold_and_warm_file_cache(): void
    {
        // Stale object caches from the old code must not be read by the fix.
        Cache::put('db2_active_users', collect([(object) ['username' => 'stale']]), 1800);
        Cache::put('db2_user_fullname_map', collect(['reader' => 'Stale name']), 1800);
        DB::connection('db2')->enableQueryLog();
        $this->withSession(['auth' => ['status' => 'login', 'user' => 'admin', 'idgrup' => 'JBT-032']]);
        for ($request = 0; $request < 2; $request++) {
            $this->getJson('/api/v1/document-views/document/7/unviewed')
                ->assertOk()->assertJsonPath('success', true)->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.username', 'unread')->assertJsonPath('data.0.full_name', 'Belum Membaca');
            $response = $this->getJson('/api/v1/document-views/document/7/viewed')
                ->assertOk()->assertJsonPath('success', true)->assertJsonCount(2, 'data');
            $readers = collect($response->json('data'))->keyBy('username');
            $this->assertSame('Pembaca', $readers['reader']['full_name']);
            $this->assertSame(3, $readers['reader']['view_count']);
            $this->assertSame('2026-09-02 11:00:00', $readers['reader']['last_viewed_at']);
            $this->assertSame('removed', $readers['removed']['full_name']);
        }
        $this->assertCount(2, DB::connection('db2')->getQueryLog());
    }

    public function test_readability_details_still_require_a_manager_session(): void
    {
        $this->getJson('/api/v1/document-views/document/7/viewed')->assertUnauthorized();
        $this->withSession(['auth' => ['status' => 'login', 'user' => 'student', 'idgrup' => 'OTHER']])
            ->getJson('/api/v1/document-views/document/7/unviewed')->assertForbidden();
    }
}
