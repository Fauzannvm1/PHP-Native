# Sistem Kasir — PHP Native POS

Aplikasi kasir (Point of Sale) berbasis web menggunakan **PHP Native**, **MySQL**, dan **Tailwind CSS**. Cocok untuk toko kecil, warung, atau project pembelajaran PHP.

![PHP](https://img.shields.io/badge/PHP-Native-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat-square&logo=mysql&logoColor=white)

---

## Fitur Utama

| Modul | Deskripsi |
|-------|-----------|
| **Login & Logout** | Autentikasi user dengan password ter-hash (bcrypt) |
| **Dashboard Admin** | Ringkasan transaksi hari ini, pendapatan, total produk, stok menipis, transaksi terbaru |
| **Manajemen Produk** | Tambah, edit, hapus produk + pencarian & filter kategori |
| **Transaksi Baru (POS)** | Keranjang session, update qty, checkout, kurangi stok otomatis |
| **Laporan** | Filter tanggal & kasir, total transaksi & pendapatan |
| **Cetak Struk** | Halaman struk siap cetak (Ctrl + P) |

---

## Screenshot Alur (Ringkas)

```text
Login → Dashboard → Produk / Transaksi / Laporan → Struk
```

---

## Persyaratan

- [XAMPP](https://www.apachefriends.org/) (atau Apache + PHP + MySQL)
- PHP 8.0+ (disarankan)
- MySQL / MariaDB
- Browser modern (Chrome, Edge, Firefox)

---

## Instalasi

### 1. Clone atau copy project

Letakkan folder project di:

```text
C:\xampp\htdocs\PHP Native\
```

### 2. Buat database

1. Buka **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Import file `database.sql`  
   — atau jalankan isi SQL untuk membuat database `kasir_db` dan semua tabel.

### 3. Konfigurasi database

Edit `config.php` jika kredensial MySQL berbeda:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kasir_db');
```

### 4. Seed akun default

Buka sekali di browser:

```text
http://localhost/PHP%20Native/seeder.php
```

Akun yang dibuat:

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | admin |
| `kasir1` | `admin123` | kasir |

> **Penting:** Hapus atau rename `seeder.php` setelah digunakan (keamanan).

### 5. Login

```text
http://localhost/PHP%20Native/index.php
```

---

## Struktur Folder

```text
PHP Native/
├── config.php           # Koneksi DB, session, helper functions
├── index.php            # Halaman login
├── dashboard.php        # Dashboard admin
├── produk.php           # CRUD produk
├── transaksi.php        # Kasir / POS + keranjang
├── laporan.php          # Laporan penjualan
├── cetak_struk.php      # Cetak struk transaksi
├── logout.php           # Logout
├── seeder.php           # Buat user default (hapus setelah dipakai)
├── database.sql         # Skema database
├── includes/
│   ├── header.php       # Layout sidebar + topbar
│   └── footer.php       # Penutup layout
├── docs/
│   └── INTRO_SCRIPT.md  # Script demo untuk perkenalan website
└── workspace-fundamentals/
    └── WORKSPACE_FUNDAMENTALS.md  # Dokumentasi teknis workspace
```

---

## Dashboard Admin — Apa yang Ditampilkan?

Setelah login, halaman **Dashboard** menampilkan:

1. **Transaksi Hari Ini** — jumlah transaksi selesai hari ini  
2. **Pendapatan Hari Ini** — total penjualan (Rupiah)  
3. **Total Produk** — jumlah produk terdaftar  
4. **Stok Menipis** — produk dengan stok ≤ 10  
5. **Tabel Transaksi Terbaru** — 10 transaksi terakhir  
6. **Daftar Stok Menipis** — produk yang perlu restock  
7. **Shortcut** — Transaksi Baru, Kelola Produk, Lihat Laporan  

---

## Alur Transaksi (Checkout)

1. Kasir memilih produk di `transaksi.php` → masuk keranjang (session).  
2. Atur jumlah item (+ / −) atau hapus dari keranjang.  
3. Isi **jumlah bayar** ≥ total.  
4. Sistem menyimpan:
   - Header ke tabel `transaksi`
   - Detail item ke `detail_transaksi`
   - Mengurangi stok di `produk`
5. Redirect ke `cetak_struk.php` untuk cetak nota.

---

## Database

| Tabel | Fungsi |
|-------|--------|
| `users` | Akun login (admin / kasir) |
| `produk` | Master data produk |
| `transaksi` | Header penjualan |
| `detail_transaksi` | Item per transaksi |

Relasi utama: `transaksi` → `users`, `detail_transaksi` → `transaksi` & `produk`.

---

## Keamanan (Catatan)

- Password disimpan dengan `password_hash()` / `password_verify()`
- Banyak query memakai **prepared statements**
- Halaman internal memakai `requireLogin()`
- Hapus `seeder.php` setelah setup
- Untuk production: aktifkan HTTPS, ganti password default, pertimbangkan CSRF token

---

## Teknologi

- PHP (Native, tanpa framework)
- MySQLi
- Tailwind CSS 2 (CDN)
- Font Awesome 6 (CDN)
- Session PHP (auth + cart)

---

## Lisensi & Penggunaan

Project pembelajaran / portofolio. Silakan modifikasi sesuai kebutuhan.

---

## Kontribusi / Pengembangan Lanjutan

Ide improvement:

- [ ] Rename `login.php` ↔ `index.php` agar redirect konsisten  
- [ ] DB transaction saat checkout (`mysqli_begin_transaction`)  
- [ ] CSRF protection pada form POST  
- [ ] Role-based access (admin vs kasir)  
- [ ] Export laporan ke PDF/Excel  

---

**Dibuat dengan PHP Native — Sistem Kasir (Point of Sale)**
