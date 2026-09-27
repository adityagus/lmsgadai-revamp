<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuizQuestionExplanation extends Migration
{
    public function up()
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->text('explanation')->nullable();
        });
    }

    public function down()
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropColumn('explanation');
        });
    }
}
