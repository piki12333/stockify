@extends('layouts.dashboard')

@section('content')
{{-- Halaman ini merangkum pengaturan identitas, keamanan, dan aktivitas akun pengguna. --}}
<div class="mx-auto min-h-screen max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Judul dan tautan kembali membantu pengguna mengenali konteks halaman. --}}
    <div class="mb-8">
        <a href="{{ route('dashboard') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke dashboard
        </a>
        <p class="text-sm font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Pengaturan akun</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Profil Saya</h1>
        <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Perbarui identitas dan foto profil yang ditampilkan di Stockify.</p>
    </div>

    {{-- Tampilkan pesan sukses sesuai tindakan terakhir pengguna. --}}
    @if (in_array(session('status'), ['profile-updated', 'avatar-updated', 'password-updated', 'session-revoked', 'sessions-revoked'], true))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300" role="status">
            <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            @switch(session('status'))
                @case('avatar-updated') Foto profil berhasil diperbarui. @break
                @case('password-updated') Kata sandi berhasil diperbarui dan sesi lain telah diakhiri. @break
                @case('session-revoked') Sesi berhasil diakhiri. @break
                @case('sessions-revoked') Sesi lain berhasil diakhiri. @break
                @default Informasi profil berhasil diperbarui.
            @endswitch
        </div>
    @endif

    {{-- Ringkas kesalahan validasi agar pengguna tahu bagian yang perlu diperbaiki. --}}
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300" role="alert">
            <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Bagian utama profil: foto dan identitas di kiri, ringkasan akun di kanan. --}}
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:col-span-2" aria-labelledby="profile-heading">
                <div class="relative h-36 overflow-hidden bg-gradient-to-r from-blue-800 via-indigo-700 to-violet-700 sm:h-44">
                    <div class="absolute -right-10 -top-24 h-64 w-64 rounded-full border-[32px] border-white/10"></div>
                    <div class="absolute right-24 top-10 h-28 w-28 rounded-full bg-white/5"></div>
                    <div class="absolute bottom-5 left-6 text-white sm:left-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Stockify account</p>
                        <p class="mt-1 text-lg font-semibold">Kelola profil profesional Anda</p>
                    </div>
                </div>

                {{-- Form foto mengirim hasil crop ke server setelah pengguna mengonfirmasi. --}}
                <div class="px-5 pb-6 sm:px-8">
                    <form id="avatar-form" method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('patch')
                    <div class="-mt-12 flex flex-col gap-5 sm:-mt-14 sm:flex-row sm:items-end sm:justify-between">
                        <div class="flex min-w-0 flex-wrap items-end gap-x-5 gap-y-4">
                            <div class="relative h-24 w-24 shrink-0 sm:h-28 sm:w-28">
                                <div class="flex h-full w-full items-center justify-center overflow-hidden rounded-full border-4 border-white bg-gradient-to-br from-blue-100 to-indigo-100 text-3xl font-bold text-blue-800 shadow-lg dark:border-gray-800 dark:from-blue-900 dark:to-indigo-900 dark:text-blue-200">
                                    <img
                                        id="avatar-preview"
                                        src="{{ $user->avatar ? asset('storage/' . $user->avatar) : '' }}"
                                        alt="Pratinjau foto profil"
                                        class="{{ $user->avatar ? '' : 'hidden' }} h-full w-full object-cover"
                                    >
                                    <span id="avatar-placeholder" class="{{ $user->avatar ? 'hidden' : '' }}" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                </div>
                                <label for="avatar" title="Ganti foto profil" aria-label="Ganti foto profil" class="absolute bottom-0 right-0 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border-2 border-white bg-blue-700 text-white shadow-md transition hover:bg-blue-800 focus-within:ring-4 focus-within:ring-blue-300 dark:border-gray-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8a2 2 0 012-2h2l1.5-2h7L17 6h2a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                        <circle cx="12" cy="12" r="3" stroke-width="2"/>
                                    </svg>
                                </label>
                                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
                            </div>

                            <div class="min-w-0 pb-1">
                                <h2 id="profile-heading" class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                                <p class="mt-1 max-w-xs break-all text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <label for="avatar" class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-white/30 bg-white/10 px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-white backdrop-blur-sm transition hover:bg-white/15 focus-within:ring-2 focus-within:ring-white/60">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-.828.472L8 17l.7-2.999a2 2 0 01.472-.828z"/>
                                </svg>
                                Edit
                            </label>
                        </div>
                    </div>
                    </form>

                    <div>
                        @error('avatar')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Informasi pribadi ditampilkan sebagai ringkasan dan dapat dibuka untuk diedit. --}}
                    <div id="personal-info" class="mt-8 border-t border-gray-100 pt-7">
                        <div class="mb-6 flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Informasi pribadi</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pastikan nama dan email akun Anda selalu terbaru.</p>
                            </div>
                            <button id="personal-info-edit" type="button" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-blue-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-blue-900">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l2.651 2.651M7.5 16.5l1.5-.3 9.9-9.9a1.875 1.875 0 00-2.65-2.65l-9.9 9.9-.3 1.5zM19.5 13.5v5.25A2.25 2.25 0 0117.25 21h-10.5A2.25 2.25 0 014.5 18.75V8.25A2.25 2.25 0 016.75 6h5.25"/>
                                </svg>
                                Edit
                            </button>
                        </div>

                        <div id="personal-info-summary" class="{{ $errors->has('name') || $errors->has('email') ? 'hidden' : '' }} grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama lengkap</p>
                                <p class="mt-2 break-words text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Alamat email</p>
                                <p class="mt-2 break-all text-sm font-semibold text-gray-900 dark:text-white">{{ $user->email }}</p>
                            </div>
                        </div>

                        <form id="personal-info-form" method="POST" action="{{ route('profile.update') }}" class="{{ $errors->has('name') || $errors->has('email') ? '' : 'hidden' }}">
                            @csrf
                            @method('patch')
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama lengkap</label>
                                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-900">
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat email</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-900">
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 dark:border-gray-700 sm:flex-row sm:justify-end">
                            <button id="personal-info-cancel" type="button" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Batal</button>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan perubahan
                            </button>
                        </div>
                        </form>
                    </div>
                </div>
            </section>

            {{-- Ringkasan ini hanya menampilkan data akun dari pengguna yang sedang masuk. --}}
            <aside class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:col-span-1" aria-labelledby="account-summary-heading">
                <div class="border-b border-gray-100 px-5 py-5 dark:border-gray-700">
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Akun Stockify</p>
                    <h3 id="account-summary-heading" class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Ringkasan akun</h3>
                </div>

                <div class="p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100 text-lg font-bold text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            @if ($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" alt="Foto profil {{ $user->name }}" class="h-full w-full object-cover">
                            @else
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                            <p class="mt-1 break-all text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                        </div>
                    </div>

                    <dl class="mt-6 divide-y divide-gray-100 border-t border-gray-100 dark:divide-gray-700 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-4 py-4">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Foto profil</dt>
                            <dd class="text-right text-sm font-medium text-gray-900 dark:text-white">{{ $user->avatar ? 'Sudah diatur' : 'Belum diatur' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-4">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Bergabung</dt>
                            <dd class="text-right text-sm font-medium text-gray-900 dark:text-white">{{ $user->created_at?->format('d M Y') ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20">
                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200">Informasi akun Anda</p>
                        <p class="mt-1 text-xs leading-5 text-blue-800/80 dark:text-blue-300">Perubahan nama, email, dan foto profil akan digunakan di seluruh dashboard Stockify.</p>
                    </div>
                </div>
            </aside>
        </div>

        {{-- Pengaturan keamanan: ganti kata sandi dan pantau sesi aktif. --}}
        <section class="mt-8 space-y-6" aria-labelledby="security-heading">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Perlindungan akun</p>
                <h2 id="security-heading" class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Keamanan</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Ganti kata sandi dan atur sesi yang masih terhubung ke akun Anda.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                {{-- Perubahan kata sandi meminta kata sandi lama sebagai verifikasi. --}}
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6" aria-labelledby="password-heading">
                    <div class="mb-5">
                        <h3 id="password-heading" class="text-base font-semibold text-gray-900 dark:text-white">Ganti kata sandi</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masukkan kata sandi saat ini untuk mengonfirmasi perubahan.</p>
                    </div>
                    <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
                        @csrf
                        @method('patch')
                        <div>
                            <label for="current_password" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kata sandi saat ini</label>
                            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('current_password') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kata sandi baru</label>
                            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('password') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi kata sandi baru</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">Perbarui kata sandi</button>
                    </form>
                </section>

                {{-- Sesi diambil dari penyimpanan database agar perangkat lain bisa diakhiri. --}}
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6" aria-labelledby="sessions-heading">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 id="sessions-heading" class="text-base font-semibold text-gray-900 dark:text-white">Sesi aktif</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Periksa perangkat yang menggunakan akun Anda.</p>
                        </div>
                        @if ($sessions->contains(fn ($session) => $session->id !== request()->session()->getId()))
                            <form method="POST" action="{{ route('profile.sessions.destroy-others') }}" onsubmit="return confirm('Akhiri semua sesi lain dari akun ini?')">
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Akhiri sesi lain</button>
                            </form>
                        @endif
                    </div>
                    <div class="space-y-3">
                        @forelse ($sessions as $session)
                            <div class="flex items-start justify-between gap-4 rounded-xl border border-gray-100 p-4 dark:border-gray-700">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $session->ip_address ?: 'Alamat IP tidak tersedia' }}</p>
                                        @if ($session->id === request()->session()->getId())
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">Perangkat ini</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 break-words text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($session->user_agent ?: 'Informasi perangkat tidak tersedia', 100) }}</p>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Aktif {{ \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}</p>
                                </div>
                                @if ($session->id !== request()->session()->getId())
                                    <form method="POST" action="{{ route('profile.sessions.destroy', $session->id) }}" onsubmit="return confirm('Akhiri sesi ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="shrink-0 text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Akhiri</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">Belum ada sesi aktif yang tercatat.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>

        {{-- Riwayat aktivitas memudahkan pengguna meninjau perubahan penting pada akun. --}}
        <section class="mt-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6" aria-labelledby="activity-heading">
            <div class="mb-5">
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Riwayat akun</p>
                <h2 id="activity-heading" class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Aktivitas akun</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Riwayat perubahan profil dan aktivitas penting pengguna.</p>
            </div>
            <ol class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($activities as $activity)
                    <li class="flex flex-col gap-1 py-4 first:pt-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity->description }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $activity->ip_address ?: 'Alamat IP tidak tersedia' }}</p>
                        </div>
                        <time class="shrink-0 text-xs text-gray-500 dark:text-gray-400" datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('d M Y, H:i') }}</time>
                    </li>
                @empty
                    <li class="py-4 text-sm text-gray-500 dark:text-gray-400">Belum ada aktivitas akun yang tercatat.</li>
                @endforelse
            </ol>
        </section>
