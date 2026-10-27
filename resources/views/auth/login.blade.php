{{-- Halaman mandiri untuk autentikasi pengguna sebelum membuka dashboard. --}}
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - {{ $applicationSettings->app_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 dark:bg-gray-900">
    <main class="login-card w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-8">
        <a href="{{ route('login') }}" class="flex items-center gap-3 text-xl font-semibold text-gray-900 dark:text-white">
            <img src="{{ $applicationSettings->logo_path ? asset('storage/' . $applicationSettings->logo_path) : asset('static/images/stockify-logo.jpg') }}" alt="Logo {{ $applicationSettings->app_name }}" class="h-8 w-8 rounded-full object-cover">
            {{ $applicationSettings->app_name }}
        </a>
        <h1 class="mt-8 text-2xl font-semibold text-gray-900 dark:text-white">Masuk ke akun</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Gunakan email dan kata sandi akun {{ $applicationSettings->app_name }} Anda.</p>

        {{-- Pesan validasi ditampilkan tanpa mengungkap alasan autentikasi secara berlebihan. --}}
        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Form memakai POST dan token CSRF agar kredensial dikirim dengan aman. --}}
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-3 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Ingat saya
            </label>
            <button type="submit" class="w-full rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">Masuk</button>
        </form>
    </main>
</body>
</html>
