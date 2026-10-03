<?php
/**
 * Dapoer Ela 85 - Sales Management
 * Database: Wasmer DB (dapoerela85_db)
 */

// 1. Database Connection
$db_host = 'db.fr-roub1.bengt.wasmernet.com';
$db_port = '20184';
$db_name = 'dapoerela85_db';
$db_user = 'user_53b82568';
$db_pass = 'pw_Ia6e5i9rEcpczY7FbmbNPrgIId49MZah';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("<div style='color:red; padding:20px; font-family:sans-serif;'>
            <h2>Koneksi Database Gagal</h2>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
         </div>");
}

// 2. Handle CRUD Actions (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO penjualan (tgl, nama, instansi, nama_produk, harga_produk, jumlah) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['tgl'], 
            $_POST['nama'], 
            $_POST['instansi'], 
            $_POST['nama_produk'], 
            $_POST['harga_produk'], 
            $_POST['jumlah']
        ]);
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE penjualan SET tgl=?, nama=?, instansi=?, nama_produk=?, harga_produk=?, jumlah=? WHERE id=?");
        $stmt->execute([
            $_POST['tgl'], 
            $_POST['nama'], 
            $_POST['instansi'], 
            $_POST['nama_produk'], 
            $_POST['harga_produk'], 
            $_POST['jumlah'], 
            $_POST['id']
        ]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM penjualan WHERE id=?");
        $stmt->execute([$_POST['id']]);
    }

    // Preserve filter parameters after form redirect
    $queryString = http_build_query([
        'year' => $_POST['filter_year'] ?? '',
        'month' => $_POST['filter_month'] ?? '',
        'search' => $_POST['filter_search'] ?? '',
        'page' => $_POST['filter_page'] ?? 1
    ]);

    header("Location: " . $_SERVER['PHP_SELF'] . ($queryString ? '?' . $queryString : ''));
    exit;
}

