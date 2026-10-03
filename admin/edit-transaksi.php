<?php
require_once '../auth.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM penjualan WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die("Data tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateStmt = $pdo->prepare("UPDATE penjualan SET tgl=?, nama=?, instansi=?, nama_produk=?, harga_produk=?, jumlah=? WHERE id=?");
    $updateStmt->execute([
        $_POST['tgl'],
        $_POST['nama'],
        $_POST['instansi'],
        $_POST['nama_produk'],
        $_POST['harga_produk'],
        $_POST['jumlah'],
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
    <title>Edit Transaksi #<?= $data['id'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-xl shadow-sm">
        <h2 class="text-lg font-bold mb-4">Edit Transaksi #<?= $data['id'] ?></h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal</label>
                <input type="date" name="tgl" value="<?= $data['tgl'] ?>" required class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Pembeli</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($data['nama']) ?>" required class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Instansi</label>
                <input type="text" name="instansi" value="<?= htmlspecialchars($data['instansi']) ?>" class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Produk</label>
                <input type="text" name="nama_produk" value="<?= htmlspecialchars($data['nama_produk']) ?>" required class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Harga per Item</label>
                    <input type="number" name="harga_produk" value="<?= $data['harga_produk'] ?>" min="0" required class="w-full border rounded-lg p-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah</label>
                    <input type="number" name="jumlah" value="<?= $data['jumlah'] ?>" min="1" required class="w-full border rounded-lg p-2 text-sm">
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <a href="index.php" class="px-4 py-2 border rounded-lg text-sm text-center flex-1">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold flex-1">Update Data</button>
            </div>
        </form>
    </div>
</body>
</html>