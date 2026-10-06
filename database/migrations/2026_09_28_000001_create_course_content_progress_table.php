<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_content_progress', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100);
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('content_id');
            $table->timestamp('completed_at');
            $table->unique(['username', 'course_id', 'content_id'], 'course_progress_user_content_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_content_progress');
    }
};
