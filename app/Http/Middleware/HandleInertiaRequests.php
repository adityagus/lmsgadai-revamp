<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => fn () => $request->session()->get('auth') ? array_merge(
                $request->session()->get('auth'), ['role' => app(\App\Services\LmsAccess::class)->role($request)]
            ) : null,
            'csrfToken' => fn () => csrf_token(),
            'flash' => ['message' => fn () => $request->session()->get('message')],
        ]);
    }
}