// 3. Filter Parameters (GET Request)
$year   = $_GET['year'] ?? '';
$month  = $_GET['month'] ?? '';
$search = trim($_GET['search'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

// 4. Build Dynamic Query & Binding
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

// Fetch Paginated Sales Data directly from Database
$dataStmt = $pdo->prepare("SELECT *, (harga_produk * jumlah) AS total_harga FROM penjualan {$whereClause} ORDER BY tgl DESC, id DESC LIMIT {$limit} OFFSET {$offset}");
$dataStmt->execute($params);
$salesData = $dataStmt->fetchAll();

// 5. Calculate KPI Summary Cards directly from Database
$summaryStmt = $pdo->prepare("SELECT SUM(harga_produk * jumlah) AS total_revenue, SUM(jumlah) AS total_items, COUNT(*) AS total_trx FROM penjualan {$whereClause}");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();

// Helper function for rupiah formatting
function formatRupiah($val) {
    return 'Rp ' . number_format($val ?? 0, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjualan - Dapoer Ela 85</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 antialiased font-sans">

    <!-- Top Navigation -->
    <header class="bg-orange-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="bg-white text-orange-600 p-2 rounded-lg font-bold text-xl">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold leading-tight">Dapoer Ela 85</h1>
                    <p class="text-xs text-orange-200">Sistem Pencatatan Penjualan</p>
                </div>
            </div>
            <button onclick="openModal('create')" class="bg-white text-orange-600 hover:bg-orange-50 font-semibold px-4 py-2 rounded-lg shadow-sm text-sm flex items-center gap-2 transition">
                <i class="fas fa-plus"></i> Tambah Transaksi
            </button>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6 space-y-6">

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 font-medium">TOTAL PENDAPATAN</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= formatRupiah($summary['total_revenue']) ?></h3>
                </div>
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center text-xl">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 font-medium">TOTAL PRODUK TERJUAL</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= number_format($summary['total_items'] ?? 0, 0, ',', '.') ?> <span class="text-sm font-normal text-gray-500">pcs</span></h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-xl">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 font-medium">TOTAL TRANSAKSI</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= number_format($summary['total_trx'] ?? 0, 0, ',', '.') ?></h3>
                </div>
                <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center text-xl">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <form method="GET" class="flex flex-wrap md:flex-nowrap gap-3">
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
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, instansi, atau produk..." class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                    </div>
                </div>

                <div class="w-full md:w-auto flex items-end gap-2">
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-900 transition flex items-center gap-1">
                        <i class="fas fa-filter text-xs"></i> Filter
                    </button>
                    <?php if ($year || $month || $search): ?>
                        <a href="?" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition flex items-center gap-1">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4">Tgl</th>
                            <th class="py-3 px-4">Nama Pembeli</th>
                            <th class="py-3 px-4">Instansi</th>
                            <th class="py-3 px-4">Nama Produk</th>
                            <th class="py-3 px-4 text-right">Harga (per item)</th>
                            <th class="py-3 px-4 text-center">Jumlah</th>
                            <th class="py-3 px-4 text-right">Total Harga</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($salesData)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-gray-400">
                                    <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                    Tidak ada data penjualan yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($salesData as$row): ?>
                                <tr class="hover:bg-orange-50/50 transition">
                                    <td class="py-3 px-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($row['tgl'])) ?></td>
                                    <td class="py-3 px-4 font-medium text-gray-900"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($row['instansi'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-gray-800"><?= htmlspecialchars($row['nama_produk']) ?></td>
                                    <td class="py-3 px-4 text-right"><?= formatRupiah($row['harga_produk']) ?></td>
                                    <td class="py-3 px-4 text-center font-semibold"><?= $row['jumlah'] ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-orange-600"><?= formatRupiah($row['total_harga']) ?></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <button onclick='openModal("update", <?= json_encode($row) ?>)' class="text-blue-600 hover:text-blue-800 p-1 rounded transition">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick='confirmDelete(<?= $row["id"] ?>, "<?= htmlspecialchars($row["nama_produk"]) ?>")' class="text-red-600 hover:text-red-800 p-1 rounded transition ml-2">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap justify-between items-center gap-2">
                <span class="text-xs text-gray-500">
                    Menampilkan <b><?= count($salesData) ?></b> dari total <b><?=$totalRows ?></b> data
                </span>
                
                <div class="flex items-center space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' =>$page - 1])) ?>" class="px-3 py-1 border rounded bg-white hover:bg-gray-100 text-xs text-gray-600">Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' =>$i])) ?>" class="px-3 py-1 border rounded text-xs <?= $i ==$page ? 'bg-orange-600 text-white font-bold' : 'bg-white hover:bg-gray-100 text-gray-600' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page <$totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' =>$page + 1])) ?>" class="px-3 py-1 border rounded bg-white hover:bg-gray-100 text-xs text-gray-600">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Form (Create / Update) -->
    <div id="formModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
            <div class="bg-gray-900 text-white px-5 py-4 flex justify-between items-center">
                <h3 id="modalTitle" class="font-bold text-base">Tambah Transaksi Baru</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" class="p-5 space-y-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId" value="">
                
                <!-- Keep filter parameters when saving -->
                <input type="hidden" name="filter_year" value="<?= htmlspecialchars($year) ?>">
                <input type="hidden" name="filter_month" value="<?= htmlspecialchars($month) ?>">
                <input type="hidden" name="filter_search" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="filter_page" value="<?= htmlspecialchars($page) ?>">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal</label>
                    <input type="date" name="tgl" id="inputTgl" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Pembeli</label>
                        <input type="text" name="nama" id="inputNama" required placeholder="Ahmad" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Instansi</label>
                        <input type="text" name="instansi" id="inputInstansi" placeholder="Dinas Pendidikan" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Produk</label>
                    <input type="text" name="nama_produk" id="inputProduk" required placeholder="Nasi Kotak Ayam Bakar" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Harga (per item)</label>
                        <input type="number" name="harga_produk" id="inputHarga" min="0" step="500" required placeholder="25000" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah</label>
                        <input type="number" name="jumlah" id="inputJumlah" min="1" required placeholder="10" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-100">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden Form for Delete Action -->
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
        <input type="hidden" name="filter_year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="filter_month" value="<?= htmlspecialchars($month) ?>">
        <input type="hidden" name="filter_search" value="<?= htmlspecialchars($search) ?>">
        <input type="hidden" name="filter_page" value="<?= htmlspecialchars($page) ?>">
    </form>

    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('formModal');
            document.getElementById('formAction').value = mode;

            if (mode === 'create') {
                document.getElementById('modalTitle').innerText = 'Tambah Transaksi Baru';
                document.getElementById('formId').value = '';
                document.getElementById('inputTgl').value = new Date().toISOString().split('T')[0];
                document.getElementById('inputNama').value = '';
                document.getElementById('inputInstansi').value = '';
                document.getElementById('inputProduk').value = '';
                document.getElementById('inputHarga').value = '';
                document.getElementById('inputJumlah').value = '';
            } else if (mode === 'update' && data) {
                document.getElementById('modalTitle').innerText = 'Edit Transaksi #' + data.id;
                document.getElementById('formId').value = data.id;
                document.getElementById('inputTgl').value = data.tgl;
                document.getElementById('inputNama').value = data.nama;
                document.getElementById('inputInstansi').value = data.instansi || '';
                document.getElementById('inputProduk').value = data.nama_produk;
                document.getElementById('inputHarga').value = data.harga_produk;
                document.getElementById('inputJumlah').value = data.jumlah;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            const modal = document.getElementById('formModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
The web interface generated in the canvas tool is an **interactive prototype (UI demo)** designed to run entirely inside your browser, which is why it uses client-side JavaScript to simulate database behavior.

When you deploy the PHP script on your server, the data **will come directly from your MySQL database** via the PHP PDO query you provided.

---

### Why the Canvas Tool Used JavaScript Demo Data

1. **Browser Limitation**: Interactive canvas previews cannot connect directly to remote MySQL databases or execute backend PHP code due to browser security restrictions (CORS) and missing PHP servers on the client side.
2. **Instant UI Preview**: JavaScript allowed you to see, test, and interact with the UI layout, Tailwind styling, pagination, and forms immediately without waiting for server deployment.

---

### How to Render PHP Database Data in Your HTML

In your final `index.php` file, replace the JavaScript array with PHP loops that output `$salesData` directly from MySQL.

#### 1. Displaying Table Rows from MySQL

Inside your HTML `<tbody>`, replace JavaScript rendering with a PHP `foreach` loop:

```php
<tbody>
    <?php if (empty($salesData)): ?>
        <tr>
            <td colspan="8" class="text-center py-6 text-slate-500">
                Tidak ada data penjualan yang ditemukan.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($salesData as $index => $row): ?>
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="py-3 px-4 font-mono text-xs"><?= $offset + $index + 1 ?></td>
                <td class="py-3 px-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($row['tgl'])) ?></td>
                <td class="py-3 px-4 font-medium text-slate-800"><?= htmlspecialchars($row['nama']) ?></td>
                <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($row['instansi'] ?: '-') ?></td>
                <td class="py-3 px-4 font-medium"><?= htmlspecialchars($row['nama_produk']) ?></td>
                <td class="py-3 px-4 text-right">Rp <?= number_format($row['harga_produk'], 0, ',', '.') ?></td>
                <td class="py-3 px-4 text-center"><?= number_format($row['jumlah']) ?></td>
                <td class="py-3 px-4 text-right font-semibold text-emerald-600">
                    Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>