<?php
// admin/pelanggan.php
require_once __DIR__ . '/auth.php';

$message = '';
$error = '';

// 1. Handle Form Submissions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama     = trim($_POST['nama'] ?? '');
        $nik_ktp  = trim($_POST['nik_ktp'] ?? '');
        $no_telp  = trim($_POST['no_telp'] ?? '');
        $instansi = trim($_POST['instansi'] ?? '');
        $alamat   = trim($_POST['alamat'] ?? '');
        $status   = $_POST['status'] ?? 'aktif';

        if (!empty($nama)) {
            $stmt = $pdo->prepare("INSERT INTO pelanggan (nama, nik_ktp, no_telp, instansi, alamat, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $nik_ktp ?: null, $no_telp ?: null, $instansi ?: null, $alamat ?: null, $status]);
            $message = 'Data pelanggan berhasil ditambahkan.';
        } else {
            $error = 'Nama pelanggan wajib diisi!';
        }

    } elseif ($action === 'update') {
        $id       = intval($_POST['id'] ?? 0);
        $nama     = trim($_POST['nama'] ?? '');
        $nik_ktp  = trim($_POST['nik_ktp'] ?? '');
        $no_telp  = trim($_POST['no_telp'] ?? '');
        $instansi = trim($_POST['instansi'] ?? '');
        $alamat   = trim($_POST['alamat'] ?? '');
        $status   = $_POST['status'] ?? 'aktif';

        if ($id > 0 && !empty($nama)) {
            $stmt = $pdo->prepare("UPDATE pelanggan SET nama=?, nik_ktp=?, no_telp=?, instansi=?, alamat=?, status=? WHERE id=?");
            $stmt->execute([$nama, $nik_ktp ?: null, $no_telp ?: null, $instansi ?: null, $alamat ?: null, $status, $id]);
            $message = 'Data pelanggan berhasil diperbarui.';
        } else {
            $error = 'ID tidak valid atau nama pelanggan kosong!';
        }

    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Cek apakah ada transaksi terkait
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM penjualan WHERE pelanggan_id = ?");
            $checkStmt->execute([$id]);
            $count = $checkStmt->fetchColumn();

            if ($count > 0) {
                // Set status non-aktif jika pelanggan sudah memiliki riwayat transaksi
                $stmt = $pdo->prepare("UPDATE pelanggan SET status = 'non-aktif' WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Pelanggan memiliki transaksi aktif. Status diubah menjadi non-aktif.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM pelanggan WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Data pelanggan berhasil dihapus secara permanen.';
            }
        }
    }
}

// 2. Filter & Pagination Parameters
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$limit        = 10;
$offset       = ($page - 1) * $limit;

// 3. Query Construction
$whereClause = "WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $whereClause .= " AND p.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($search)) {
    $whereClause .= " AND (p.nama LIKE :s1 OR p.nik_ktp LIKE :s2 OR p.no_telp LIKE :s3 OR p.instansi LIKE :s4)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
    $params[':s4'] = "%{$search}%";
}

// Total Rows
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pelanggan p {$whereClause}");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

