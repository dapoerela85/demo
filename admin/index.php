<?php
// admin/index.php
require_once __DIR__ . '/auth.php';

// Handle Action Delete (Bisa hapus Single Item atau Hapus Seluruh Transaksi Tanggal Tersebut)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = $_POST['id'] ?? null;
    $deleteAllGroup = $_POST['delete_group'] ?? '0';

    if ($deleteId) {
        if ($deleteAllGroup === '1') {
            // Hapus seluruh pesanan atas nama & tanggal yang sama
            $targetStmt = $pdo->prepare("SELECT tgl, nama FROM penjualan WHERE id = ?");
            $targetStmt->execute([$deleteId]);
            $target = $targetStmt->fetch();

            if ($target) {
                $delGroupStmt = $pdo->prepare("DELETE FROM penjualan WHERE tgl = ? AND nama = ?");
                $delGroupStmt->execute([$target['tgl'], $target['nama']]);
            }
        } else {
            // Hapus single item
            $delStmt = $pdo->prepare("DELETE FROM penjualan WHERE id = ?");
            $delStmt->execute([$deleteId]);
        }
    }
    
    $queryString = http_build_query([
        'year' => $_GET['year'] ?? '',
        'month' => $_GET['month'] ?? '',
        'search' => $_GET['search'] ?? '',
        'page' => $_GET['page'] ?? 1
    ]);
    header('Location: index.php' . ($queryString ? '?' . $queryString : ''));
    exit;
}

