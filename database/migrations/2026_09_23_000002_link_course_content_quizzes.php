<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkCourseContentQuizzes extends Migration
{
    public function up()
    {
        Schema::table('course_details', function (Blueprint $table) {
            $table->foreignId('quiz_id')->nullable()->constrained('quizzes');
        });
    }

    public function down()
    {
        Schema::table('course_details', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropColumn('quiz_id');
        });
    }
}
