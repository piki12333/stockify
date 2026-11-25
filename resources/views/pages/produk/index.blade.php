@extends('layouts.dashboard')

@section('content')
{{-- Daftar produk, pencarian, filter kategori, serta dialog tambah dan edit produk. --}}
<div class="px-4 mx-auto max-w-screen-2xl py-6 min-h-screen">
    <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">

        <div class="flex flex-col gap-5 border-b border-gray-200 px-4 py-5 dark:border-gray-700 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-blue-700 dark:text-blue-400">Inventaris</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Manajemen Produk</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelola katalog, harga, dan persediaan produk.</p>
                <p class="mt-3 inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    {{ number_format($totalProducts ?? 0, 0, ',', '.') }} produk
                    @if (request()->filled('search') || request()->filled('category_id'))
                        ditemukan
                    @else
                        terdaftar
                    @endif
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">

                @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
                    <a href="{{ route('produk.export') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Ekspor CSV
                    </a>
                    <button id="btn-hapus-banyak" type="button"
                        class="hidden items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Hapus Terpilih (<span id="jumlah-terpilih">0</span>)
                    </button>

                    <form id="form-hapus-banyak" action="{{ route('produk.bulkDestroy') }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="ids" id="input-hapus-ids">
                    </form>
                @endif

                @if (auth()->user()->canManageProducts())
                    <button data-modal-target="crud-modal" data-modal-toggle="crud-modal" onclick="openAddModal()" type="button" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        + Tambah Produk
                    </button>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="mx-4 mt-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300" role="status">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300" role="alert">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300" role="alert">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
            <details class="mx-4 mt-4 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/50">
                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-800 dark:text-gray-200">Impor data produk (CSV)</summary>
                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-600">
                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        Pilih berkas CSV dengan format yang sama seperti hasil ekspor. Kategori dan supplier harus sudah tersedia; maksimal 1.000 produk per impor.
                    </p>
                    <form action="{{ route('produk.import') }}" method="POST" enctype="multipart/form-data" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                        @csrf
                        <label for="products-csv" class="sr-only">Berkas CSV produk</label>
                        <input id="products-csv" type="file" name="file" accept=".csv,text/csv" required
                            class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-700 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-blue-800 dark:text-gray-200">
                        <button type="submit" class="shrink-0 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Impor CSV
                        </button>
                    </form>
                </div>
            </details>
        @endif

        <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-700 sm:px-6">
            <form action="{{ route('produk.index') }}" method="GET" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="w-full lg:max-w-md">
                    <label for="search-products" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Cari produk</label>
                    <input type="search" name="search" id="search-products" value="{{ request('search') }}" autocomplete="off"
                        placeholder="Kode, nama, kategori, atau supplier..."
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="w-full lg:max-w-xs">
                    <label for="filter-category" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Kategori</label>
                    <select name="category_id" id="filter-category"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Cari
                    </button>
                    @if (request()->filled('search') || request()->filled('category_id'))
                        <a href="{{ route('produk.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
            @if (request()->filled('category_id'))
                <a href="{{ route('categories.index') }}" class="mt-3 inline-flex text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                    &larr; Kembali ke kategori
                </a>
            @endif
        </div>

        {{-- Tabel Produk --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
                            <th class="p-4">
                                <input id="checkbox-all" type="checkbox" class="w-4 h-4 rounded border-gray-300 bg-gray-100 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                            </th>
                        @endif
                        <th class="px-4 py-3">Kode Barang</th>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Harga Beli</th>
                        <th class="px-4 py-3">Harga Jual</th>
                        <th class="px-4 py-3">Stock</th>
                        <th class="px-4 py-3">Min Stock</th>
                        @if (auth()->user()->canManageProducts())
                            <th class="px-4 py-3">Aksi</th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @forelse ($products as $product)
                        @php
                            $dotColor = 'bg-green-500';

                            if ($product->stock <= $product->min_stock) {
                                $dotColor = 'bg-red-500';
                            } elseif ($product->stock <= ($product->min_stock + 5)) {
                                $dotColor = 'bg-yellow-500';
                            }
                        @endphp

                        <tr class="product-row border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                            @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
                                <td class="p-4">
                                    <input type="checkbox" value="{{ $product->id }}" class="product-checkbox w-4 h-4 rounded border-gray-300 bg-gray-100 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                                </td>
                            @endif

                            <td class="px-4 py-2 font-mono text-gray-900 dark:text-white">
                                {{ $product->product_code ?? '-' }}
                            </td>

                            <td class="px-4 py-2 min-w-[200px]">
                                <div class="flex items-center">
                                    @if ($product->image_url)
                                        <img src="{{ asset('storage/' . $product->image_url) }}"
                                            alt="{{ $product->name }}"
                                            class="w-12 h-12 mr-3 object-cover rounded">
                                    @else
                                        <div class="w-12 h-12 mr-3 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center text-xs text-gray-500 dark:text-gray-400">
                                            No Img
                                        </div>
                                    @endif

                                    <div>
                                        <a href="{{ route('produk.show', $product->id) }}" class="text-base font-semibold text-blue-700 hover:underline dark:text-blue-400">
                                            {{ $product->name }}
                                        </a>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                            {{ $product->description ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-2">
                                <span class="px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded">
                                    {{ $product->category_name ?? 'Tanpa Kategori' }}
                                </span>
                            </td>

                            <td class="px-4 py-2 text-gray-900 dark:text-white">
                                {{ $product->supplier_name ?? '-' }}
                            </td>

                            <td class="px-4 py-2 text-gray-900 dark:text-white">
                                Rp {{ number_format($product->purchase_price, 0, ',', '.') }}
                            </td>

                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
                                Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                            </td>

                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
                                <div class="flex items-center">
                                    <span class="inline-block w-3 h-3 mr-2 rounded-full {{ $dotColor }}"></span>
                                    {{ $product->stock }}
                                </div>
                            </td>

                            <td class="px-4 py-2 text-gray-900 dark:text-white">
                                {{ $product->min_stock }}
                            </td>

                            @if (auth()->user()->canManageProducts())
                            <td class="px-4 py-2">
                                <div class="flex items-center space-x-3">
                                    <button type="button"
                                        onclick="openEditModal({{ $product->id }})"
                                        class="font-medium text-blue-600 dark:text-blue-500 hover:underline">
                                        Edit
                                    </button>

                                    @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
                                    <form action="{{ route('produk.destroy', $product->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus produk ini?');" class="inline-block">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                            class="font-medium text-red-600 dark:text-red-500 hover:underline">
                                            Hapus
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN) ? 10 : (auth()->user()->canManageProducts() ? 9 : 8) }}" class="px-4 py-4 text-center">
                                @if (request()->filled('search') || request()->filled('category_id'))
                                    Produk tidak ditemukan. Coba ubah kata kunci atau filter.
                                @else
                                    Belum ada data produk di database Stockify.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>

@if (auth()->user()->canManageProducts())
{{-- Modal Tambah Produk --}}
<div id="crud-modal" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 dark:bg-opacity-80">
    <div class="flex justify-center items-center min-h-full p-4">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700 w-full max-w-2xl">

            <div class="flex items-center justify-between p-4 border-b dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Tambah Produk Baru
                </h3>

                <button type="button" onclick="closeAddModal()" data-modal-hide="crud-modal"
                    class="px-3 py-1 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm dark:hover:bg-gray-600 dark:hover:text-white">X</button>
            </div>

            <form action="{{ route('produk.store') }}" method="POST"
                enctype="multipart/form-data" class="p-4 space-y-4">
                @csrf

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kode Barang </label>
                    <input type="text" name="product_code" placeholder="Contoh: BRG-001"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Produk</label>
                    <input type="text" name="name" required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kategori</label>
                        <select name="category_id" id="add_category_id" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                            <option value="" disabled selected hidden></option>
                            @foreach ($categories as $kategori)
                                <option value="{{ $kategori->id }}">
                                    {{ $kategori->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Supplier</label>
                        <select name="supplier_id" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                            <option value="" disabled selected hidden></option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}" data-category-ids="{{ implode(',', $sup->category_ids ?? []) }}">
                                    {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga Beli (Rp)</label>
                        <input type="number" name="purchase_price" min="0" step="0.01" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga Jual (Rp)</label>
                        <input type="number" name="selling_price" min="0" step="0.01" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Stok Awal</label>
                        <input type="number" name="stock" min="0" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Minimum Stok</label>
                        <input type="number" name="min_stock" min="0" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                    </div>
                </div>

                @include('pages.produk.partials.attribute-fields', ['containerId' => 'add-product-attributes'])

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Foto Produk</label>
                    <input type="file" name="image" accept="image/*"
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Deskripsi Produk <span class="font-normal text-gray-500 dark:text-gray-400">(Opsional)</span></label>
                    <textarea name="description" rows="3" placeholder="Tambahkan keterangan produk jika diperlukan"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"></textarea>
                </div>

                <button type="submit"
                    class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Simpan Produk
                </button>
            </form>
        </div>
    </div>
</div>

@endif

@if (auth()->user()->canManageProducts())
{{-- Modal Edit Produk --}}
<div id="edit-modal" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 dark:bg-opacity-80">
    <div class="flex justify-center items-center min-h-full p-4">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700 w-full max-w-2xl">

            <div class="flex items-center justify-between p-4 border-b dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Edit Produk
                </h3>

                <button type="button" onclick="closeEditModal()"
                    class="px-3 py-1 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm dark:hover:bg-gray-600 dark:hover:text-white">X</button>
            </div>

            <form id="edit-form" method="POST" enctype="multipart/form-data"
                class="p-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kode Barang</label>
                    <input type="text" name="product_code" id="edit_product_code"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Produk</label>
                    <input type="text" name="name" id="edit_name" required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kategori</label>
                        <select name="category_id" id="edit_category_id" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                            <option value="" disabled selected hidden></option>
                            @foreach ($categories as $kategori)
                                <option value="{{ $kategori->id }}">
                                    {{ $kategori->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Supplier</label>
                        <select name="supplier_id" id="edit_supplier_id" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                            <option value="" disabled selected hidden></option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}" data-category-ids="{{ implode(',', $sup->category_ids ?? []) }}">
                                    {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga Beli (Rp)</label>
                        <input type="number" name="purchase_price" id="edit_purchase_price"
                            min="0" step="0.01" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Harga Jual (Rp)</label>
                        <input type="number" name="selling_price" id="edit_selling_price"
                            min="0" step="0.01" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Stok</label>
                        <input type="number" name="stock" id="edit_stock"
                            min="0" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Minimum Stok</label>
                        <input type="number" name="min_stock" id="edit_min_stock"
                            min="0" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                    </div>
                </div>

                @include('pages.produk.partials.attribute-fields', ['containerId' => 'edit-product-attributes'])

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Foto Produk Baru (Opsional)</label>
                    <input type="file" name="image" accept="image/*"
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Deskripsi Produk <span class="font-normal text-gray-500 dark:text-gray-400">(Opsional)</span></label>
                    <textarea name="description" id="edit_description"
                        rows="3" placeholder="Tambahkan keterangan produk jika diperlukan"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"></textarea>
                </div>

                <button type="submit"
                    class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Update Produk
                </button>
            </form>
        </div>
    </div>
</div>
@endif

<script>
const productEditUrlTemplate = @json(route('produk.edit', ['id' => '__PRODUCT_ID__']));
const productUpdateUrlTemplate = @json(route('produk.update', ['id' => '__PRODUCT_ID__']));
const supplierOptionsBySelect = new WeakMap();

function filterSuppliersByCategory(categorySelect, supplierSelect, preferredSupplierId = null) {
    const categoryId = categorySelect.value;
    const selectedSupplierId = preferredSupplierId ?? supplierSelect.value;
    let supplierOptions = supplierOptionsBySelect.get(supplierSelect);

    if (!supplierOptions) {
        supplierOptions = Array.from(supplierSelect.options).slice(1);
        supplierOptionsBySelect.set(supplierSelect, supplierOptions);
    }

    const matchingOptions = supplierOptions.filter(option => {
        const categoryIds = option.dataset.categoryIds
            ? option.dataset.categoryIds.split(',').filter(Boolean)
            : [];

        return !categoryId || categoryIds.length === 0 || categoryIds.includes(categoryId);
    });

    supplierSelect.replaceChildren(supplierSelect.options[0], ...matchingOptions);
    supplierSelect.value = matchingOptions.some(option => option.value === String(selectedSupplierId))
        ? selectedSupplierId
        : '';
}

document.getElementById('add_category_id')?.addEventListener('change', function () {
    filterSuppliersByCategory(this, document.querySelector('#crud-modal select[name="supplier_id"]'));
});

document.getElementById('edit_category_id')?.addEventListener('change', function () {
    filterSuppliersByCategory(this, document.getElementById('edit_supplier_id'));
});

document.addEventListener('DOMContentLoaded', function () {
    const checkboxAll = document.getElementById('checkbox-all');
    const productCheckboxes = Array.from(document.querySelectorAll('.product-checkbox'));
    const btnHapusBanyak = document.getElementById('btn-hapus-banyak');
    const teksJumlah = document.getElementById('jumlah-terpilih');
    const formHapusBanyak = document.getElementById('form-hapus-banyak');
    const inputHapusIds = document.getElementById('input-hapus-ids');

    document.querySelectorAll('[data-product-attributes]').forEach(container => {
        appendProductAttributeRow(container);
    });

    document.addEventListener('click', function (event) {
        const addButton = event.target.closest('[data-add-product-attribute]');
        if (addButton) {
            appendProductAttributeRow(document.getElementById(addButton.dataset.container));
            return;
        }

        const removeButton = event.target.closest('[data-remove-product-attribute]');
        if (removeButton) {
            const container = removeButton.closest('[data-product-attributes]');
            removeButton.closest('[data-product-attribute-row]').remove();

            if (container.children.length === 0) {
                appendProductAttributeRow(container);
            }
        }
    });

    function toggleTombolHapus() {
        if (!btnHapusBanyak || !checkboxAll || !teksJumlah) {
            return;
        }

        const checkedBoxes = productCheckboxes.filter(checkbox => checkbox.checked);

        btnHapusBanyak.classList.toggle('hidden', checkedBoxes.length === 0);
        btnHapusBanyak.classList.toggle('inline-flex', checkedBoxes.length > 0);

        teksJumlah.textContent = checkedBoxes.length;

        checkboxAll.checked = productCheckboxes.length > 0 &&
            checkedBoxes.length === productCheckboxes.length;

        checkboxAll.indeterminate = checkedBoxes.length > 0 &&
            checkedBoxes.length < productCheckboxes.length;
    }

    checkboxAll?.addEventListener('change', function () {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });

        toggleTombolHapus();
    });

    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', toggleTombolHapus);
    });

    btnHapusBanyak?.addEventListener('click', function () {
        const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(checkbox => checkbox.value);

        if (ids.length === 0) {
            alert('Pilih minimal satu produk terlebih dahulu.');
            return;
        }

        if (confirm('Yakin ingin menghapus ' + ids.length + ' produk yang dipilih?')) {
            inputHapusIds.value = ids.join(',');
            formHapusBanyak.submit();
        }
    });

    toggleTombolHapus();
});

function appendProductAttributeRow(container, name = '', value = '') {
    const index = Number(container.dataset.nextIndex || 0);
    container.dataset.nextIndex = String(index + 1);

    const row = document.createElement('div');
    row.className = 'grid grid-cols-1 items-start gap-2 rounded-lg border border-gray-200 p-3 dark:border-gray-600 sm:grid-cols-[1fr_1fr_auto]';
    row.dataset.productAttributeRow = '';

    const nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.name = `attributes[${index}][name]`;
    nameInput.value = name;
    nameInput.maxLength = 50;
    nameInput.placeholder = 'Nama atribut, misalnya Warna';
    nameInput.setAttribute('aria-label', 'Nama atribut');
    nameInput.className = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-500 dark:bg-gray-600 dark:text-white';

    const valueInput = document.createElement('input');
    valueInput.type = 'text';
    valueInput.name = `attributes[${index}][value]`;
    valueInput.value = value;
    valueInput.maxLength = 100;
    valueInput.placeholder = 'Nilai, misalnya Hitam';
    valueInput.setAttribute('aria-label', 'Nilai atribut');
    valueInput.className = nameInput.className;

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.dataset.removeProductAttribute = '';
    removeButton.className = 'rounded-lg px-3 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30';
    removeButton.textContent = 'Hapus';

    row.append(nameInput, valueInput, removeButton);
    container.append(row);
}

function renderProductAttributes(containerId, attributes) {
    const container = document.getElementById(containerId);
    container.replaceChildren();
    container.dataset.nextIndex = '0';

    if (attributes.length === 0) {
        appendProductAttributeRow(container);
        return;
    }

    attributes.forEach(attribute => {
        appendProductAttributeRow(container, attribute.name, attribute.value);
    });
}

function openEditModal(id) {
    const encodedId = encodeURIComponent(id);
    fetch(productEditUrlTemplate.replace('__PRODUCT_ID__', encodedId), {
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal mengambil data produk. HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('edit-form').action = productUpdateUrlTemplate.replace('__PRODUCT_ID__', encodedId);
            document.getElementById('edit_product_code').value = data.product_code ?? '';
            document.getElementById('edit_name').value = data.name ?? '';
            document.getElementById('edit_category_id').value = data.category_id ?? '';
            filterSuppliersByCategory(
                document.getElementById('edit_category_id'),
                document.getElementById('edit_supplier_id'),
                data.supplier_id ?? ''
            );
            document.getElementById('edit_purchase_price').value = data.purchase_price ?? '';
            document.getElementById('edit_selling_price').value = data.selling_price ?? '';
            document.getElementById('edit_stock').value = data.stock ?? '';
            document.getElementById('edit_min_stock').value = data.min_stock ?? '';
            document.getElementById('edit_description').value = data.description ?? '';
            renderProductAttributes('edit-product-attributes', data.attributes ?? []);

            const modal = document.getElementById('edit-modal');
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
        })
        .catch(error => {
            console.error(error);
            alert('Data produk gagal diambil. Periksa route dan controller produk.');
        });
}

function closeEditModal() {
    const modal = document.getElementById('edit-modal');
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
}

function openAddModal() {
    const modal = document.getElementById('crud-modal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
    }
}

function closeAddModal() {
    const modal = document.getElementById('crud-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
    }
}
</script>
@endsection