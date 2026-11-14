<?php
session_start();
require 'koneksi.php';

// Menangkap pesan sukses dari session agar tidak dobel saat di-refresh
$pesan_sukses = "";
if (isset($_SESSION['flash_pesan'])) {
    $pesan_sukses = $_SESSION['flash_pesan'];
    unset($_SESSION['flash_pesan']);
}

$hasil_tracking = null;

$is_member = isset($_SESSION['member_id']);
$member_id = $is_member ? $_SESSION['member_id'] : null;
$member_nama = $is_member ? $_SESSION['member_nama'] : '';
$member_poin = 0;

if ($is_member) {
    $stmt_poin = $conn->prepare("SELECT poin FROM member WHERE id = ?");
    $stmt_poin->bind_param("i", $member_id);
    $stmt_poin->execute();
    $res_poin = $stmt_poin->get_result()->fetch_assoc();
    $member_poin = $res_poin['poin'];
    $stmt_poin->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pesan'])) {
    $nama = $is_member ? $member_nama : $_POST['nama_pelanggan'];
    $layanan = $_POST['jenis_layanan'];
    $jumlah = $_POST['jumlah_kg'];
    $catatan = $_POST['catatan'];
    $opsi_pengiriman = $_POST['opsi_pengiriman'];
    $metode_bayar = $_POST['metode_pembayaran']; 

    // VALIDASI KETAT BACKEND: Jika Transfer Bank atau QRIS, bukti pembayaran wajib diisi
    $bukti_nama = '';
    if ($metode_bayar == 'Transfer Bank' || $metode_bayar == 'QRIS') {
        if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] != 0) {
            $_SESSION['flash_pesan'] = "<div class='text-red-600 font-bold'>Gagal! Metode pembayaran Transfer Bank dan QRIS wajib mengunggah bukti pembayaran.</div>";
            header("Location: index.php");
            exit;
        }

        $file_tmp = $_FILES['bukti_pembayaran']['tmp_name'];
        $file_name = $_FILES['bukti_pembayaran']['name'];
        $ekstensi = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $ekstensi_boleh = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($ekstensi, $ekstensi_boleh)) {
            $_SESSION['flash_pesan'] = "<div class='text-red-600 font-bold'>Format file tidak didukung! Harap upload file berformat JPG, JPEG, PNG, atau PDF.</div>";
            header("Location: index.php");
            exit;
        }

        $bukti_nama = 'bukti_' . time() . '_' . rand(1000, 9999) . '.' . $ekstensi;
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        move_uploaded_file($file_tmp, 'uploads/' . $bukti_nama);
    }
    
    $harga_per_kg = 7000; 
    if ($layanan == 'Express') $harga_per_kg = 12000;
    if ($layanan == 'Cuci Setrika') $harga_per_kg = 10000;

    $subtotal = $harga_per_kg * $jumlah;
    $diskon = $is_member ? ($subtotal * 0.10) : 0;
    
    // LOGIKA ONGKIR BACKEND: Jika Diantarkan, dasar non-member 2000, member 0. Jika > 4kg tambah 1000.
    $biaya_ongkir = 0;
    if ($opsi_pengiriman == 'Diantarkan') {
        $biaya_ongkir = $is_member ? 0 : 2000;
        if ($jumlah > 4) {
            $biaya_ongkir += 1000;
        }
    }
    
    $total_sementara = ($subtotal - $diskon) + $biaya_ongkir;

    $poin_digunakan = 0;
    $diskon_poin = 0;
    if ($is_member && isset($_POST['pakai_poin'])) {
        $maks_poin_dibutuhkan = ceil($total_sementara / 10);
        $poin_digunakan = min($member_poin, $maks_poin_dibutuhkan);
        $diskon_poin = $poin_digunakan * 10;
    }

    $total_harga = $total_sementara - $diskon_poin;
    $kode_unik = "LNDRY-" . strtoupper(substr(md5(uniqid()), 0, 4));
    
    // Status awal pesanan: Bayar di tempat langsung 'Pesanan Diterima', Transfer/QRIS 'Menunggu Konfirmasi Admin'
    $status_awal = ($metode_bayar == 'Bayar di Tempat') ? 'Pesanan Diterima' : 'Menunggu Konfirmasi Admin';
    
    $stmt = $conn->prepare("INSERT INTO pesanan (member_id, kode_unik, nama_pelanggan, jenis_layanan, jumlah_kg, catatan, opsi_pengiriman, biaya_ongkir, diskon, poin_digunakan, total_harga, metode_pembayaran, bukti_pembayaran, stats) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssiiiisss", $member_id, $kode_unik, $nama, $layanan, $jumlah, $catatan, $opsi_pengiriman, $biaya_ongkir, $diskon, $poin_digunakan, $total_harga, $metode_bayar, $bukti_nama, $status_awal);
    
    if ($stmt->execute()) {
        $pesan_flash = "<div class='mb-2 text-lg'>Pesanan berhasil! Kode Unik: <strong class='font-mono bg-white px-2 py-1 rounded text-blue-700'>$kode_unik</strong></div>";
        $pesan_flash .= "<div class='text-sm mb-2 font-bold'>Total Tagihan Akhir: Rp " . number_format($total_harga, 0, ',', '.') . "</div>";
        
        if ($poin_digunakan > 0) {
            $pesan_flash .= "<div class='text-sm text-green-700 font-bold mb-2'>- Anda menggunakan $poin_digunakan Poin (Potongan Rp " . number_format($diskon_poin, 0, ',', '.') . ")</div>";
        }

        if ($metode_bayar == 'Transfer Bank') {
            $pesan_flash .= "<div class='mt-3 p-4 bg-white bg-opacity-50 border border-green-300 rounded text-sm text-gray-800'><p class='font-bold mb-1'><i class='fas fa-university'></i> Transfer Bank:</p><p>Bukti transfer berhasil diunggah. Status: <strong>Menunggu Konfirmasi Admin</strong>.</p></div>";
        } elseif ($metode_bayar == 'QRIS') {
            $pesan_flash .= "<div class='mt-3 p-4 bg-white bg-opacity-50 border border-green-300 rounded text-sm text-gray-800'><p class='font-bold mb-1'><i class='fas fa-qrcode'></i> QRIS / E-Wallet:</p><p>Bukti QRIS berhasil diunggah. Status: <strong>Menunggu Konfirmasi Admin</strong>.</p></div>";
        } else {
            $pesan_flash .= "<div class='mt-3 p-4 bg-white bg-opacity-50 border border-green-300 rounded text-sm text-gray-800'><p class='font-bold mb-1'><i class='fas fa-money-bill-wave'></i> Bayar di Tempat:</p><p>Pembayaran tunai dilakukan saat serah terima pakaian.</p></div>";
        }

        if ($is_member) {
            $poin_didapat = $jumlah * 10;
            
            $update_poin = $conn->prepare("UPDATE member SET poin = poin - ? + ? WHERE id = ?");
            $update_poin->bind_param("iii", $poin_digunakan, $poin_didapat, $member_id);
            $update_poin->execute();
            $update_poin->close();
            
            $pesan_flash .= "<div class='mt-3 font-bold text-yellow-600'>⭐ Anda mendapat tambahan $poin_didapat Poin baru!</div>";
        }

        $_SESSION['flash_pesan'] = $pesan_flash;
        header("Location: index.php");
        exit;
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['track'])) {
    $kode_track = $_POST['kode_unik'];
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE kode_unik = ?");
    $stmt->bind_param("s", $kode_track);
    $stmt->execute();
    $hasil_tracking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Laundry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script>
        const isMember = <?= $is_member ? 'true' : 'false' ?>;
        const memberPoin = <?= $member_poin ?>;

        function hitungTotal() {
            const layanan = document.getElementById('jenis_layanan').value;
            const berat = parseFloat(document.getElementById('jumlah_kg').value) || 0;
            const pengiriman = document.getElementById('opsi_pengiriman').value;
            
            let hargaPerKg = 7000;
            if (layanan === 'Express') hargaPerKg = 12000;
            if (layanan === 'Cuci Setrika') hargaPerKg = 10000;
            
            let subtotal = hargaPerKg * berat;
            let diskon = isMember ? (subtotal * 0.10) : 0;
            
            // LOGIKA ONGKIR FRONTEND: Tambah 1000 jika berat > 4 kg
            let ongkir = 0;
            if (pengiriman === 'Diantarkan') {
                ongkir = isMember ? 0 : 2000;
                if (berat > 4) {
                    ongkir += 1000;
                }
            }
            
            let totalSementara = (subtotal - diskon) + ongkir;

            let diskonPoin = 0;
            const cbPoin = document.getElementById('pakai_poin');
            if (isMember && cbPoin && cbPoin.checked) {
                let maxPoin = Math.ceil(totalSementara / 10);
                let poinDipakai = Math.min(memberPoin, maxPoin);
                diskonPoin = poinDipakai * 10;
            }
            
            const totalAkhir = totalSementara - diskonPoin;
            
            document.getElementById('display_subtotal').innerText = "Rp " + subtotal.toLocaleString('id-ID');
            document.getElementById('display_ongkir').innerText = "Rp " + ongkir.toLocaleString('id-ID');
            document.getElementById('display_diskon').innerText = "- Rp " + diskon.toLocaleString('id-ID');
            
            const trPoin = document.getElementById('tr_diskon_poin');
            if (diskonPoin > 0) {
                trPoin.classList.remove('hidden');
                document.getElementById('display_diskon_poin').innerText = "- Rp " + diskonPoin.toLocaleString('id-ID');
            } else {
                trPoin.classList.add('hidden');
            }

            document.getElementById('display_total').innerText = "Rp " + totalAkhir.toLocaleString('id-ID');
        }

        // Fungsi menampilkan detail metode pembayaran & wajibkan upload bukti jika Transfer/QRIS
        function tampilkanDetailPembayaran() {
            const metode = document.getElementById('metode_pembayaran').value;
            const boxTransfer = document.getElementById('box-transfer');
            const boxQris = document.getElementById('box-qris');
            const boxUpload = document.getElementById('box-upload');
            const inputBukti = document.getElementById('bukti_pembayaran');

            boxTransfer.classList.add('hidden');
            boxQris.classList.add('hidden');
            boxUpload.classList.add('hidden');
            inputBukti.required = false;

            if (metode === 'Transfer Bank') {
                boxTransfer.classList.remove('hidden');
                boxUpload.classList.remove('hidden');
                inputBukti.required = true;
            } else if (metode === 'QRIS') {
                boxQris.classList.remove('hidden');
                boxUpload.classList.remove('hidden');
                inputBukti.required = true;
            }
        }

        function salinTeks(idInput) {
            const inputElement = document.getElementById(idInput);
            inputElement.select();
            inputElement.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(inputElement.value);
            alert("Nomor rekening berhasil disalin: " + inputElement.value);
        }
    </script>
</head>
<body class="bg-gray-50 font-sans leading-normal tracking-normal">
    
    <nav class="bg-white shadow px-8 py-4 mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <h1 class="text-2xl font-bold text-blue-600 uppercase tracking-wider"><i class="fas fa-tshirt"></i> Smart Laundry</h1>
        <div class="text-sm font-semibold text-gray-700 flex items-center gap-4">
            <?php if($is_member): ?>
                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full shadow-sm border border-yellow-300"><i class="fas fa-coins text-yellow-500"></i> Poin: <?= $member_poin ?></span>
                <span>Halo, <?= htmlspecialchars($member_nama) ?>!</span>
                <a href="logout.php" class="text-red-500 hover:underline">Logout</a>
            <?php else: ?>
                <a href="login.php" class="bg-blue-100 text-blue-700 px-4 py-2 rounded font-bold hover:bg-blue-200">Login Member</a>
                <a href="admin/login.php" class="text-gray-500 hover:text-blue-600"><i class="fas fa-user-shield"></i> Admin</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container mx-auto max-w-5xl px-8 pb-12">
        
        <div class="bg-blue-900 text-white p-4 rounded-lg shadow mb-8 flex flex-wrap justify-between items-center text-sm md:text-base">
            <div class="font-bold mb-2 md:mb-0"><i class="fas fa-tags text-yellow-400"></i> Daftar Harga Per Kilogram:</div>
            <div class="flex gap-4 font-semibold">
                <span>Cuci Biasa: <span class="text-yellow-400">Rp 7.000</span></span>
                <span>Setrika: <span class="text-yellow-400">Rp 10.000</span></span>
                <span>Express: <span class="text-yellow-400">Rp 12.000</span></span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-blue-500">
                <h2 class="text-2xl font-semibold mb-6 text-gray-800 border-b pb-2"><i class="fas fa-plus-circle text-blue-500"></i> Buat Pesanan</h2>
                
                <?php if($pesan_sukses): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 mb-6 shadow-sm rounded">
                        <?= $pesan_sukses ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" onsubmit="document.getElementById('btn_pesan').style.pointerEvents='none'; document.getElementById('btn_pesan').classList.add('opacity-50'); document.getElementById('btn_pesan').innerHTML='Memproses...';">
                    
                    <input type="hidden" name="pesan" value="1">

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Pelanggan</label>
                        <?php if($is_member): ?>
                            <input type="text" value="<?= htmlspecialchars($member_nama) ?>" disabled class="shadow-sm border rounded w-full py-2 px-3 text-gray-500 bg-gray-100 cursor-not-allowed">
                        <?php else: ?>
                            <input type="text" name="nama_pelanggan" required class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Masukkan nama Anda">
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Jenis Layanan</label>
                            <select name="jenis_layanan" id="jenis_layanan" onchange="hitungTotal()" class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                <option value="Cuci Biasa">Cuci Biasa</option>
                                <option value="Express">Express</option>
                                <option value="Cuci Setrika">Cuci Setrika</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Jumlah (Kg)</label>
                            <input type="number" name="jumlah_kg" id="jumlah_kg" min="1" step="1" oninput="hitungTotal()" required class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Misal: 3">
                        </div>
                    </div>

                    <div class="mb-4 border-t pt-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2"><i class="fas fa-motorcycle text-blue-500"></i> Opsi Pengiriman</label>
                        <select name="opsi_pengiriman" id="opsi_pengiriman" onchange="hitungTotal()" class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium">
                            <option value="Ambil Sendiri">Ambil Sendiri di Laundry (Gratis)</option>
                            <?php if($is_member): ?>
                                <option value="Diantarkan">Antar/Jemput (GRATIS - Member, +Rp1rb jika > 4kg)</option>
                            <?php else: ?>
                                <option value="Diantarkan">Antar/Jemput (+ Rp 2.000, +Rp1rb jika > 4kg)</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2"><i class="fas fa-wallet text-green-500"></i> Metode Pembayaran</label>
                        <select name="metode_pembayaran" id="metode_pembayaran" onchange="tampilkanDetailPembayaran()" class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400 font-medium">
                            <option value="Bayar di Tempat">Bayar di Tempat (Tunai)</option>
                            <option value="Transfer Bank">Transfer Bank (BCA / Mandiri)</option>
                            <option value="QRIS">QRIS / E-Wallet</option>
                        </select>
                    </div>

                    <!-- BOX TRANSFER BANK -->
                    <div id="box-transfer" class="hidden mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-gray-700">
                        <p class="font-bold text-blue-800 mb-2"><i class="fas fa-university"></i> Informasi Rekening Transfer:</p>
                        <div class="mb-2">
                            <p class="text-xs text-gray-500 font-semibold">Bank BCA</p>
                            <div class="flex items-center gap-2 mt-1">
                                <input type="text" id="rek_bca" value="1234567890" readonly class="bg-white border rounded px-2 py-1 text-sm font-mono w-full">
                                <button type="button" onclick="salinTeks('rek_bca')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-bold transition">Salin</button>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">a.n. Smart Laundry Official</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold">Bank Mandiri</p>
                            <div class="flex items-center gap-2 mt-1">
                                <input type="text" id="rek_mandiri" value="0987654321" readonly class="bg-white border rounded px-2 py-1 text-sm font-mono w-full">
                                <button type="button" onclick="salinTeks('rek_mandiri')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-bold transition">Salin</button>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">a.n. Smart Laundry Official</p>
                        </div>
                    </div>

                    <!-- BOX QRIS -->
                    <div id="box-qris" class="hidden mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-center text-sm text-gray-700">
                        <p class="font-bold text-green-800 mb-2"><i class="fas fa-qrcode"></i> Scan QRIS untuk Pembayaran:</p>
                        <div class="flex justify-center mb-2">
                            <img src="assets/img/qris.png" alt="QRIS Smart Laundry" class="w-48 h-48 object-contain border bg-white p-2 rounded shadow-sm">
                        </div>
                        <p class="text-xs text-gray-600">Scan menggunakan BCA Mobile, Livin', DANA, OVO, GoPay, atau ShopeePay.</p>
                    </div>

                    <!-- BOX UPLOAD BUKTI PEMBAYARAN -->
                    <div id="box-upload" class="hidden mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-gray-700">
                        <label class="block font-bold text-yellow-900 mb-1"><i class="fas fa-file-upload"></i> Unggah Bukti Pembayaran / Struk <span class="text-red-500">*</span></label>
                        <p class="text-xs text-yellow-700 mb-2">Sertakan screenshot bukti transfer atau berhasil bayar agar admin dapat memverifikasi pesanan Anda.</p>
                        <input type="file" name="bukti_pembayaran" id="bukti_pembayaran" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Catatan Khusus/Alamat</label>
                        <textarea name="catatan" rows="2" class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                    </div>

                    <?php if($is_member && $member_poin > 0): ?>
                    <div class="mb-6 bg-yellow-50 border border-yellow-300 p-4 rounded-lg flex items-start gap-3">
                        <input type="checkbox" name="pakai_poin" id="pakai_poin" onchange="hitungTotal()" class="mt-1 w-5 h-5 text-blue-600 rounded">
                        <div>
                            <label for="pakai_poin" class="font-bold text-yellow-800 cursor-pointer">Gunakan Poin Reward</label>
                            <p class="text-xs text-yellow-700 mt-1">Anda memiliki <?= $member_poin ?> poin. Centang untuk menukarnya menjadi potongan harga (1 Poin = Rp 10). Maksimal diskon akan menyesuaikan total tagihan.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="bg-gray-100 p-5 rounded mb-6 border border-gray-300">
                        <div class="flex justify-between items-center mb-2 text-sm text-gray-600">
                            <span>Subtotal Cucian:</span>
                            <span id="display_subtotal" class="font-semibold">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center mb-2 text-sm text-gray-600">
                            <span>Biaya Ongkir:</span>
                            <span id="display_ongkir" class="font-semibold">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center mb-2 text-sm text-green-600">
                            <span class="font-bold">Diskon Member (10%):</span>
                            <span id="display_diskon" class="font-bold">Rp 0</span>
                        </div>
                        <div id="tr_diskon_poin" class="flex justify-between items-center mb-2 text-sm text-yellow-600 font-bold border-b border-gray-200 pb-2 hidden">
                            <span>Tukar Poin Reward:</span>
                            <span id="display_diskon_poin">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center mt-3">
                            <span class="font-bold text-gray-800 text-lg">Total Bayar:</span>
                            <span id="display_total" class="text-2xl font-black text-blue-600">Rp 0</span>
                        </div>
                    </div>

                    <button type="submit" id="btn_pesan" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded w-full shadow transition"><i class="fas fa-paper-plane"></i> Kirim Pesanan Sekarang</button>
                </form>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-green-500 h-fit">
                <h2 class="text-2xl font-semibold mb-6 text-gray-800 border-b pb-2"><i class="fas fa-search text-green-500"></i> Lacak Status Cucian</h2>
                <form method="POST" action="">
                    <div class="mb-4">
                        <div class="flex">
                            <input type="text" name="kode_unik" required class="shadow-sm border rounded-l w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400 font-mono uppercase" placeholder="LNDRY-XXXX">
                            <button type="submit" name="track" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-r transition"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>

                <?php if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['track'])): ?>
                    <div class="mt-6 border-t pt-6">
                        <?php if($hasil_tracking): ?>
                            <h3 class="font-bold text-lg text-gray-800 mb-3 text-center">Detail Cucian Anda</h3>
                            <div class="bg-gray-50 border rounded p-4 text-sm mb-4">
                                <p class="mb-1"><span class="text-gray-500 font-semibold w-24 inline-block">Nama</span> : <span class="font-bold"><?= htmlspecialchars($hasil_tracking['nama_pelanggan']) ?></span></p>
                                <p class="mb-1"><span class="text-gray-500 font-semibold w-24 inline-block">Layanan</span> : <?= htmlspecialchars($hasil_tracking['jenis_layanan']) ?> (<?= htmlspecialchars($hasil_tracking['jumlah_kg']) ?> Kg)</p>
                                <p class="mb-1"><span class="text-gray-500 font-semibold w-24 inline-block">Pengiriman</span> : <?= htmlspecialchars($hasil_tracking['opsi_pengiriman']) ?></p>
                                <p class="mb-2"><span class="text-gray-500 font-semibold w-24 inline-block">Pembayaran</span> : <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs font-bold"><?= htmlspecialchars($hasil_tracking['metode_pembayaran']) ?></span></p>
                                
                                <?php if(!empty($hasil_tracking['bukti_pembayaran'])): ?>
                                    <p class="mb-2"><span class="text-gray-500 font-semibold w-24 inline-block">Bukti Bayar</span> : <a href="uploads/<?= htmlspecialchars($hasil_tracking['bukti_pembayaran']) ?>" target="_blank" class="text-blue-600 underline font-semibold">Lihat Gambar</a></p>
                                <?php endif; ?>

                                <?php if($hasil_tracking['diskon'] > 0): ?>
                                    <p class="mb-1 text-green-600"><span class="font-semibold w-24 inline-block">Diskon</span> : - Rp <?= number_format($hasil_tracking['diskon'], 0, ',', '.') ?></p>
                                <?php endif; ?>
                                
                                <?php if($hasil_tracking['poin_digunakan'] > 0): ?>
                                    <p class="mb-1 text-yellow-600"><span class="font-semibold w-24 inline-block">Tukar Poin</span> : - Rp <?= number_format($hasil_tracking['poin_digunakan'] * 10, 0, ',', '.') ?> (<?= $hasil_tracking['poin_digunakan'] ?> Poin)</p>
                                <?php endif; ?>

                                <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center text-lg">
                                    <span class="font-bold text-gray-700">Total Tagihan:</span>
                                    <span class="font-black text-blue-600">Rp <?= number_format($hasil_tracking['total_harga'], 0, ',', '.') ?></span>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded text-blue-800 text-center shadow-sm">
                                <p class="text-xs uppercase tracking-widest text-blue-500 font-bold mb-1">Status Operasional</p>
                                <p class="text-xl font-black uppercase">
                                    <?php 
                                        $icon = "fa-info-circle";
                                        if($hasil_tracking['stats'] == 'Siap Diambil') $icon = "fa-box";
                                        if($hasil_tracking['stats'] == 'Sedang Diantarkan') $icon = "fa-motorcycle";
                                        if($hasil_tracking['stats'] == 'Selesai') $icon = "fa-check-double";
                                        if($hasil_tracking['stats'] == 'Menunggu Konfirmasi Admin') $icon = "fa-clock text-orange-500";
                                    ?>
                                    <i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($hasil_tracking['stats']) ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="bg-red-50 text-red-600 p-4 rounded text-center border border-red-200">
                                <p class="font-bold">Data Tidak Ditemukan</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</body>
</html>