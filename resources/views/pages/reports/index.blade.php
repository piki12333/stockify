{{-- Menampilkan laporan stok, transaksi, atau aktivitas sesuai filter pengguna. --}}
@extends('layouts.dashboard')

@section('content')
<div class="mx-auto min-h-screen max-w-screen-2xl px-4 py-7 sm:px-6 lg:px-8">
    <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-blue-700 dark:text-blue-400">Ringkasan dan evaluasi</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Laporan</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tinjau posisi stok, pergerakan barang, dan aktivitas akun berdasarkan periode.</p>
        </div>
        <div class="flex gap-2 print:hidden">
            <a href="{{ route('reports.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 17v3h14v-3"/></svg>
                Unduh CSV
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                Cetak
            </button>
        </div>
    </header>

    <nav class="mb-5 flex flex-wrap gap-2 print:hidden" aria-label="Jenis laporan">
        @php
            $reportTypes = [
                'stock' => 'Laporan Stok',
                'transactions' => 'Barang Masuk & Keluar',
            ];
            if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER)) {
                $reportTypes['activities'] = 'Aktivitas Pengguna';
            }
        @endphp
        @foreach ($reportTypes as $key => $label)
            <a href="{{ route('reports.index', array_merge(request()->except(['report', 'page']), ['report' => $key])) }}"
               class="rounded-lg px-4 py-2.5 text-sm font-semibold {{ $report === $key ? 'bg-blue-700 text-white dark:bg-blue-600' : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <form action="{{ route('reports.index') }}" method="GET" class="mb-6 grid grid-cols-1 gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-6 print:hidden">
        <input type="hidden" name="report" value="{{ $report }}">
        <div>
            <label for="start_date" class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">Dari tanggal</label>
            <input id="start_date" type="date" name="start_date" value="{{ $filters['start_date'] }}" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label for="end_date" class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">Sampai tanggal</label>
            <input id="end_date" type="date" name="end_date" value="{{ $filters['end_date'] }}" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>
        @if ($report !== 'activities')
            <div>
                <label for="category_id" class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">Kategori</label>
                <select id="category_id" name="category_id" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @if ($report === 'transactions')
            <div>
                <label for="type" class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">Jenis transaksi</label>
                <select id="type" name="type" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Masuk dan keluar</option>
                    <option value="in" @selected($filters['type'] === 'in')>Barang masuk</option>
                    <option value="out" @selected($filters['type'] === 'out')>Barang keluar</option>
                </select>
            </div>
        @endif
        <div class="{{ $report === 'activities' ? 'sm:col-span-2 lg:col-span-3' : '' }}">
            <label for="search" class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-300">{{ $report === 'activities' ? 'Cari pengguna atau aktivitas' : 'Cari produk' }}</label>
            <input id="search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="{{ $report === 'activities' ? 'Nama, email, atau deskripsi' : 'Nama atau kode produk' }}" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">Terapkan</button>
            <a href="{{ route('reports.index', ['report' => $report]) }}" class="rounded-lg bg-gray-100 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">Reset</a>
        </div>
    </form>

    <div id="report-print-area">
    <div class="mb-5 text-sm text-gray-500 dark:text-gray-400">
        Periode: <span class="font-medium text-gray-800 dark:text-gray-200">{{ \Illuminate\Support\Carbon::parse($filters['start_date'])->locale('id')->translatedFormat('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($filters['end_date'])->locale('id')->translatedFormat('d M Y') }}</span>
    </div>

    @if ($summary && $report === 'stock')
        <section id="report-summary-cards" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3 print:hidden" aria-label="Ringkasan laporan stok">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Produk tercatat</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary->product_count, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total stok akhir periode</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary->closing_stock ?? 0, 0, ',', '.') }} unit</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Pergerakan selama periode</p>
                <p class="mt-2 text-lg font-bold text-gray-900 dark:text-white"><span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($summary->stock_in ?? 0, 0, ',', '.') }}</span> / <span class="text-rose-600 dark:text-rose-400">−{{ number_format($summary->stock_out ?? 0, 0, ',', '.') }}</span></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Masuk / keluar (unit)</p>
            </article>
        </section>
    @elseif ($summary && $report === 'transactions')
        <section id="report-summary-cards" class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3 print:hidden" aria-label="Ringkasan laporan transaksi">
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Jumlah transaksi</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($summary->transaction_count ?? 0, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Barang masuk</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($summary->stock_in ?? 0, 0, ',', '.') }} unit</p>
            </article>
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Barang keluar</p>
                <p class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($summary->stock_out ?? 0, 0, ',', '.') }} unit</p>
            </article>
        </section>
    @endif

    @if ($summary && $report === 'stock')
        <div class="print-summary mb-4">
            <p>Produk: {{ number_format($summary->product_count, 0, ',', '.') }}</p>
            <p>Total stok akhir periode: {{ number_format($summary->closing_stock ?? 0, 0, ',', '.') }} unit</p>
            <p>Barang masuk: +{{ number_format($summary->stock_in ?? 0, 0, ',', '.') }} unit</p>
            <p>Barang keluar: −{{ number_format($summary->stock_out ?? 0, 0, ',', '.') }} unit</p>
        </div>
    @elseif ($summary && $report === 'transactions')
        <div class="print-summary mb-4">
            <p>Transaksi: {{ number_format($summary->transaction_count ?? 0, 0, ',', '.') }}</p>
            <p>Barang masuk: {{ number_format($summary->stock_in ?? 0, 0, ',', '.') }} unit</p>
            <p>Barang keluar: {{ number_format($summary->stock_out ?? 0, 0, ',', '.') }} unit</p>
        </div>
    @endif

    <section id="report-table" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                @if ($report === 'stock')
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <tr><th class="px-4 py-3">Produk</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3 text-right">Stok awal</th><th class="px-4 py-3 text-right">Masuk</th><th class="px-4 py-3 text-right">Keluar</th><th class="px-4 py-3 text-right">Stok akhir</th><th class="px-4 py-3 text-right">Batas minimum</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($data as $row)
                            <tr><td class="px-4 py-3"><span class="font-medium text-gray-900 dark:text-white">{{ $row->product_name }}</span><span class="block text-xs text-gray-500 dark:text-gray-400">{{ $row->product_code ?: 'Tanpa kode' }}</span></td><td class="px-4 py-3">{{ $row->category_name }}</td><td class="px-4 py-3 text-right">{{ number_format($row->opening_stock, 0, ',', '.') }}</td><td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-400">+{{ number_format($row->stock_in, 0, ',', '.') }}</td><td class="px-4 py-3 text-right text-red-700 dark:text-red-400">−{{ number_format($row->stock_out, 0, ',', '.') }}</td><td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row->closing_stock, 0, ',', '.') }}</td><td class="px-4 py-3 text-right">{{ number_format($row->min_stock, 0, ',', '.') }}</td></tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Tidak ada data produk untuk filter yang dipilih.</td></tr>
                        @endforelse
                    </tbody>
                @elseif ($report === 'transactions')
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <tr><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Produk</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3 text-right">Jumlah</th><th class="px-4 py-3">Dibuat oleh</th><th class="px-4 py-3">Dikonfirmasi oleh</th><th class="px-4 py-3">Catatan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($data as $row)
                            <tr><td class="whitespace-nowrap px-4 py-3">{{ \Illuminate\Support\Carbon::parse($row->activity_at)->locale('id')->translatedFormat('d M Y, H:i') }}</td><td class="px-4 py-3"><span class="font-medium text-gray-900 dark:text-white">{{ $row->product_name }}</span><span class="block text-xs text-gray-500 dark:text-gray-400">{{ $row->product_code ?: 'Tanpa kode' }}</span></td><td class="px-4 py-3">{{ $row->category_name }}</td><td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ in_array($row->type, ['in', 'Barang Masuk'], true) ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' }}">{{ in_array($row->type, ['in', 'Barang Masuk'], true) ? 'Barang masuk' : 'Barang keluar' }}</span></td><td class="px-4 py-3 text-right font-semibold">{{ number_format($row->quantity, 0, ',', '.') }}</td><td class="px-4 py-3">{{ $row->user_name ?: 'Pengguna tidak diketahui' }}</td><td class="px-4 py-3">{{ $row->confirmer_name ?: '—' }}</td><td class="px-4 py-3">{{ $row->notes ?: '—' }}</td></tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Tidak ada transaksi pada periode dan filter ini.</td></tr>
                        @endforelse
                    </tbody>
                @else
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <tr><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Pengguna</th><th class="px-4 py-3">Aktivitas</th><th class="px-4 py-3">Deskripsi</th><th class="px-4 py-3">Alamat IP</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($data as $row)
                            <tr><td class="whitespace-nowrap px-4 py-3">{{ \Illuminate\Support\Carbon::parse($row->created_at)->locale('id')->translatedFormat('d M Y, H:i') }}</td><td class="px-4 py-3"><span class="font-medium text-gray-900 dark:text-white">{{ $row->user_name ?: 'Pengguna dihapus' }}</span><span class="block text-xs text-gray-500 dark:text-gray-400">{{ $row->user_email ?: 'Email tidak tersedia' }}</span></td><td class="px-4 py-3">{{ str_replace('-', ' ', ucfirst($row->event)) }}</td><td class="px-4 py-3">{{ $row->description }}</td><td class="px-4 py-3">{{ $row->ip_address ?: '—' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">Tidak ada aktivitas pengguna pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>
        <div class="report-pagination border-t border-gray-100 px-4 py-3 dark:border-gray-700 print:hidden">
            {{ $data->links() }}
        </div>
    </section>
    </div>
</div>
<style>
    .print-summary {
        display: none;
    }

    @media print {
        #report-summary-cards {
            display: none !important;
        }

        body * {
            visibility: hidden !important;
        }

        #report-print-area,
        #report-print-area * {
            visibility: visible !important;
        }

        body, #main-content {
            background: #fff !important;
            margin: 0 !important;
            overflow: visible !important;
        }

        #main-content {
            position: static !important;
            min-height: 0 !important;
        }

        #report-print-area {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            padding: 0 !important;
            color: #111827 !important;
            background: #fff !important;
        }

        #report-print-area article,
        #report-table {
            color: #111827 !important;
            background: #fff !important;
            border-color: #d1d5db !important;
            box-shadow: none !important;
        }

        #report-print-area p,
        #report-print-area span,
        #report-print-area td,
        #report-print-area th {
            color: #111827 !important;
        }

        #report-table thead {
            background: #f3f4f6 !important;
        }

        #report-table tr {
            break-inside: avoid;
        }

        #report-table .report-pagination {
            display: none !important;
        }

        .print-summary {
            display: block !important;
            color: #111827 !important;
            font-size: 10pt;
        }
    }
</style>
@endsection
