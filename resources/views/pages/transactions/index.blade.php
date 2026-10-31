@extends('layouts.dashboard')

@section('content')
{{-- Riwayat barang masuk dan keluar dengan filter serta formulir pencatatan. --}}
    <div class="px-4 mx-auto max-w-screen-2xl py-6 min-h-screen">
        <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg p-4">
            
            <!-- ALERT / PESAN PERINGATAN ERROR & SUCCESS -->
            @if(session('error'))
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-300" role="alert">
                    <span class="font-medium">Perhatian!</span> {{ session('error') }}
                </div>
            @endif

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

            <!-- Bagian Header & Tombol Aksi -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                <div>
                    <h5 class="text-lg font-semibold dark:text-white">Riwayat Transaksi Stok & Perusahaan</h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tugas dan transaksi stok: {{ $totalTransactions ?? 0 }}</p>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Tombol Cetak / Print Laporan -->
                    <button type="button" onclick="window.print()" class="px-4 py-2 text-sm font-medium flex items-center gap-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2a2 2 0 012 2v0a2 2 0 012 2v0a2 2 0 012-2h2a2 2 0 002-2v-6a2 2 0 00-1-1.73M13 7h8l2 2h-6zM7 12H5a2 2 0 01-2-2v-4a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2h-2M13 7l2-2M7 12l2 2"/></svg>
                        Cetak Laporan
                    </button>

                    @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER))
                        <button data-modal-target="transaction-modal" data-modal-toggle="transaction-modal" type="button" class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                            + Buat Tugas Transaksi
                        </button>
                    @endif
                </div>
            </div>
            <p class="mb-4 rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-900/30 dark:text-blue-200">
                Tugas dibuat oleh Admin/Manajer dan diberikan kepada Staff. Stok baru berubah setelah Staff memeriksa barang dan mengonfirmasi tugas.
            </p>

            <!-- Tab Navigasi (Semua, Barang Masuk, Barang Keluar) -->
            <div class="flex flex-wrap gap-2 mb-4 border-b border-gray-200 dark:border-gray-700 pb-3">
                <a href="{{ route('transactions.index', array_merge(request()->except(['type', 'page']), ['type' => ''])) }}" 
                   class="px-4 py-2 text-sm font-medium rounded-lg {{ request('type') == '' ? 'bg-blue-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' }}">
                    Semua Transaksi
                </a>
                <a href="{{ route('transactions.index', array_merge(request()->except(['type', 'page']), ['type' => 'in'])) }}" 
                   class="px-4 py-2 text-sm font-medium rounded-lg {{ request('type') == 'in' ? 'bg-green-700 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-900/40 dark:text-green-300 dark:hover:bg-green-900' }}">
                    Barang Masuk
                </a>
                <a href="{{ route('transactions.index', array_merge(request()->except(['type', 'page']), ['type' => 'out'])) }}" 
                   class="px-4 py-2 text-sm font-medium rounded-lg {{ request('type') == 'out' ? 'bg-red-700 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900' }}">
                    Barang Keluar
                </a>
            </div>

            <!-- Form Filter Pencarian Nama Produk & Rentang Tanggal -->
            <form action="{{ route('transactions.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Cari Produk</label>
                    <input type="text" name="search" autocomplete="off" value="{{ request('search') }}" placeholder="Nama / Kode produk..." class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Filter</button>
                    <a href="{{ route('transactions.index', request('type') ? ['type' => request('type')] : []) }}" class="px-3 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500 text-center">Reset</a>
                </div>
            </form>

            <!-- Tabel Riwayat Transaksi -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">Kode Barang</th>
                            <th class="px-4 py-3">Tanggal & Waktu</th>
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3">Perusahaan / Supplier</th>
                            <th class="px-4 py-3">Jenis Transaksi</th>
                            <th class="px-4 py-3">Jumlah</th>
                            <th class="px-4 py-3">Sisa Stok</th>
                            <th class="px-4 py-3">Dicatat oleh</th>
                            <th class="px-4 py-3">Ditugaskan kepada</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Catatan</th>
                            @if (auth()->user()->hasRole(\App\Models\User::ROLE_STAFF))
                                <th class="px-4 py-3">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $index => $trx)
                            @php
                                $supplierName = '-';
                                if ($trx->product && $trx->product->supplier_id) {
                                    $sup = \Illuminate\Support\Facades\DB::table('suppliers')->where('id', $trx->product->supplier_id)->first();
                                    $supplierName = $sup ? $sup->name : '-';
                                }
                            @endphp
                            <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $index % 2 === 0 ? 'bg-gray-50 dark:bg-gray-800/30' : '' }}">
                                <td class="px-4 py-2">{{ $transactions->firstItem() + $index }}</td>
                                <td class="px-4 py-2 font-mono text-gray-900 dark:text-white">{{ $trx->product->product_code ?? '-' }}</td>
                                <td class="px-4 py-2">{{ \Illuminate\Support\Carbon::parse($trx->confirmed_at ?? $trx->created_at)->format('d M Y, H:i') }}</td>
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">{{ $trx->product->name ?? 'Produk Dihapus' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-medium">{{ $supplierName }}</td>
                                <td class="px-4 py-2">
                                    @if($trx->type == 'in')
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Barang Masuk</span>
                                    @else
                                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Barang Keluar</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 font-semibold text-gray-900 dark:text-white">{{ $trx->quantity }}</td>
                                <td class="px-4 py-2 font-bold text-blue-600 dark:text-blue-400">{{ $trx->status === 'completed' ? ($trx->current_stock ?? '-') : 'Belum diterapkan' }}</td>
                                <td class="px-4 py-2">{{ $trx->user->name ?? 'Pengguna tidak diketahui' }}</td>
                                <td class="px-4 py-2">{{ $trx->assignedTo->name ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    @if ($trx->status === 'pending')
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Menunggu konfirmasi Staff</span>
                                    @elseif ($trx->confirmer)
                                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-300">Dikonfirmasi oleh {{ $trx->confirmer->name }}</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">Riwayat transaksi sebelumnya</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $trx->notes ?? '-' }}</td>
                                @if (auth()->user()->hasRole(\App\Models\User::ROLE_STAFF))
                                    <td class="px-4 py-2">
                                        @if ($trx->status === 'pending' && (int) $trx->assigned_to === (int) auth()->id())
                                            <form method="POST" action="{{ route('transactions.confirm', $trx->id) }}">
                                                @csrf
                                                <button type="button" data-transaction-confirmation class="rounded-lg bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-800">Konfirmasi</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400">-</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->hasRole(\App\Models\User::ROLE_STAFF) ? 13 : 12 }}" class="text-center py-6 text-gray-500 dark:text-gray-400">Belum ada data riwayat transaksi stok yang ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Navigasi Paginasi -->
            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

    @if (auth()->user()->hasRole(\App\Models\User::ROLE_STAFF))
        @include('components.transaction-confirmation-modal')
    @endif

    @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER))
    <!-- Modal pembuatan tugas transaksi untuk Staff -->
    <div id="transaction-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-4xl max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Buat Tugas Transaksi</h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="transaction-modal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('transactions.store') }}" method="POST" class="p-6">
                    @csrf
                    <div class="grid gap-4 mb-4">
                        <div>
                            <label for="assigned_to" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tugaskan kepada Staff</label>
                            <select id="assigned_to" name="assigned_to" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-3 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                <option value="">Pilih Staff Gudang</option>
                                @foreach ($staffMembers as $staffMember)
                                    <option value="{{ $staffMember->id }}">{{ $staffMember->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="relative">
                            <label for="input_product_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pilih Produk</label>
                            <input type="hidden" name="product_id" id="product_id_hidden">

                            <input id="input_product_name" required autocomplete="off" aria-autocomplete="list" aria-controls="list-produk" aria-expanded="false"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-3 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                placeholder="Ketik kode, nama produk, atau perusahaan..."
                                oninput="filterProducts(this.value)"
                                onfocus="showProductOptions()">

                            <div id="list-produk" role="listbox" class="absolute z-20 mt-1 hidden max-h-72 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-600 dark:bg-gray-800">
                                @foreach($products as $p)
                                    @php
                                        $supName = 'Tanpa Supplier';
                                        if ($p->supplier_id) {
                                            $s = \Illuminate\Support\Facades\DB::table('suppliers')->where('id', $p->supplier_id)->first();
                                            if ($s) {
                                                $supName = $s->name;
                                            }
                                        }
                                        $codePrefix = !empty($p->product_code) ? '[' . $p->product_code . '] ' : '';
                                        $displayText = $codePrefix . $p->name . ' (' . $supName . ')';
                                        $searchText = $codePrefix . $p->name . ' ' . $supName;
                                    @endphp
                                    <button type="button" role="option" aria-selected="false" data-id="{{ $p->id }}" data-display="{{ $displayText }}" data-search="{{ \Illuminate\Support\Str::lower($searchText) }}" onclick="selectProduct(this)" class="flex w-full items-center justify-between gap-4 rounded-md px-3 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:bg-gray-700">
                                        <span class="min-w-0">
                                            <span class="block break-words font-medium text-gray-900 dark:text-white">{{ $displayText }}</span>
                                        </span>
                                        <span class="shrink-0 whitespace-nowrap rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">Stok: {{ number_format($p->stock, 0, ',', '.') }}</span>
                                    </button>
                                @endforeach
                                <p id="no-product-match" class="hidden px-3 py-3 text-sm text-gray-500 dark:text-gray-400">Produk tidak ditemukan.</p>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilih barang yang harus diterima atau disiapkan oleh Staff. Stok saat ini ditampilkan pada setiap produk.</p>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jenis Transaksi</label>
                            <select name="type" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-3 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                <option value="in">Barang Masuk (Tambah Stok)</option>
                                <option value="out">Barang Keluar (Kurangi Stok)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Jumlah</label>
                            <input type="number" name="quantity" min="1" required autocomplete="off" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-3 dark:bg-gray-600 dark:border-gray-500 dark:text-white" placeholder="Masukkan jumlah barang">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan (Opsional)</label>
                            <textarea name="notes" rows="3" autocomplete="off" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-3 dark:bg-gray-600 dark:border-gray-500 dark:text-white" placeholder="Keterangan transaksi..."></textarea>
                        </div>
                    </div>
                    <p class="mb-4 text-sm text-amber-700 dark:text-amber-300">Stok tidak berubah sampai Staff mengonfirmasi tugas ini.</p>
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Buat Tugas untuk Staff</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Script untuk pencarian dan pemilihan produk beserta stoknya. -->
    <script>
        const productInput = document.getElementById('input_product_name');
        const productList = document.getElementById('list-produk');
        const productOptions = Array.from(productList.querySelectorAll('[role="option"]'));
        const noProductMatch = document.getElementById('no-product-match');

        function showProductOptions() {
            productList.classList.remove('hidden');
            productInput.setAttribute('aria-expanded', 'true');
        }

        function filterProducts(value) {
            const search = value.trim().toLocaleLowerCase();
            let visibleCount = 0;
            document.getElementById('product_id_hidden').value = '';

            productOptions.forEach((option) => {
                const matches = option.dataset.search.includes(search);
                option.classList.toggle('hidden', !matches);
                option.setAttribute('aria-selected', 'false');
                visibleCount += matches ? 1 : 0;
            });

            noProductMatch.classList.toggle('hidden', visibleCount > 0);
            showProductOptions();
        }

        function selectProduct(option) {
            productInput.value = option.dataset.display;
            document.getElementById('product_id_hidden').value = option.dataset.id;
            productList.classList.add('hidden');
            productInput.setAttribute('aria-expanded', 'false');
        }

        document.addEventListener('click', (event) => {
            if (!productList.contains(event.target) && event.target !== productInput) {
                productList.classList.add('hidden');
                productInput.setAttribute('aria-expanded', 'false');
            }
        });

        productInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                productList.classList.add('hidden');
                productInput.setAttribute('aria-expanded', 'false');
            }
        });
    </script>

    <!-- CSS khusus untuk cetak laporan -->
    <style>
    @media print {
        nav, aside, header, footer, form, button, .flex.flex-wrap, .modal, script {
            display: none !important;
        }
        body, html {
            background: white !important;
            color: black !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .max-w-screen-2xl, .bg-white, .shadow-md {
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        th, td {
            border: 1px solid #ccc !important;
            padding: 8px !important;
            color: black !important;
        }
        th {
            background-color: #f2f2f2 !important;
            -webkit-print-color-adjust: exact;
        }
    }
    </style>
@endsection