<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quiz_access_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->string('type', 20);
            $table->string('value', 100);
            $table->unique(['quiz_id', 'type', 'value']);
        });
    }
    public function down(): void { Schema::dropIfExists('quiz_access_rules'); }
};
