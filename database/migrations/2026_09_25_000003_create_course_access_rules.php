<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_access_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('position_code', 100);
            $table->unique(['course_id', 'position_code']);
            $table->index('position_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_access_rules');
    }
};