</div>

{{-- Modal crop memastikan foto avatar berbentuk persegi sebelum dipotong menjadi lingkaran. --}}
<div id="avatar-crop-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-950/75 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="avatar-crop-title" aria-hidden="true">
    <div class="mx-auto my-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-gray-700 dark:bg-gray-800 sm:my-8 sm:p-6">
        <div class="mb-5">
            <h2 id="avatar-crop-title" class="text-lg font-semibold text-gray-900 dark:text-white">Sesuaikan foto profil</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Geser foto untuk mengatur posisi, lalu gunakan slider untuk memperbesar.</p>
        </div>

        <div class="mx-auto h-72 w-72 max-w-full overflow-hidden rounded-full bg-gray-100 ring-4 ring-blue-100 dark:bg-gray-900 dark:ring-blue-900/60">
            <canvas id="avatar-crop-canvas" width="640" height="640" class="h-full w-full touch-none cursor-grab active:cursor-grabbing" aria-label="Area pemotongan foto, seret untuk mengatur posisi"></canvas>
        </div>

        <label for="avatar-zoom" class="mt-6 flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300">
            <span>Perbesar foto</span>
            <span id="avatar-zoom-value">100%</span>
        </label>
        <input id="avatar-zoom" type="range" min="1" max="3" step="0.01" value="1" class="mt-3 h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 accent-blue-700 dark:bg-gray-700">
        <p id="avatar-crop-error" class="mt-3 hidden text-sm text-red-600 dark:text-red-400" role="alert"></p>

        <div class="mt-6 flex justify-end gap-3">
            <button id="avatar-crop-cancel" type="button" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Batal</button>
            <button id="avatar-crop-save" type="button" class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Gunakan foto
            </button>
        </div>
    </div>
