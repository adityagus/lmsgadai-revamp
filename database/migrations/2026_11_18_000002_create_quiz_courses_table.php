<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bank quiz: one quiz can be linked to many courses (kelas).
 * quizzes.course_id is kept as the primary/legacy course and is backfilled here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quiz_courses')) {
            Schema::create('quiz_courses', function (Blueprint $table) {
                $table->unsignedBigInteger('quiz_id');
                $table->unsignedBigInteger('course_id');
                $table->timestamp('created_at')->nullable();
                $table->primary(['quiz_id', 'course_id']);
                $table->index('course_id');
            });
        }

        if (Schema::hasTable('quizzes')) {
            $now = now();
            DB::table('quizzes')->whereNotNull('course_id')->orderBy('id')
                ->select('id', 'course_id')
                ->chunk(500, function ($rows) use ($now) {
                    DB::table('quiz_courses')->insertOrIgnore($rows->map(fn ($row) => [
                        'quiz_id' => $row->id,
                        'course_id' => $row->course_id,
                        'created_at' => $now,
                    ])->all());
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_courses');
    }
};
