<?php
namespace App\Http\Middleware;

use App\Services\RoleAccess;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => fn () => $request->session()->get('auth') ? array_merge(
                $request->session()->get('auth'),
                [
                    'role' => RoleAccess::role($request),
                    'roles' => RoleAccess::roles($request),
                ]
            ) : null,
            'csrfToken' => fn () => csrf_token(),
            'flash' => ['message' => fn () => $request->session()->get('message')],
        ]);
    }
}
