<?php

namespace App\Console\Commands;

use App\Services\Quiz\QuizService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireQuizzes extends Command
{
    protected $signature = 'quiz:expire';
    protected $description = 'Auto-submit quiz attempts whose server deadline has elapsed';
    public function handle(QuizService $service)
    {
        DB::table('quiz_attempts')->where('status', 'IN_PROGRESS')->where('expired_at', '<=', now())->orderBy('id')->chunkById(100, function ($rows) use ($service) {
            foreach ($rows as $row) { $service->expire($row->id); }
        });
        return 0;
    }
}
