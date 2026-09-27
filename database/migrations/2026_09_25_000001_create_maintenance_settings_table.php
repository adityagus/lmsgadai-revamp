<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMaintenanceSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('maintenance_settings', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->timestamps();
        });

        DB::table('maintenance_settings')->insert([
            'id' => 1,
            'estimated_hours' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_settings');
    }
}
