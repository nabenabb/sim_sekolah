<?php

namespace App\Http\Controllers;

use App\Models\ProgramKeahlian;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'programKeahlian']);
        $roles = Role::orderBy('name')->orderBy('id')->get()->unique('name')->values();

        if ($request->filled('q')) {
            $keyword = $request->query('q');
            $query->where(function ($w) use ($keyword) {
                $w->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('role')) {
            $roleName = Role::whereKey($request->query('role'))->value('name');

            if ($roleName !== null) {
                $query->whereHas('role', fn ($roleQuery) => $roleQuery->where('name', $roleName));
            } else {
                $query->where('role_id', $request->query('role'));
            }
        }

        $statusFilter = $request->query('status', 'semua');
        if ($statusFilter === 'aktif') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'nonaktif') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();
        $programKeahlian = ProgramKeahlian::orderBy('nama_jurusan')->get();

        return view('manajemen-pengguna.index', compact('users', 'roles', 'programKeahlian', 'statusFilter'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:5',
            'role_id' => 'required|exists:roles,id',
            'program_keahlian_id' => 'nullable|exists:program_keahlian,id',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'program_keahlian_id' => $data['program_keahlian_id'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('manajemen-pengguna.index')->with('success', 'User "' . $data['name'] . '" berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'program_keahlian_id' => 'nullable|exists:program_keahlian,id',
        ]);

        $user->update($data);

        return redirect()->route('manajemen-pengguna.index')->with('success', 'Data user "' . $user->name . '" berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => 'required|string|min:5',
        ]);

        $user->update(['password_hash' => Hash::make($data['password'])]);
        $user->forceFill([
            'remember_token' => \Illuminate\Support\Str::random(60),
            'session_token' => \Illuminate\Support\Str::random(64),
        ])->save();

        return redirect()->route('manajemen-pengguna.index')->with('success', 'Password user "' . $user->name . '" berhasil direset.');
    }

    public function toggleActive(User $user)
    {
        // Admin tidak bisa menonaktifkan akunnya sendiri (biar tidak terkunci dari sistem)
        if ($user->id === auth()->id()) {
            return back()->withErrors(['status' => 'Anda tidak bisa menonaktifkan akun Anda sendiri.']);
        }

        $user->update(['is_active' => ! $user->is_active]);
        $user->forceFill(['session_token' => \Illuminate\Support\Str::random(64)])->save();

        $pesan = $user->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return redirect()->route('manajemen-pengguna.index')->with('success', 'User "' . $user->name . '" berhasil ' . $pesan . '.');
    }
}
