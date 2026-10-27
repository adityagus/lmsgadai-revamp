<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateLmsPositionRolesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('lms_position_roles')) {
            Schema::create('lms_position_roles', function (Blueprint $table) {
                $table->id();
                $table->string('position_code', 50)->unique();
                $table->string('position_name', 150)->nullable();
                $table->string('role', 50);
                $table->text('description')->nullable();
                $table->timestamps();
            });

            $defaults = config('access.position_roles', []);
            $rows = [];
            foreach ($defaults as $code => $role) {
                $rows[] = [
                    'position_code' => $code,
                    'position_name' => null,
                    'role' => $role,
                    'description' => 'Default system mapping',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($rows)) {
                DB::table('lms_position_roles')->insertOrIgnore($rows);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('lms_position_roles');
    }
}
