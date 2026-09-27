<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    // public function up()
    // {
    //     Schema::create('regions', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('code')->unique();
    //         $table->string('name');
    //         $table->boolean('is_active')->default(true); // 'active', 'inactive', 'deleted'
    //         $table->integer('sort_order')->nullable(); // For sorting purposes
    //         $table->timestamps();
    //     });
    // }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('regions');
    }
}
