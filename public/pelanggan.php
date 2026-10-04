<?php
// public/pelanggan.php
require_once __DIR__ . '/../config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("<div style='padding:40px; text-align:center; font-family:sans-serif;'>
            <h2>Halaman Tidak Ditemukan</h2>
            <p>Parameter pelanggan tidak valid.</p>
         </div>");
}

// 1. Ambil Profil Pelanggan
$stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE id = ?");
$stmt->execute([$id]);
$pelanggan = $stmt->fetch();

if (!$pelanggan) {
    die("<div style='padding:40px; text-align:center; font-family:sans-serif;'>
            <h2>Pelanggan Tidak Ditemukan</h2>
            <p>Data tidak tersedia di sistem.</p>
         </div>");
}

// 2. Fetch Riwayat Transaksi
$historyStmt = $pdo->prepare("
    SELECT *, (harga_produk * jumlah) AS total_harga 
    FROM penjualan 
    WHERE pelanggan_id = ? OR (pelanggan_id IS NULL AND nama = ?) 
    ORDER BY tgl DESC, id DESC
");
$historyStmt->execute([$id, $pelanggan['nama']]);
$historyList = $historyStmt->fetchAll();

// 3. Hitung Ringkasan Belanja
$totalAkumulasi = 0;
$totalLunas = 0;
$totalBelumLunas = 0;

foreach ($historyList as $item) {
    $subtotal = floatval($item['total_harga']);
    $totalAkumulasi += $subtotal;
    if (strtolower($item['status'] ?? '') === 'lunas') {
        $totalLunas += $subtotal;
    } else {
        $totalBelumLunas += $subtotal;
    }
}

// Helper Masking NIK
function maskNIK($nik) {
    if (empty($nik)) return '-';
    $len = strlen($nik);
    if ($len <= 4) return $nik;
    return substr($nik, 0, 4) . str_repeat('*', $len - 8) . substr($nik, -4);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - Dapoer Ela 85</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased p-4 md:p-6">

    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Public Header Bar -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="bg-orange-600 text-white p-2.5 rounded-xl text-xl font-bold">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 leading-tight">Dapoer Ela 85</h1>
                    <p class="text-xs text-gray-500">Lembar Catatan Transaksi Penjualan</p>
                </div>
            </div>
            <a href="../index.php" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg font-semibold transition">
                Halaman Utama
            </a>
        </div>

        <!-- Identitas Pelanggan (Disamarkan) -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase">Pelanggan Terhormat</p>
                    <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= htmlspecialchars(maskName($pelanggan['nama'])) ?></h2>
                </div>
                <?= renderStatusBadge($pelanggan['status']) ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-gray-600">
                <p><i class="fas fa-building w-4 text-gray-400"></i> Instansi: <b><?= htmlspecialchars($pelanggan['instansi'] ?: '-') ?></b></p>
            </div>
        </div>

        <!-- Cards Ringkasan Pembayaran -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase">Total Akumulasi Transaksi</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?= formatRupiah($totalAkumulasi) ?></p>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Sudah Lunas</p>
                <p class="text-2xl font-bold text-emerald-700 mt-1"><?= formatRupiah($totalLunas) ?></p>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <p class="text-xs font-semibold text-rose-600 uppercase">Sisa Belum Lunas</p>
                <p class="text-2xl font-bold text-rose-700 mt-1"><?= formatRupiah($totalBelumLunas) ?></p>
            </div>
        </div>

        <!-- Tabel Rincian Transaksi -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
            <h3 class="font-bold text-gray-800 text-base">Rincian Riwayat Transaksi</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="p-3.5">Tanggal</th>
                            <th class="p-3.5">Nama Produk</th>
                            <th class="p-3.5 text-right">Harga Satuan</th>
                            <th class="p-3.5 text-center">Jumlah</th>
                            <th class="p-3.5 text-right">Total Harga</th>
                            <th class="p-3.5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($historyList)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-6 text-gray-400">Belum ada catatan transaksi.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historyList as $h): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="p-3.5 whitespace-nowrap"><?= date('d/m/Y', strtotime($h['tgl'])) ?></td>
                                    <td class="p-3.5 font-medium text-gray-800"><?= htmlspecialchars($h['nama_produk']) ?></td>
                                    <td class="p-3.5 text-right"><?= formatRupiah($h['harga_produk']) ?></td>
                                    <td class="p-3.5 text-center font-semibold"><?= $h['jumlah'] ?></td>
                                    <td class="p-3.5 text-right font-bold text-orange-600"><?= formatRupiah($h['total_harga']) ?></td>
                                    <td class="p-3.5 text-center whitespace-nowrap"><?= renderStatusBadge($h['status'] ?? 'lunas') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="text-center text-xs text-gray-400 py-4">
            &copy; <?= date('Y') ?> Dapoer Ela 85. Seluruh Hak Cipta Dilindungi.
        </footer>

    </div>

</body>
</html>
