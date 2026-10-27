{{-- Mengelola akun pengguna dan role yang menentukan hak akses di aplikasi. --}}
@extends('layouts.dashboard')

@section('content')
<div class="mx-auto min-h-screen max-w-screen-2xl px-4 py-7 sm:px-6 lg:px-8">
    <header class="mb-6">
        <p class="text-sm font-medium text-blue-700 dark:text-blue-400">Pengaturan akses</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Manajemen Pengguna</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Buat akun dan atur peran Admin, Manajer Gudang, atau Staff Gudang.</p>
    </header>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah pengguna</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat akun baru dan tentukan tingkat aksesnya.</p>
            </div>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Kata sandi minimal 8 karakter</span>
        </div>
        <form method="POST" action="{{ route('users.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            @csrf
            <div>
                <label for="new-name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama</label>
                <input id="new-name" name="name" value="{{ old('name') }}" required maxlength="255" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label for="new-email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input id="new-email" type="email" name="email" value="{{ old('email') }}" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label for="new-password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kata sandi awal</label>
                <input id="new-password" type="password" name="password" required minlength="8" autocomplete="new-password" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label for="new-role" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                <select id="new-role" name="role" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @foreach ($roles as $role => $label)
                        <option value="{{ $role }}" @selected(old('role', \App\Models\User::ROLE_STAFF) === $role)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">Buat akun</button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="user-list-heading">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Akun terdaftar</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola informasi, role, dan kata sandi pengguna.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ number_format($users->total(), 0, ',', '.') }} pengguna</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <tr><th class="px-4 py-3">Pengguna</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Kata sandi baru</th><th class="px-4 py-3">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($users as $user)
                        <tr>
                            <td colspan="4" class="p-0">
                                <form id="update-user-{{ $user->id }}" method="POST" action="{{ route('users.update', $user) }}" class="user-edit-form grid grid-cols-1 gap-3 px-4 py-4 md:grid-cols-2 xl:grid-cols-4">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label for="name-{{ $user->id }}" class="sr-only">Nama</label>
                                        <input id="name-{{ $user->id }}" name="name" value="{{ $user->name }}" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <label for="email-{{ $user->id }}" class="sr-only">Email</label>
                                        <input id="email-{{ $user->id }}" type="email" name="email" value="{{ $user->email }}" required class="mt-2 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label for="role-{{ $user->id }}" class="sr-only">Role</label>
                                        <select id="role-{{ $user->id }}" name="role" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            @foreach ($roles as $role => $label)
                                                <option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="password-{{ $user->id }}" class="sr-only">Kata sandi baru (opsional)</label>
                                        <input id="password-{{ $user->id }}" type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Kata sandi baru (opsional)" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div class="flex flex-wrap items-start gap-2">
                                        <button type="submit" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800 dark:bg-blue-600">Simpan</button>
                                        @if ($user->id !== auth()->id())
                                            <button type="submit" form="delete-user-{{ $user->id }}" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/30">Hapus</button>
                                        @endif
                                    </div>
                                </form>
                                @if ($user->id !== auth()->id())
                                    <form id="delete-user-{{ $user->id }}" method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Hapus akun pengguna ini?')" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada akun pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700">{{ $users->links() }}</div>
    </section>
</div>
@endsection
