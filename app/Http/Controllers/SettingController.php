<?php

namespace App\Http\Controllers;

use App\Models\AccountActivity;
use App\Models\ApplicationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    // Tampilkan identitas aplikasi yang sedang digunakan.
    public function index()
    {
        return view('pages.settings.index', [
            'settings' => ApplicationSetting::current(),
        ]);
    }

    // Validasi identitas baru, simpan logo, dan catat perubahan oleh Admin.
    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $settings = ApplicationSetting::current();
        $oldLogoPath = $settings->logo_path;
        $newLogoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('branding', 'public')
            : $oldLogoPath;

        if ($request->hasFile('logo') && ! $newLogoPath) {
            return back()->withErrors(['logo' => 'Logo gagal disimpan.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $settings, $newLogoPath): void {
            $settings->app_name = trim($validated['app_name']);
            $settings->logo_path = $newLogoPath;
            $settings->save();

            AccountActivity::create([
                'user_id' => $request->user()->id,
                'event' => 'application-settings-updated',
                'description' => 'Memperbarui nama atau logo aplikasi.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        if ($request->hasFile('logo') && $oldLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return redirect()->route('settings.index')->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }
}
