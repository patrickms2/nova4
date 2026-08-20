<?php

/**
 * Module: AuthController
 * Created: 2026-06-23
 * Author: Raditya Natha Azra
 * Synopsis: Controller untuk autentikasi pengguna (login, logout)
 *
 * Functions:
 *   - showLogin() : view -> tampilkan form login
 *   - login(Request) : redirect -> proses login
 *   - logout(Request) : redirect -> proses logout
 *
 * Input Parameters:
 *   - email : string -> email pengguna
 *   - password : string -> kata sandi
 *
 * Return Values:
 *   - 0 : gagal
 *   - 1 : berhasil
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('comunigest.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->Role === 'anggota') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['email' => 'Akun anggota tidak memiliki hak akses. Silakan hubungi admin.']);
            }
            $request->session()->regenerate();
            $defaultDestination = in_array(strtolower((string) $user->role), ['admin','superadmin', 'super'], true)
                ? route('community-portal')
                : route('comunigest.inicio');

            return redirect()->intended($defaultDestination);
        }

        return back()->withErrors(['email' => 'Email atau password salah']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'username' => 'required|unique:users,username,'.$user->id,
            'email' => 'nullable|email',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $user->username = $request->username;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui');
    }
}
