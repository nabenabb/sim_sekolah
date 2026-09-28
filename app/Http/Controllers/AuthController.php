<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        Auth::login($user);

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
