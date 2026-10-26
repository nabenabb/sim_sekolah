<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && (! $request->user()->is_active
            || ! $request->session()->has('auth_session_token')
            || ! hash_equals((string) $request->user()->session_token, (string) $request->session()->get('auth_session_token')))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Sesi Anda berakhir atau akses akun telah berubah. Silakan masuk kembali.',
            ]);
        }

        return $next($request);
    }
}
