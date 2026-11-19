<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('course_details')) {
            Schema::table('course_details', function (Blueprint $table) {
                if (!Schema::hasColumn('course_details', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('course_id')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('course_details')) {
            Schema::table('course_details', function (Blueprint $table) {
                if (Schema::hasColumn('course_details', 'parent_id')) {
                    $table->dropColumn('parent_id');
                }
            });
        }
    }
};

