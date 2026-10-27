{{-- Form Admin untuk mengubah nama aplikasi dan logo yang dipakai bersama. --}}
@extends('layouts.dashboard')

@section('content')
<div class="mx-auto min-h-screen max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6">
        <p class="text-sm font-medium text-blue-700 dark:text-blue-400">Administrasi</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Pengaturan Umum</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Atur nama dan logo yang digunakan pada aplikasi.</p>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300" role="alert">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="max-w-3xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700 sm:px-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Identitas Aplikasi</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Perubahan akan terlihat pada navbar, halaman masuk, dan judul browser.</p>
        </div>

        <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6 p-5 sm:p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="app_name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Nama aplikasi</label>
                <input id="app_name" name="app_name" type="text" maxlength="100" required value="{{ old('app_name', $settings->app_name) }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nama ini akan digunakan di bagian identitas aplikasi.</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Logo aplikasi</label>
                <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700/50 sm:flex-row sm:items-center">
                    <img src="{{ $settings->logo_path ? asset('storage/' . $settings->logo_path) : asset('static/images/stockify-logo.jpg') }}" alt="Logo {{ $settings->app_name }}" class="h-20 w-20 rounded-xl border border-gray-200 bg-white object-contain p-1 dark:border-gray-600">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $settings->logo_path ? 'Logo khusus saat ini' : 'Logo bawaan Stockify' }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilih JPG, PNG, atau WebP. Maksimal 2 MB. Logo lama akan diganti setelah pengaturan disimpan.</p>
                        <label for="logo" class="sr-only">Unggah logo aplikasi</label>
                        <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-3 block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-700 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-blue-800 dark:text-gray-200">
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-100 pt-5 dark:border-gray-700">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
