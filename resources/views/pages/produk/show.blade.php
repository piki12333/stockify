{{-- Menampilkan informasi lengkap produk, atribut khusus, dan riwayat transaksinya. --}}
@extends('layouts.dashboard')

@section('content')
<div class="mx-auto min-h-screen max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('produk.index') }}" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">← Kembali ke daftar produk</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Detail Produk</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Informasi persediaan dan riwayat transaksi produk.</p>
        </div>
    </div>

    @php
        $lowStock = $product->stock <= $product->min_stock;
    @endphp

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-labelledby="product-heading">
        <div class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_2fr]">
            <div class="flex min-h-56 items-center justify-center overflow-hidden rounded-xl bg-gray-50 dark:bg-gray-700">
                @if ($product->image_url)
                    <img src="{{ asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" class="h-full max-h-80 w-full object-contain">
                @else
                    <div class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
                        <svg class="h-14 w-14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3 16 5-5 4 4 4-6 5 7M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                        </svg>
                        <span class="text-sm">Tidak ada foto produk</span>
                    </div>
                @endif
            </div>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">{{ $product->category_name ?? 'Tanpa kategori' }}</span>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $lowStock ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                        {{ $lowStock ? 'Stok mencapai batas minimum' : 'Stok aman' }}
                    </span>
                </div>
                <p class="mt-4 font-mono text-sm text-gray-500 dark:text-gray-400">{{ $product->product_code ?: 'Tanpa kode produk' }}</p>
                <h2 id="product-heading" class="mt-1 text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">{{ $product->name }}</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $product->description ?: 'Belum ada deskripsi produk.' }}</p>

                <dl class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Supplier</dt>
                        <dd class="mt-1 break-words text-sm font-semibold text-gray-900 dark:text-white">{{ $product->supplier_name ?? 'Tidak diketahui' }}</dd>
                    </div>
                    <div class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20">
                        <dt class="text-xs font-medium text-blue-700 dark:text-blue-300">Stok saat ini</dt>
                        <dd class="mt-1 text-2xl font-bold text-blue-800 dark:text-blue-200">{{ number_format($product->stock, 0, ',', '.') }} <span class="text-sm font-medium">unit</span></dd>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Batas minimum stok</dt>
                        <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($product->min_stock, 0, ',', '.') }} unit</dd>
                    </div>
                    @foreach ($attributes as $attribute)
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $attribute->name }}</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-gray-900 dark:text-white">{{ $attribute->value }}</dd>
                        </div>
                    @endforeach
                    @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Harga beli</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</dd>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700 sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Harga jual</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-labelledby="product-history-heading">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700 sm:px-6">
            <h2 id="product-history-heading" class="text-lg font-semibold text-gray-900 dark:text-white">Riwayat Transaksi Produk</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daftar transaksi stok yang tercatat untuk {{ $product->name }}.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3">Jumlah</th>
                        <th class="px-5 py-3">Sisa stok</th>
                        <th class="px-5 py-3">Dibuat oleh</th>
                        <th class="px-5 py-3">Ditugaskan kepada</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-4">{{ \Illuminate\Support\Carbon::parse($transaction->confirmed_at ?? $transaction->created_at)->format('d M Y, H:i') }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $transaction->type === 'in' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                    {{ $transaction->type === 'in' ? 'Barang masuk' : 'Barang keluar' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-semibold">{{ number_format($transaction->quantity, 0, ',', '.') }} unit</td>
                            <td class="px-5 py-4">{{ $transaction->status === 'completed' ? number_format($transaction->current_stock, 0, ',', '.') . ' unit' : 'Belum diterapkan' }}</td>
                            <td class="px-5 py-4">{{ $transaction->creator_name ?? 'Pengguna tidak diketahui' }}</td>
                            <td class="px-5 py-4">{{ $transaction->assignee_name ?? '—' }}</td>
                            <td class="px-5 py-4">
                                @if ($transaction->status === 'pending')
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Menunggu konfirmasi</span>
                                @elseif ($transaction->confirmer_name)
                                    <span class="text-xs">Dikonfirmasi oleh {{ $transaction->confirmer_name }}</span>
                                @else
                                    <span class="text-xs">Selesai</span>
                                @endif
                            </td>
                            <td class="max-w-xs whitespace-pre-line px-5 py-4">{{ $transaction->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat transaksi untuk produk ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-700">
            {{ $transactions->links() }}
        </div>
    </section>
</div>
@endsection
