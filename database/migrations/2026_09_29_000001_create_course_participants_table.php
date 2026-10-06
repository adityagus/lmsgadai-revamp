<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('username', 150);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['course_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_participants');
    }
};
