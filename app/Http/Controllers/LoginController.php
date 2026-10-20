<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\UserRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Services\LmsAccess;

class LoginController extends Controller
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * LoginController Constructor.
     *
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Handle user login.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function aksiLogin(Request $request)
    {
        $request->validate([
            'user' => 'required|string',
            'pass' => 'required|string',
        ]);
        $username = trim($request->input('user'));
        $password = $request->input('pass');

        $users = $this->userRepository->cekLogin($username);
        $user = $users->first();

        if ($user) {
            $hashedPassword = trim($user->password_hash ?? '');
            // Employee auth stores Bcrypt $2a$ hashes. PHP verifies these directly;
            // Laravel's algorithm guard expects its own $2y$ variant.
            $passwordMatches = $hashedPassword !== '' && password_verify($password, $hashedPassword);
            $localBypass = app()->environment('local') && (bool) config('auth.login_bypass');

            if ($passwordMatches || $localBypass) {

                $nama = $user->full_name ?? '';
                $datasession = [
                    'nama' => Str::title($nama),
                    'user' => $user->username,
                    'cabang' => $user->branch_code ?? null,
                    'jabatan' => $user->position_name ?? null,
                    'idgrup' => $user->position_code ?? null,
                    // 'role' => app(LmsAccess::class)->roleForPosition($user->position_code ?? null),
                    'npk' => $user->npk ?? null,
                    'status' => 'login'
                ];
                
                $request->session()->migrate(true);
                $request->session()->put('auth', $datasession);

                $redirectUrl = '/overview';
                if ($request->header('X-Inertia')) return redirect($redirectUrl);
                return response()->json([
                    'success' => true,
                    'user' => $datasession,
                    'redirect' => $redirectUrl
                ]);
            } else {
                if ($request->header('X-Inertia')) throw \Illuminate\Validation\ValidationException::withMessages(['user' => 'Username atau password tidak sesuai.']);
                return response()->json([
                    'success' => false,
                    'message' => "Oops... Username/Password Salah!!!"
                ]);
            }
        } else {
            if ($request->header('X-Inertia')) throw \Illuminate\Validation\ValidationException::withMessages(['user' => 'Username atau password tidak sesuai.']);
            return response()->json([
                'success' => false,
                'message' => "Oops... Username/Password Salah!!!"
            ]);
        }
    }
    
    /**
     * Handle user logout.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return Redirect::to('/sign-in');
    }
}
