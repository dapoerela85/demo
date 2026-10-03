<?php
// admin/edit-transaksi.php
require_once __DIR__ . '/auth.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Ambil data transaksi berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM penjualan WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Data transaksi tidak ditemukan.");
}

// Proses Update Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl          = $_POST['tgl'] ?? $data['tgl'];
    $nama         = trim($_POST['nama'] ?? '');
    $instansi     = trim($_POST['instansi'] ?? '');
    $nama_produk  = trim($_POST['nama_produk'] ?? '');
    $harga_produk = floatval($_POST['harga_produk'] ?? 0);
    $jumlah       = intval($_POST['jumlah'] ?? 1);

    $updateStmt = $pdo->prepare("UPDATE penjualan SET tgl=?, nama=?, instansi=?, nama_produk=?, harga_produk=?, jumlah=? WHERE id=?");
    $updateStmt->execute([
        $tgl,
        $nama,
        $instansi,
        $nama_produk,
        $harga_produk,
        $jumlah,
        $id
    ]);

    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi #<?= htmlspecialchars($data['id']) ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-6 font-sans">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
        
        <!-- Header Title -->
        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
            <h2 class="text-lg font-bold text-gray-800">Edit Transaksi #<?= htmlspecialchars($data['id']) ?></h2>
            <span class="text-xs font-mono text-gray-400">ID: <?= htmlspecialchars($data['id']) ?></span>
        </div>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal</label>
                <input type="date" name="tgl" value="<?= htmlspecialchars($data['tgl']) ?>" required class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Pembeli (Asli)</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($data['nama'] ?? '') ?>" required class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Instansi</label>
                <input type="text" name="instansi" value="<?= htmlspecialchars($data['instansi'] ?? '') ?>" placeholder="Puskesmas Melati / - " class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Produk</label>
                <input type="text" name="nama_produk" value="<?= htmlspecialchars($data['nama_produk'] ?? '') ?>" required class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Harga per Item</label>
                    <input type="number" name="harga_produk" value="<?= htmlspecialchars($data['harga_produk']) ?>" min="0" step="1" required class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah</label>
                    <input type="number" name="jumlah" value="<?= htmlspecialchars($data['jumlah']) ?>" min="1" required class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
            </div>

            <div class="flex gap-2 pt-3">
                <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-center flex-1 text-gray-600 hover:bg-gray-100 transition">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold flex-1 hover:bg-orange-700 transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</body>
</html>
