<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password_hash)) {
            return back()->withErrors(['email' => 'Email atau password salah'])->onlyInput('email');
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi Admin sekolah.'])->onlyInput('email');
        }

        $user->forceFill(['session_token' => Str::random(64)])->save();
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth_session_token', $user->session_token);

        return match ($user->role->name) {
            'Koordinator TU' => redirect()->route('dashboard.tu'),
            'Kepala Sekolah' => redirect()->route('dashboard.kepsek'),
            'Waka Humas' => redirect()->route('dashboard.humas'),
            default => redirect()->route('dashboard'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
