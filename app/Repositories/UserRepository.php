<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function cekLogin(string $username)
    {
        return DB::connection('db2')
            ->table('auth.users as a')
            ->select('a.username', DB::raw('TRIM(a.password_hash) as password_hash'), 'a.full_name', 'c.position_code', 'c.position_name', 'b.branch_id', DB::raw('RIGHT(d.branch_code, 4) as branch_code'), 'b.employee_code as npk')
            ->join('master.employee as b', 'a.employee_id', '=', 'b.employee_id')
            ->join('master.position as c', 'b.position_id', '=', 'c.position_id')
            ->leftJoin('master.branch as d', 'b.branch_id', '=', 'd.branch_id')
            ->where('a.is_active', 'true')
            ->where('a.username', '=', $username)
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getUserRole(string $username)
    {
        return DB::connection('db2')
            ->table('auth.users as a')
            ->select('c.position_code as kd_jabatan', 'c.position_name as nm_jabatan')
            ->leftJoin('master.employee as b', 'a.employee_id', '=', 'b.employee_id')
            ->leftJoin('master.position as c', 'b.position_id', '=', 'c.position_id')
            ->where('a.username', '=', $username)
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getUserProfile(string $username)
    {
        return DB::connection('db2')
            ->table('auth.users as a')
            ->select('a.username', 'a.full_name', 'b.branch_id as fk_cabang_user')
            ->leftJoin('master.employee as b', 'a.employee_id', '=', 'b.employee_id')
            ->where('a.username', '=', $username)
            ->get();
    }
}
