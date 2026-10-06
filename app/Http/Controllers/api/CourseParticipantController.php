<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\EmployeeDirectory;
use App\Services\LmsAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseParticipantController extends Controller
{
    private function authorizeManager(Request $request, ?int $id = null): void
    {
        abort_unless(in_array(app(LmsAccess::class)->role($request), ['admin', 'teacher', 'trainer'], true), 403);
        if ($id !== null) Course::findOrFail($id);
    }

    public function options(Request $request, EmployeeDirectory $directory)
    {
        $this->authorizeManager($request);
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        return ['employees' => $directory->search(trim($data['q'] ?? ''))];
    }

    public function index(Request $request, int $id, EmployeeDirectory $directory)
    {
        $this->authorizeManager($request, $id);
        $usernames = DB::table('course_participants')->where('course_id', $id)->orderBy('username')->pluck('username');
        $names = $directory->names($usernames->all());
        $students = $directory->countForAudience(app(\App\Services\CourseAudience::class)->read($id), []);
        $participants = $usernames->count();
        return [
            'participants' => $usernames->map(fn ($username) => ['username' => $username, 'name' => $names[$username] ?? $username]),
            'students_count' => $students,
            'participants_count' => $participants,
            'total_students' => $students + $participants,
        ];
    }

    public function store(Request $request, int $id, EmployeeDirectory $directory)
    {
        $this->authorizeManager($request, $id);
        $data = $request->validate(['username' => 'required|string|max:150']);
        if (!$directory->active($data['username'])) {
            throw ValidationException::withMessages(['username' => 'Pilih karyawan aktif yang tersedia.']);
        }
        DB::table('course_participants')->insertOrIgnore(['course_id' => $id, 'username' => $data['username'], 'created_at' => now()]);
        return ['message' => 'Peserta ditambahkan ke kelas.'];
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeManager($request, $id);
        $data = $request->validate(['username' => 'required|string|max:150']);
        DB::table('course_participants')->where('course_id', $id)->where('username', $data['username'])->delete();
        return ['message' => 'Akses khusus peserta dihapus dari kelas.'];
    }
}
