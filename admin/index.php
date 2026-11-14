<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require '../koneksi.php';

// ==========================================
// 1. PROSES AKSI DATA (POST NATIVE WITH SOFT-DELETE)
// ==========================================

// Proses Update Status Pesanan Cucian
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stats'])) {
    $id_pesanan = $_POST['id_pesanan'];
    $stats_baru = $_POST['stats_baru'];
    $stmt = $conn->prepare("UPDATE pesanan SET stats = ? WHERE id = ?");
    $stmt->bind_param("si", $stats_baru, $id_pesanan);
    $stmt->execute();
    $stmt->close();
}

// Proses Hapus Data Pesanan (SOFT-DELETE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_pesanan'])) {
    $id_hapus = $_POST['id_pesanan'];
    $status_arsip = 'Dihapus_Selesai';
    $stmt = $conn->prepare("UPDATE pesanan SET stats = ? WHERE id = ?");
    $stmt->bind_param("si", $status_arsip, $id_hapus);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?page=transaksi");
    exit;
}

// Proses Action Approval Member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_member'])) {
    $id_member = $_POST['id_member'];
    $status_baru = $_POST['status_baru']; 
    $stmt = $conn->prepare("UPDATE member SET status_member = ? WHERE id = ?");
    $stmt->bind_param("si", $status_baru, $id_member);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php?page=member");
    exit;
}

// ==========================================
// 2. QUERY RETRIEVE DATA STATISTIK BULANAN
// ==========================================

// A. Hitung Total Omzet Bulan Ini (Terhitung sejak pesanan disetujui/diproses, bukan menunggu konfirmasi)
$query_omzet = "SELECT SUM(total_harga) AS total FROM pesanan 
                WHERE MONTH(tanggal_pesan) = MONTH(CURRENT_DATE()) 
                AND YEAR(tanggal_pesan) = YEAR(CURRENT_DATE())
                AND stats != 'Menunggu Konfirmasi Admin' 
                AND stats != 'Dihapus_Selesai'";
$res_omzet = $conn->query($query_omzet)->fetch_assoc();
$total_omzet = $res_omzet['total'] ?? 0;

// B. Hitung Berat Produksi (Kg) Bulan Ini (Terhitung sejak pesanan disetujui/diproses)
$query_kg = "SELECT SUM(jumlah_kg) AS total_kg FROM pesanan 
             WHERE MONTH(tanggal_pesan) = MONTH(CURRENT_DATE()) 
             AND YEAR(tanggal_pesan) = YEAR(CURRENT_DATE())
             AND stats != 'Menunggu Konfirmasi Admin' 
             AND stats != 'Dihapus_Selesai'";
$res_kg = $conn->query($query_kg)->fetch_assoc();
$total_kg = $res_kg['total_kg'] ?? 0;

// C. Hitung Total Member Aktif
$query_tot_member = "SELECT COUNT(*) AS total FROM member WHERE status_member = 'Aktif'";
$res_tot_member = $conn->query($query_tot_member)->fetch_assoc();
$total_member_aktif = $res_tot_member['total'] ?? 0;

// D. Ambil Data Antrean Member Baru (Pending)
$query_member = "SELECT * FROM member WHERE status_member = 'Pending' ORDER BY id ASC";
$result_member = $conn->query($query_member);

// E. Ambil Data Pesanan Transaksi Aktif (Kecuali yang berstatus 'Dihapus_Selesai')
$query_pesanan = "SELECT p.*, m.poin AS member_poin, m.no_whatsapp 
                  FROM pesanan p 
                  LEFT JOIN member m ON p.member_id = m.id 
                  WHERE p.stats != 'Dihapus_Selesai' 
                  ORDER BY p.tanggal_pesan DESC";
$result_pesanan = $conn->query($query_pesanan);

