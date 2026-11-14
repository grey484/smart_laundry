<?php
session_start();
require 'koneksi.php';

if (isset($_SESSION['member_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $no_wa = $_POST['no_whatsapp'];
    $password = $_POST['password'];

    // Menarik data termasuk status_member
    $stmt = $conn->prepare("SELECT id, nama, password, status_member FROM member WHERE no_whatsapp = ?");
    $stmt->bind_param("s", $no_wa);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            
            // CEK STATUS APPROVAL ADMIN
            if ($row['status_member'] === 'Aktif') {
                $_SESSION['member_id'] = $row['id'];
                $_SESSION['member_nama'] = $row['nama'];
                header("Location: index.php");
                exit;
            } elseif ($row['status_member'] === 'Pending') {
                $error = "Akun Anda masih dalam tahap peninjauan. Silakan hubungi Admin untuk mempercepat persetujuan.";
            } else {
                $error = "Pendaftaran Anda ditolak oleh sistem/Admin.";
            }

        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Nomor WhatsApp belum terdaftar!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Member - Smart Laundry</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center h-screen font-sans">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md border-t-4 border-blue-600">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Login Member</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm font-semibold"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nomor WhatsApp</label>
                <input type="text" name="no_whatsapp" required class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Contoh: 081234567890">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" required class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full transition">Masuk</button>
        </form>
        <div class="mt-4 text-center text-sm text-gray-600">
            Belum punya akun? <a href="register.php" class="text-blue-600 hover:underline font-semibold">Daftar sekarang</a>
        </div>
        <div class="mt-4 text-center">
            <a href="index.php" class="text-sm text-gray-500 hover:text-blue-600">&larr; Lanjutkan sebagai Tamu</a>
        </div>
    </div>
</body>
</html>