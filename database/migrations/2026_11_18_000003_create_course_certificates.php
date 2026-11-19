<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificate templates (per course, or course_id NULL = default) and issued certificates.
 * Positions are percentages of the template page (0-100), measured from the top-left.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_certificate_templates')) {
            Schema::create('course_certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->nullable()->index();
                $table->string('name', 150)->nullable();
                $table->string('file_path', 500);
                $table->string('file_type', 20);
                $table->decimal('name_x', 6, 2)->default(50);
                $table->decimal('name_y', 6, 2)->default(50);
                $table->unsignedInteger('font_size')->default(36);
                $table->string('font_color', 9)->default('#1F2937');
                $table->boolean('show_number')->default(true);
                $table->decimal('number_x', 6, 2)->default(50);
                $table->decimal('number_y', 6, 2)->default(80);
                $table->boolean('show_date')->default(true);
                $table->decimal('date_x', 6, 2)->default(50);
                $table->decimal('date_y', 6, 2)->default(86);
                $table->string('created_by', 150)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('course_certificates')) {
            Schema::create('course_certificates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id');
                $table->string('username', 150);
                $table->string('recipient_name', 255);
                $table->string('certificate_number', 100)->unique();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->unsignedInteger('download_count')->default(0);
                $table->timestamp('last_downloaded_at')->nullable();
                $table->timestamps();
                $table->unique(['course_id', 'username']);
                $table->index('username');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_certificates');
        Schema::dropIfExists('course_certificate_templates');
    }
};
