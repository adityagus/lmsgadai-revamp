<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireSessionAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->has('auth.user') || $request->session()->get('auth.status') !== 'login') {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return $next($request);
    }
}
