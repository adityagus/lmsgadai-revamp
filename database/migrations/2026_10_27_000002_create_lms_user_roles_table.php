<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLmsUserRolesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('lms_user_roles')) {
            Schema::create('lms_user_roles', function (Blueprint $table) {
                $table->id();
                $table->string('username', 100)->unique();
                $table->string('name', 150)->nullable();
                $table->string('position', 150)->nullable();
                $table->string('role', 50)->nullable();
                $table->json('roles')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('lms_user_roles', 'roles')) {
            Schema::table('lms_user_roles', function (Blueprint $table) {
                $table->json('roles')->nullable()->after('role');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('lms_user_roles');
    }
}
