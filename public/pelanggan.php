<?php
// public/pelanggan.php
require_once __DIR__ . '/../config.php';

$id  = $_GET['id'] ?? null;
$pin = $_GET['pin'] ?? null;

if (!$id || !$pin) {
    die("
    <!DOCTYPE html>
    <html lang='id'>
    <head><meta charset='UTF-8'><title>Akses Ditolak</title><script src='https://cdn.tailwindcss.com'></script></head>
    <body class='bg-gray-100 flex items-center justify-center min-h-screen text-center p-4'>
        <div class='bg-white p-8 rounded-2xl shadow-sm border max-w-md w-full'>
            <div class='text-rose-500 text-4xl mb-3'><i class='fas fa-lock'></i></div>
            <h2 class='text-xl font-bold text-gray-800 mb-2'>Akses Membutuhkan PIN</h2>
            <p class='text-xs text-gray-500 mb-4'>Parameter ID dan PIN 6 digit diperlukan untuk membuka laporan ini.</p>
            <a href='../index.php' class='text-xs font-semibold text-orange-600 hover:underline'>&larr; Kembali ke Utama</a>
        </div>
    </body>
    </html>");
}

// 1. Ambil Profil Pelanggan & Validasi PIN
$stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE id = ? AND pin = ?");
$stmt->execute([$id, $pin]);
$pelanggan = $stmt->fetch();

if (!$pelanggan) {
    die("
    <!DOCTYPE html>
    <html lang='id'>
    <head><meta charset='UTF-8'><title>Akses Ditolak</title><script src='https://cdn.tailwindcss.com'></script></head>
    <body class='bg-gray-100 flex items-center justify-center min-h-screen text-center p-4'>
        <div class='bg-white p-8 rounded-2xl shadow-sm border max-w-md w-full'>
            <div class='text-rose-500 text-4xl mb-3'><i class='fas fa-exclamation-triangle'></i></div>
            <h2 class='text-xl font-bold text-gray-800 mb-2'>PIN Tidak Valid</h2>
            <p class='text-xs text-gray-500 mb-4'>Kombinasi ID pelanggan dan PIN tidak cocok atau tidak ditemukan.</p>
            <a href='../index.php' class='text-xs font-semibold text-orange-600 hover:underline'>&larr; Kembali ke Utama</a>
        </div>
    </body>
    </html>");
}

// 2. Filter Parameters (Bulan & Tahun)
$year  = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';

$whereClause = "WHERE (pelanggan_id = :pid OR (pelanggan_id IS NULL AND nama = :pname))";
$params = [
    ':pid'   => $id,
    ':pname' => $pelanggan['nama']
];

if (!empty($year)) {
    $whereClause .= " AND YEAR(tgl) = :year";
    $params[':year'] = $year;
}
if (!empty($month)) {
    $whereClause .= " AND MONTH(tgl) = :month";
    $params[':month'] = $month;
}

// 3. Fetch Riwayat Transaksi Berdasarkan Filter
$historyStmt = $pdo->prepare("
    SELECT *, (harga_produk * jumlah) AS total_harga 
    FROM penjualan 
    {$whereClause}
    ORDER BY tgl DESC, id DESC
");
$historyStmt->execute($params);
$historyList = $historyStmt->fetchAll();

// 4. Hitung Ringkasan Belanja
$totalAkumulasi = 0;
$totalLunas     = 0;
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

        <!-- Header Store -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="bg-orange-600 text-white p-2.5 rounded-xl text-xl font-bold">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 leading-tight">Dapoer Ela 85</h1>
                    <p class="text-xs text-gray-500">Lembar Catatan Penjualan & Transaksi</p>
                </div>
            </div>
            <a href="../index.php" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg font-semibold transition">
                Utama
            </a>
        </div>

        <!-- Profil Pelanggan (Instansi Ditampilkan, NIK Dihapus) -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-3">
            <div class="flex justify-between items-start border-b border-gray-100 pb-3">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase">Pelanggan Terhormat</p>
                    <h2 class="text-2xl font-bold text-gray-900 mt-0.5"><?= htmlspecialchars(maskName($pelanggan['nama'])) ?></h2>
                </div>
                <?= renderStatusBadge($pelanggan['status']) ?>
            </div>

            <div class="text-xs text-gray-600">
                <p><i class="fas fa-building w-4 text-gray-400"></i> Instansi: <b><?= htmlspecialchars($pelanggan['instansi'] ?: '-') ?></b></p>
            </div>
        </div>

        <!-- Filter Bulan & Tahun -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
            <form method="GET" class="flex flex-wrap md:flex-nowrap gap-3 items-end">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="pin" value="<?= htmlspecialchars($pin) ?>">

                <div class="w-full md:w-36">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Tahun</label>
                    <select name="year" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <option value="">Semua Tahun</option>
                        <?php for ($y = 2024; $y <= 2027; $y++): ?>
                            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="w-full md:w-44">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Bulan</label>
                    <select name="month" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <option value="">Semua Bulan</option>
                        <?php 
                        $months = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        foreach ($months as $mNum => $mName): 
                        ?>
                            <option value="<?= $mNum ?>" <?= $month == $mNum ? 'selected' : '' ?>><?= $mName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-full md:w-auto flex gap-2">
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm transition font-semibold flex items-center gap-1">
                        <i class="fas fa-filter text-xs"></i> Filter Data
                    </button>
                    <?php if ($year || $month): ?>
                        <a href="pelanggan.php?id=<?= $id ?>&pin=<?= $pin ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition flex items-center gap-1">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
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
                                <td colspan="6" class="text-center py-6 text-gray-400">Belum ada catatan transaksi pada periode ini.</td>
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
