<?php

namespace App\Services\Quiz;

use App\Services\EmployeeDirectory;
use Illuminate\Support\Facades\DB;

class EmployeeParticipants
{
    public function __construct(private EmployeeDirectory $directory) {}

    // Existing quiz foreign keys use these IDs. Names and permissions never come from this table.
    private function attachIds($employees)
    {
        $ids = DB::table('lms_users')->whereIn('username', $employees->pluck('username'))->pluck('id', 'username');
        $missing = $employees->reject(fn ($employee) => $ids->has($employee->username));
        foreach ($missing->chunk(500) as $chunk) {
            DB::table('lms_users')->insertOrIgnore($chunk->map(fn ($employee) => [
                'username' => $employee->username, 'name' => $employee->name,
                'role' => 'student', 'created_at' => now(), 'updated_at' => now(),
            ])->all());
        }
        if ($missing->isNotEmpty()) {
            $ids = DB::table('lms_users')->whereIn('username', $employees->pluck('username'))->pluck('id', 'username');
        }
        return $employees->map(function ($employee) use ($ids) {
            $employee->id = $ids[$employee->username];
            return $employee;
        });
    }

    public function all()
    {
        return $this->attachIds($this->directory->accounts());
    }

    public function findActive(string $username)
    {
        return $this->attachIds($this->directory->accounts([$username]))->first();
    }

    public function names($ids)
    {
        $usernames = DB::table('lms_users')->whereIn('id', $ids)->pluck('username', 'id');
        $names = $this->directory->names($usernames->values()->all());
        return $usernames->map(fn ($username) => $names[$username] ?? $username);
    }

    public function validationRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $username = DB::table('lms_users')->where('id', $value)->value('username');
            if (!$username || !$this->directory->active($username)) {
                $fail('Peserta harus berasal dari akun karyawan aktif.');
            }
        };
    }
}
