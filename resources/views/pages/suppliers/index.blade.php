@extends('layouts.dashboard')

@section('content')
{{-- Daftar pemasok dengan pencarian dan formulir tambah, edit, serta hapus. --}}
<div class="px-4 mx-auto max-w-screen-2xl py-6 min-h-screen">
    
    <!-- ALERT SUCCESS -->
    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-300" role="alert">
            <span class="font-medium">Berhasil!</span> {{ session('success') }}
        </div>
    @endif

    <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        
        {{-- Header & Tombol Tambah --}}
        <div class="flex flex-col px-4 py-3 space-y-3 lg:flex-row lg:items-center lg:justify-between lg:space-y-0 lg:space-x-4 border-b dark:border-gray-700">
            <div class="flex items-center flex-1 space-x-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Daftar Supplier (Pemasok)</h2>
            </div>
            <div class="flex flex-col space-y-3 md:flex-row md:items-center md:space-y-0 md:space-x-3">
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER))
                    <button data-modal-target="add-modal" data-modal-toggle="add-modal" type="button" class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                        + Tambah Supplier
                    </button>
                @endif
            </div>
        </div>

        {{-- Kotak Pencarian (Search) --}}
        <div class="px-4 py-4 border-b border-gray-200 dark:border-gray-700">
            <input type="search" id="search-supplier" autocomplete="off" placeholder="Cari nama perusahaan, no telepon, atau alamat..." class="block w-full max-w-md p-2.5 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
        </div>

        {{-- Tabel Data Supplier --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 w-16">No</th>
                        <th class="px-4 py-3">Nama Perusahaan</th>
                        <th class="px-4 py-3">No. Telepon / WA</th>
                        <th class="px-4 py-3">Alamat Lengkap</th>
                        @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER))
                            <th class="px-4 py-3">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="supplier-table-body">
                    @forelse ($suppliers as $index => $supplier)
                        <tr class="supplier-row border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $index + 1 }}</td>
                            <td class="supplier-name px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $supplier->name }}</td>
                            <td class="supplier-phone px-4 py-3 text-gray-900 dark:text-white">{{ $supplier->phone ?? '-' }}</td>
                            <td class="supplier-address px-4 py-3 text-gray-900 dark:text-white">{{ $supplier->address ?? '-' }}</td>
                            @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER))
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-3">
                                    <button type="button" onclick="openEditModal({{ $supplier->id }})" class="px-3 py-1 text-sm font-medium text-white bg-yellow-400 rounded-lg hover:bg-yellow-500">
                                        Edit
                                    </button>
                                    <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus supplier ini?');" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr id="empty-data-row">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                Belum ada data supplier. Silakan tambahkan supplier baru.
                            </td>
                        </tr>
                    @endforelse
                    
                    {{-- Baris khusus jika pencarian tidak ditemukan --}}
                    <tr id="search-not-found" class="hidden">
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            Supplier yang dicari tidak ditemukan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH SUPPLIER --}}
<div id="add-modal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 dark:bg-opacity-80">
    <div class="flex justify-center items-center min-h-full p-4 w-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700" style="width: 700px !important; max-width: 95vw !important;">
            
            <div class="flex items-center justify-between p-4 border-b dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Supplier</h3>
                <button type="button" data-modal-hide="add-modal" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>

            <form action="{{ route('suppliers.store') }}" method="POST" class="p-6 space-y-6">
                @csrf
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Perusahaan/Toko</label>
                    <input type="text" name="name" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">No. Telepon / WA (Opsional)</label>
                    <input type="text" name="phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat (Opsional)</label>
                    <textarea name="address" rows="4" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"></textarea>
                </div>
                <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Simpan Data
                </button>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT SUPPLIER --}}
<div id="edit-modal" tabindex="-1" aria-hidden="true" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 dark:bg-opacity-80">
    <div class="flex justify-center items-center min-h-full p-4 w-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700" style="width: 700px !important; max-width: 95vw !important;">
            
            <div class="flex items-center justify-between p-4 border-b dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Edit Supplier</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>

            <form id="edit-form" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Perusahaan/Toko</label>
                    <input type="text" name="name" id="edit_name" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">No. Telepon / WA</label>
                    <input type="text" name="phone" id="edit_phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alamat</label>
                    <textarea name="address" id="edit_address" rows="4" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"></textarea>
                </div>
                <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Update Data
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Skrip untuk pencarian real-time pada tabel supplier
document.getElementById('search-supplier').addEventListener('input', function(e) {
    let keyword = e.target.value.toLowerCase();
    let rows = document.querySelectorAll('.supplier-row');
    let foundCount = 0;
    let notFoundRow = document.getElementById('search-not-found');

    rows.forEach(row => {
        let name = row.querySelector('.supplier-name').textContent.toLowerCase();
        let phone = row.querySelector('.supplier-phone').textContent.toLowerCase();
        let address = row.querySelector('.supplier-address').textContent.toLowerCase();

        if (name.includes(keyword) || phone.includes(keyword) || address.includes(keyword)) {
            row.style.display = '';
            foundCount++;
        } else {
            row.style.display = 'none';
        }
    });

    if (notFoundRow) {
        if (foundCount === 0 && rows.length > 0) {
            notFoundRow.classList.remove('hidden');
        } else {
            notFoundRow.classList.add('hidden');
        }
    }
});

function openEditModal(id) {
    fetch('/suppliers/' + id + '/edit', {
        headers: { 'Accept': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('edit-form').action = '/suppliers/' + id;
        document.getElementById('edit_name').value = data.name ?? '';
        document.getElementById('edit_phone').value = data.phone ?? '';
        document.getElementById('edit_address').value = data.address ?? '';
        
        const modal = document.getElementById('edit-modal');
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
    })
    .catch(error => alert('Gagal mengambil data supplier!'));
}

function closeEditModal() {
    const modal = document.getElementById('edit-modal');
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
}
</script>
@endsection