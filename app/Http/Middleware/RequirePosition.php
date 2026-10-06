<?php

namespace App\Http\Middleware;

use App\Services\LmsAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePosition
{
    public function handle(Request $request, Closure $next, string ...$allowed): Response
    {
        if ($request->session()->get('auth.status') !== 'login' || ! $request->session()->get('auth.user')) {
            return $request->expectsJson() && ! $request->header('X-Inertia')
                ? response()->json(['message' => 'Silakan login terlebih dahulu.'], 401)
                : redirect()->route('login');
        }

        $position = $request->session()->get('auth.idgrup');
        $role = app(LmsAccess::class)->role($request);
        $groups = config('access.groups', []);

        foreach ($allowed as $requirement) {
            if (isset($groups[$requirement])) {
                if (in_array($role, $groups[$requirement], true)) {
                    return $next($request);
                }
            } elseif ($requirement !== '' && is_string($position) && $position === $requirement) {
                return $next($request);
            }
        }

        abort(403, 'Jabatan Anda tidak memiliki akses ke fitur ini.');
    }
}
