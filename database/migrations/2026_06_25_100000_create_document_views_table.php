<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_views', function (Blueprint $table) {
            $table->id();
            $table->string('username'); // Menggunakan username karena foreign key users berada di database eksternal db2
            $table->unsignedBigInteger('document_id');
            $table->timestamp('first_viewed_at')->useCurrent();
            $table->timestamp('last_viewed_at')->useCurrent();
            $table->integer('view_count')->default(1);
            $table->timestamps();

            // Composite unique index untuk fitur Upsert & optimasi performa query
            $table->unique(['username', 'document_id']);
            
            // Index untuk foreign key document_id
            $table->index('document_id');
            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_views');
    }
}
