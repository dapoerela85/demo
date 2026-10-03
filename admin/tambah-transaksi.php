<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO penjualan (tgl, nama, instansi, nama_produk, harga_produk, jumlah) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['tgl'],
        $_POST['nama'],
        $_POST['instansi'],
        $_POST['nama_produk'],
        $_POST['harga_produk'],
        $_POST['jumlah']
    ]);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Transaksi - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-lg mx-auto bg-white p-6 rounded-xl shadow-sm">
        <h2 class="text-lg font-bold mb-4">Tambah Transaksi Baru</h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal</label>
                <input type="date" name="tgl" value="<?= date('Y-m-d') ?>" required class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Pembeli (Asli)</label>
                <input type="text" name="nama" required placeholder="Contoh: Siti Aisyah" class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Instansi</label>
                <input type="text" name="instansi" placeholder="Puskesmas Melati" class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Produk</label>
                <input type="text" name="nama_produk" required placeholder="Nasi Kotak Ayam Bakar" class="w-full border rounded-lg p-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Harga per Item</label>
                    <input type="number" name="harga_produk" min="0" required placeholder="25000" class="w-full border rounded-lg p-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah</label>
                    <input type="number" name="jumlah" min="1" required placeholder="10" class="w-full border rounded-lg p-2 text-sm">
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <a href="index.php" class="px-4 py-2 border rounded-lg text-sm text-center flex-1">Batal</a>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold flex-1">Simpan Data</button>
            </div>
        </form>
    </div>
</body>
</html>