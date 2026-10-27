@extends('layouts.dashboard')

@section('content')
{{-- Pencatatan hasil stok fisik, selisih stok, pencarian, dan filter tanggal. --}}
<div class="px-4 mx-auto max-w-screen-2xl py-6 min-h-screen">
    
    <!-- ALERT SUCCESS -->
    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-300" role="alert">
            <span class="font-medium">Berhasil!</span> {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-300 border border-red-300" role="alert">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        
        {{-- Header, Pencarian, Filter Tanggal, & Tombol Tambah --}}
        <div class="flex flex-col px-4 py-3 space-y-3 lg:flex-row lg:items-center lg:justify-between lg:space-y-0 lg:space-x-4 border-b dark:border-gray-700">
            <div class="flex items-center flex-1 space-x-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Stock Opname</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pengajuan staf perlu disetujui Admin atau Manajer sebelum stok diperbarui.</p>
                </div>
            </div>
        </div>

        {{-- Form Filter & Search --}}
        <form method="GET" action="{{ route('stock-opnames.index') }}" class="flex flex-col lg:flex-row items-center justify-between p-4 space-y-3 lg:space-y-0 lg:space-x-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <div class="flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-3 w-full lg:w-auto">
                <!-- Input Pencarian -->
                <div class="w-full sm:w-auto">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari produk / catatan..." class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                </div>

                <!-- Filter Tanggal Mulai & Akhir -->
                <div class="flex items-center space-x-2 w-full sm:w-auto">
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <span class="text-gray-500 text-sm">s/d</span>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <!-- Tombol Filter & Reset -->
                <div class="flex space-x-2 w-full sm:w-auto">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-850 focus:ring-4 focus:ring-blue-300">
                        Filter
                    </button>
                    <a href="{{ route('stock-opnames.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 text-center">Reset</a>
                </div>
            </div>

            <!-- Tombol Tambah Modal -->
            <div class="w-full lg:w-auto flex justify-end">
                <button data-modal-target="add-opname-modal" data-modal-toggle="add-opname-modal" type="button" class="w-full lg:w-auto px-4 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                    + Input Stock Opname
                </button>
            </div>
        </form>

        {{-- Tabel Data Stock Opname --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 w-16">No</th>
                        <th class="px-4 py-3">Tanggal / Waktu</th>
                        <th class="px-4 py-3">Nama Produk</th>
                        <th class="px-4 py-3">Stok Sistem</th>
                        <th class="px-4 py-3">Stok Fisik</th>
                        <th class="px-4 py-3">Selisih</th>
                        <th class="px-4 py-3">Catatan Petugas</th>
                        <th class="px-4 py-3">Petugas</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Pemeriksa</th>
                        @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER))
                            <th class="px-4 py-3">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockOpnames as $index => $opname)
                        <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $opname->created_at }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $opname->product_name ?? 'Produk Dihapus' }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $opname->system_stock }}</td>
                            <td class="px-4 py-3 font-medium text-blue-600 dark:text-blue-400">{{ $opname->physical_stock }}</td>
                            <td class="px-4 py-3 font-bold {{ $opname->difference < 0 ? 'text-red-600' : ($opname->difference > 0 ? 'text-green-600' : 'text-gray-500') }}">
                                {{ $opname->difference > 0 ? '+' . $opname->difference : $opname->difference }}
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $opname->notes ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $opname->user_name ?? 'Tidak tercatat' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Menunggu Persetujuan',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$opname->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statusLabels[$opname->status] ?? $opname->status }}
                                </span>
                                @if ($opname->review_notes)
                                    <p class="mt-1 max-w-xs text-xs text-gray-500 dark:text-gray-400">{{ $opname->review_notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">
                                {{ $opname->reviewer_name ?? '-' }}
                                @if ($opname->reviewed_at)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($opname->reviewed_at)->format('d M Y, H:i') }}</p>
                                @endif
                            </td>
                            @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER))
                            <td class="px-4 py-3">
                                @if ($opname->status === 'pending' && (int) $opname->user_id !== (int) auth()->id())
                                    <form action="{{ route('stock-opnames.approve', $opname->id) }}" method="POST" class="mb-2 space-y-2">
                                        @csrf
                                        <input type="text" name="review_notes" maxlength="255" placeholder="Catatan persetujuan (opsional)" class="w-56 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <button type="submit" class="rounded-lg bg-green-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-800">
                                            Setujui & Terapkan
                                        </button>
                                    </form>
                                    <form action="{{ route('stock-opnames.reject', $opname->id) }}" method="POST" class="mb-2 space-y-2">
                                        @csrf
                                        <input type="text" name="review_notes" maxlength="255" required placeholder="Alasan penolakan (wajib)" class="w-56 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <button type="submit" class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700">
                                            Tolak
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('stock-opnames.destroy', $opname->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus riwayat stock opname ini?');" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER) ? 11 : 10 }}" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                Belum ada data stock opname yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH STOCK OPNAME --}}
<div id="add-opname-modal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 dark:bg-opacity-80">
    <div class="flex justify-center items-center min-h-full p-4 w-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700" style="width: 700px !important; max-width: 95vw !important;">
            
            <div class="flex items-center justify-between p-4 border-b dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Input Stock Opname</h3>
                <button type="button" data-modal-hide="add-opname-modal" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>

            <form action="{{ route('stock-opnames.store') }}" method="POST" class="p-6 space-y-6">
                @csrf
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pilih Produk</label>
                    <select name="product_id" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} (Stok Sistem: {{ $product->stock }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jumlah Stok Fisik (Hasil Hitung di Rak)</label>
                    <input type="number" name="physical_stock" min="0" required placeholder="Masukkan jumlah fisik saat ini" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan / Alasan Selisih (Opsional)</label>
                    <textarea name="notes" rows="2" placeholder="Contoh: Barang rusak 2 pcs, salah hitung, dll..." class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"></textarea>
                </div>

                <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Ajukan untuk Persetujuan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection