<?php
require_once __DIR__ . '/auth.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// 1. Ambil Detail Transaksi Utama
$stmt = $pdo->prepare("SELECT *, (harga_produk * jumlah) AS total_harga FROM penjualan WHERE id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    die("Transaksi tidak ditemukan.");
}

// 2. Ambil Seluruh Riwayat Pembelian dari Pembeli ini
$historyStmt = $pdo->prepare("SELECT *, (harga_produk * jumlah) AS total_harga FROM penjualan WHERE nama = ? ORDER BY tgl DESC");
$historyStmt->execute([$trx['nama']]);
$historyList = $historyStmt->fetchAll();

// 3. Hitung Total Pembelian Akumulasi Pembeli
$totalBelanja = array_sum(array_column($historyList, 'total_harga'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Transaksi #<?= $trx['id'] ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
            <a href="index.php" class="text-sm font-semibold text-gray-600 hover:underline">&larr; Kembali ke Dashboard Admin</a>
            <span class="text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded-full font-bold">ID Transaksi #<?= $trx['id'] ?></span>
        </div>

        <!-- Card Detail Utama -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Informasi Pembeli</h3>
                <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($trx['nama']) ?></p>
                <p class="text-sm text-gray-500">Instansi: <?= htmlspecialchars($trx['instansi'] ?: '-') ?></p>
                <p class="text-xs text-gray-400 mt-2">Tanggal Transaksi: <?= date('d F Y', strtotime($trx['tgl'])) ?></p>
            </div>
            <div class="bg-orange-50 p-4 rounded-xl border border-orange-100 flex flex-col justify-between">
                <h3 class="text-xs font-bold text-orange-800 uppercase tracking-wider">Ringkasan Pembelian Ini</h3>
                <div class="mt-2">
                    <p class="text-sm text-orange-900 font-semibold"><?= htmlspecialchars($trx['nama_produk']) ?></p>
                    <p class="text-xs text-orange-700"><?= $trx['jumlah'] ?> pcs x <?= formatRupiah($trx['harga_produk']) ?></p>
                </div>
                <div class="mt-4 pt-2 border-t border-orange-200 flex justify-between items-end">
                    <span class="text-xs font-bold text-orange-800">Total Transaksi</span>
                    <span class="text-xl font-bold text-orange-600"><?= formatRupiah($trx['total_harga']) ?></span>
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Pembelian Lengkap Pembeli Ini -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-gray-800">Riwayat Pembelian Pasien / Pembeli</h3>
                    <p class="text-xs text-gray-500">Seluruh transaksi atas nama "<b><?= htmlspecialchars($trx['nama']) ?></b>"</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-500 block">Total Akumulasi Pembelian</span>
                    <span class="text-lg font-bold text-emerald-600"><?= formatRupiah($totalBelanja) ?></span>
                </div>
            </div>

            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs font-semibold">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Produk</th>
                        <th class="p-3 text-right">Harga</th>
                        <th class="p-3 text-center">Jumlah</th>
                        <th class="p-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($historyList as $h): ?>
                        <tr class="<?= $h['id'] == $trx['id'] ? 'bg-orange-50/60 font-semibold' : 'hover:bg-gray-50' ?>">
                            <td class="p-3 text-xs text-gray-400">#<?= $h['id'] ?></td>
                            <td class="p-3"><?= date('d/m/Y', strtotime($h['tgl'])) ?></td>
                            <td class="p-3"><?= htmlspecialchars($h['nama_produk']) ?></td>
                            <td class="p-3 text-right"><?= formatRupiah($h['harga_produk']) ?></td>
                            <td class="p-3 text-center"><?= $h['jumlah'] ?></td>
                            <td class="p-3 text-right text-gray-800"><?= formatRupiah($h['total_harga']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>