</div>

<script>
    (() => {
        // Hubungkan tombol edit dan batal dengan ringkasan informasi pribadi.
        const personalInfoEdit = document.getElementById('personal-info-edit');
        const personalInfoCancel = document.getElementById('personal-info-cancel');
        const personalInfoSummary = document.getElementById('personal-info-summary');
        const personalInfoForm = document.getElementById('personal-info-form');

        personalInfoEdit.addEventListener('click', () => {
            personalInfoSummary.classList.add('hidden');
            personalInfoForm.classList.remove('hidden');
            personalInfoEdit.classList.add('hidden');
            document.getElementById('name').focus();
        });

        personalInfoCancel.addEventListener('click', () => {
            personalInfoForm.reset();
            personalInfoForm.classList.add('hidden');
            personalInfoSummary.classList.remove('hidden');
            personalInfoEdit.classList.remove('hidden');
        });

        // Elemen untuk memilih gambar, menampilkan pratinjau, dan mengirim hasil crop.
        const input = document.getElementById('avatar');
        const avatarForm = document.getElementById('avatar-form');
        const preview = document.getElementById('avatar-preview');
        const placeholder = document.getElementById('avatar-placeholder');
        const modal = document.getElementById('avatar-crop-modal');
        const canvas = document.getElementById('avatar-crop-canvas');
        const context = canvas.getContext('2d');
        const zoomInput = document.getElementById('avatar-zoom');
        const zoomValue = document.getElementById('avatar-zoom-value');
        const cropError = document.getElementById('avatar-crop-error');
        const cancelButton = document.getElementById('avatar-crop-cancel');
        const saveButton = document.getElementById('avatar-crop-save');
        const cropSize = canvas.width;
        let sourceImage = null;
        let pendingFile = null;
        let pendingUrl = null;
        let zoom = 1;
        let offsetX = 0;
        let offsetY = 0;
        let dragPosition = null;

        // Gambar ulang foto ke kanvas sambil menjaga area crop tetap terisi.
        const drawCrop = () => {
            if (!sourceImage) return;

            const scale = Math.max(cropSize / sourceImage.naturalWidth, cropSize / sourceImage.naturalHeight) * zoom;
            const width = sourceImage.naturalWidth * scale;
            const height = sourceImage.naturalHeight * scale;
            const minX = Math.min(0, cropSize - width);
            const minY = Math.min(0, cropSize - height);
            offsetX = Math.max(minX, Math.min(0, offsetX));
            offsetY = Math.max(minY, Math.min(0, offsetY));

            context.clearRect(0, 0, cropSize, cropSize);
            context.drawImage(sourceImage, (cropSize - width) / 2 + offsetX, (cropSize - height) / 2 + offsetY, width, height);
        };

        // Tutup modal dan lepaskan URL sementara agar memori browser tidak bocor.
        const closeCropper = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
            sourceImage = null;
            dragPosition = null;
            if (pendingUrl) URL.revokeObjectURL(pendingUrl);
            pendingUrl = null;
            pendingFile = null;
        };

        const cancelCrop = () => {
            closeCropper();
        };

        // Validasi file lokal sebelum membuka alat pemotong foto.
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;

            input.value = '';
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
                cropError.textContent = 'Pilih gambar JPG, PNG, atau WebP dengan ukuran maksimal 2 MB.';
                cropError.classList.remove('hidden');
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                return;
            }

            pendingFile = file;
            pendingUrl = URL.createObjectURL(file);
            sourceImage = new Image();
            sourceImage.onload = () => {
                zoom = 1;
                offsetX = 0;
                offsetY = 0;
                zoomInput.value = '1';
                zoomValue.textContent = '100%';
                cropError.classList.add('hidden');
                drawCrop();
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                cancelButton.focus();
            };
            sourceImage.onerror = () => {
                cropError.textContent = 'Gambar tidak dapat dibuka. Silakan pilih file gambar lain.';
                cropError.classList.remove('hidden');
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
            };
            sourceImage.src = pendingUrl;
        });

        zoomInput.addEventListener('input', () => {
            zoom = Number(zoomInput.value);
            zoomValue.textContent = `${Math.round(zoom * 100)}%`;
            drawCrop();
        });

        // Izinkan foto digeser dengan mouse atau sentuhan di dalam area crop.
        canvas.addEventListener('pointerdown', (event) => {
            if (!sourceImage) return;
            canvas.setPointerCapture(event.pointerId);
            dragPosition = { x: event.clientX, y: event.clientY };
        });

        canvas.addEventListener('pointermove', (event) => {
            if (!dragPosition) return;
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            offsetX += (event.clientX - dragPosition.x) * scaleX;
            offsetY += (event.clientY - dragPosition.y) * scaleY;
            dragPosition = { x: event.clientX, y: event.clientY };
            drawCrop();
        });

        canvas.addEventListener('pointerup', () => {
            dragPosition = null;
        });
        canvas.addEventListener('pointercancel', () => {
            dragPosition = null;
        });

        cancelButton.addEventListener('click', cancelCrop);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) cancelCrop();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) cancelCrop();
        });

        // Hasil crop dibuat sebagai JPEG dan langsung dikirim melalui form avatar.
        saveButton.addEventListener('click', () => {
            if (!sourceImage || !pendingFile) return;

            const outputCanvas = document.createElement('canvas');
            outputCanvas.width = 512;
            outputCanvas.height = 512;
            outputCanvas.getContext('2d').drawImage(canvas, 0, 0, 512, 512);
            outputCanvas.toBlob((blob) => {
                if (!blob) {
                    cropError.textContent = 'Foto gagal diproses. Silakan coba gambar lain.';
                    cropError.classList.remove('hidden');
                    return;
                }

                const baseName = pendingFile.name.replace(/\.[^.]+$/, '');
                const croppedFile = new File([blob], `${baseName}-cropped.jpg`, { type: 'image/jpeg' });

                const files = new DataTransfer();
                files.items.add(croppedFile);
                input.files = files.files;
                preview.src = URL.createObjectURL(croppedFile);
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                closeCropper();
                avatarForm.submit();
            }, 'image/jpeg', 0.9);
        });
    })();
</script>
@endsection
