<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LmsAccess
{
    public function role($request)
    {
        if ($request->session()->get('auth.status') !== 'login') {
            return null;
        }
        if (in_array($request->session()->get('auth.idgrup'), config('quiz.admin_positions', []), true)) {
            return 'admin';
        }
        return Schema::hasTable('lms_users')
            ? DB::table('lms_users')->where('username', $request->session()->get('auth.user'))->value('role') ?? 'student'
            : 'student';
    }
}
