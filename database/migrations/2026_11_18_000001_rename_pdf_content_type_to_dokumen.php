<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Course material type "pdf" becomes "dokumen" (PDF, PPT/PPTX, DOCX, Excel uploads).
 * MySQL keeps an ENUM; SQLite (tests) uses a plain string column.
 */
return new class extends Migration
{
    private const TYPES = "'text','video','dokumen','quiz','parent','form','google_form'";

    public function up(): void
    {
        if (!Schema::hasTable('course_details')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE course_details MODIFY COLUMN `type` ENUM('text','video','pdf','dokumen','quiz','parent','form','google_form') NULL");
            DB::table('course_details')->where('type', 'pdf')->update(['type' => 'dokumen']);
            DB::statement('ALTER TABLE course_details MODIFY COLUMN `type` ENUM('.self::TYPES.') NULL');

            return;
        }

        Schema::table('course_details', function (Blueprint $table) {
            $table->string('type', 50)->nullable()->change();
        });
        DB::table('course_details')->where('type', 'pdf')->update(['type' => 'dokumen']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('course_details')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE course_details MODIFY COLUMN `type` ENUM('text','video','pdf','dokumen','quiz','parent','form','google_form') NULL");
            DB::table('course_details')->where('type', 'dokumen')->update(['type' => 'pdf']);
            DB::statement("ALTER TABLE course_details MODIFY COLUMN `type` ENUM('text','video','pdf','quiz','parent') NULL");

            return;
        }

        DB::table('course_details')->where('type', 'dokumen')->update(['type' => 'pdf']);
    }
};
