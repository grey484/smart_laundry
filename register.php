<?php
session_start();
require 'koneksi.php';

if (isset($_SESSION['member_id'])) {
    header("Location: index.php");
    exit;
}

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $no_wa = $_POST['no_whatsapp'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $cek = $conn->prepare("SELECT id FROM member WHERE no_whatsapp = ?");
    $cek->bind_param("s", $no_wa);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4 font-semibold'>Nomor WhatsApp sudah terdaftar!</div>";
    } else {
        // Status otomatis "Pending" sesuai default database
        $stmt = $conn->prepare("INSERT INTO member (nama, no_whatsapp, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $no_wa, $password);
        if ($stmt->execute()) {
            $pesan = "<div class='bg-yellow-100 text-yellow-800 p-4 rounded mb-4 text-sm border border-yellow-300'>
                        <p class='font-bold mb-1'><i class='fas fa-clock'></i> Pendaftaran Berhasil, Menunggu Persetujuan!</p>
                        <p>Akun Anda telah dicatat dan sedang dalam tahap verifikasi oleh Admin. Silakan hubungi kasir/admin untuk konfirmasi pembayaran langganan, setelah itu akun Anda akan diaktifkan untuk <a href='login.php' class='underline font-bold text-blue-600'>Login</a>.</p>
                      </div>";
        }
        $stmt->close();
    }
    $cek->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Member - Smart Laundry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen font-sans py-8">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md border-t-4 border-blue-600">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Daftar Member</h2>
        
        <div class="bg-blue-50 text-blue-800 text-sm p-3 rounded mb-6 border border-blue-200">
            <p class="font-bold mb-1"><i class="fas fa-star text-yellow-500"></i> Keuntungan Member:</p>
            <ul class="list-disc pl-5">
                <li>Diskon 10% setiap cuci</li>
                <li>Gratis ongkir antar-jemput</li>
                <li>Dapat Tukar Poin Reward</li>
            </ul>
            <p class="mt-2 text-xs italic">*Biaya membership: <strong>Rp 30.000 / bulan</strong>.</p>
        </div>

        <?= $pesan ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap</label>
                <input type="text" name="nama" required class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Masukkan nama Anda">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nomor WhatsApp</label>
                <input type="text" name="no_whatsapp" required class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Contoh: 081234567890">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" required class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Buat password yang aman">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full transition">Daftar Sekarang</button>
        </form>
        <div class="mt-4 text-center text-sm text-gray-600">
            Sudah punya akun? <a href="login.php" class="text-blue-600 hover:underline font-semibold">Masuk di sini</a>
        </div>
        <div class="mt-4 text-center">
            <a href="index.php" class="text-sm text-gray-500 hover:text-blue-600">&larr; Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>