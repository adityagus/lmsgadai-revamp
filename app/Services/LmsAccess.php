<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Compatibility service for existing callers. RoleAccess owns the rules.
 */
class LmsAccess
{
    public function role(Request $request): ?string
    {
        return RoleAccess::role($request);
    }

    public function roleForPosition(?string $position): string
    {
        return RoleAccess::roleForPosition($position);
    }
}
