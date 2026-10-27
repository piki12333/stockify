<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // Siapkan data profil, sesi aktif, dan aktivitas terbaru untuk halaman profil.
    public function edit(Request $request)
    {
        $user = $request->user();
        $sessionExpiry = now()->subMinutes(config('session.lifetime'))->timestamp;
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', $sessionExpiry)
            ->orderByDesc('last_activity')
            ->get();
        $activities = $user->activities()->latest()->limit(10)->get();

        return view('pages.profile.edit', compact('user', 'sessions', 'activities'));
    }

    // Validasi lalu simpan nama dan email milik pengguna yang sedang masuk.
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        $this->recordActivity($request, 'profile-updated', 'Mengubah informasi nama atau email.');

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    // Simpan foto baru atau hapus foto lama, lalu catat perubahan akun.
    public function updateAvatar(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048|required_without:remove_avatar',
            'remove_avatar' => 'nullable|boolean',
        ]);

        $oldAvatar = $user->avatar;

        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars', 'public');

            if (! $newAvatar) {
                return back()->withErrors(['avatar' => 'Foto profil gagal disimpan.'])->withInput();
            }

            $user->avatar = $newAvatar;
        } elseif ($request->boolean('remove_avatar') && $user->avatar) {
            $user->avatar = null;
        } else {
            return back()->withErrors(['avatar' => 'Pilih foto atau tandai hapus foto terlebih dahulu.'])->withInput();
        }

        $user->save();

        if ($oldAvatar && ($request->hasFile('avatar') || $request->boolean('remove_avatar'))) {
            Storage::disk('public')->delete($oldAvatar);
        }

        $this->recordActivity(
            $request,
            $request->boolean('remove_avatar') ? 'avatar-removed' : 'avatar-updated',
            $request->boolean('remove_avatar') ? 'Menghapus foto profil.' : 'Memperbarui foto profil.'
        );

        return redirect()->route('profile.edit')->with('status', 'avatar-updated');
    }

    // Pastikan kata sandi lama benar sebelum menyimpan kata sandi baru.
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        $this->recordActivity($request, 'password-updated', 'Mengganti kata sandi akun.');

        return redirect()->route('profile.edit')->with('status', 'password-updated');
    }

    // Hanya hapus sesi lain yang memang dimiliki pengguna saat ini.
    public function destroySession(Request $request, string $sessionId)
    {
        abort_if($sessionId === $request->session()->getId(), 422, 'Sesi yang sedang digunakan tidak dapat diakhiri dari sini.');

        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_if(! $session, 404);

        DB::table('sessions')->where('id', $sessionId)->delete();
        $this->recordActivity($request, 'session-revoked', 'Mengakhiri sesi lain.');

        return redirect()->route('profile.edit')->with('status', 'session-revoked');
    }

    // Akhiri seluruh sesi lain tanpa mengganggu sesi yang sedang digunakan.
    public function destroyOtherSessions(Request $request)
    {
        $deleted = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        if ($deleted > 0) {
            $this->recordActivity($request, 'sessions-revoked', 'Mengakhiri semua sesi lain.');
        }

        return redirect()->route('profile.edit')->with('status', 'sessions-revoked');
    }

    // Gunakan satu format pencatatan untuk semua aktivitas terkait akun.
    private function recordActivity(Request $request, string $event, string $description): void
    {
        $request->user()->activities()->create([
            'event' => $event,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
