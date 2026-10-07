<?php
// admin/produk.php
require_once __DIR__ . '/auth.php';

$message = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';

// Folder Upload Gambar
$uploadDir = __DIR__ . '/../uploads/produk/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 1. Handle POST Actions (Create & Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama_produk    = trim($_POST['nama_produk'] ?? '');
        $kemasan_produk = trim($_POST['kemasan_produk'] ?? '');
        $stok_produk    = floatval($_POST['stok_produk'] ?? 0);
        $berat_produk   = floatval($_POST['berat_produk'] ?? 0);
        $harga_produk   = floatval($_POST['harga_produk'] ?? 0);
        $varian_produk  = trim($_POST['varian_produk'] ?? '');
        $status         = $_POST['status'] ?? 'published';
        $img_name       = null;

        // Upload Gambar
        if (!empty($_FILES['img_produk']['name'])) {
            $ext = strtolower(pathinfo($_FILES['img_produk']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                $img_name = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['img_produk']['tmp_name'], $uploadDir . $img_name);
            } else {
                header("Location: produk.php?err=" . urlencode('Format gambar harus JPG, PNG, atau WEBP.'));
                exit;
            }
        }

        if (!empty($nama_produk)) {
            $stmt = $pdo->prepare("INSERT INTO produk (nama_produk, kemasan_produk, stok_produk, berat_produk, harga_produk, img_produk, varian_produk, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama_produk, $kemasan_produk ?: null, $stok_produk, $berat_produk, $harga_produk, $img_name, $varian_produk ?: null, $status]);

            header("Location: produk.php?msg=" . urlencode('Produk baru berhasil ditambahkan.'));
            exit;
        } else {
            header("Location: produk.php?err=" . urlencode('Nama produk wajib diisi!'));
            exit;
        }

    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Unlink Gambar jika ada
            $stmtImg = $pdo->prepare("SELECT img_produk FROM produk WHERE id = ?");
            $stmtImg->execute([$id]);
            $oldImg = $stmtImg->fetchColumn();
            if ($oldImg && file_exists($uploadDir . $oldImg)) {
                @unlink($uploadDir . $oldImg);
            }

            $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
            $stmt->execute([$id]);

            header("Location: produk.php?msg=" . urlencode('Produk berhasil dihapus.'));
            exit;
        }
    }
}

// 2. Filter & Query
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$limit        = 10;
$offset       = ($page - 1) * $limit;

$whereClause = "WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $whereClause .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($search)) {
    $whereClause .= " AND (nama_produk LIKE :s1 OR kemasan_produk LIKE :s2 OR varian_produk LIKE :s3)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM produk {$whereClause}");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

