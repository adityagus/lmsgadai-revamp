<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EmployeeDirectory
{
    private function query()
    {
        return DB::connection('db2')->table('auth.users as users')
            ->join('master.employee as employee', 'employee.employee_id', '=', 'users.employee_id');
    }

    public function search(string $search)
    {
        if (mb_strlen($search) < 2) return collect();
        $term = '%'.mb_strtolower($search).'%';
        return $this->query()->where('users.is_active', true)
            ->where(function ($query) use ($term) {
                $query->whereRaw('LOWER(users.username) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(users.full_name) LIKE ?', [$term]);
            })->orderBy('users.full_name')->limit(30)
            ->get(['users.username', 'users.full_name as name']);
    }

    public function active(string $username): bool
    {
        return $this->query()->where('users.is_active', true)->where('users.username', $username)->exists();
    }

    /** Only employee-backed accounts may participate; never expose authentication fields. */
    public function accounts(?array $usernames = null, bool $activeOnly = true)
    {
        return $this->query()
            ->leftJoin('master.position as position', 'position.position_id', '=', 'employee.position_id')
            ->when($activeOnly, fn ($query) => $query->where('users.is_active', true))
            ->when($usernames !== null, fn ($query) => $query->whereIn('users.username', $usernames))
            ->orderBy('users.full_name')
            ->get(['users.username', 'users.full_name as name', 'position.position_code as position'])
            ->map(function ($employee) {
                $employee->role = RoleAccess::roleForPosition($employee->position);
                return $employee;
            });
    }

    public function names(array $usernames)
    {
        if (!$usernames) return collect();
        return $this->query()->whereIn('users.username', $usernames)->pluck('users.full_name', 'users.username');
    }

    public function countForAudience(array $positions, array $usernames): int
    {
        $query = $this->query()->where('users.is_active', true);
        if ($positions) {
            $query->leftJoin('master.position as position', 'position.position_id', '=', 'employee.position_id')
                ->where(function ($query) use ($positions, $usernames) {
                    $query->whereIn('position.position_code', $positions)->orWhereIn('users.username', $usernames);
                });
        }
        // Multiple accounts or overlapping position/individual access still represent one employee.
        return $query->distinct()->count('employee.employee_id');
    }
}
