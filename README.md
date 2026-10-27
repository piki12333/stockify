# Stockify - Aplikasi Manajemen Stok Barang

Stockify adalah aplikasi manajemen inventaris gudang berbasis web yang dibangun dengan Laravel 10 dan Flowbite (Tailwind CSS). Aplikasi ini dirancang untuk memudahkan pemantauan stok secara akurat dan efisien dengan pembagian wewenang berbasis peran (*Role-Based Access Control*).

---

## 👥 Akun Demo Pengguna (Role-Based)

Untuk pengujian dan demonstrasi fitur, gunakan akun bawaan berikut (password sama untuk semua akun):

| Role | Email | Password | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@stockify.test` | `password` | Mengelola seluruh sistem, CRUD Kategori, Supplier, Pengguna, Pengaturan App, Laporan |
| **Manajer Gudang** | `manager@stockify.test` | `password` | Tambah produk baru, buat tugas barang masuk/keluar, approve/reject stock opname, laporan stok |
| **Staff Gudang** | `staff@stockify.test` | `password` | Konfirmasi penerimaan barang masuk & penyiapan barang keluar, eksekusi stock opname |

---

## 🚀 Fitur Utama

1. **Dashboard Multi-Role:**
   - **Admin:** Ringkasan metrik total produk, nilai aset, pergerakan hari ini, grafik 7 hari, dan riwayat aktivitas akun.
   - **Manajer:** Panel monitoring stok menipis (*low stock warning*), rekap barang masuk/keluar hari ini.
   - **Staff:** Panel tugas gudang aktif yang menunggu konfirmasi fisik.

2. **Manajemen Produk & Stok:**
   - CRUD Produk lengkap dengan kode barang, harga beli/jual, stok awal, dan **Stok Minimum**.
   - Atribut produk dinamis (ukuran, warna, berat, dsb.).
   - Ekspor dan Impor data produk format CSV.
   - Indikator status stok visual (Hijau = aman, Kuning = mendekati batas, Merah = stok kritis).

3. **Alur Transaksi Stok Terkontrol:**
   - Admin/Manajer membuat tugas barang masuk/keluar dan menugaskannya ke Staff tertentu.
   - Staff Gudang memeriksa fisik barang dan mengonfirmasi tugas melalui tombol konfirmasi.
   - Stok fisik hanya bertambah/berkurang setelah konfirmasi selesai (mencegah *human error* dan stok minus).

4. **Stock Opname:**
   - Pencatatan selisih stok fisik vs stok sistem dengan persetujuan bertingkat (*Approval*).

5. **Laporan & Log Aktivitas:**
   - Laporan mutasi barang dan stok per kategori/periode dengan kemampuan ekspor CSV.
   - Audit trail aktivitas login dan aksi pengguna.

---

## 🏗️ Arsitektur Kode (Controller-Service-Repository)

Proyek ini mengadopsi pola arsitektur **Controller-Service-Repository**:
* **Controller:** Menangani HTTP request, validasi input, dan mengembalikan response/view.
* **Service:** Menampung alur logika bisnis (business logic).
* **Repository:** Bertanggung jawab penuh terhadap query dan persistensi data ke database.

Struktur folder:
```
app/
├── Http/Controllers/       # Controller (Product, Category, Supplier, Transaction, dll.)
├── Services/               # Logika Bisnis (ProductService, CategoryService, SupplierService, dll.)
└── Repositories/           # Query Database (ProductRepository, CategoryRepository, dll.)
```

---

## 🛠️ Instalasi & Menjalankan Proyek

1. **Clone repository:**
   ```bash
   git clone <url-repo>
   cd laravel-flowbite-admin-dashboard
   ```

2. **Install dependensi:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Sesuaikan pengaturan database di file `.env`.*

4. **Migrasi & Seeder Database:**
   ```bash
   php artisan migrate --seed
   ```

5. **Jalankan Server Lokal:**
   ```bash
   php artisan serve
   ```
