<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Resolves LMS permissions from user-level assignments or verified employee position in the session.
 */
class RoleAccess
{
    public const VALID_ROLES = ['admin', 'sop', 'trainer', 'audit', 'student'];

    public const ROLE_PRIORITIES = [
        'admin' => 100,
        'trainer' => 80,
        'sop' => 70,
        'audit' => 60,
        'student' => 10,
    ];

    public static function role(?Request $request = null): ?string
    {
        $request ??= request();
        $auth = $request->session()->get('auth', []);

        if (($auth['status'] ?? null) !== 'login' || empty($auth['user'])) {
            return null;
        }

        return static::roleForUser($auth['user'], $auth['idgrup'] ?? null);
    }

    public static function roles(?Request $request = null): array
    {
        $request ??= request();
        $auth = $request->session()->get('auth', []);

        if (($auth['status'] ?? null) !== 'login' || empty($auth['user'])) {
            return [];
        }

        return static::rolesForUser($auth['user'], $auth['idgrup'] ?? null);
    }

    public static function rolesForUser(?string $username, ?string $position = null): array
    {
        $username = strtolower(trim((string) $username));
        if ($username !== '') {
            $userRoleRecords = static::allUserRoleRecords();
            if (isset($userRoleRecords[$username])) {
                $assigned = $userRoleRecords[$username];
                if (!empty($assigned)) {
                    return $assigned;
                }
            }
        }

        $fallbackRole = static::roleForPosition($position);
        return [$fallbackRole];
    }

    public static function roleForUser(?string $username, ?string $position = null): string
    {
        $roles = static::rolesForUser($username, $position);
        if (empty($roles)) {
            return 'student';
        }

        usort($roles, function ($a, $b) {
            $pA = static::ROLE_PRIORITIES[$a] ?? 0;
            $pB = static::ROLE_PRIORITIES[$b] ?? 0;
            return $pB <=> $pA;
        });

        return $roles[0] ?? 'student';
    }

    public static function allUserRoleRecords(): array
    {
        return Cache::remember('lms:user_roles', 3600, function () {
            try {
                if (Schema::hasTable('lms_user_roles')) {
                    $rows = DB::table('lms_user_roles')->get();
                    $map = [];
                    foreach ($rows as $row) {
                        $userKey = strtolower(trim((string) $row->username));
                        $userRoles = [];

                        if (!empty($row->roles)) {
                            $decoded = is_string($row->roles) ? json_decode($row->roles, true) : (array) $row->roles;
                            if (is_array($decoded)) {
                                foreach ($decoded as $r) {
                                    $cleanRole = strtolower(trim((string) $r));
                                    if (in_array($cleanRole, static::VALID_ROLES, true)) {
                                        $userRoles[] = $cleanRole;
                                    }
                                }
                            }
                        }

                        if (empty($userRoles) && !empty($row->role)) {
                            $cleanRole = strtolower(trim((string) $row->role));
                            if (in_array($cleanRole, static::VALID_ROLES, true)) {
                                $userRoles[] = $cleanRole;
                            }
                        }

                        if (!empty($userRoles)) {
                            $map[$userKey] = array_values(array_unique($userRoles));
                        }
                    }
                    return $map;
                }
            } catch (Throwable $e) {
                // Fallback gracefully on database exceptions
            }

            return [];
        });
    }

    public static function allPositionRoles(): array
    {
        return Cache::remember('lms:position_roles', 3600, function () {
            try {
                if (Schema::hasTable('lms_position_roles')) {
                    $dbRoles = DB::table('lms_position_roles')
                        ->pluck('role', 'position_code')
                        ->mapWithKeys(fn ($role, $code) => [strtoupper(trim((string) $code)) => strtolower(trim((string) $role))])
                        ->toArray();

                    if (!empty($dbRoles)) {
                        return array_merge(config('access.position_roles', []), $dbRoles);
                    }
                }
            } catch (Throwable $e) {
                // Fallback gracefully on database exceptions
            }

            return config('access.position_roles', []);
        });
    }

    public static function clearRoleCache(): void
    {
        Cache::forget('lms:position_roles');
        Cache::forget('lms:user_roles');
    }

    public static function roleForPosition(?string $position): string
    {
        $position = strtoupper(trim((string) $position));
        if ($position === '') {
            return 'student';
        }

        $roles = static::allPositionRoles();
        $role = $roles[$position] ?? null;

        // Employee directory values can contain a code followed by its title.
        if ($role === null && preg_match('/^(JBT-[A-Z0-9-]+|042)\b/', $position, $matches)) {
            $role = $roles[$matches[1]] ?? null;
        }

        return in_array($role, static::VALID_ROLES, true) ? $role : 'student';
    }

    public static function hasRole(string|array $checkRoles, ?Request $request = null): bool
    {
        $checkRoles = (array) $checkRoles;
        $currentRoles = static::roles($request);

        foreach ($checkRoles as $r) {
            if (in_array(strtolower(trim((string) $r)), $currentRoles, true)) {
                return true;
            }
        }

        return false;
    }

    public static function isAdmin(?Request $request = null): bool
    {
        return static::hasRole('admin', $request);
    }

    public static function isTrainer(?Request $request = null): bool
    {
        return static::hasRole('trainer', $request);
    }

    public static function isSop(?Request $request = null): bool
    {
        return static::hasRole('sop', $request);
    }

    public static function isAudit(?Request $request = null): bool
    {
        return static::hasRole('audit', $request);
    }

    public static function canManageDocuments(?Request $request = null): bool
    {
        return static::hasRole(['admin', 'sop'], $request);
    }

    public static function canManageLearning(?Request $request = null): bool
    {
        return static::hasRole(['admin', 'trainer'], $request);
    }

    public static function isManager(?Request $request = null): bool
    {
        return static::hasRole(['admin', 'trainer'], $request);
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
