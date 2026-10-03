<?php
/**
 * Dapoer Ela 85 - Sales Management Single Page Application
 * Database: Wasmer DB (dapoerela85_db)
 */

// 1. Database Connection Parameters
$db_host = 'db.fr-roub1.bengt.wasmernet.com';
$db_port = '20184';
$db_name = 'dapoerela85_db';
$db_user = 'user_53b82568';
$db_pass = ''; // Insert your database password here

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// 2. Handle CRUD Actions (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO penjualan (tgl, nama, instansi, nama_produk, harga_produk, jumlah) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['tgl'], $_POST['nama'], $_POST['instansi'], $_POST['nama_produk'], $_POST['harga_produk'], $_POST['jumlah']]);
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE penjualan SET tgl=?, nama=?, instansi=?, nama_produk=?, harga_produk=?, jumlah=? WHERE id=?");
        $stmt->execute([$_POST['tgl'], $_POST['nama'], $_POST['instansi'], $_POST['nama_produk'], $_POST['harga_produk'], $_POST['jumlah'], $_POST['id']]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM penjualan WHERE id=?");
        $stmt->execute([$_POST['id']]);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 3. Filter Parameters (GET Request)
$year   = $_GET['year'] ?? '';
$month  = $_GET['month'] ?? '';
$search = trim($_GET['search'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = max(5, intval($_GET['limit'] ?? 10));
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
$totalPages = ceil($totalRows / $limit);

// Fetch Paginated Sales Data
$dataStmt = $pdo->prepare("SELECT *, (harga_produk * jumlah) AS total_harga FROM penjualan {$whereClause} ORDER BY tgl DESC, id DESC LIMIT {$limit} OFFSET {$offset}");
$dataStmt->execute($params);
$salesData = $dataStmt->fetchAll();

// 5. Calculate KPI Summary Cards
$summaryStmt = $pdo->prepare("SELECT SUM(harga_produk * jumlah) AS total_revenue, SUM(jumlah) AS total_items FROM penjualan {$whereClause}");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dapoer Ela 85 - Sales Management Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        amber: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                        brand: {
                            DEFAULT: '#d97706',
                            dark: '#78350f',
                            light: '#fef3c7'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        [v-cloak] { display: none; }
    </style>
</head>
<body class="bg-amber-50/40 text-slate-800 min-h-screen flex flex-col">

    <!-- Navigation / Header -->
    <header class="bg-amber-900 text-amber-50 shadow-md sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Title -->
                <div class="flex items-center space-x-3">
                    <div class="bg-amber-500 text-amber-950 p-2 rounded-xl shadow-inner font-bold flex items-center justify-center">
                        <i data-lucide="utensils" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg leading-tight tracking-wide">Dapoer Ela 85</h1>
                        <p class="text-xs text-amber-200">Sistem Pencatatan & Manajemen Penjualan</p>
                    </div>
                </div>

                <!-- Database Status Indicator & PHP Code Button -->
                <div class="flex items-center space-x-3">
                    <div class="hidden sm:flex items-center space-x-2 bg-amber-950/60 border border-amber-700/50 px-3 py-1.5 rounded-full text-xs">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="font-mono text-amber-200">Wasmer DB: dapoerela85_db</span>
                    </div>

                    <button onclick="toggleCodeModal(true)" class="flex items-center space-x-2 bg-amber-600 hover:bg-amber-500 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all shadow-sm">
                        <i data-lucide="code" class="w-4 h-4"></i>
                        <span>Lihat Code index.php</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Analytics Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Total Revenue -->
            <div class="bg-white p-5 rounded-2xl border border-amber-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Pendapatan</p>
                    <p id="cardTotalRevenue" class="text-xl sm:text-2xl font-bold text-amber-800 mt-1">Rp 0</p>
                    <span id="cardRevenueSub" class="text-[11px] text-amber-600/80 mt-0.5 block">Hasil filter terpilih</span>
                </div>
                <div class="p-3 bg-amber-100 text-amber-700 rounded-xl">
                    <i data-lucide="wallet" class="w-6 h-6"></i>
                </div>
            </div>

            <!-- Card 2: Total Items Sold -->
            <div class="bg-white p-5 rounded-2xl border border-amber-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Item Terjual</p>
                    <p id="cardTotalItems" class="text-xl sm:text-2xl font-bold text-slate-800 mt-1">0 Porsi/Pcs</p>
                    <span class="text-[11px] text-slate-400 mt-0.5 block">Total kuantitas produk</span>
                </div>
                <div class="p-3 bg-orange-100 text-orange-700 rounded-xl">
                    <i data-lucide="shopping-bag" class="w-6 h-6"></i>
                </div>
            </div>

            <!-- Card 3: Total Transactions -->
            <div class="bg-white p-5 rounded-2xl border border-amber-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jumlah Transaksi</p>
                    <p id="cardTotalTx" class="text-xl sm:text-2xl font-bold text-slate-800 mt-1">0 Data</p>
                    <span class="text-[11px] text-slate-400 mt-0.5 block">Total entri pesanan</span>
                </div>
                <div class="p-3 bg-yellow-100 text-yellow-700 rounded-xl">
                    <i data-lucide="receipt" class="w-6 h-6"></i>
                </div>
            </div>

            <!-- Card 4: Top Product -->
            <div class="bg-white p-5 rounded-2xl border border-amber-100 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Produk Terlaris</p>
                    <p id="cardTopProduct" class="text-base font-bold text-slate-800 mt-1 truncate max-w-[150px]" title="-">-</p>
                    <span id="cardTopProductSub" class="text-[11px] text-emerald-600 font-medium mt-0.5 block">0 porsi terjual</span>
                </div>
                <div class="p-3 bg-emerald-100 text-emerald-700 rounded-xl">
                    <i data-lucide="trophy" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Filter Controls Bar -->
        <div class="bg-white p-5 rounded-2xl border border-amber-100 shadow-sm space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                
                <!-- Left: Filter Controls -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-grow">
                    <!-- Search Input -->
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="searchInput" oninput="handleFilterChange()" placeholder="Cari Nama, Instansi, Produk..." 
                            class="w-full pl-9 pr-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                    </div>

                    <!-- Year Filter -->
                    <div class="relative">
                        <i data-lucide="calendar" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <select id="yearFilter" onchange="handleFilterChange()" 
                            class="w-full pl-9 pr-8 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all appearance-none">
                            <option value="">Semua Tahun</option>
                            <option value="2026" selected>2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                        </select>
                        <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>

                    <!-- Month Filter -->
                    <div class="relative">
                        <i data-lucide="filter" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <select id="monthFilter" onchange="handleFilterChange()" 
                            class="w-full pl-9 pr-8 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all appearance-none">
                            <option value="">Semua Bulan</option>
                            <option value="01">01 - Januari</option>
                            <option value="02">02 - Februari</option>
                            <option value="03">03 - Maret</option>
                            <option value="04">04 - April</option>
                            <option value="05">05 - Mei</option>
                            <option value="06">06 - Juni</option>
                            <option value="07">07 - Juli</option>
                            <option value="08">08 - Agustus</option>
                            <option value="09">09 - September</option>
                            <option value="10">10 - Oktober</option>
                            <option value="11">11 - November</option>
                            <option value="12">12 - Desember</option>
                        </select>
                        <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Right: Action Buttons -->
                <div class="flex items-center justify-between sm:justify-end space-x-2">
                    <button onclick="resetFilters()" class="p-2 text-slate-500 hover:text-amber-800 hover:bg-amber-100 rounded-xl transition-all" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                    </button>

                    <button onclick="openFormModal()" class="flex items-center space-x-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all shadow-md shadow-amber-600/20">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Tambah Penjualan</span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Data Table Container -->
        <div class="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-amber-100/60 text-amber-950 font-semibold border-b border-amber-100">
                        <tr>
                            <th class="py-3.5 px-4 text-center w-12">#</th>
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Nama Pembeli</th>
                            <th class="py-3.5 px-4">Instansi</th>
                            <th class="py-3.5 px-4">Nama Produk</th>
                            <th class="py-3.5 px-4 text-right">Harga Item</th>
                            <th class="py-3.5 px-4 text-center">Jumlah</th>
                            <th class="py-3.5 px-4 text-right">Total Harga</th>
                            <th class="py-3.5 px-4 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody" class="divide-y divide-slate-100">
                        <!-- Dynamic Rows loaded via Javascript -->
                    </tbody>
                </table>
            </div>

            <!-- Table Footer & Pagination -->
            <div class="bg-slate-50/70 border-t border-slate-100 px-4 py-3 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                
                <!-- Info & Items per Page -->
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                    <span id="paginationInfo">Menampilkan 0 - 0 dari 0 data</span>
                    <span class="text-slate-300">|</span>
                    <div class="flex items-center space-x-1.5">
                        <label for="pageSizeSelect">Tampilkan:</label>
                        <select id="pageSizeSelect" onchange="changePageSize()" class="bg-white border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:border-amber-500">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <!-- Page Navigation Buttons -->
                <div class="flex items-center space-x-1.5" id="paginationControls">
                    <!-- Navigation Buttons Inserted via JS -->
                </div>

            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-100 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-400">
            &copy; 2026 Dapoer Ela 85 Catering Management System &bull; Database: <span class="font-mono text-slate-600">dapoerela85_db</span>
        </div>
    </footer>

    <!-- Modal Form (Tambah / Edit Penjualan) -->
    <div id="formModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center hidden p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform transition-all">
            
            <!-- Modal Header -->
            <div class="bg-amber-900 text-white px-6 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i data-lucide="edit-3" class="w-5 h-5 text-amber-400"></i>
                    <h3 id="modalTitle" class="font-bold text-base">Tambah Data Penjualan</h3>
                </div>
                <button onclick="closeFormModal()" class="text-amber-200 hover:text-white rounded-lg p-1 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Form Body -->
            <form id="salesForm" onsubmit="handleFormSubmit(event)" class="p-6 space-y-4">
                <input type="hidden" id="editId" value="">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Tanggal (`tgl`)</label>
                    <input type="date" id="formTgl" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Nama Pembeli (`nama`)</label>
                        <input type="text" id="formNama" required placeholder="Contoh: Ibu Rahma" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Instansi (`instansi`)</label>
                        <input type="text" id="formInstansi" placeholder="Contoh: Dinas Kesehatan" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Nama Produk (`nama_produk`)</label>
                    <input type="text" id="formProduk" required placeholder="Contoh: Nasi Kotak Ayam Bakar" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Harga Produk (`harga_produk`)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">Rp</span>
                            <input type="number" id="formHarga" min="0" step="500" required oninput="calculateFormTotal()" placeholder="25000" class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Jumlah (`jumlah`)</label>
                        <input type="number" id="formJumlah" min="1" required oninput="calculateFormTotal()" placeholder="10" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                </div>

                <!-- Total Calculated Preview -->
                <div class="bg-amber-50 p-3.5 rounded-xl border border-amber-200 flex justify-between items-center">
                    <span class="text-xs font-semibold text-amber-900">Total Harga Estimasi:</span>
                    <span id="formTotalPreview" class="text-base font-bold text-amber-800">Rp 0</span>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeFormModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md transition-colors">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal PHP Code Viewer -->
    <div id="codeModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center hidden p-4">
        <div class="bg-slate-900 text-slate-100 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden border border-slate-800">
            <!-- Header -->
            <div class="bg-slate-800/80 px-6 py-4 flex items-center justify-between border-b border-slate-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-amber-500/20 text-amber-400 rounded-lg">
                        <i data-lucide="file-code" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-white">Full Production Backend PHP Code (`index.php`)</h3>
                        <p class="text-xs text-slate-400">Koneksi PDO MySQL Wasmer DB dengan Prepared Statements</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="copyPhpCode()" id="copyCodeBtn" class="flex items-center space-x-1.5 bg-amber-600 hover:bg-amber-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                        <i data-lucide="copy" class="w-4 h-4"></i>
                        <span>Copy Code</span>
                    </button>
                    <button onclick="toggleCodeModal(false)" class="text-slate-400 hover:text-white p-1 rounded-lg">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Code Content -->
            <div class="p-6 overflow-y-auto font-mono text-xs text-emerald-400 bg-slate-950 leading-relaxed selection:bg-amber-500 selection:text-slate-900">
<pre id="phpCodeContent"><code>&lt;?php
/**
 * Dapoer Ela 85 - Sales Management Single Page Application
 * Database: Wasmer DB (dapoerela85_db)
 */

// 1. Database Connection Parameters
$db_host = 'db.fr-roub1.bengt.wasmernet.com';
$db_port = '20184';
$db_name = 'dapoerela85_db';
$db_user = 'user_53b82568';
$db_pass = ''; // Insert your database password here

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE =&gt; PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE =&gt; PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e-&gt;getMessage());
}

// 2. Handle CRUD Actions (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $pdo-&gt;prepare("INSERT INTO penjualan (tgl, nama, instansi, nama_produk, harga_produk, jumlah) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt-&gt;execute([$_POST['tgl'], $_POST['nama'], $_POST['instansi'], $_POST['nama_produk'], $_POST['harga_produk'], $_POST['jumlah']]);
    } elseif ($action === 'update') {
        $stmt = $pdo-&gt;prepare("UPDATE penjualan SET tgl=?, nama=?, instansi=?, nama_produk=?, harga_produk=?, jumlah=? WHERE id=?");
        $stmt-&gt;execute([$_POST['tgl'], $_POST['nama'], $_POST['instansi'], $_POST['nama_produk'], $_POST['harga_produk'], $_POST['jumlah'], $_POST['id']]);
    } elseif ($action === 'delete') {
        $stmt = $pdo-&gt;prepare("DELETE FROM penjualan WHERE id=?");
        $stmt-&gt;execute([$_POST['id']]);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 3. Filter Parameters (GET Request)
$year   = $_GET['year'] ?? '';
$month  = $_GET['month'] ?? '';
$search = trim($_GET['search'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = max(5, intval($_GET['limit'] ?? 10));
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
$countStmt = $pdo-&gt;prepare("SELECT COUNT(*) FROM penjualan {$whereClause}");
$countStmt-&gt;execute($params);
$totalRows = $countStmt-&gt;fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch Paginated Sales Data
$dataStmt = $pdo-&gt;prepare("SELECT *, (harga_produk * jumlah) AS total_harga FROM penjualan {$whereClause} ORDER BY tgl DESC, id DESC LIMIT {$limit} OFFSET {$offset}");
$dataStmt-&gt;execute($params);
$salesData = $dataStmt-&gt;fetchAll();

// 5. Calculate KPI Summary Cards
$summaryStmt = $pdo-&gt;prepare("SELECT SUM(harga_produk * jumlah) AS total_revenue, SUM(jumlah) AS total_items FROM penjualan {$whereClause}");
$summaryStmt-&gt;execute($params);
$summary = $summaryStmt-&gt;fetch();

?&gt;
&lt;!-- HTML UI layout renders here with Tailwind CSS --&gt;</code></pre>
            </div>
        </div>
    </div>

    <script>
        // Sample Initial Mock Data (Simulating Database Records)
        let salesData = [
            { id: 101, tgl: '2026-10-01', nama: 'Ahmad Subagja', instansi: 'Dinas Pendidikan', nama_produk: 'Nasi Kotak Ayam Bakar', harga_produk: 25000, jumlah: 50 },
            { id: 102, tgl: '2026-10-02', nama: 'Siti Nurhaliza', instansi: 'Puskesmas Melati', nama_produk: 'Snack Box Premium', harga_produk: 15000, jumlah: 35 },
            { id: 103, tgl: '2026-09-15', nama: 'Budi Santoso', instansi: 'PT Maju Bersama', nama_produk: 'Tumpeng Mini Nusantara', harga_produk: 35000, jumlah: 20 },
            { id: 104, tgl: '2026-09-20', nama: 'Rina Wijaya', instansi: 'Bank Mandiri Cabang', nama_produk: 'Kue Basah Tampah', harga_produk: 150000, jumlah: 3 },
            { id: 105, tgl: '2026-08-10', nama: 'Dedi Kurniawan', instansi: 'Kecamatan Sukajadi', nama_produk: 'Buffet Catering Prasmanan', harga_produk: 45000, jumlah: 100 },
            { id: 106, tgl: '2026-10-03', nama: 'Eka Putri', instansi: 'Mandiri / Pribadi', nama_produk: 'Nasi Kotak Empal Gentong', harga_produk: 30000, jumlah: 25 },
            { id: 107, tgl: '2025-12-25', nama: 'Hendera Gunawan', instansi: 'Gereja Pasundan', nama_produk: 'Snack Box Standard', harga_produk: 12000, jumlah: 80 },
            { id: 108, tgl: '2026-10-03', nama: 'Fitriani', instansi: 'Kelurahan Kebon Jeruk', nama_produk: 'Nasi Kotak Ayam Bakar', harga_produk: 25000, jumlah: 40 },
            { id: 109, tgl: '2026-07-14', nama: 'Bambang Tri', instansi: 'PT Telkom Indonesia', nama_produk: 'Coffee Break Package', harga_produk: 20000, jumlah: 60 },
            { id: 110, tgl: '2026-05-18', nama: 'Dewi Lestari', instansi: 'Studio Foto Kenanga', nama_produk: 'Nasi Box Rendang Daging', harga_produk: 32000, jumlah: 15 }
        ];

        // Application State
        let currentPage = 1;
        let pageSize = 10;
        let filteredData = [];

        // Format Currency Helper
        function formatRp(number) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
        }

        // Format Date Helper
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const [y, m, d] = dateStr.split('-');
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${d} ${months[parseInt(m)-1]} ${y}`;
        }

        // Filter and Render Logic
        function handleFilterChange() {
            const year = document.getElementById('yearFilter').value;
            const month = document.getElementById('monthFilter').value;
            const query = document.getElementById('searchInput').value.toLowerCase().trim();

            filteredData = salesData.filter(item => {
                const itemYear = item.tgl.substring(0, 4);
                const itemMonth = item.tgl.substring(5, 7);

                const matchYear = !year || itemYear === year;
                const matchMonth = !month || itemMonth === month;
                const matchQuery = !query || 
                    item.nama.toLowerCase().includes(query) ||
                    (item.instansi && item.instansi.toLowerCase().includes(query)) ||
                    item.nama_produk.toLowerCase().includes(query);

                return matchYear && matchMonth && matchQuery;
            });

            // Sort by Date Descending
            filteredData.sort((a, b) => new Date(b.tgl) - new Date(a.tgl));

            currentPage = 1;
            renderAll();
        }

        // Reset Filters
        function resetFilters() {
            document.getElementById('yearFilter').value = '';
            document.getElementById('monthFilter').value = '';
            document.getElementById('searchInput').value = '';
            handleFilterChange();
        }

        // Change Page Size
        function changePageSize() {
            pageSize = parseInt(document.getElementById('pageSizeSelect').value);
            currentPage = 1;
            renderAll();
        }

        // Update Dashboard Analytics Summary
        function updateSummaryCards() {
            const totalRevenue = filteredData.reduce((sum, item) => sum + (item.harga_produk * item.jumlah), 0);
            const totalItems = filteredData.reduce((sum, item) => sum + item.jumlah, 0);
            const totalTx = filteredData.length;

            // Determine Top Product
            const productCounts = {};
            filteredData.forEach(item => {
                productCounts[item.nama_produk] = (productCounts[item.nama_produk] || 0) + item.jumlah;
            });

            let topProduct = '-';
            let topQty = 0;
            for (const [prod, qty] of Object.entries(productCounts)) {
                if (qty > topQty) {
                    topQty = qty;
                    topProduct = prod;
                }
            }

            document.getElementById('cardTotalRevenue').innerText = formatRp(totalRevenue);
            document.getElementById('cardTotalItems').innerText = `${totalItems.toLocaleString('id-ID')} Porsi/Pcs`;
            document.getElementById('cardTotalTx').innerText = `${totalTx} Data`;
            
            const topProdElem = document.getElementById('cardTopProduct');
            topProdElem.innerText = topProduct;
            topProdElem.title = topProduct;
            document.getElementById('cardTopProductSub').innerText = `${topQty} porsi terjual`;
        }

        // Render Sales Table Rows
        function renderTable() {
            const tbody = document.getElementById('salesTableBody');
            tbody.innerHTML = '';

            if (filteredData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-8 text-slate-400">
                            <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
                            <p class="text-sm">Tidak ada data penjualan yang cocok.</p>
                        </td>
                    </tr>
                `;
                lucide.createIcons();
                return;
            }

            const startIndex = (currentPage - 1) * pageSize;
            const paginatedItems = filteredData.slice(startIndex, startIndex + pageSize);

            paginatedItems.forEach((item, index) => {
                const totalHarga = item.harga_produk * item.jumlah;
                const tr = document.createElement('tr');
                tr.className = "hover:bg-amber-50/50 transition-colors border-b border-slate-100/80";
                tr.innerHTML = `
                    <td class="py-3 px-4 text-center font-mono text-xs text-slate-400">${startIndex + index + 1}</td>
                    <td class="py-3 px-4 text-slate-700 whitespace-nowrap">${formatDate(item.tgl)}</td>
                    <td class="py-3 px-4 font-semibold text-slate-800">${escapeHtml(item.nama)}</td>
                    <td class="py-3 px-4 text-slate-600">
                        <span class="inline-block bg-slate-100 text-slate-700 text-xs px-2.5 py-0.5 rounded-full font-medium border border-slate-200/60">
                            ${escapeHtml(item.instansi || '-')}
                        </span>
                    </td>
                    <td class="py-3 px-4 font-medium text-amber-900">${escapeHtml(item.nama_produk)}</td>
                    <td class="py-3 px-4 text-right font-mono text-slate-600">${formatRp(item.harga_produk)}</td>
                    <td class="py-3 px-4 text-center">
                        <span class="bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-md text-xs">
                            ${item.jumlah}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-right font-mono font-bold text-amber-800">${formatRp(totalHarga)}</td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center space-x-1">
                            <button onclick="editRecord(${item.id})" class="p-1.5 text-slate-500 hover:text-amber-700 hover:bg-amber-100 rounded-lg transition-colors" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteRecord(${item.id})" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            lucide.createIcons();
        }

        // Render Pagination Controls
        function renderPagination() {
            const total = filteredData.length;
            const totalPages = Math.ceil(total / pageSize) || 1;
            const start = total === 0 ? 0 : (currentPage - 1) * pageSize + 1;
            const end = Math.min(currentPage * pageSize, total);

            document.getElementById('paginationInfo').innerText = `Menampilkan ${start} - ${end} dari ${total} data`;

            const container = document.getElementById('paginationControls');
            container.innerHTML = '';

            // Previous Button
            const prevBtn = document.createElement('button');
            prevBtn.className = `p-1.5 rounded-lg border text-xs flex items-center ${currentPage === 1 ? 'text-slate-300 border-slate-200 cursor-not-allowed' : 'text-slate-600 border-slate-200 hover:bg-white hover:border-amber-500'}`;
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => { if (currentPage > 1) { currentPage--; renderAll(); } };
            prevBtn.innerHTML = `<i data-lucide="chevron-left" class="w-4 h-4"></i>`;
            container.appendChild(prevBtn);

            // Page Number Buttons
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    const pageBtn = document.createElement('button');
                    pageBtn.className = `px-3 py-1 rounded-lg text-xs font-semibold ${i === currentPage ? 'bg-amber-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:border-amber-500'}`;
                    pageBtn.innerText = i;
                    pageBtn.onclick = () => { currentPage = i; renderAll(); };
                    container.appendChild(pageBtn);
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    const dots = document.createElement('span');
                    dots.className = 'text-slate-400 text-xs px-1';
                    dots.innerText = '...';
                    container.appendChild(dots);
                }
            }

            // Next Button
            const nextBtn = document.createElement('button');
            nextBtn.className = `p-1.5 rounded-lg border text-xs flex items-center ${currentPage >= totalPages ? 'text-slate-300 border-slate-200 cursor-not-allowed' : 'text-slate-600 border-slate-200 hover:bg-white hover:border-amber-500'}`;
            nextBtn.disabled = currentPage >= totalPages;
            nextBtn.onclick = () => { if (currentPage < totalPages) { currentPage++; renderAll(); } };
            nextBtn.innerHTML = `<i data-lucide="chevron-right" class="w-4 h-4"></i>`;
            container.appendChild(nextBtn);

            lucide.createIcons();
        }

        function renderAll() {
            renderTable();
            renderPagination();
            updateSummaryCards();
        }

        // Form Modal Actions (Create / Edit)
        function openFormModal(editData = null) {
            const modal = document.getElementById('formModal');
            const form = document.getElementById('salesForm');
            
            form.reset();
            
            if (editData) {
                document.getElementById('modalTitle').innerText = 'Edit Data Penjualan';
                document.getElementById('editId').value = editData.id;
                document.getElementById('formTgl').value = editData.tgl;
                document.getElementById('formNama').value = editData.nama;
                document.getElementById('formInstansi').value = editData.instansi || '';
                document.getElementById('formProduk').value = editData.nama_produk;
                document.getElementById('formHarga').value = editData.harga_produk;
                document.getElementById('formJumlah').value = editData.jumlah;
            } else {
                document.getElementById('modalTitle').innerText = 'Tambah Data Penjualan Baru';
                document.getElementById('editId').value = '';
                document.getElementById('formTgl').value = new Date().toISOString().split('T')[0];
            }

            calculateFormTotal();
            modal.classList.remove('hidden');
        }

        function closeFormModal() {
            document.getElementById('formModal').classList.add('hidden');
        }

        function calculateFormTotal() {
            const harga = parseFloat(document.getElementById('formHarga').value) || 0;
            const jumlah = parseInt(document.getElementById('formJumlah').value) || 0;
            document.getElementById('formTotalPreview').innerText = formatRp(harga * jumlah);
        }

        function handleFormSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('editId').value;
            const record = {
                id: id ? parseInt(id) : Date.now(),
                tgl: document.getElementById('formTgl').value,
                nama: document.getElementById('formNama').value,
                instansi: document.getElementById('formInstansi').value,
                nama_produk: document.getElementById('formProduk').value,
                harga_produk: parseFloat(document.getElementById('formHarga').value) || 0,
                jumlah: parseInt(document.getElementById('formJumlah').value) || 0
            };

            if (id) {
                // Edit existing
                const index = salesData.findIndex(item => item.id == id);
                if (index !== -1) salesData[index] = record;
            } else {
                // Add new
                salesData.unshift(record);
            }

            closeFormModal();
            handleFilterChange();
        }

        function editRecord(id) {
            const record = salesData.find(item => item.id === id);
            if (record) openFormModal(record);
        }

        function deleteRecord(id) {
            if (confirm("Apakah Anda yakin ingin menghapus data penjualan ini?")) {
                salesData = salesData.filter(item => item.id !== id);
                handleFilterChange();
            }
        }

        // Toggle PHP Code View Modal
        function toggleCodeModal(show) {
            const modal = document.getElementById('codeModal');
            if (show) modal.classList.remove('hidden');
            else modal.classList.add('hidden');
        }

        function copyPhpCode() {
            const codeText = document.getElementById('phpCodeContent').innerText;
            const textarea = document.createElement('textarea');
            textarea.value = codeText;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            const btn = document.getElementById('copyCodeBtn');
            btn.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i><span>Tersalin!</span>`;
            btn.classList.replace('bg-amber-600', 'bg-emerald-600');

            setTimeout(() => {
                btn.innerHTML = `<i data-lucide="copy" class="w-4 h-4"></i><span>Copy Code</span>`;
                btn.classList.replace('bg-emerald-600', 'bg-amber-600');
                lucide.createIcons();
            }, 2000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Initialize App on DOM Load
        window.onload = function() {
            lucide.createIcons();
            handleFilterChange();
        };
    </script>
</body>
</html>