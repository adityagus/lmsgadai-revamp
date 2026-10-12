<?php

namespace Tests\Feature;

use App\Services\AnnouncementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnnouncementListTest extends TestCase
{
    public function test_counts_preserve_audience_and_archives_without_loading_documents(): void
    {
        Schema::create('submenu', function ($table) {
            $table->id(); $table->integer('id_menu'); $table->string('name'); $table->string('icon')->nullable();
        });
        Schema::create('documents', function ($table) {
            $table->id(); $table->integer('submenu_id'); $table->text('content'); $table->softDeletes();
            foreach (['title', 'no_surat', 'url', 'tgl_berlaku', 'created_by', 'updated_by', 'type'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('document_position', function ($table) {
            $table->integer('document_id'); $table->string('kd_jbt');
        });
        Schema::create('document_region', function ($table) {
            $table->integer('document_id'); $table->string('regional_id');
        });
        DB::table('submenu')->insert([
            ['id' => 1, 'id_menu' => 3, 'name' => 'Documents'],
            ['id' => 2, 'id_menu' => 3, 'name' => 'Empty'],
            ['id' => 3, 'id_menu' => 2, 'name' => 'Other category'],
        ]);
        foreach ([1, 2, 3, 4] as $id) {
            DB::table('documents')->insert(['id' => $id, 'submenu_id' => 1,
                'content' => str_repeat('large content ', 1000), 'deleted_at' => $id === 4 ? now() : null]);
            DB::table('document_position')->insert(['document_id' => $id, 'kd_jbt' => $id === 2 ? 'OTHER' : 'JBT']);
            DB::table('document_region')->insert(['document_id' => $id, 'regional_id' => $id === 3 ? 'OTHER' : '0001']);
        }
        $service = app(AnnouncementService::class);
        DB::enableQueryLog();
        $menus = $service->getAnnouncementsByCategory(3, 'JBT', '0001');
        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame([1, 0], $menus->pluck('count_tipe_announcement')->all());
        $this->assertFalse($menus->first()->relationLoaded('announcements'));
        foreach ([null, ''] as $branch) {
            $this->assertSame([3, 0], $service->getAnnouncementsByCategory(3, 'JBT', $branch)
                ->pluck('count_tipe_announcement')->all());
        }
        for ($id = 5; $id <= 45; $id++) {
            DB::table('documents')->insert(['id' => $id, 'submenu_id' => 1, 'content' => 'text']);
        }
        $first = $service->getAnnouncementDetails(1, null, null, 1)['items'];
        $second = $service->getAnnouncementDetails(1, null, null, 2)['items'];
        $last = $service->getAnnouncementDetails(1, null, null, 3)['items'];
        $this->assertCount(20, $first);
        $this->assertCount(20, $second);
        $this->assertCount(4, $last);
        $this->assertCount(44, $first->concat($second)->concat($last)->pluck('id')->unique());
        $this->assertSame([1], $service->getAnnouncementDetails(1, 'JBT', '0001', 1)['items']->pluck('id')->all());
        $restricted = $service->getAnnouncementDetails(1, 'JBT', '0001', 1)['items']->first();
        $this->assertSame([['document_id' => 1, 'regional_id' => '0001']], $restricted->document_regional->toArray());
        $this->assertSame([], $first->first()->document_regional->toArray());
        DB::flushQueryLog();
        $light = $service->getAnnouncementDetails(1, null, null, 1, false)['items'];
        $this->assertFalse($light->first()->relationLoaded('document_regional'));
        $this->assertCount(3, DB::getQueryLog());
    }
}
