const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
const themeToggleBtn = document.getElementById('theme-toggle');

// Halaman sederhana seperti login tidak memiliki tombol tema.
if (themeToggleDarkIcon && themeToggleLightIcon && themeToggleBtn) {
    // Pilih ikon awal sesuai tema yang tersimpan atau preferensi perangkat.
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        themeToggleLightIcon.classList.remove('hidden');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
    }

    const darkModeEvent = new Event('dark-mode');

    themeToggleBtn.addEventListener('click', function() {
        // Perbarui ikon agar mencerminkan tema yang akan dipilih.
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');

        // Jika pengguna pernah memilih tema, balikkan pilihan tersimpan tersebut.
        if (localStorage.getItem('color-theme')) {
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }

        // Jika belum ada pilihan tersimpan, gunakan tema halaman sebagai titik awal.
        } else if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        }

        document.dispatchEvent(darkModeEvent);
    });
}
