<?php
// admin/pelanggan-detail.php
require_once __DIR__ . '/auth.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: pelanggan.php');
    exit;
}

// 1. Ambil Profil Pelanggan Master
$stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE id = ?");
$stmt->execute([$id]);
$pelanggan = $stmt->fetch();

if (!$pelanggan) {
    die("<div style='padding:20px; font-family:sans-serif; color:red;'>Data pelanggan tidak ditemukan. <a href='pelanggan.php'>Kembali ke Master Pelanggan</a></div>");
}

// 2. Ambil Semua Riwayat Transaksi Penjualan milik Pelanggan ini
$historyStmt = $pdo->prepare("
    SELECT *, (harga_produk * jumlah) AS total_harga 
    FROM penjualan 
    WHERE pelanggan_id = ? OR (pelanggan_id IS NULL AND nama = ?) 
    ORDER BY tgl DESC, id DESC
");
$historyStmt->execute([$id, $pelanggan['nama']]);
$historyList = $historyStmt->fetchAll();

// 3. Hitung Kalkulasi Piutang & Keuangan Pelanggan
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

// Domain URL untuk Public Share Link
$publicShareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/public/pelanggan.php?id={$pelanggan['id']}";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pelanggan: <?= htmlspecialchars($pelanggan['nama']) ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased p-6">

    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Top Nav -->
        <div class="flex justify-between items-center">
            <a href="pelanggan.php" class="text-sm font-semibold text-gray-600 hover:text-orange-600 transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali ke Master Pelanggan
            </a>
            <div class="flex items-center gap-2">
                <button onclick="copyPublicLink()" class="text-xs bg-orange-600 hover:bg-orange-700 text-white font-semibold px-3 py-1.5 rounded-lg shadow-sm transition flex items-center gap-1">
                    <i class="fas fa-share-alt"></i> Salin Link Publik
                </button>
            </div>
        </div>

        <!-- Header Card Profil Pelanggan -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 space-y-3">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($pelanggan['nama']) ?></h2>
                    <?= renderStatusBadge($pelanggan['status']) ?>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 pt-2 border-t border-gray-100">
                    <p><i class="fas fa-id-card w-4 text-gray-400"></i> NIK KTP: <b><?= htmlspecialchars($pelanggan['nik_ktp'] ?: '-') ?></b></p>
                    <p><i class="fas fa-phone w-4 text-gray-400"></i> No. Telp: <b><?= htmlspecialchars($pelanggan['no_telp'] ?: '-') ?></b></p>
                    <p><i class="fas fa-building w-4 text-gray-400"></i> Instansi: <b><?= htmlspecialchars($pelanggan['instansi'] ?: '-') ?></b></p>
                    <p><i class="fas fa-map-marker-alt w-4 text-gray-400"></i> Alamat: <b><?= htmlspecialchars($pelanggan['alamat'] ?: '-') ?></b></p>
                </div>
            </div>

            <!-- Public Share Box -->
            <div class="bg-orange-50 p-4 rounded-xl border border-orange-100 flex flex-col justify-between">
                <div>
                    <h4 class="text-xs font-bold text-orange-900 uppercase">Public Share Link</h4>
                    <p class="text-[11px] text-orange-700 mt-1">Gunakan link ini untuk memberikan lembar riwayat transaksi kepada pelanggan (nama & NIK disamarkan).</p>
                </div>
                <div class="mt-3">
                    <input type="text" id="shareInput" readonly value="<?= $publicShareUrl ?>" class="w-full text-xs font-mono p-2 border border-orange-200 rounded-lg bg-white text-gray-600 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase">Total Akumulasi Belanja</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?= formatRupiah($totalAkumulasi) ?></p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Total Terbayar (Lunas)</p>
                <p class="text-2xl font-bold text-emerald-700 mt-1"><?= formatRupiah($totalLunas) ?></p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-semibold text-rose-600 uppercase">Sisa Piutang (Belum Lunas)</p>
                <p class="text-2xl font-bold text-rose-700 mt-1"><?= formatRupiah($totalBelumLunas) ?></p>
            </div>
        </div>

        <!-- Table History -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
            <h3 class="font-bold text-gray-800 text-base">Riwayat Transaksi Penjualan</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="p-3">ID</th>
                            <th class="p-3">Tanggal</th>
                            <th class="p-3">Produk</th>
                            <th class="p-3 text-right">Harga</th>
                            <th class="p-3 text-center">Jumlah</th>
                            <th class="p-3 text-right">Total</th>
                            <th class="p-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($historyList)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-6 text-gray-400">Belum ada riwayat transaksi.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historyList as $h): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-3 text-xs font-mono text-gray-400">#<?= $h['id'] ?></td>
                                    <td class="p-3 whitespace-nowrap"><?= date('d/m/Y', strtotime($h['tgl'])) ?></td>
                                    <td class="p-3 font-medium text-gray-800"><?= htmlspecialchars($h['nama_produk']) ?></td>
                                    <td class="p-3 text-right"><?= formatRupiah($h['harga_produk']) ?></td>
                                    <td class="p-3 text-center font-semibold"><?= $h['jumlah'] ?></td>
                                    <td class="p-3 text-right font-bold text-orange-600"><?= formatRupiah($h['total_harga']) ?></td>
                                    <td class="p-3 text-center whitespace-nowrap"><?= renderStatusBadge($h['status'] ?? 'lunas') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        function copyPublicLink() {
            const input = document.getElementById('shareInput');
            input.select();
            document.execCommand('copy');
            alert('Link publik berhasil disalin!');
        }
    </script>
</body>
</html>
