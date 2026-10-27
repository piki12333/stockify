{{-- Baris input atribut produk yang dapat ditambah atau dihapus dari formulir. --}}
<div>
    <div class="mb-2 flex items-center justify-between gap-3">
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Atribut Tambahan</h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tambahkan informasi khusus seperti ukuran, warna, atau berat.</p>
        </div>
        <button type="button" data-add-product-attribute data-container="{{ $containerId }}" class="shrink-0 rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-900/30">
            + Tambah atribut
        </button>
    </div>
    <div id="{{ $containerId }}" data-product-attributes data-next-index="0" class="space-y-2"></div>
</div>