// ==========================================
// 3. ROUTING PARAMETER HALAMAN (MENU)
// ==========================================
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Sinkronisasi Judul Topbar Halaman Aktif
$header_title = "Kelola Data Laundry";
if ($page == 'member') { $header_title = "Persetujuan Member Baru"; }
if ($page == 'transaksi') { $header_title = "Kelola Antrean Cucian"; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Smart Laundry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans flex h-screen overflow-hidden">

    <!-- SISI KIRI: SIDEBAR LAYOUT -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex shrink-0">
        <div class="h-16 flex items-center justify-center border-b border-gray-700 bg-gray-800">
            <h1 class="text-sm font-bold tracking-wider uppercase text-blue-400 text-center px-2">
                <i class="fas fa-tshirt"></i> Dashboard Admin Laundry
            </h1>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="index.php?page=dashboard" class="flex items-center gap-3 px-4 py-3 rounded-lg text-white font-medium transition <?= $page == 'dashboard' ? 'bg-blue-600 shadow' : 'hover:bg-gray-800 text-gray-300' ?>">
                <i class="fas fa-chart-line w-5"></i> Kelola Data Laundry
            </a>
            <a href="index.php?page=member" class="flex items-center gap-3 px-4 py-3 rounded-lg text-white font-medium transition <?= $page == 'member' ? 'bg-blue-600 shadow' : 'hover:bg-gray-800 text-gray-300' ?>">
                <i class="fas fa-user-clock w-5"></i> Persetujuan Member
            </a>
            <a href="index.php?page=transaksi" class="flex items-center gap-3 px-4 py-3 rounded-lg text-white font-medium transition <?= $page == 'transaksi' ? 'bg-blue-600 shadow' : 'hover:bg-gray-800 text-gray-300' ?>">
                <i class="fas fa-list-alt w-5"></i> Kelola Antrean
            </a>
        </nav>
        <div class="p-4 border-t border-gray-700 space-y-2">
            <a href="../index.php" class="flex items-center gap-3 text-sm text-gray-400 hover:text-white transition">
                <i class="fas fa-external-link-alt w-5"></i> Cek Web Publik
            </a>
            <a href="logout.php" class="flex items-center gap-3 text-sm text-red-400 hover:text-red-300 transition">
                <i class="fas fa-sign-out-alt w-5"></i> Logout
            </a>
        </div>
    </aside>

    <!-- SISI KANAN: TOPBAR & CONTAINER VIEW DINAMIS -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto">
        <header class="h-16 bg-white shadow flex items-center justify-between px-8 shrink-0">
            <h2 class="text-2xl font-semibold text-gray-800"><?= $header_title ?></h2>
            <a href="logout.php" class="md:hidden text-red-500 font-bold"><i class="fas fa-sign-out-alt"></i></a>
        </header>

        <div class="p-8 flex-1">
            <?php if ($page == 'dashboard'): ?>
                <!-- VIEW MENU 1: PANTAU OMZET (DASHBOARD) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Pemasukan Bulan Ini</p>
                            <h3 class="text-2xl font-black text-gray-800">Rp <?= number_format($total_omzet, 0, ',', '.') ?></h3>
                        </div>
                        <div class="bg-blue-100 text-blue-600 h-12 w-12 rounded-lg flex items-center justify-center text-xl shadow-inner">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Berat Produksi Bulanan</p>
                            <h3 class="text-2xl font-black text-gray-800"><?= $total_kg ?> <span class="text-sm font-semibold text-gray-500">Kg</span></h3>
                        </div>
                        <div class="bg-green-100 text-green-600 h-12 w-12 rounded-lg flex items-center justify-center text-xl shadow-inner">
                            <i class="fas fa-weight"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Member Aktif</p>
                            <h3 class="text-2xl font-black text-gray-800"><?= $total_member_aktif ?> <span class="text-sm font-semibold text-gray-500">User</span></h3>
                        </div>
                        <div class="bg-yellow-100 text-yellow-600 h-12 w-12 rounded-lg flex items-center justify-center text-xl shadow-inner">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h4 class="text-base font-bold text-blue-900 mb-1"><i class="fas fa-info-circle"></i> Catatan Sistem Informasi Pemilik:</h4>
                    <p class="text-sm text-blue-800 leading-relaxed">Akumulasi pemasukan (*omzet*) dan berat produksi di atas otomatis terhitung sejak pesanan divalidasi/disetujui oleh admin (status Diproses dan seterusnya) hingga selesai, mencerminkan arus kas pemasukan real-time.</p>
                </div>

            <?php elseif ($page == 'member'): ?>
                <!-- VIEW MENU 2: PERSETUJUAN MEMBER -->
                <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal text-left">
                            <thead>
                                <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider font-bold border-b border-gray-200">
                                    <th class="px-6 py-4">Nama Pendaftar</th>
                                    <th class="px-6 py-4">Nomor WhatsApp</th>
                                    <th class="px-6 py-4 text-center">Aksi Verifikasi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <?php if($result_member->num_rows > 0): ?>
                                    <?php while($row_m = $result_member->fetch_assoc()): ?>
                                    <tr class="hover:bg-yellow-50/50 transition border-b border-gray-100">
                                        <td class="px-6 py-4 font-bold text-gray-800"><?= htmlspecialchars($row_m['nama']) ?></td>
                                        <td class="px-6 py-4 text-blue-600 font-semibold">
                                            <a href="https://wa.me/<?= htmlspecialchars($row_m['no_whatsapp']) ?>" target="_blank" class="hover:underline">
                                                <i class="fab fa-whatsapp text-green-500"></i> <?= htmlspecialchars($row_m['no_whatsapp']) ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <form method="POST" action="" class="flex justify-center gap-2">
                                                <input type="hidden" name="id_member" value="<?= $row_m['id'] ?>">
                                                <input type="hidden" name="action_member" value="1">
                                                <button type="submit" name="status_baru" value="Aktif" class="bg-green-500 hover:bg-green-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition">
                                                    <i class="fas fa-check"></i> Setujui
                                                </button>
                                                <button type="submit" name="status_baru" value="Ditolak" class="bg-red-500 hover:bg-red-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-sm transition" onclick="return confirm('Yakin menolak pendaftaran ini?');">
                                                    <i class="fas fa-times"></i> Tolak
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-400 font-medium bg-gray-50/50">
                                            <i class="fas fa-check-circle text-gray-300 text-3xl mb-2 block"></i>
                                            Tidak ada permintaan pendaftaran member baru saat ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($page == 'transaksi'): ?>
                <!-- VIEW MENU 3: KELOLA ANTREAN -->
                <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal text-left">
                            <thead>
                                <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider font-bold border-b border-gray-200">
                                    <th class="px-6 py-4">Kode Unik</th>
                                    <th class="px-6 py-4">Data Pelanggan</th>
                                    <th class="px-6 py-4">Rincian Layanan & Harga</th>
                                    <th class="px-6 py-4 text-center">Status & Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-gray-700">
                                <?php while($row = $result_pesanan->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50/50 transition border-b border-gray-100">
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-bold text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1 rounded-lg shadow-sm">
                                            <?= htmlspecialchars($row['kode_unik']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-gray-900 text-base"><?= htmlspecialchars($row['nama_pelanggan']) ?></p>
                                        <?php if($row['member_id']): ?>
                                            <div class="mt-1 flex flex-col gap-1">
                                                <div>
                                                    <span class="inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded font-bold border border-yellow-300 shadow-sm">
                                                        <i class="fas fa-star text-yellow-500"></i> Member
                                                    </span>
                                                </div>
                                                <p class="text-xs font-semibold text-gray-600"><i class="fab fa-whatsapp text-green-500"></i> <?= htmlspecialchars($row['no_whatsapp']) ?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-1">
                                                <span class="inline-block bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded font-bold border border-gray-200">
                                                    <i class="fas fa-user text-gray-400"></i> Non-Member
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-400 font-medium mt-3"><i class="far fa-calendar-alt"></i> <?= date('d M Y, H:i', strtotime($row['tanggal_pesan'])) ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-gray-800 mb-1"><?= htmlspecialchars($row['jenis_layanan']) ?> (<?= $row['jumlah_kg'] ?> Kg)</p>
                                        <div class="mb-2 bg-gray-50 p-3 rounded-lg border border-gray-100 space-y-0.5">
                                            <?php if($row['biaya_ongkir'] > 0): ?>
                                                <p class="text-xs text-gray-500">+ Ongkir: Rp <?= number_format($row['biaya_ongkir'], 0, ',', '.') ?></p>
                                            <?php endif; ?>
                                            <?php if($row['diskon'] > 0): ?>
                                                <p class="text-xs text-green-600 font-bold">Diskon (10%): - Rp <?= number_format($row['diskon'], 0, ',', '.') ?></p>
                                            <?php endif; ?>
                                            <?php if($row['poin_digunakan'] > 0): ?>
                                                <p class="text-xs text-yellow-600 font-bold">Tukar Poin (<?= $row['poin_digunakan'] ?> Poin): - Rp <?= number_format($row['poin_digunakan'] * 10, 0, ',', '.') ?></p>
                                            <?php endif; ?>
                                            <p class="text-lg font-black text-blue-600 border-t border-gray-200 pt-1 mt-1">
                                                Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>
                                            </p>
                                        </div>
                                        <div class="text-xs font-bold text-gray-500 space-y-1">
                                            <p><i class="fas fa-motorcycle text-blue-400 w-4"></i> <?= htmlspecialchars($row['opsi_pengiriman']) ?></p>
                                            <p><i class="fas fa-wallet text-green-400 w-4"></i> <?= htmlspecialchars($row['metode_pembayaran']) ?></p>
                                            
                                            <!-- LINK BUKTI PEMBAYARAN -->
                                            <?php if(!empty($row['bukti_pembayaran'])): ?>
                                                <p class="mt-1">
                                                    <i class="fas fa-image text-purple-500 w-4"></i> 
                                                    <a href="../uploads/<?= htmlspecialchars($row['bukti_pembayaran']) ?>" target="_blank" class="text-blue-600 hover:underline bg-blue-50 px-2 py-0.5 rounded border border-blue-200 inline-block mt-0.5">
                                                        <i class="fas fa-external-link-alt"></i> Lihat Bukti Bayar
                                                    </a>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        
                                        <!-- Tombol Cepat Verifikasi jika Status Menunggu Konfirmasi -->
                                        <?php if($row['stats'] == 'Menunggu Konfirmasi Admin'): ?>
                                            <div class="mb-3 p-3 bg-orange-50 border border-orange-200 rounded-lg text-left">
                                                <p class="text-xs font-bold text-orange-800 mb-2"><i class="fas fa-exclamation-circle"></i> Perlu Verifikasi Pembayaran</p>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="id_pesanan" value="<?= $row['id'] ?>">
                                                    <input type="hidden" name="stats_baru" value="Diproses">
                                                    <button type="submit" name="update_stats" class="w-full bg-green-600 hover:bg-green-700 text-white py-1.5 px-3 rounded text-xs font-bold shadow-sm transition">
                                                        <i class="fas fa-check-circle"></i> Setujui & Proses Cucian
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>

                                        <form method="POST" action="" class="flex flex-col sm:flex-row justify-center gap-1.5 items-center">
                                            <input type="hidden" name="id_pesanan" value="<?= $row['id'] ?>">
                                            <select name="stats_baru" class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm bg-white focus:outline-none focus:border-blue-500 font-medium w-full sm:w-auto">
                                                <option value="Menunggu Konfirmasi Admin" <?= $row['stats'] == 'Menunggu Konfirmasi Admin' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                                <option value="Diproses" <?= $row['stats'] == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                                <option value="Pengeringan" <?= $row['stats'] == 'Pengeringan' ? 'selected' : '' ?>>Pengeringan</option>
                                                <option value="Siap Diambil" <?= $row['stats'] == 'Siap Diambil' ? 'selected' : '' ?>>Siap Diambil</option>
                                                <option value="Sedang Diantarkan" <?= $row['stats'] == 'Sedang Diantarkan' ? 'selected' : '' ?>>Sedang Diantarkan</option>
                                                <option value="Selesai" <?= $row['stats'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                            </select>
                                            <button type="submit" name="update_stats" class="bg-blue-600 hover:bg-blue-700 text-white h-8 w-8 rounded-lg text-sm flex items-center justify-center shadow-sm transition shrink-0" title="Simpan Perubahan">
                                                <i class="fas fa-save"></i>
                                            </button>
                                            <button type="submit" name="delete_pesanan" onclick="return confirm('Yakin ingin menghapus data antrean cucian ini dari monitor kerja?');" class="bg-red-500 hover:bg-red-600 text-white h-8 w-8 rounded-lg text-sm flex items-center justify-center shadow-sm transition shrink-0" title="Hapus Transaksi">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if($result_pesanan->num_rows == 0): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-400 font-medium">
                                        <i class="fas fa-inbox text-3xl text-gray-300 mb-2 block"></i>
                                        Tidak ada antrean pesanan masuk saat ini.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>