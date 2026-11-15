# 🧺 Smart Laundry Web Application

Aplikasi manajemen layanan laundry berbasis web yang dikembangkan untuk memenuhi Tugas Mata Kuliah **Cyber Security**.

---

## 📋 Daftar Isi
1. [Deskripsi Aplikasi](#-deskripsi-aplikasi)
2. [Fitur Utama](#-fitur-utama)
3. [Teknologi yang Digunakan](#️-teknologi-yang-digunakan)
4. [URL Aplikasi Aktif](#-url-aplikasi-aktif)
5. [Konfigurasi Environment Variable](#-konfigurasi-environment-variable)
6. [Cara Instalasi & Menjalankan Secara Lokal](#-cara-instalasi--menjalankan-secara-lokal)
7. [Penerapan Keamanan (Cyber Security)](#-penerapan-keamanan-cyber-security)
8. [Penggunaan AI Collaborator / Agent](#-penggunaan-ai-collaborator--agent)
9. [Ringkasan Self-Testing Keamanan](#-ringkasan-self-testing-keamanan)

---

## 📌 1. Deskripsi Aplikasi
**Smart Laundry** adalah sistem informasi manajemen laundry berbasis web yang dirancang untuk mengoptimalkan proses pemesanan cucian kiloan secara digital. Aplikasi ini menyediakan dua hak akses utama, yaitu **Pelanggan (Member/Non-Member)** yang dapat melakukan pemesanan, penukaran poin, pelacakan status cucian, serta **Administrator** yang mengelola antrean operasional, verifikasi pembayaran digital, manajemen masa aktif keanggotaan (membership), dan rekapitulasi omzet bulanan secara *real-time*.

---

## 🚀 2. Fitur Utama
- **Autentikasi & Otorisasi Pengguna:** Sistem registrasi dan login terpisah dengan hak akses terproteksi antara Pelanggan dan Admin menggunakan sesi aman (*session management*).
- **Pengelolaan Data (CRUD):** Operasi data lengkap mencakup pembuatan pesanan, perubahan status pengerjaan, penghapusan data aman berbasis *soft-delete* (mengarsipkan transaksi selesai tanpa merusak relasi database), dan persetujuan member baru.
- **Sistem Pembayaran Fleksibel:** Mendukung Bayar di Tempat (Tunai), Transfer Bank, dan QRIS dengan fitur unggah bukti transaksi serta verifikasi admin.
- **Sistem Poin & Diskon Member:** Pelanggan member mendapatkan diskon 10% dan akumulasi poin yang dapat ditukarkan menjadi potongan harga.
- **Manajemen Masa Aktif Membership (1 Bulan):** Fitur otomatis yang memvalidasi masa keanggotaan member selama 30 hari sejak pendaftaran (`INTERVAL 1 MONTH`), dan secara otomatis memperbarui status menjadi *Expired* tanpa menghapus riwayat transaksi.
- **Dashboard Omzet & Laporan:** Rekapitulasi pemasukan bulanan dan total berat produksi secara otomatis berdasarkan data transaksi yang valid.

---

## 🛠️ 3. Teknologi yang Digunakan
- **Bahasa Pemrograman:** PHP Native (OOP & Procedural MySQLi)
- **Frontend Framework:** Tailwind CSS & FontAwesome Icons
- **Manajemen Database:** MySQL / MariaDB (XAMPP / Railway MySQL Service)
- **Deployment & Hosting:** Railway Cloud Platform

---

## 🌐 4. URL Aplikasi Aktif
- **Aplikasi Publik (Live Demo):** [https://smartlaundry-production-a7e5d258.up.railway.app](https://smartlaundry-production-a7e5d258.up.railway.app)[cite: 3]
- **Akun Uji Coba (Login Admin):**
  * **Dashboard Admin:** Akses melalui direktori `/admin/` dengan kredensial administrator yang terdaftar di database.

---

## ⚙️ 5. Konfigurasi Environment Variable
Untuk menjaga kerahasiaan kredensial database sesuai standar *secure coding*, aplikasi ini membaca konfigurasi melalui variabel lingkungan sistem (`getenv()`) agar terhindar dari hardcode password di dalam source code.

Struktur environment variable yang digunakan pada server Railway:
```env
MYSQLHOST=your_host_address
MYSQLUSER=root
MYSQLPASSWORD=your_secure_password
MYSQLDATABASE=railway
MYSQLPORT=3306