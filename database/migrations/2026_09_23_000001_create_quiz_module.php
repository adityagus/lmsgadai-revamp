<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizModule extends Migration
{
    public function up()
    {
        Schema::create('lms_users', function (Blueprint $t) {
            $t->id(); $t->string('username')->unique(); $t->string('name');
            $t->string('role')->default('student'); $t->timestamps();
        });
        Schema::create('classes', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->foreignId('created_by')->constrained('lms_users'); $t->timestamps();
        });
        Schema::create('class_students', function (Blueprint $t) {
            $t->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $t->foreignId('student_id')->constrained('lms_users'); $t->primary(['class_id', 'student_id']);
        });
        Schema::create('question_banks', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->foreignId('created_by')->constrained('lms_users'); $t->timestamps();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id(); $t->foreignId('bank_id')->constrained('question_banks');
            $t->unsignedBigInteger('course_id')->nullable()->index();
            $t->text('question'); $t->string('type'); $t->decimal('weight', 10, 2)->default(1);
            $t->string('category')->nullable(); $t->string('topic')->nullable(); $t->string('difficulty');
            $t->text('explanation')->nullable(); $t->foreignId('created_by')->constrained('lms_users'); $t->timestamps();
        });
        Schema::create('question_options', function (Blueprint $t) {
            $t->id(); $t->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $t->text('text'); $t->boolean('is_correct')->default(false);
        });
        Schema::create('quizzes', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('course_id')->nullable()->index(); $t->string('title'); $t->text('description')->nullable();
            $t->unsignedInteger('duration_minutes'); $t->decimal('passing_grade', 5, 2)->default(75);
            $t->unsignedInteger('maximum_attempt')->default(1); $t->string('grading_method')->default('highest');
            $t->unsignedInteger('question_count'); $t->boolean('randomize_question')->default(false); $t->boolean('randomize_answer')->default(false);
            foreach (['show_score', 'show_correct_answer', 'show_wrong_answer', 'show_explanation', 'show_total_correct', 'show_passing_status'] as $field) { $t->boolean($field)->default(false); }
            $t->boolean('release_after_close')->default(false); $t->dateTime('available_from'); $t->dateTime('available_until');
            $t->string('status')->default('DRAFT')->index(); $t->foreignId('created_by')->constrained('lms_users'); $t->timestamps();
        });
        Schema::create('quiz_classes', function (Blueprint $t) {
            $t->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete(); $t->foreignId('class_id')->constrained('classes'); $t->primary(['quiz_id', 'class_id']);
        });
        Schema::create('quiz_students', function (Blueprint $t) {
            $t->id(); $t->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete(); $t->foreignId('student_id')->constrained('lms_users');
            $t->unsignedInteger('additional_minutes')->default(0); $t->unsignedInteger('maximum_attempt')->nullable();
            $t->dateTime('available_from')->nullable(); $t->dateTime('available_until')->nullable(); $t->unique(['quiz_id', 'student_id']);
        });
        Schema::create('quiz_questions', function (Blueprint $t) {
            $t->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete(); $t->foreignId('question_id')->constrained('questions');
            $t->unsignedInteger('position'); $t->primary(['quiz_id', 'question_id']);
        });
        Schema::create('quiz_attempts', function (Blueprint $t) {
            $t->id(); $t->foreignId('quiz_id')->constrained('quizzes'); $t->foreignId('student_id')->constrained('lms_users');
            $t->unsignedInteger('attempt_number'); $t->dateTime('started_at'); $t->dateTime('expired_at'); $t->dateTime('submitted_at')->nullable();
            $t->string('status')->default('IN_PROGRESS'); $t->decimal('score', 7, 2)->nullable();
            $t->unsignedInteger('total_correct')->default(0); $t->unsignedInteger('total_wrong')->default(0); $t->unsignedInteger('total_unanswered')->default(0);
            $t->boolean('pending_review')->default(false); $t->unique(['quiz_id', 'student_id', 'attempt_number']); $t->index(['status', 'expired_at']);
        });
        Schema::create('quiz_attempt_questions', function (Blueprint $t) {
            $t->id(); $t->foreignId('attempt_id')->constrained('quiz_attempts'); $t->foreignId('question_id')->constrained('questions');
            $t->unsignedInteger('position'); $t->json('option_order');
            $t->unique(['attempt_id', 'position']); $t->unique(['attempt_id', 'question_id']);
        });
        Schema::create('student_answers', function (Blueprint $t) {
            $t->id(); $t->foreignId('attempt_question_id')->unique()->constrained('quiz_attempt_questions');
            $t->json('selected_options')->nullable(); $t->text('essay')->nullable(); $t->boolean('visited')->default(false); $t->boolean('flagged')->default(false);
            $t->unsignedInteger('revision')->default(0); $t->decimal('points', 10, 2)->nullable();
            $t->foreignId('graded_by')->nullable()->constrained('lms_users'); $t->text('feedback')->nullable(); $t->timestamps();
        });
        // Aggregate results are derived from attempts to avoid stale duplicate scores.
    }

    public function down()
    {
        foreach (['student_answers','quiz_attempt_questions','quiz_attempts','quiz_questions','quiz_students','quiz_classes','quizzes','question_options','questions','question_banks','class_students','classes','lms_users'] as $table) { Schema::dropIfExists($table); }
    }
}
