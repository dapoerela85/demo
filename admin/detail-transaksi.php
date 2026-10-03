<?php
// admin/detail-transaksi.php
require_once __DIR__ . '/auth.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// 1. Ambil Detail Transaksi Utama berdasarkan ID
$stmt = $pdo->prepare("SELECT *, (harga_produk * jumlah) AS total_harga FROM penjualan WHERE id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    die("<div style='padding:20px; font-family:sans-serif; color:red;'>Data transaksi #{$id} tidak ditemukan. <a href='index.php'>Kembali ke Dashboard</a></div>");
}

// 2. Ambil Seluruh Riwayat Pembelian dari Pembeli ini (Berdasarkan Nama)
$historyStmt = $pdo->prepare("SELECT *, (harga_produk * jumlah) AS total_harga FROM penjualan WHERE nama = ? ORDER BY tgl DESC, id DESC");
$historyStmt->execute([$trx['nama']]);
$historyList = $historyStmt->fetchAll();

// 3. Kalkulasi Ringkasan Keuangan Pembeli
$totalBelanja = 0;
$totalLunas = 0;
$totalBelumLunas = 0;

foreach ($historyList as $item) {
    $subtotal = floatval($item['total_harga']);
    $totalBelanja += $subtotal;
    
    if (strtolower($item['status'] ?? '') === 'lunas') {
        $totalLunas += $subtotal;
    } else {
        $totalBelumLunas += $subtotal;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi #<?= htmlspecialchars($trx['id']) ?> - Admin Dapoer Ela 85</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased p-6">

    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Top Navigation -->
        <div class="flex flex-wrap justify-between items-center gap-3">
            <a href="index.php" class="text-sm font-semibold text-gray-600 hover:text-orange-600 transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard Admin
            </a>
            <div class="flex items-center gap-2">
                <a href="edit-transaksi.php?id=<?= $trx['id'] ?>" class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold px-3 py-1.5 rounded-lg border border-amber-200 transition flex items-center gap-1">
                    <i class="fas fa-edit"></i> Edit Transaksi Ini
                </a>
                <span class="text-xs bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-mono font-bold">
                    ID Transaksi #<?= htmlspecialchars($trx['id']) ?>
                </span>
            </div>
        </div>

        <!-- Card Rincian Transaksi Utama -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Informasi Pembeli</h3>
                <div>
                    <p class="text-xl font-bold text-gray-900"><?= htmlspecialchars($trx['nama']) ?></p>
                    <p class="text-sm text-gray-500 mt-0.5">Instansi: <span class="font-medium text-gray-700"><?= htmlspecialchars($trx['instansi'] ?: '-') ?></span></p>
                </div>
                <div class="pt-2 border-t border-gray-100 text-xs text-gray-500 space-y-1">
                    <p><i class="far fa-calendar-alt w-4 text-gray-400"></i> Tanggal Transaksi: <b class="text-gray-700"><?= date('d F Y', strtotime($trx['tgl'])) ?></b></p>
                    <p><i class="fas fa-info-circle w-4 text-gray-400"></i> Status Pembayaran: <?= renderStatusBadge($trx['status'] ?? 'lunas') ?></p>
                </div>
            </div>

            <div class="bg-orange-50/60 p-5 rounded-xl border border-orange-100 flex flex-col justify-between space-y-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-xs font-bold text-orange-800 uppercase tracking-wider">Item Transaksi Ini</h3>
                        <p class="text-base font-bold text-gray-900 mt-1"><?= htmlspecialchars($trx['nama_produk']) ?></p>
                    </div>
                    <div>
                        <?= renderStatusBadge($trx['status'] ?? 'lunas') ?>
                    </div>
                </div>

                <div class="text-xs text-orange-900 space-y-1 bg-white/80 p-3 rounded-lg border border-orange-100">
                    <div class="flex justify-between">
                        <span>Harga Satuan:</span>
                        <b><?= formatRupiah($trx['harga_produk']) ?></b>
                    </div>
                    <div class="flex justify-between">
                        <span>Jumlah Pesanan:</span>
                        <b><?= $trx['jumlah'] ?> item</b>
                    </div>
                </div>

                <div class="pt-2 border-t border-orange-200 flex justify-between items-end">
                    <span class="text-xs font-bold text-orange-800">Total Harga Transaksi Ini</span>
                    <span class="text-2xl font-bold text-orange-600"><?= formatRupiah($trx['total_harga']) ?></span>
                </div>
            </div>
        </div>

        <!-- Metric KPI Pembeli (Akumulasi Semua Transaksi Pembeli Ini) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase">Total Akumulasi Belanja</p>
                <p class="text-xl font-bold text-gray-800 mt-1"><?= formatRupiah($totalBelanja) ?></p>
                <p class="text-[11px] text-gray-400 mt-1"><?= count($historyList) ?> transaksi tercatat</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Total Terbayar (Lunas)</p>
                <p class="text-xl font-bold text-emerald-700 mt-1"><?= formatRupiah($totalLunas) ?></p>
                <p class="text-[11px] text-emerald-600/80 mt-1">Pembayaran selesai</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-semibold text-rose-600 uppercase">Sisa Tagihan (Belum Lunas)</p>
                <p class="text-xl font-bold text-rose-700 mt-1"><?= formatRupiah($totalBelumLunas) ?></p>
                <p class="text-[11px] text-rose-600/80 mt-1">Belum terselesaikan</p>
            </div>
        </div>

        <!-- Tabel Seluruh Riwayat Pembelian Pembeli Ini -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-gray-100 pb-3">
                <div>
                    <h3 class="font-bold text-gray-800 text-base">Riwayat Pembelian Lengkap</h3>
                    <p class="text-xs text-gray-500">Seluruh catatan transaksi atas nama "<b><?= htmlspecialchars($trx['nama']) ?></b>"</p>
                </div>
                <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-medium">
                    Total <?= count($historyList) ?> Item Pesanan
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="p-3">ID</th>
                            <th class="p-3">Tanggal</th>
                            <th class="p-3">Produk</th>
                            <th class="p-3 text-right">Harga Satuan</th>
                            <th class="p-3 text-center">Jumlah</th>
                            <th class="p-3 text-right">Total</th>
                            <th class="p-3 text-center">Status</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($historyList as $h): ?>
                            <tr class="<?= $h['id'] == $trx['id'] ? 'bg-orange-50/70 font-semibold' : 'hover:bg-gray-50' ?> transition">
                                <td class="p-3 text-xs font-mono text-gray-400">
                                    #<?= $h['id'] ?>
                                    <?php if ($h['id'] == $trx['id']): ?>
                                        <span class="ml-1 text-[10px] text-orange-600 font-bold">(Aktif)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 whitespace-nowrap"><?= date('d/m/Y', strtotime($h['tgl'])) ?></td>
                                <td class="p-3 text-gray-800"><?= htmlspecialchars($h['nama_produk']) ?></td>
                                <td class="p-3 text-right"><?= formatRupiah($h['harga_produk']) ?></td>
                                <td class="p-3 text-center"><?= $h['jumlah'] ?></td>
                                <td class="p-3 text-right font-bold text-gray-900"><?= formatRupiah($h['total_harga']) ?></td>
                                <td class="p-3 text-center whitespace-nowrap">
                                    <?= renderStatusBadge($h['status'] ?? 'lunas') ?>
                                </td>
                                <td class="p-3 text-center whitespace-nowrap">
                                    <?php if ($h['id'] != $trx['id']): ?>
                                        <a href="detail-transaksi.php?id=<?= $h['id'] ?>" class="text-xs text-blue-600 hover:underline">
                                            Lihat
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>