// Fetch Data Pelanggan + Realtime Sync Total Pembelian
$dataStmt = $pdo->prepare("
    SELECT p.*, 
           COALESCE((SELECT SUM(harga_produk * jumlah) FROM penjualan WHERE pelanggan_id = p.id), p.total, 0) AS total_belanja_real
    FROM pelanggan p 
    {$whereClause} 
    ORDER BY p.id DESC 
    LIMIT {$limit} OFFSET {$offset}
");
$dataStmt->execute($params);
$pelangganList = $dataStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Pelanggan - Admin Dapoer Ela 85</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

    <!-- Header Navigation -->
    <header class="bg-gray-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="bg-orange-600 p-2 rounded-lg text-white font-bold text-lg">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-tight">Master Pelanggan</h1>
                    <p class="text-xs text-gray-400">Dapoer Ela 85 - Database Pelanggan & Instansi</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="index.php" class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-200 px-3 py-2 rounded-lg border border-gray-700 transition flex items-center gap-1">
                    <i class="fas fa-chart-line"></i> Dashboard Penjualan
                </a>
                <a href="logout.php" onclick="return confirm('Keluar dari sistem?')" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6 space-y-6">

        <!-- Notification Alerts -->
        <?php if ($message): ?>
            <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-between">
                <span><i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars($message) ?></span>
                <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-rose-100 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-between">
                <span><i class="fas fa-exclamation-triangle mr-1"></i> <?= htmlspecialchars($error) ?></span>
                <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>

        <!-- Title & Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Daftar Master Pelanggan</h2>
                <p class="text-xs text-gray-500">Kelola informasi kontak, NIK, instansi, dan riwayat akumulasi belanja pelanggan.</p>
            </div>
            <button onclick="openModal('create')" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-user-plus"></i> Tambah Pelanggan
            </button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <form method="GET" class="flex flex-wrap md:flex-nowrap gap-3 items-end">
                <div class="w-full md:w-40">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Status Pelanggan</label>
                    <select name="status" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <option value="">Semua Status</option>
                        <option value="aktif" <?= $statusFilter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="non-aktif" <?= $statusFilter === 'non-aktif' ? 'selected' : '' ?>>Non-Aktif</option>
                    </select>
                </div>

                <div class="w-full md:flex-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Cari Pelanggan</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, NIK KTP, No Telp, atau Instansi..." class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                    </div>
                </div>

                <div class="w-full md:w-auto flex gap-2">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm transition font-medium flex items-center gap-1">
                        <i class="fas fa-filter text-xs"></i> Filter
                    </button>
                    <?php if ($statusFilter || $search): ?>
                        <a href="pelanggan.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition flex items-center gap-1">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table Master Pelanggan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4">ID</th>
                            <th class="py-3.5 px-4">Nama Pelanggan</th>
                            <th class="py-3.5 px-4">NIK KTP</th>
                            <th class="py-3.5 px-4">No. Telepon</th>
                            <th class="py-3.5 px-4">Instansi</th>
                            <th class="py-3.5 px-4">Alamat</th>
                            <th class="py-3.5 px-4 text-right">Total Akumulasi</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($pelangganList)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-8 text-gray-400">
                                    <i class="fas fa-users-slash text-3xl mb-2 block"></i>
                                    Tidak ada data pelanggan yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pelangganList as $p): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-3 px-4 text-xs font-mono text-gray-400">#<?= $p['id'] ?></td>
                                    <td class="py-3 px-4 font-bold text-gray-900"><?= htmlspecialchars($p['nama']) ?></td>
                                    <td class="py-3 px-4 text-xs font-mono text-gray-600"><?= htmlspecialchars($p['nik_ktp'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-gray-600 whitespace-nowrap"><?= htmlspecialchars($p['no_telp'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($p['instansi'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-gray-500 text-xs max-w-xs truncate" title="<?= htmlspecialchars($p['alamat'] ?? '') ?>">
                                        <?= htmlspecialchars($p['alamat'] ?: '-') ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-emerald-600 whitespace-nowrap">
                                        <?= formatRupiah($p['total_belanja_real']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <?= renderStatusBadge($p['status']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap space-x-1">
                                        <button onclick='openModal("update", <?= json_encode($p) ?>)' class="bg-gray-100 hover:bg-gray-200 text-amber-600 p-2 rounded-lg text-xs transition" title="Edit Pelanggan">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Hapus/Non-aktifkan pelanggan \'<?= htmlspecialchars($p['nama']) ?>\'?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="bg-gray-100 hover:bg-red-50 text-red-600 p-2 rounded-lg text-xs transition" title="Hapus Pelanggan">
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

            <!-- Pagination Bar -->
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap justify-between items-center gap-2 text-xs text-gray-600">
                <span>Menampilkan <b><?= count($pelangganList) ?></b> dari total <b><?= $totalRows ?></b> pelanggan</span>
                
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

    <!-- Modal Form (Tambah & Edit Pelanggan) -->
    <div id="pelangganModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
            <div class="bg-gray-900 text-white px-5 py-4 flex justify-between items-center">
                <h3 id="modalTitle" class="font-bold text-base">Tambah Pelanggan Baru</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" class="p-5 space-y-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId" value="">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" id="inputNama" required placeholder="Siti Aisyah" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">NIK KTP (Opsional)</label>
                        <input type="text" name="nik_ktp" id="inputNik" placeholder="327101..." class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">No. Telepon (Opsional)</label>
                        <input type="text" name="no_telp" id="inputTelp" placeholder="08123456..." class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Instansi (Opsional)</label>
                        <input type="text" name="instansi" id="inputInstansi" placeholder="Dinas Kesehatan" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                        <select name="status" id="inputStatus" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none font-semibold">
                            <option value="aktif">Aktif</option>
                            <option value="non-aktif">Non-Aktif</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat (Opsional)</label>
                    <textarea name="alamat" id="inputAlamat" rows="2" placeholder="Jl. Raya Utama No. 45, Jakarta" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script JavaScript modal handler -->
    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('pelangganModal');
            document.getElementById('formAction').value = mode;

            if (mode === 'create') {
                document.getElementById('modalTitle').innerText = 'Tambah Pelanggan Baru';
                document.getElementById('formId').value = '';
                document.getElementById('inputNama').value = '';
                document.getElementById('inputNik').value = '';
                document.getElementById('inputTelp').value = '';
                document.getElementById('inputInstansi').value = '';
                document.getElementById('inputStatus').value = 'aktif';
                document.getElementById('inputAlamat').value = '';
            } else if (mode === 'update' && data) {
                document.getElementById('modalTitle').innerText = 'Edit Pelanggan #' + data.id;
                document.getElementById('formId').value = data.id;
                document.getElementById('inputNama').value = data.nama || '';
                document.getElementById('inputNik').value = data.nik_ktp || '';
                document.getElementById('inputTelp').value = data.no_telp || '';
                document.getElementById('inputInstansi').value = data.instansi || '';
                document.getElementById('inputStatus').value = data.status || 'aktif';
                document.getElementById('inputAlamat').value = data.alamat || '';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            const modal = document.getElementById('pelangganModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>
