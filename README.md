# 🧺 Smart Laundry Web Application

Aplikasi manajemen layanan laundry berbasis web yang dikembangkan secara mandiri untuk memenuhi Tugas Besar Mata Kuliah **Cyber Security**.

---

## 📋 Daftar Isi
1. [Deskripsi Aplikasi](#-deskripsi-aplikasi)
2. [Fitur Utama](#-fitur-utama)
3. [Teknologi yang Digunakan](#️-teknologi-yang-digunakan)
4. [URL Aplikasi Aktif](#-url-aplikasi-aktif)
5. [Konfigurasi Environment Variable](#-konfigurasi-environment-variable)
6. [Cara Instalasi & Menjalankan Secara Lokal](#-cara-instalasi--menjalankan-secara-lokal)
7. [Penerapan Keamanan (Cyber Security)](#-penerapan-keamanan-cyber-security)
8. [Penggunaan AI Agent](#-penggunaan-ai-agent)
9. [Ringkasan Self-Testing Keamanan](#-ringkasan-self-testing-keamanan)

---

## 📌 1. Deskripsi Aplikasi
**Smart Laundry** adalah sistem informasi manajemen laundry berbasis web yang dirancang untuk mengoptimalkan proses pemesanan cucian kiloan secara digital. Aplikasi ini menyediakan dua hak akses utama, yaitu **Pelanggan (Member/Non-Member)** yang dapat melakukan pemesanan, penukaran poin, pelacakan status cucian, serta **Administrator** yang mengelola antrean operasional, verifikasi pembayaran digital, dan rekapitulasi omzet bulanan secara *real-time*.

---

## 🚀 2. Fitur Utama
* **Autentikasi & Otorisasi Pengguna:** Sistem registrasi dan login terpisah dengan hak akses terproteksi antara Pelanggan dan Admin[cite: 2, 3].
* **Pengelolaan Data (CRUD):** Operasi data lengkap mencakup pembuatan pesanan, perubahan status pengerjaan, penghapusan data aman (*soft-delete*), dan persetujuan member baru[cite: 2, 3].
* **Sistem Pembayaran Fleksibel:** Mendukung Bayar di Tempat (Tunai), Transfer Bank, dan QRIS dengan fitur unggah bukti transaksi serta verifikasi admin.
* **Sistem Poin & Diskon Member:** Pelanggan member mendapatkan diskon 10% dan akumulasi poin yang dapat ditukarkan menjadi potongan harga.
* **Dashboard Omzet & Laporan:** Rekapitulasi pemasukan bulanan dan total berat produksi secara otomatis[cite: 2, 3].

---

## 🛠️ 3. Teknologi yang Digunakan
* **Bahasa Pemrograman:** PHP Native (OOP & Procedural MySQLi)
* **Frontend Framework:** Tailwind CSS & FontAwesome Icons
* **Manajemen Database:** MySQL / MariaDB (XAMPP / Local Server)
* **Deployment & Hosting:** [Sebutkan nama hosting, misal: Railway / Render / Vercel][cite: 3]

---

## 🌐 4. URL Aplikasi Aktif
* **Aplikasi Publik:** [https://nama-aplikasi.example.com] *(Akan diisi setelah dideploy)*[cite: 4]
* **Akun Uji Coba (Login):**
  * **Admin:** Username: `admin` / Password: `adminpassword`[cite: 4]
  * **Member:** Username/No. Telp terdaftar / Password terdaftar[cite: 4]

---

## ⚙️ 5. Konfigurasi Environment Variable
Untuk menjaga kerahasiaan kredensial database sesuai standar *secure coding*, aplikasi ini membaca konfigurasi melalui variabel lingkungan sistem (`getenv()`). 

Berikut adalah contoh struktur variabel lingkungan yang perlu diset pada server hosting atau file konfigurasi lokal:
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=smart_laundry