<?php

namespace App\Services;

use App\Services\Quiz\AudienceDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CourseAudience
{
    public function read(int $courseId): array
    {
        if (!Schema::hasTable('course_access_rules')) return [];
        return DB::table('course_access_rules')->where('course_id', $courseId)
            ->pluck('position_code')->map(fn ($code) => (string) $code)->values()->all();
    }

    public function validate(Request $request): ?array
    {
        if (!$request->exists('access_positions')) return null;
        $data = $request->validate([
            'access_positions' => 'present|array|max:1000',
            'access_positions.*' => 'string|distinct|max:100',
        ])['access_positions'];
        $known = array_map(fn ($item) => (string) $item->value, app(AudienceDirectory::class)->positions());
        validator(['access_positions' => $data], ['access_positions.*' => [Rule::in($known)]])->validate();
        return array_values(array_unique($data));
    }

    public function save(int $courseId, ?array $positions): void
    {
        if ($positions === null) return;
        if (!Schema::hasTable('course_access_rules')) return;
        DB::table('course_access_rules')->where('course_id', $courseId)->delete();
        foreach ($positions as $position) {
            DB::table('course_access_rules')->insert(['course_id' => $courseId, 'position_code' => $position]);
        }
    }

    public function allows(int $courseId, Request $request): bool
    {
        if (in_array(app(LmsAccess::class)->role($request), ['admin', 'trainer', 'teacher'], true)) return true;
        $rules = $this->read($courseId);
        if ($request->session()->get('auth.status') !== 'login') return false;
        $username = (string) $request->session()->get('auth.user', '');
        if (Schema::hasTable('course_participants') && $username !== '' && DB::table('course_participants')
            ->where('course_id', $courseId)->where('username', $username)->exists()) {
            return true;
        }
        if (!$rules) return true;
        $position = (string) $request->session()->get('auth.idgrup', '');
        return $position !== '' && in_array($position, $rules, true);
    }

    public function assertAllowed(int $courseId, Request $request): void
    {
        abort_unless($this->allows($courseId, $request), 403, 'Jabatan Anda belum mendapat akses ke kelas ini.');
    }
}