// Filter Parameters
$year   = $_GET['year'] ?? '';
$month  = $_GET['month'] ?? '';
$search = trim($_GET['search'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

// Query Construction
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

// Count Total Rows
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM penjualan {$whereClause}");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

// Fetch Data Penjualan
$dataStmt = $pdo->prepare("SELECT *, (harga_produk * jumlah) AS total_harga FROM penjualan {$whereClause} ORDER BY tgl DESC, id DESC LIMIT {$limit} OFFSET {$offset}");
$dataStmt->execute($params);
$salesData = $dataStmt->fetchAll();

// Hitung jumlah item dalam transaksi yang sama (Multi-Item Indicator)
$itemCountMap = [];
if (!empty($salesData)) {
    $keys = array_map(function($r) {
        return $r['tgl'] . '___' . $r['nama'];
    }, $salesData);

    $keys = array_unique($keys);
    foreach ($keys as $key) {
        list($t, $n) = explode('___', $key);
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM penjualan WHERE tgl = ? AND nama = ?");
        $cStmt->execute([$t, $n]);
        $itemCountMap[$key] = $cStmt->fetchColumn();
    }
}

// Summary
$summaryStmt = $pdo->prepare("SELECT SUM(harga_produk * jumlah) AS total_revenue, SUM(jumlah) AS total_items, COUNT(*) AS total_trx FROM penjualan {$whereClause}");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();
?>
<?php include 'include/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-6 space-y-6">

        <!-- Header Action -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Ringkasan & Daftar Penjualan</h2>
                <p class="text-xs text-gray-500">Data berikut menampilkan nama pembeli secara lengkap tanpa sensor.</p>
            </div>
            <a href="tambah-transaksi.php" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-plus"></i> Tambah Transaksi
            </a>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Pendapatan</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= formatRupiah($summary['total_revenue'] ?? 0) ?></h3>
                </div>
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center text-xl">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Terjual</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= number_format($summary['total_items'] ?? 0, 0, ',', '.') ?> <span class="text-sm font-normal txt-gray-500">pcs</span></h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-xl">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Transaksi</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= number_format($summary['total_trx'] ?? 0, 0, ',', '.') ?> <span class="text-sm font-normal text-gray-500">baris</span></h3>
                </div>
                <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center text-xl">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <form method="GET" class="flex flex-wrap md:flex-nowrap gap-3 items-end">
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
                        $months = [1=>'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        foreach ($months as $mNum => $mName): 
                        ?>
                            <option value="<?= $mNum ?>" <?= $month == $mNum ? 'selected' : '' ?>><?= $mName ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-full md:flex-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Pencarian</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama pembeli, instansi, produk..." class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                    </div>
                </div>

                <div class="w-full md:w-auto flex gap-2">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm transition font-medium flex items-center gap-1">
                        <i class="fas fa-filter text-xs"></i> Filter
                    </button>
                    <?php if ($year || $month || $search): ?>
                        <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition flex items-center gap-1">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table Data -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4">ID</th>
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Nama Pembeli (Asli)</th>
                            <th class="py-3.5 px-4">Instansi</th>
                            <th class="py-3.5 px-4">Produk</th>
                            <th class="py-3.5 px-4 text-right">Harga</th>
                            <th class="py-3.5 px-4 text-center">Jumlah</th>
                            <th class="py-3.5 px-4 text-right">Total</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($salesData)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-8 text-gray-400">
                                    <i class="fas fa-folder-open text-3xl mb-2 block"></i>
                                    Tidak ada data penjualan yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salesData as $row): 
                                $groupKey = $row['tgl'] . '___' . $row['nama'];
                                $groupCount = $itemCountMap[$groupKey] ?? 1;
                            ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-3 px-4 text-xs font-mono text-gray-400">#<?= $row['id'] ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($row['tgl'])) ?></td>
                                    <td class="py-3 px-4 font-bold text-gray-900">
                                        <?= htmlspecialchars($row['nama']) ?>
                                        <?php if ($groupCount > 1): ?>
                                            <span class="inline-block ml-1 px-1.5 py-0.5 text-[10px] bg-orange-100 text-orange-700 font-medium rounded" title="<?= $groupCount ?> produk pada tanggal ini">
                                                <?= $groupCount ?> Item
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-gray-500"><?= htmlspecialchars($row['instansi'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-gray-800"><?= htmlspecialchars($row['nama_produk']) ?></td>
                                    <td class="py-3 px-4 text-right"><?= formatRupiah($row['harga_produk']) ?></td>
                                    <td class="py-3 px-4 text-center font-semibold"><?= $row['jumlah'] ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-orange-600"><?= formatRupiah($row['total_harga']) ?></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap space-x-1">
                                        <a href="detail-transaksi.php?id=<?= $row['id'] ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 p-2 rounded-lg text-xs transition inline-block" title="Lihat Detail & Riwayat">
                                            <i class="fas fa-eye text-blue-600"></i>
                                        </a>
                                        <a href="edit-transaksi.php?id=<?= $row['id'] ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 p-2 rounded-lg text-xs transition inline-block" title="Edit Multi-Produk Transaksi Ini">
                                            <i class="fas fa-edit text-amber-600"></i>
                                        </a>
                                        <form method="POST" class="inline-block" onsubmit="return confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama']) ?>', <?= $groupCount ?>);">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="delete_group" id="delete_group_<?= $row['id'] ?>" value="0">
                                            <button type="submit" class="bg-gray-100 hover:bg-red-50 text-red-600 p-2 rounded-lg text-xs transition" title="Hapus Data">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap justify-between items-center gap-2 text-xs text-gray-600">
                <span>Menampilkan <b><?= count($salesData) ?></b> dari total <b><?= $totalRows ?></b> data</span>
                
                <div class="flex items-center space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="px-3 py-1.5 border rounded bg-white hover:bg-gray-100">Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="px-3 py-1.5 border rounded <?= $i == $page ? 'bg-orange-600 text-white font-bold border-orange-600' : 'bg-white hover:bg-gray-100' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="px-3 py-1.5 border rounded bg-white hover:bg-gray-100">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function confirmDelete(id, nama, itemCount) {
            if (itemCount > 1) {
                const deleteGroup = confirm("Pembeli '" + nama + "' memiliki " + itemCount + " produk pada tanggal ini.\n\nKlik [OK] untuk MENGHAPUS SEMUA produk dalam transaksi ini.\nKlik [Batal] untuk HANYA menghapus baris produk ini.");
                if (deleteGroup) {
                    document.getElementById('delete_group_' + id).value = '1';
                } else {
                    document.getElementById('delete_group_' + id).value = '0';
                }
                return true;
            }
            return confirm('Apakah Anda yakin ingin menghapus baris transaksi #' + id + '?');
        }
    </script>
    
<?php include 'include/footer.php'; ?>
