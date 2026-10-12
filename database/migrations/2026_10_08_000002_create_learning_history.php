<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('learning_quiz_versions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('quiz_id')->index(); $t->unsignedInteger('version');
            $t->string('fingerprint', 64); $t->longText('snapshot'); $t->timestamp('created_at');
            $t->unique(['quiz_id', 'version']); $t->unique(['quiz_id', 'fingerprint']);
        });
        Schema::create('learning_attempt_history', function (Blueprint $t) {
            $t->unsignedBigInteger('attempt_id')->primary(); $t->unsignedBigInteger('quiz_version_id')->index();
            $t->unsignedBigInteger('batch_id')->nullable()->index(); $t->string('batch_name')->nullable();
            $t->string('batch_owner', 150)->nullable(); $t->string('username', 150)->index();
            $t->unsignedBigInteger('course_id')->nullable(); $t->longText('course_snapshot')->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('learning_content_versions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('content_id')->index(); $t->unsignedBigInteger('course_id')->index();
            $t->string('fingerprint', 64); $t->longText('snapshot'); $t->string('recorded_by', 150)->nullable(); $t->timestamp('created_at');
            $t->unique(['content_id', 'fingerprint']);
        });
        Schema::create('learning_grade_audits', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('attempt_id')->index(); $t->unsignedBigInteger('answer_id');
            $t->decimal('previous_points', 10, 2)->nullable(); $t->decimal('points', 10, 2);
            $t->text('feedback')->nullable(); $t->string('graded_by', 150); $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_grade_audits');
        Schema::dropIfExists('learning_content_versions');
        Schema::dropIfExists('learning_attempt_history');
        Schema::dropIfExists('learning_quiz_versions');
    }
};
