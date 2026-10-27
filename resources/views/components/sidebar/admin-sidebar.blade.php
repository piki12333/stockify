<x-sidebar-dashboard>
    {{-- Tautan navigasi untuk fitur utama yang tersedia bagi administrator. --}}
    <!-- Menu Dashboard Baru -->
    <li>
        <a href="{{ route('dashboard') }}" aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}" class="flex items-center p-2 text-base text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
            <svg class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
            </svg>
            <span class="ml-3">Dashboard</span>
        </a>
    </li>
    <!-- Menu Produk (Tanpa Dropdown / Tanpa Panah) -->
    <li>
        <a href="{{ route('produk.index') }}" aria-current="{{ request()->routeIs('produk.*') ? 'page' : 'false' }}" class="flex items-center p-2 text-base text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
            <svg class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7 1a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
            </svg>
            <span class="ml-3">Produk</span>
        </a>
    </li>

    <!-- Menu Transaksi Stok -->
    <li>
        <a href="{{ route('transactions.index') }}" aria-current="{{ request()->routeIs('transactions.*') ? 'page' : 'false' }}" class="flex items-center p-2 text-base text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
            <svg class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path>
                <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path>
            </svg>
            <span class="ml-3">Transaksi Stok</span>
        </a>
    </li>

    @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
    <!-- Menu Kategori khusus Admin -->
    <li>
        <a href="{{ route('categories.index') }}" aria-current="{{ request()->routeIs('categories.*') ? 'page' : 'false' }}" class="flex items-center p-2 text-base text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700">
            <svg class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
            </svg>
            <span class="ml-3">Kategori</span>
        </a>
    </li>
    @endif

    @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER))
    <!-- Menu Supplier untuk Admin dan Manajer -->
    <li>
        <a href="{{ route('suppliers.index') }}" aria-current="{{ request()->routeIs('suppliers.*') ? 'page' : 'false' }}" class="flex items-center p-2 text-base text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700 group">
            <svg class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
            </svg>
            <span class="ml-3">Supplier</span>
        </a>
    </li>
    @endif

    <!-- Menu Stock Opname -->
    <li>
        <a href="{{ route('stock-opnames.index') }}" aria-current="{{ request()->routeIs('stock-opnames.*') ? 'page' : 'false' }}" class="flex items-center p-2 text-base text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
            <svg class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            <span class="ml-3">Stock Opname</span>
        </a>
    </li>

    @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER))
    <!-- Menu laporan persediaan dan transaksi untuk Admin/Manajer -->
    <li>
        <a href="{{ route('reports.index') }}" aria-current="{{ request()->routeIs('reports.*') ? 'page' : 'false' }}" class="flex items-center p-2 text-base text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('reports.*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
            <svg class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V5m0 14h16M8 15v-4m4 4V7m4 8V9"/>
            </svg>
            <span class="ml-3">Laporan</span>
        </a>
    </li>
    @endif

    @if (auth()->user()->hasRole(\App\Models\User::ROLE_ADMIN))
    <li>
        <a href="{{ route('users.index') }}" aria-current="{{ request()->routeIs('users.*') ? 'page' : 'false' }}" class="flex items-center p-2 text-base text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('users.*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m12-13a4 4 0 11-8 0 4 4 0 018 0zm2 5a4 4 0 014 4v2h-3"/>
            </svg>
            <span class="ml-3">Pengguna</span>
        </a>
    </li>
    <li>
        <a href="{{ route('settings.index') }}" aria-current="{{ request()->routeIs('settings.*') ? 'page' : 'false' }}" class="flex items-center p-2 text-base text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('settings.*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8a4 4 0 100 8 4 4 0 000-8zm8 4a8 8 0 01-.1 1.3l1.2.9-1.2 2.1-1.4-.5a8 8 0 01-2.2 1.3l-.2 1.5h-2.4l-.2-1.5a8 8 0 01-2.2-1.3l-1.4.5-1.2-2.1 1.2-.9A8 8 0 018 12c0-.4 0-.9.1-1.3l-1.2-.9 1.2-2.1 1.4.5a8 8 0 012.2-1.3l.2-1.5h2.4l.2 1.5a8 8 0 012.2 1.3l1.4-.5 1.2 2.1-1.2.9c.1.4.1.9.1 1.3z"/>
            </svg>
            <span class="ml-3">Pengaturan</span>
        </a>
    </li>
    @endif
</x-sidebar-dashboard>