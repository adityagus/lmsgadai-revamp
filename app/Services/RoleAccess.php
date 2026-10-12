<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Resolves LMS permissions from the verified employee position in the session.
 * Access decisions must always use auth.idgrup, not a client-supplied role.
 */
class RoleAccess
{
    public const VALID_ROLES = ['admin', 'sop', 'trainer', 'audit', 'student'];

    public static function role(?Request $request = null): ?string
    {
        $request ??= request();
        $auth = $request->session()->get('auth', []);

        if (($auth['status'] ?? null) !== 'login' || empty($auth['user'])) {
            return null;
        }

        return static::roleForPosition($auth['idgrup'] ?? null);
    }

    public static function roleForPosition(?string $position): string
    {
        $position = strtoupper(trim((string) $position));
        $roles = config('access.position_roles', []);
        $role = $roles[$position] ?? null;

        // Employee directory values can contain a code followed by its title.
        if ($role === null && preg_match('/^(JBT-[A-Z0-9-]+|042)\b/', $position, $matches)) {
            $role = $roles[$matches[1]] ?? null;
        }

        return in_array($role, static::VALID_ROLES, true) ? $role : 'student';
    }

    public static function isAdmin(?Request $request = null): bool
    {
        return static::role($request) === 'admin';
    }

    public static function canManageDocuments(?Request $request = null): bool
    {
        return in_array(static::role($request), ['admin', 'sop'], true);
    }

    public static function canManageLearning(?Request $request = null): bool
    {
        return in_array(static::role($request), ['admin', 'trainer'], true);
    }

    public static function isManager(?Request $request = null): bool
    {
        return in_array(static::role($request), ['admin', 'trainer'], true);
    }

    public static function assertAdmin(?Request $request = null): void
    {
        abort_unless(static::isAdmin($request), 403, 'Akses ditolak. Memerlukan hak akses Admin (IT SPV).');
    }

    public static function assertManager(?Request $request = null): void
    {
        abort_unless(static::isManager($request), 403, 'Akses ditolak. Memerlukan hak akses Manager.');
    }

    public static function assertDocumentManager(?Request $request = null): void
    {
        abort_unless(static::canManageDocuments($request), 403, 'Akses ditolak. Memerlukan hak akses SOP atau Admin (IT SPV).');
    }

    public static function assertLearningManager(?Request $request = null): void
    {
        abort_unless(static::canManageLearning($request), 403, 'Akses ditolak. Memerlukan hak akses Trainer atau Admin (IT SPV).');
    }
}
