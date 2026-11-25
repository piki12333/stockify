@php
  // Data menu profil hanya berasal dari pengguna yang sudah terautentikasi.
  $navbarUser = Auth::user();
@endphp

{{-- Navigasi atas untuk identitas aplikasi, menu umum, tema, dan akun pengguna. --}}
<nav class="app-navbar fixed z-50 w-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center justify-start">
          <button id="toggleSidebarMobile" type="button" aria-expanded="true" aria-controls="sidebar" aria-label="Sembunyikan menu samping" class="p-2 text-gray-600 rounded cursor-pointer hover:text-gray-900 hover:bg-gray-100 focus:bg-gray-100 dark:focus:bg-gray-700 focus:ring-2 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
            <svg id="toggleSidebarMobileHamburger" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
            <svg id="toggleSidebarMobileClose" class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
          </button>
          <a href="{{ route('dashboard') }}" class="flex ml-2 md:mr-24">
            <img src="{{ $applicationSettings->logo_path ? asset('storage/' . $applicationSettings->logo_path) : asset('static/images/stockify-logo.jpg') }}" class="h-8 w-8 mr-3 rounded-full object-cover" alt="Logo {{ $applicationSettings->app_name }}" />
            <span class="self-center text-xl font-semibold sm:text-2xl whitespace-nowrap dark:text-white">{{ $applicationSettings->app_name }}</span>
          </a>
        </div>
        <div class="flex items-center">
            <!-- Search mobile -->
            <button id="toggleSidebarMobileSearch" type="button" class="p-2 text-gray-500 rounded-lg lg:hidden hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
              <span class="sr-only">Search</span>
              <!-- Search icon -->
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
            </button>
            <!-- Notifications -->
            <button type="button" data-dropdown-toggle="notification-dropdown" class="relative p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700">
              <span class="sr-only">View notifications</span>
              <!-- Bell icon -->
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path></svg>
              @if ($unreadNotificationCount > 0)
                <span class="absolute top-0 right-0 inline-flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-red-600 border-2 border-white rounded-full dark:border-gray-800">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
              @endif
            </button>
            <!-- Dropdown menu -->
            <div class="z-20 z-50 hidden max-w-sm my-4 overflow-hidden text-base list-none bg-white divide-y divide-gray-100 rounded shadow-lg dark:divide-gray-600 dark:bg-gray-700" id="notification-dropdown">
              <div class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-200">
                <span>Notifikasi</span>
                @if ($unreadNotificationCount > 0)
                  <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $unreadNotificationCount }} belum dibaca</span>
                @endif
              </div>
              <div class="max-h-80 overflow-y-auto">
                @forelse ($recentNotifications as $notification)
                  <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    <button type="submit" class="flex w-full items-start gap-3 px-4 py-3 text-left border-b hover:bg-gray-100 dark:hover:bg-gray-600 dark:border-gray-600 {{ $notification->read_at ? '' : 'bg-blue-50 dark:bg-gray-700/70' }}">
                      <span class="mt-1 inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full {{ ($notification->data['type'] ?? '') === 'in' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' }}">
                        @if (($notification->data['type'] ?? '') === 'in')
                          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4m8-8l-4 4m4-4l4 4"></path></svg>
                        @else
                          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V4m-8 8h16m-8 8l4-4m-4 4l-4-4"></path></svg>
                        @endif
                      </span>
                      <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $notification->data['title'] ?? 'Aktivitas stok' }}</span>
                        <span class="mt-1 block text-sm text-gray-600 dark:text-gray-300">{{ $notification->data['message'] ?? '' }}</span>
                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $notification->created_at->locale('id')->diffForHumans() }}</span>
                      </span>
                      @if (!$notification->read_at)
                        <span class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-blue-600" aria-label="Belum dibaca"></span>
                      @endif
                    </button>
                  </form>
                @empty
                  <p class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada aktivitas barang masuk atau keluar.</p>
                @endforelse
              </div>
              <div class="flex items-center justify-between bg-gray-50 px-4 py-2 dark:bg-gray-700">
                <a href="{{ route('transactions.index') }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-400">Lihat transaksi</a>
                @if ($unreadNotificationCount > 0)
                  <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-xs text-gray-600 hover:underline dark:text-gray-300">Tandai dibaca</button>
                  </form>
                @endif
              </div>
            </div>
            <!-- Dropdown menu -->
            <div class="z-20 z-50 hidden max-w-sm my-4 overflow-hidden text-base list-none bg-white divide-y divide-gray-100 rounded shadow-lg dark:bg-gray-700 dark:divide-gray-600" id="apps-dropdown">
              <div class="block px-4 py-2 text-base font-medium text-center text-gray-700 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                  Apps
              </div>
              <div class="grid grid-cols-3 gap-4 p-4">
                <a href="#" class="block p-4 text-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                  <svg class="mx-auto mb-1 text-gray-500 w-7 h-7 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path></svg>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">Sales</div>
                </a>
                <a href="#" class="block p-4 text-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                  <svg class="mx-auto mb-1 text-gray-500 w-7 h-7 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path></svg>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">Users</div>
                </a>
                <a href="#" class="block p-4 text-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                  <svg class="mx-auto mb-1 text-gray-500 w-7 h-7 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm0 2h10v7h-2l-1 2H8l-1-2H5V5z" clip-rule="evenodd"></path></svg>
                  <div class="text-sm font-medium text-gray-900 dark:text-white">Inbox</div>
                </a>
              </div>
            </div>

            <!-- Tombol Tema Gelap/Terang -->
            <button id="theme-toggle" data-tooltip-target="tooltip-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
              <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
              <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
            </button>
            <div id="tooltip-toggle" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
                Toggle dark mode
                <div class="tooltip-arrow" data-popper-arrow></div>
            </div>

            @if ($navbarUser)
            <!-- Menu Profil Pengguna -->
            <div class="flex items-center ml-3">
              <div>
                <button type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" id="user-menu-button-2" aria-expanded="false" data-dropdown-toggle="dropdown-2">
                  <span class="sr-only">Open user menu</span>
                  @if ($navbarUser?->avatar)
                    <img class="w-8 h-8 rounded-full object-cover" src="{{ asset('storage/' . $navbarUser->avatar) }}" alt="Foto profil {{ $navbarUser->name }}">
                  @else
                    <span class="flex items-center justify-center w-8 h-8 font-semibold text-white bg-blue-700 rounded-full">{{ strtoupper(substr($navbarUser?->name ?? 'U', 0, 1)) }}</span>
                  @endif
                </button>
              </div>

              <!-- Dropdown menu -->
              <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded shadow dark:bg-gray-700 dark:divide-gray-600" id="dropdown-2">
                <div class="px-4 py-3" role="none">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white" role="none">
                    {{ $navbarUser?->name ?? 'Pengguna Stockify' }}
                  </p>
                  <p class="text-sm font-medium text-gray-500 truncate dark:text-gray-300" role="none">
                    {{ $navbarUser?->email ?? 'Email belum tersedia' }}
                  </p>
                  @if ($navbarUser?->role)
                    <p class="mt-1 text-xs font-medium text-blue-700 dark:text-blue-400">{{ \App\Models\User::ROLE_LABELS[$navbarUser->role] ?? 'Role tidak dikenal' }}</p>
                  @endif
                </div>
                <ul class="py-1" role="none">
                  <li>
                    <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Dashboard</a>
                  </li>
                  <li>
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">Pengaturan Profil</a>
                  </li>
                </ul>
                <div class="py-1" role="none">
                  <form method="POST" action="{{ route('logout') }}">
                      @csrf
                      <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-red-400 dark:hover:text-white" role="menuitem">Keluar (Logout)</button>
                  </form>
                </div>
              </div>
              @else
              <a href="{{ route('login') }}" class="ml-3 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">Masuk</a>
              @endif
            </div>

          </div>
      </div>
    </div>
</nav>