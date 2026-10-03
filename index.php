<?php
require_once 'config.php';

$year   = $_GET['year'] ?? '';
$month  = $_GET['month'] ?? '';
$search = trim($_GET['search'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($year)) {
    $whereClause .= " AND YEAR(tgl) = :year";
    $params[':year'] = $year;
}
if (!empty($month)) {
    $whereClause .= " AND MONTH(tgl) = :month";
    $params[':month'] = $month;
}
if (!empty($search)) {
    $whereClause .= " AND (nama LIKE :s1 OR instansi LIKE :s2 OR nama_produk LIKE :s3)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}

// Count Total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM penjualan {$whereClause}");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

// Fetch Data
$dataStmt = $pdo->prepare("SELECT *, (harga_produk * jumlah) AS total_harga FROM penjualan {$whereClause} ORDER BY tgl DESC LIMIT {$limit} OFFSET {$offset}");
$dataStmt->execute($params);
$salesData = $dataStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dapoer Ela 85</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 p-6">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <h1 class="text-xl font-bold text-orange-600">Dapoer Ela 85</h1>
            
            <?php 
            $isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

            if ($isLoggedIn) {
                // Tampilan jika admin SUDAH login
                echo '<a href="admin/index.php" class="text-xs font-semibold bg-orange-600 text-white px-3 py-2 rounded-lg hover:bg-orange-700 transition">Admin Panel</a>';
            } elseif (!$isLoggedIn) {
                // Tampilan jika admin BELUM login (menggunakan elseif)
                echo '<a href="admin/login.php" class="text-xs font-semibold bg-gray-800 text-white px-3 py-2 rounded-lg hover:bg-gray-900 transition">Login Admin</a>';
            }
            ?>
        </div>

        <!-- Filter Bar -->
        <form method="GET" class="bg-white p-4 rounded-xl shadow-sm flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Tahun</label>
                <select name="year" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua Tahun</option>
                    <?php for ($y = 2024; $y <= 2027; $y++): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Bulan</label>
                <select name="month" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua Bulan</option>
                    <?php 
                    $months = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    foreach ($months as $mNum => $mName): 
                    ?>
                        <option value="<?= $mNum ?>" <?= $month == $mNum ? 'selected' : '' ?>><?= $mName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Cari Produk / Instansi</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Kata kunci..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filter</button>
        </form>

        <!-- Tabel Publik -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-600 font-semibold border-b">
                    <tr>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Nama Pembeli</th>
                        <th class="p-3">Instansi</th>
                        <th class="p-3">Produk</th>
                        <th class="p-3 text-right">Harga</th>
                        <th class="p-3 text-center">Jumlah</th>
                        <th class="p-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($salesData as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-3"><?= date('d/m/Y', strtotime($row['tgl'])) ?></td>
                            <!-- Nama disamarkan secara otomatis -->
                            <td class="p-3 font-semibold text-gray-700"><?= htmlspecialchars(maskName($row['nama'])) ?></td>
                            <td class="p-3 text-gray-500"><?= htmlspecialchars($row['instansi'] ?: '-') ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td class="p-3 text-right"><?= formatRupiah($row['harga_produk']) ?></td>
                            <td class="p-3 text-center"><?= $row['jumlah'] ?></td>
                            <td class="p-3 text-right font-bold text-orange-600"><?= formatRupiah($row['total_harga']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination Bar -->
            <div class="p-4 bg-gray-50 border-t flex justify-between items-center text-xs">
                <span>Halaman <b><?= $page ?></b> dari <b><?= $totalPages ?></b></span>
                <div class="flex gap-1">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="px-3 py-1 bg-white border rounded">Prev</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="px-3 py-1 bg-white border rounded">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
