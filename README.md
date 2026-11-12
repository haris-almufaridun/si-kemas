# SI-KEMAS (Sistem Informasi Keluar Masuk Surat)

Aplikasi arsip surat masuk dan keluar untuk **DPPKB Kabupaten OKU Timur**, tersedia dalam versi **PHP Native + MySQL** dan **HTML + CSS + JavaScript murni (`localStorage`)**.

## Fitur Utama
- **Autentikasi & Hak Akses (`admin` / `petugas`):** Login menggunakan username atau email.
- **Dashboard:** Ringkasan jumlah surat masuk/keluar (total & bulan berjalan), 5 arsip surat terbaru, distribusi kategori, dan akses cepat.
- **Surat Masuk & Surat Keluar:** Pencarian, filter kategori, tambah, edit, modal detail surat, pagination, lampiran PDF/JPG/PNG (maks. 5 MB), serta hapus data (khusus admin).
- **Kategori Surat & Manajemen Pengguna (Admin):** Tambah, edit, dan hapus kategori maupun akun pengguna.
- **Laporan & Rekapitulasi:** Filter rentang tanggal, jenis surat, dan kategori; cetak / PDF dengan kop surat resmi; serta **Export CSV**.

## Menjalankan Versi PHP Native (Laragon)
1. Letakkan folder `si-kemas-php-native` di `C:\laragon\www\`.
2. Jalankan Laragon dan aktifkan **Apache** serta **MySQL**.
3. Import `database.sql` ke MySQL (database `db_arsip_surat`).
4. Periksa konfigurasi di `config/database.php` (default Laragon: `root`, password kosong).
5. Buka `http://localhost/si-kemas-php-native/install.php` untuk membuat akun admin pertama, lalu hapus `install.php` setelah selesai.
6. Akses aplikasi di `http://localhost/si-kemas-php-native/`.

## Menjalankan Versi HTML Murni (`html/`)
- Buka `http://localhost/si-kemas-php-native/html/` melalui server lokal atau buka langsung file `html/index.html` di browser.
- Akun bawaan pada versi HTML: username `admin`, password `admin123` (data tersimpan di `localStorage` browser).

## Catatan Teknis
- Versi PHP memerlukan **PHP 8.1+**, ekstensi **PDO MySQL**, dan **Fileinfo**.
- Bootstrap 5, Bootstrap Icons, dan font Plus Jakarta Sans dimuat via CDN sehingga koneksi internet diperlukan untuk tampilan penuh.
