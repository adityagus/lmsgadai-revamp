<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class QuizRequestGuard
{
    public function handle(Request $request, Closure $next)
    {
        // Session login is not Laravel Auth: rate-limit each employee, not a shared office IP.
        $key = 'quiz:requests:'.hash('sha256', (string) $request->session()->get('auth.user'));
        if (RateLimiter::tooManyAttempts($key, 240)) {
            return response()->json(['message' => 'Terlalu banyak permintaan. Coba lagi sebentar.'], 429)
                ->header('Retry-After', RateLimiter::availableIn($key))->header('Cache-Control', 'private, no-store');
        }
        RateLimiter::hit($key, 60);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        return $response;
    }
}
