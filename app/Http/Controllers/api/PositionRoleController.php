<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\PositionRole;
use App\Models\UserRole;
use App\Services\EmployeeDirectory;
use App\Services\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PositionRoleController extends Controller
{
    public function index(Request $request)
    {
        RoleAccess::assertAdmin($request);

        $this->ensureDefaultRecordsExist();

        $search = trim((string) $request->input('search', ''));

        // 1. User Roles (Per-karyawan)
        $userRolesQuery = UserRole::query()->orderBy('name');
        if ($search !== '') {
            $userRolesQuery->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $userRoles = Schema::hasTable('lms_user_roles') ? $userRolesQuery->get() : collect();

        // 2. Position Roles (Fallback per-jabatan)
        $posQuery = PositionRole::query()->orderBy('position_code');
        if ($search !== '') {
            $posQuery->where(function ($q) use ($search) {
                $q->where('position_code', 'like', "%{$search}%")
                    ->orWhere('position_name', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        $positionRoles = $posQuery->get();

        // 3. Employee Directory from db2 (db_gadaimulia)
        $employees = [];
        try {
            $employees = app(EmployeeDirectory::class)->directory();
        } catch (Throwable $e) {
            // db2 may not be accessible in tests or when db2 is offline
        }

        // 4. Master Positions from db2
        $positions = [];
        try {
            $positions = DB::connection('db2')
                ->table('master.position as jb')
                ->where('jb.is_active', true)
                ->select('jb.position_code', 'jb.position_name')
                ->orderBy('jb.position_name')
                ->get();
        } catch (Throwable $e) {
            // db2 position table may not be accessible in tests
        }

        $availableRoles = [
            [
                'role' => 'admin',
                'label' => 'Admin (IT SPV)',
                'badge_color' => 'bg-purple-100 text-purple-700 border-purple-200',
                'description' => 'Akses penuh ke semua modul: Kursus, Kuis, Dokumen, Laporan, Master Data, dan Manajemen Role.',
            ],
            [
                'role' => 'trainer',
                'label' => 'Trainer (Instruktur)',
                'badge_color' => 'bg-blue-100 text-blue-700 border-blue-200',
                'description' => 'Akses instruktur: Kelola Kelas/Materi, Bank Soal & Peserta Kuis, Batch Pelatihan, dan Riwayat Kuis.',
            ],
            [
                'role' => 'sop',
                'label' => 'SOP (Dokumen)',
                'badge_color' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                'description' => 'Pengelolaan dokumen operasional: Buat dokumen, Edit dokumen, Arsip dokumen, dan Laporan Dokumen.',
            ],
            [
                'role' => 'audit',
                'label' => 'Audit',
                'badge_color' => 'bg-amber-100 text-amber-700 border-amber-200',
                'description' => 'Akses pemeriksaan: Laporan pembacaan dokumen dan Log aktivitas pengguna (read-only).',
            ],
        ];

        // Return user roles as primary data, fallback to position roles if empty
        $data = $userRoles->isNotEmpty() ? $userRoles : $positionRoles;

        return response()->json([
            'status' => 200,
            'data' => $data,
            'user_roles' => $userRoles,
            'position_roles' => $positionRoles,
            'employees' => $employees,
            'positions' => $positions,
            'available_roles' => $availableRoles,
        ]);
    }

    public function employees(Request $request, EmployeeDirectory $directory)
    {
        RoleAccess::assertAdmin($request);

        $q = trim((string) $request->input('q', ''));

        return response()->json([
            'status' => 200,
            'data' => $directory->directory($q !== '' ? $q : null, 30)->values(),
        ]);
    }

    public function store(Request $request)
    {
        RoleAccess::assertAdmin($request);

        // A. Assign Role to Employee (Checklist Multi-role)
        if ($request->has('username')) {
            $rolesInput = $request->input('roles');
            if (empty($rolesInput) && $request->filled('role')) {
                $rolesInput = [$request->input('role')];
                $request->merge(['roles' => $rolesInput]);
            }

            $validated = $request->validate([
                'username' => ['required', 'string', 'max:100', 'unique:lms_user_roles,username'],
                'name' => ['nullable', 'string', 'max:150'],
                'position' => ['nullable', 'string', 'max:150'],
                'roles' => ['required', 'array', 'min:1'],
                'roles.*' => ['required', 'string', 'in:' . implode(',', RoleAccess::VALID_ROLES)],
                'description' => ['nullable', 'string', 'max:500'],
            ], [
                'username.required' => 'Karyawan / Username wajib dipilih.',
                'username.unique' => 'Karyawan ini sudah memiliki pengaturan role khusus.',
                'roles.required' => 'Minimal pilih 1 role (checklist) untuk karyawan.',
                'roles.min' => 'Minimal pilih 1 role (checklist) untuk karyawan.',
            ]);

            $item = UserRole::create($validated);
            RoleAccess::clearRoleCache();

            return response()->json([
                'status' => 201,
                'message' => 'Role karyawan berhasil ditambahkan.',
                'data' => $item,
            ], 201);
        }

        // B. Position-based Mapping Fallback
        $validated = $request->validate([
            'position_code' => ['required', 'string', 'max:50', 'unique:lms_position_roles,position_code'],
            'position_name' => ['nullable', 'string', 'max:150'],
            'role' => ['required', 'string', 'in:' . implode(',', RoleAccess::VALID_ROLES)],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'position_code.required' => 'Kode jabatan wajib diisi.',
            'position_code.unique' => 'Kode jabatan ini sudah terdaftar dalam manajemen role.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
        ]);

        $item = PositionRole::create($validated);
        RoleAccess::clearRoleCache();

        return response()->json([
            'status' => 201,
            'message' => 'Pemetaan role jabatan berhasil ditambahkan.',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        RoleAccess::assertAdmin($request);

        $userRole = Schema::hasTable('lms_user_roles') ? UserRole::find($id) : null;
        if ($userRole) {
            $rolesInput = $request->input('roles');
            if (empty($rolesInput) && $request->filled('role')) {
                $rolesInput = [$request->input('role')];
                $request->merge(['roles' => $rolesInput]);
            }

            $validated = $request->validate([
                'username' => ['required', 'string', 'max:100', 'unique:lms_user_roles,username,' . $id],
                'name' => ['nullable', 'string', 'max:150'],
                'position' => ['nullable', 'string', 'max:150'],
                'roles' => ['required', 'array', 'min:1'],
                'roles.*' => ['required', 'string', 'in:' . implode(',', RoleAccess::VALID_ROLES)],
                'description' => ['nullable', 'string', 'max:500'],
            ], [
                'username.required' => 'Karyawan / Username wajib dipilih.',
                'username.unique' => 'Karyawan ini sudah digunakan pada entri lain.',
                'roles.required' => 'Minimal pilih 1 role (checklist) untuk karyawan.',
                'roles.min' => 'Minimal pilih 1 role (checklist) untuk karyawan.',
            ]);

            $userRole->update($validated);
            RoleAccess::clearRoleCache();

            return response()->json([
                'status' => 200,
                'message' => 'Role karyawan berhasil diperbarui.',
                'data' => $userRole,
            ]);
        }

        $positionRole = PositionRole::findOrFail($id);
        $validated = $request->validate([
            'position_code' => ['required', 'string', 'max:50', 'unique:lms_position_roles,position_code,' . $id],
            'position_name' => ['nullable', 'string', 'max:150'],
            'role' => ['required', 'string', 'in:' . implode(',', RoleAccess::VALID_ROLES)],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'position_code.required' => 'Kode jabatan wajib diisi.',
            'position_code.unique' => 'Kode jabatan ini sudah digunakan pada entri lain.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
        ]);

        $positionRole->update($validated);
        RoleAccess::clearRoleCache();

        return response()->json([
            'status' => 200,
            'message' => 'Pemetaan role jabatan berhasil diperbarui.',
            'data' => $positionRole,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        RoleAccess::assertAdmin($request);

        $userRole = Schema::hasTable('lms_user_roles') ? UserRole::find($id) : null;
        if ($userRole) {
            $userRole->delete();
            RoleAccess::clearRoleCache();

            return response()->json([
                'status' => 200,
                'message' => 'Role karyawan berhasil dihapus. Karyawan kembali ke hak akses standar.',
            ]);
        }

        $positionRole = PositionRole::findOrFail($id);
        $positionRole->delete();
        RoleAccess::clearRoleCache();

        return response()->json([
            'status' => 200,
            'message' => 'Pemetaan role jabatan berhasil dihapus.',
        ]);
    }

    private function ensureDefaultRecordsExist(): void
    {
        if (!Schema::hasTable('lms_position_roles')) {
            return;
        }

        if (PositionRole::count() > 0) {
            return;
        }

        $defaults = config('access.position_roles', []);
        $rows = [];
        foreach ($defaults as $code => $role) {
            $rows[] = [
                'position_code' => $code,
                'position_name' => null,
                'role' => $role,
                'description' => 'Default system mapping',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($rows)) {
            DB::table('lms_position_roles')->insertOrIgnore($rows);
            RoleAccess::clearRoleCache();
        }
    }
}

