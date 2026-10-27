<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PositionRole extends Model
{
    protected $table = 'lms_position_roles';

    protected $fillable = [
        'position_code',
        'position_name',
        'role',
        'description',
    ];

    public function setPositionCodeAttribute($value)
    {
        $this->attributes['position_code'] = strtoupper(trim((string) $value));
    }

    public function setRoleAttribute($value)
    {
        $this->attributes['role'] = strtolower(trim((string) $value));
    }
}
