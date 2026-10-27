<?php

namespace App\Models;

use App\Services\RoleAccess;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $table = 'lms_user_roles';

    protected $fillable = [
        'username',
        'name',
        'position',
        'role',
        'roles',
        'description',
    ];

    protected $casts = [
        'roles' => 'array',
    ];

    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtolower(trim((string) $value));
    }

    public function setRoleAttribute($value)
    {
        $this->attributes['role'] = strtolower(trim((string) $value));
    }

    public function setRolesAttribute($value)
    {
        $roles = is_array($value) ? $value : (is_string($value) ? (json_decode($value, true) ?: [$value]) : []);
        $clean = [];
        foreach ($roles as $r) {
            $r = strtolower(trim((string) $r));
            if (in_array($r, RoleAccess::VALID_ROLES, true)) {
                $clean[] = $r;
            }
        }
        $clean = array_values(array_unique($clean));
        $this->attributes['roles'] = json_encode($clean);

        if (!empty($clean)) {
            usort($clean, function ($a, $b) {
                $pA = RoleAccess::ROLE_PRIORITIES[$a] ?? 0;
                $pB = RoleAccess::ROLE_PRIORITIES[$b] ?? 0;
                return $pB <=> $pA;
            });
            $this->attributes['role'] = $clean[0];
        }
    }

    public function getRolesAttribute($value)
    {
        if (empty($value)) {
            return !empty($this->role) ? [$this->role] : ['student'];
        }
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        return is_array($decoded) && !empty($decoded) ? array_values($decoded) : (!empty($this->role) ? [$this->role] : ['student']);
    }
}
