<?php
namespace App\Services\Quiz;

use Illuminate\Support\Facades\DB;

class AudienceDirectory
{
    public function positions(): array
    {
        return DB::connection('db2')->table('master.position')->where('is_active', true)
            ->orderBy('position_name')->get(['position_code as value', 'position_name as label'])->toArray();
    }
    public function branches(): array
    {
        // Employee login uses the final four characters of branch_code as auth.cabang.
        return DB::connection('db2')->table('master.branch')->where('is_active', true)
            ->orderBy('branch_name')->get([DB::raw('RIGHT(branch_code, 4) as value'), 'branch_name as label'])->toArray();
    }
}
