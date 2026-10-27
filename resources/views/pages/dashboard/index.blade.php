@extends('layouts.dashboard')

@section('content')
{{-- Dashboard persediaan dengan ringkasan metrik, grafik pergerakan, dan aktivitas terbaru. --}}
<div class="mx-auto min-h-screen max-w-screen-2xl px-4 py-7 sm:px-6 lg:px-8">
    <header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-blue-700 dark:text-blue-400">Ringkasan inventaris</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
                Selamat datang, {{ $user->name }}
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pantau kondisi persediaan dan aktivitas gudang Anda.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>
                </svg>
                {{ now()->translatedFormat('d F Y') }}
            </span>
            <a href="{{ route('transactions.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/>
                </svg>
                {{ $user->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER) ? 'Buat tugas transaksi' : 'Lihat tugas transaksi' }}
            </a>
        </div>
    </header>

    @if ($user->hasRole(\App\Models\User::ROLE_STAFF))
        <section class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm dark:border-amber-800 dark:bg-gray-800" aria-labelledby="assigned-tasks-heading">
            <div class="flex items-center justify-between gap-3 border-b border-amber-100 px-5 py-4 dark:border-amber-900/50 sm:px-6">
                <div>
                    <h2 id="assigned-tasks-heading" class="text-base font-semibold text-gray-900 dark:text-white">Tugas gudang Anda</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Periksa barang dan konfirmasikan transaksi agar stok diperbarui.</p>
                </div>
                <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-amber-100 px-2.5 py-1 text-sm font-semibold text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">{{ $assignedTaskCount }}</span>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($assignedTasks as $task)
                    <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $task->type === 'in' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' }}">
                                    {{ $task->type === 'in' ? 'Periksa barang masuk' : 'Siapkan barang keluar' }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($task->created_at)->format('d M Y, H:i') }}</span>
                            </div>
                            <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $task->product_name }} · {{ number_format($task->quantity, 0, ',', '.') }} unit</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dibuat oleh {{ $task->creator_name ?? 'Pengguna tidak diketahui' }}{{ $task->notes ? ' · ' . $task->notes : '' }}</p>
                        </div>
                        <form method="POST" action="{{ route('transactions.confirm', $task->id) }}">
                            @csrf
                            <button type="button" data-transaction-confirmation class="inline-flex shrink-0 items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                                Konfirmasi tugas
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400 sm:px-6">Tidak ada tugas transaksi yang menunggu konfirmasi.</p>
                @endforelse
            </div>
            @if ($assignedTasks->isNotEmpty())
                <div class="border-t border-gray-100 px-5 py-3 dark:border-gray-700 sm:px-6">
                    <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Lihat semua tugas dan transaksi</a>
                </div>
            @endif
        </section>
    @endif

    @if ($user->hasRole(\App\Models\User::ROLE_STAFF))
        @include('components.transaction-confirmation-modal')
    @endif

    {{-- Tiga metrik utama merangkum jumlah produk, nilai persediaan, dan pergerakan hari ini. --}}
    <section class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3" aria-label="Ringkasan inventaris">
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total produk</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($totalProducts, 0, ',', '.') }}</p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totalCategories, 0, ',', '.') }} kategori terdaftar</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
            </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nilai persediaan</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totalStockUnits, 0, ',', '.') }} unit barang tersedia</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="w-full">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pergerakan stok hari ini</p>
                    <div class="mt-3 flex flex-wrap items-baseline gap-x-5 gap-y-2">
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">+{{ number_format($todayIn, 0, ',', '.') }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">masuk</span></p>
                        <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">-{{ number_format($todayOut, 0, ',', '.') }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">keluar</span></p>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($totalIn, 0, ',', '.') }} unit masuk · {{ number_format($totalOut, 0, ',', '.') }} unit keluar sepanjang waktu</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-900/40 dark:text-violet-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l-4-4m4 4l4-4"/>
                    </svg>
                </span>
            </div>
        </article>
    </section>

    <section class="mb-6 grid grid-cols-1 items-stretch gap-6 xl:grid-cols-5">
        {{-- Grafik menggunakan jumlah transaksi nyata selama tujuh hari terakhir. --}}
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6 xl:col-span-3">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Pergerakan stok</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Barang masuk dan keluar selama 7 hari terakhir</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-indigo-600"></span>Masuk</span>
                    <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Keluar</span>
                </div>
            </div>
            <div
                id="dashboard-stock-chart"
                class="min-h-[280px]"
                data-labels='@json($stockMovementLabels)'
                data-in='@json($stockMovementIn)'
                data-out='@json($stockMovementOut)'
                role="img"
                aria-label="Grafik jumlah barang masuk dan keluar selama tujuh hari terakhir"
            ></div>
        </article>

        {{-- Panel stok minimum memberi jalan cepat untuk memeriksa produk yang perlu diisi ulang. --}}
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6 xl:col-span-2">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Perlu perhatian</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Produk yang mencapai batas minimum stok</p>
                </div>
                <span class="inline-flex min-w-9 items-center justify-center rounded-full bg-rose-50 px-3 py-1 text-sm font-semibold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ $lowStockCount }}</span>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($lowStockProducts as $product)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 p-3 dark:border-gray-700">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</p>
                            <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $product->category_name }} · {{ $product->product_code ?: 'Tanpa kode' }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-semibold text-rose-600 dark:text-rose-400">{{ number_format($product->stock, 0, ',', '.') }} unit</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Min. {{ number_format($product->min_stock, 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="flex min-h-40 flex-col items-center justify-center rounded-xl bg-emerald-50 px-5 text-center dark:bg-emerald-900/20">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <p class="mt-3 text-sm font-medium text-emerald-900 dark:text-emerald-200">Stok aman</p>
                        <p class="mt-1 text-xs text-emerald-800/80 dark:text-emerald-300">Tidak ada produk yang mencapai batas minimum.</p>
                    </div>
                @endforelse
            </div>

            <a href="{{ route('produk.index') }}" class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                Lihat semua produk
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7l7 7-7 7"/>
                </svg>
            </a>
        </article>
    </section>

    @if ($user->hasRole(\App\Models\User::ROLE_ADMIN))
        <section class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-labelledby="recent-account-activities-heading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:px-6">
                <div>
                    <h2 id="recent-account-activities-heading" class="text-base font-semibold text-gray-900 dark:text-white">Aktivitas pengguna terbaru</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aktivitas akun yang tercatat di Stockify</p>
                </div>
                <a href="{{ route('reports.index', ['report' => 'activities']) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Lihat laporan aktivitas</a>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($recentAccountActivities as $activity)
                    <li class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $activity->user_name }} <span class="font-normal text-gray-500 dark:text-gray-400">({{ \App\Models\User::ROLE_LABELS[$activity->user_role] ?? $activity->user_role }})</span></p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $activity->description }}</p>
                            <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $activity->user_email }}</p>
                        </div>
                        <time class="shrink-0 text-xs text-gray-500 dark:text-gray-400" datetime="{{ \Illuminate\Support\Carbon::parse($activity->created_at)->toIso8601String() }}">
                            {{ \Illuminate\Support\Carbon::parse($activity->created_at)->format('d M Y, H:i') }}
                        </time>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400 sm:px-6">Belum ada aktivitas pengguna yang tercatat.</li>
                @endforelse
            </ul>
        </section>
    @endif

    {{-- Tabel aktivitas mengarahkan pengguna ke riwayat lengkap bila diperlukan. --}}
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-labelledby="recent-transactions-heading">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:px-6">
            <div>
                <h2 id="recent-transactions-heading" class="text-base font-semibold text-gray-900 dark:text-white">Transaksi terbaru</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aktivitas barang masuk dan keluar terakhir</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">Lihat semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-5 py-3 font-medium sm:px-6">Waktu</th>
                        <th scope="col" class="px-5 py-3 font-medium">Produk</th>
                        <th scope="col" class="px-5 py-3 font-medium">Supplier</th>
                        <th scope="col" class="px-5 py-3 font-medium">Jenis</th>
                        <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($recentTransactions as $transaction)
                        @php($isIncoming = in_array($transaction->type, ['in', 'Barang Masuk'], true))
                        <tr class="transition hover:bg-gray-50/80 dark:hover:bg-gray-700/30">
                            <td class="whitespace-nowrap px-5 py-4 text-gray-500 dark:text-gray-400 sm:px-6">{{ \Illuminate\Support\Carbon::parse($transaction->activity_at)->format('d M Y, H:i') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-gray-900 dark:text-white">{{ $transaction->product_name }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-500 dark:text-gray-400">{{ $transaction->supplier_name }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $isIncoming ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isIncoming ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    {{ $isIncoming ? 'Barang masuk' : 'Barang keluar' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-gray-900 dark:text-white sm:px-6">{{ number_format($transaction->quantity, 0, ',', '.') }} unit</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada transaksi untuk ditampilkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