$dataStmt = $pdo->prepare("SELECT * FROM produk {$whereClause} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}");
$dataStmt->execute($params);
$produkList = $dataStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Produk - Admin Dapoer Ela 85</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

    <!-- Header Navigation -->
    <header class="bg-gray-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="bg-orange-600 p-2 rounded-lg text-white font-bold text-lg">
                    <i class="fas fa-box"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-tight">Master Produk</h1>
                    <p class="text-xs text-gray-400">Dapoer Ela 85 - Kelola Produk & Stok</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="index.php" class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-200 px-3 py-2 rounded-lg border border-gray-700 transition">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="pelanggan.php" class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-200 px-3 py-2 rounded-lg border border-gray-700 transition">
                    <i class="fas fa-users"></i> Pelanggan
                </a>
                <a href="logout.php" onclick="return confirm('Keluar dari sistem?')" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6 space-y-6">

        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold flex justify-between items-center">
                <span><i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars($message) ?></span>
                <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-rose-100 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-semibold flex justify-between items-center">
                <span><i class="fas fa-exclamation-triangle mr-1"></i> <?= htmlspecialchars($error) ?></span>
                <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>

        <!-- Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Daftar Produk</h2>
                <p class="text-xs text-gray-500">Kelola item produk, harga, varian, berat, dan gambar.</p>
            </div>
            <button onclick="openModal()" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition flex items-center justify-center gap-2">
                <i class="fas fa-plus"></i> Tambah Produk Baru
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <form method="GET" class="flex flex-wrap md:flex-nowrap gap-3 items-end">
                <div class="w-full md:w-40">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Status Produk</label>
                    <select name="status" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <option value="">Semua Status</option>
                        <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>

                <div class="w-full md:flex-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Cari Produk</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama produk, kemasan, varian..." class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                    </div>
                </div>

                <div class="w-full md:w-auto flex gap-2">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm transition font-medium flex items-center gap-1">
                        <i class="fas fa-filter text-xs"></i> Filter
                    </button>
                    <?php if ($statusFilter || $search): ?>
                        <a href="produk.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition flex items-center gap-1">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table Master Produk -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4">ID</th>
                            <th class="py-3.5 px-4">Gambar</th>
                            <th class="py-3.5 px-4">Nama Produk</th>
                            <th class="py-3.5 px-4">Kemasan / Varian</th>
                            <th class="py-3.5 px-4 text-center">Berat</th>
                            <th class="py-3.5 px-4 text-center">Stok</th>
                            <th class="py-3.5 px-4 text-right">Harga Satuan</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($produkList)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-8 text-gray-400">
                                    <i class="fas fa-box-open text-3xl mb-2 block"></i>
                                    Tidak ada data produk yang ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($produkList as $p): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-3 px-4 text-xs font-mono text-gray-400">#<?= $p['id'] ?></td>
                                    <td class="py-3 px-4">
                                        <?php if (!empty($p['img_produk']) && file_exists($uploadDir . $p['img_produk'])): ?>
                                            <img src="../uploads/produk/<?= htmlspecialchars($p['img_produk']) ?>" class="w-10 h-10 object-cover rounded-lg border">
                                        <?php else: ?>
                                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs border">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-gray-900"><?= htmlspecialchars($p['nama_produk']) ?></td>
                                    <td class="py-3 px-4 text-xs text-gray-600">
                                        <?= htmlspecialchars($p['kemasan_produk'] ?: '-') ?> 
                                        <?= $p['varian_produk'] ? '<span class="text-orange-600">(' . htmlspecialchars($p['varian_produk']) . ')</span>' : '' ?>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs"><?= number_format($p['berat_produk'], 0, ',', '.') ?>g</td>
                                    <td class="py-3 px-4 text-center font-semibold"><?= number_format($p['stok_produk'], 0, ',', '.') ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-orange-600"><?= formatRupiah($p['harga_produk']) ?></td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <?php if ($p['status'] === 'published'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Published</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-200 text-gray-700">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap space-x-1">
                                        <a href="produk-edit.php?id=<?= $p['id'] ?>" class="bg-gray-100 hover:bg-gray-200 text-amber-600 p-2 rounded-lg text-xs transition inline-block" title="Edit Produk">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Hapus produk \'<?= htmlspecialchars($p['nama_produk']) ?>\'?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="bg-gray-100 hover:bg-red-50 text-red-600 p-2 rounded-lg text-xs transition" title="Hapus Produk">
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
                <span>Menampilkan <b><?= count($produkList) ?></b> dari total <b><?= $totalRows ?></b> produk</span>
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

    <!-- Modal Form (Tambah Produk Baru) -->
    <div id="produkModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
            <div class="bg-gray-900 text-white px-5 py-4 flex justify-between items-center">
                <h3 class="font-bold text-base">Tambah Produk Baru</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_produk" required placeholder="Contoh: Basreng Daun Jeruk Pouch" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kemasan</label>
                        <input type="text" name="kemasan_produk" placeholder="Pouch / Toples 1L" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Varian Rasa</label>
                        <input type="text" name="varian_produk" placeholder="Pedas / Original" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Harga (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="harga_produk" required min="0" placeholder="20000" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Berat (Gram)</label>
                        <input type="number" name="berat_produk" min="0" placeholder="120" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Stok (Pcs)</label>
                        <input type="number" name="stok_produk" min="0" value="0" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Gambar Produk</label>
                        <input type="file" name="img_produk" accept="image/*" class="w-full border rounded-lg p-1.5 text-xs focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                        <select name="status" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none font-semibold">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            const modal = document.getElementById('produkModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            const modal = document.getElementById('produkModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>
