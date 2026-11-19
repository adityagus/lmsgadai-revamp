<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document download tracking for the audit activity log (separate from document_views / "Sudah Dibaca").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_downloads')) {
            Schema::create('document_downloads', function (Blueprint $table) {
                $table->id();
                $table->string('username', 255);
                $table->unsignedBigInteger('document_id');
                $table->timestamp('first_downloaded_at')->nullable();
                $table->timestamp('last_downloaded_at')->nullable();
                $table->unsignedInteger('download_count')->default(0);
                $table->timestamps();
                $table->unique(['username', 'document_id']);
                $table->index('document_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_downloads');
    }
};
