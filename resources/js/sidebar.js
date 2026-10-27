const sidebar = document.getElementById('sidebar');

// Pasang tombol buka/tutup sidebar hanya pada halaman yang memiliki sidebar.
if (sidebar) {
    const toggleSidebarMobileEl = document.getElementById('toggleSidebarMobile');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const toggleSidebarMobileHamburger = document.getElementById('toggleSidebarMobileHamburger');
    const toggleSidebarMobileClose = document.getElementById('toggleSidebarMobileClose');
    const toggleSidebarMobileSearch = document.getElementById('toggleSidebarMobileSearch');
    const desktopViewport = window.matchMedia('(min-width: 1024px)');
    const collapsedStorageKey = 'stockify-sidebar-collapsed';

    const updateMobileToggle = (isOpen) => {
        toggleSidebarMobileEl.setAttribute('aria-expanded', String(isOpen));
        toggleSidebarMobileEl.setAttribute('aria-label', isOpen ? 'Sembunyikan menu samping' : 'Tampilkan menu samping');
        toggleSidebarMobileHamburger.classList.toggle('hidden', isOpen);
        toggleSidebarMobileClose.classList.toggle('hidden', !isOpen);
        sidebarBackdrop.classList.toggle('hidden', !isOpen);
    };

    const setDesktopCollapsed = (isCollapsed, persist = true) => {
        sidebar.classList.toggle('lg:-translate-x-full', isCollapsed);
        document.getElementById('main-content').classList.toggle('lg:ml-0', isCollapsed);
        document.getElementById('main-content').classList.toggle('lg:ml-64', !isCollapsed);
        toggleSidebarMobileEl.setAttribute('aria-expanded', String(!isCollapsed));
        toggleSidebarMobileEl.setAttribute('aria-label', isCollapsed ? 'Tampilkan menu samping' : 'Sembunyikan menu samping');

        if (persist) {
            localStorage.setItem(collapsedStorageKey, String(isCollapsed));
        }
    };

    // Pulihkan preferensi desktop agar area konten tetap sesuai pilihan terakhir.
    setDesktopCollapsed(localStorage.getItem(collapsedStorageKey) === 'true', false);
    if (!desktopViewport.matches) {
        sidebar.classList.add('hidden');
        updateMobileToggle(false);
    }

    toggleSidebarMobileEl.addEventListener('click', () => {
        if (desktopViewport.matches) {
            setDesktopCollapsed(!sidebar.classList.contains('lg:-translate-x-full'));
            return;
        }

        const isOpen = sidebar.classList.contains('hidden');
        sidebar.classList.toggle('hidden', !isOpen);
        updateMobileToggle(isOpen);
    });

    toggleSidebarMobileSearch?.addEventListener('click', () => {
        const isOpen = sidebar.classList.contains('hidden');
        sidebar.classList.toggle('hidden', !isOpen);
        updateMobileToggle(isOpen);
    });

    sidebarBackdrop.addEventListener('click', () => {
        sidebar.classList.add('hidden');
        updateMobileToggle(false);
    });

    desktopViewport.addEventListener('change', (event) => {
        if (event.matches) {
            sidebar.classList.add('hidden');
            setDesktopCollapsed(localStorage.getItem(collapsedStorageKey) === 'true', false);
        } else {
            sidebar.classList.add('hidden');
            updateMobileToggle(false);
        }
    });
}
