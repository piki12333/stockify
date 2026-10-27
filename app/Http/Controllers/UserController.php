<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Tampilkan daftar pengguna beserta label role yang digunakan formulir.
    public function index()
    {
        $users = User::query()->orderBy('name')->paginate(20);

        return view('pages.users.index', [
            'users' => $users,
            'roles' => User::ROLE_LABELS,
        ]);
    }

    // Buat akun baru setelah memastikan email dan role belum bermasalah.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil dibuat.');
    }

    // Perbarui profil dan hak akses, termasuk menjaga agar Admin terakhir tidak hilang.
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== User::ROLE_ADMIN) {
            return back()->withErrors(['role' => 'Anda tidak dapat menurunkan role akun yang sedang digunakan.']);
        }

        DB::transaction(function () use ($user, $validated): void {
            if ($user->hasRole(User::ROLE_ADMIN) && $validated['role'] !== User::ROLE_ADMIN) {
                $this->ensureAnotherAdminExists($user);
            }

            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ]);
            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();
        });

        return redirect()->route('users.index')->with('success', 'Data dan hak akses pengguna berhasil diperbarui.');
    }

    // Hapus sesi dan notifikasi akun sebelum menghapus pengguna.
    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'Akun yang sedang digunakan tidak dapat dihapus.');

        DB::transaction(function () use ($user): void {
            if ($user->hasRole(User::ROLE_ADMIN)) {
                $this->ensureAnotherAdminExists($user);
            }

            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->notifications()->delete();
            $user->delete();
        });

        return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil dihapus.');
    }

    // Cegah perubahan yang menghapus satu-satunya Admin aplikasi.
    private function ensureAnotherAdminExists(User $user): void
    {
        $anotherAdminExists = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('id', '!=', $user->id)
            ->lockForUpdate()
            ->exists();

        abort_unless($anotherAdminExists, 422, 'Aplikasi harus memiliki setidaknya satu akun Admin.');
    }
}
