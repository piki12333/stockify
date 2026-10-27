<?php

namespace App\Http\Controllers;

use App\Models\AccountActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Menampilkan formulir masuk untuk pengunjung yang belum terautentikasi.
    public function create()
    {
        return view('auth.login');
    }

    // Memeriksa kredensial, memperbarui ID sesi, lalu mencatat login berhasil.
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang dimasukkan tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();
        $this->recordActivity($request, 'login', 'Berhasil masuk ke akun.');

        return redirect()->intended(route('dashboard'));
    }

    // Catat aktivitas sebelum menghapus sesi login pengguna.
    public function destroy(Request $request)
    {
        $this->recordActivity($request, 'logout', 'Keluar dari akun.');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Simpan metadata seperlunya agar riwayat akun dapat ditinjau pengguna.
    private function recordActivity(Request $request, string $event, string $description): void
    {
        AccountActivity::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
