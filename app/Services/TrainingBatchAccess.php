<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrainingBatchAccess
{
    public function courseIds(string $username)
    {
        if ($username === '' || !Schema::hasTable('training_batches')) return collect();
        return DB::table('training_batch_courses as c')
            ->join('training_batches as b', 'b.id', '=', 'c.batch_id')
            ->join('training_batch_members as m', 'm.batch_id', '=', 'b.id')
            ->where('m.username', $username)->where('b.status', 'active')
            ->where('b.starts_on', '<=', today()->toDateString())
            ->where('b.ends_on', '>=', today()->toDateString())
            ->distinct()->pluck('c.course_id');
    }
}
