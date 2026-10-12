<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_batches', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->text('description')->nullable();
            $t->date('starts_on'); $t->date('ends_on'); $t->string('status')->default('draft');
            $t->string('created_by', 150)->index(); $t->unsignedInteger('version')->default(1); $t->timestamps();
        });
        Schema::create('training_batch_members', function (Blueprint $t) {
            $t->foreignId('batch_id')->constrained('training_batches')->cascadeOnDelete();
            $t->string('username', 150); $t->primary(['batch_id', 'username']); $t->index('username');
        });
        Schema::create('training_batch_courses', function (Blueprint $t) {
            $t->foreignId('batch_id')->constrained('training_batches')->cascadeOnDelete();
            $t->unsignedBigInteger('course_id'); $t->primary(['batch_id', 'course_id']); $t->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_batch_courses');
        Schema::dropIfExists('training_batch_members');
        Schema::dropIfExists('training_batches');
    }
};
