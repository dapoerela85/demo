<?php
// admin/edit-transaksi.php
require_once __DIR__ . '/auth.php';

$id =$_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// 1. Ambil data transaksi acuan
$stmt =$pdo->prepare("SELECT * FROM penjualan WHERE id = ?");
$stmt->execute([$id]);
$trxData =$stmt->fetch();

if (!$trxData) {
    die("Data transaksi tidak ditemukan.");
}

// 2. Ambil semua produk milik pembeli & tanggal yang sama
$itemsStmt =$pdo->prepare("SELECT * FROM penjualan WHERE tgl = ? AND nama = ? ORDER BY id ASC");
$itemsStmt->execute([$trxData['tgl'],$trxData['nama']]);
$itemsList =$itemsStmt->fetchAll();

if (empty($itemsList)) {
    $itemsList = [$trxData];
}

// 3. Proses Update Multi-Produk (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl =$_POST['tgl'];
    $nama =$_POST['nama'];
    $instansi =$_POST['instansi'] ?? '';

    $item_ids =$_POST['item_id'] ?? [];
    $nama_produk_list =$_POST['nama_produk'] ?? [];
    $harga_produk_list =$_POST['harga_produk'] ?? [];
    $jumlah_list =$_POST['jumlah'] ?? [];

    $existingIds = array_column($itemsList, 'id');
    $submittedIds = array_filter($item_ids);

    // Hapus item produk yang dibuang dari baris oleh admin
    $idsToDelete = array_diff($existingIds,$submittedIds);
    if (!empty($idsToDelete)) {
        $inClause = implode(',', array_fill(0, count($idsToDelete), '?'));
        $deleteStmt =$pdo->prepare("DELETE FROM penjualan WHERE id IN ($inClause)");
        $deleteStmt->execute(array_values($idsToDelete));
    }

    // Loop untuk Update / Insert Item Produk
    $updateStmt =$pdo->prepare("UPDATE penjualan SET tgl=?, nama=?, instansi=?, nama_produk=?, harga_produk=?, jumlah=? WHERE id=?");
    $insertStmt =$pdo->prepare("INSERT INTO penjualan (tgl, nama, instansi, nama_produk, harga_produk, jumlah) VALUES (?, ?, ?, ?, ?, ?)");

    for ($i = 0; $i < count($nama_produk_list); $i++) {$itemId = !empty($item_ids[$i]) ? intval($item_ids[$i]) : null;
        $produk = trim($nama_produk_list[$i]);
        $harga  = floatval($harga_produk_list[$i]);$jumlah = intval($jumlah_list[$i]);

        if (!empty($produk) && $harga >= 0 &&$jumlah > 0) {
            if ($itemId && in_array($itemId,$existingIds)) {
                // Update item lama
                $updateStmt->execute([$tgl, $nama,$instansi, $produk,$harga, $jumlah,$itemId]);
            } else {
                // Insert item baru yang ditambahkan di form edit
                $insertStmt->execute([$tgl,$nama, $instansi,$produk, $harga,$jumlah]);
            }
        }
    }

    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi #<?= htmlspecialchars($trxData['id']) ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">Edit Transaksi</h2>
            <span class="text-xs bg-orange-100 text-orange-700 font-semibold px-2.5 py-1 rounded-md">ID Utama #<?= htmlspecialchars($trxData['id']) ?></span>
        </div>
        
        <form method="POST" class="space-y-4">
            <!-- Data Pembeli & Tanggal -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal</label>
                    <input type="date" name="tgl" value="<?= htmlspecialchars($trxData['tgl']) ?>" required class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Pembeli (Asli)</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($trxData['nama']) ?>" required placeholder="Contoh: Siti Aisyah" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Instansi</label>
                    <input type="text" name="instansi" value="<?= htmlspecialchars($trxData['instansi'] ?? '') ?>" placeholder="Puskesmas Melati" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
            </div>

            <hr class="my-4 border-gray-100">

            <!-- Container Produk Dinamis -->
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Daftar Produk / Pesanan</label>
                    <button type="button" onclick="addProductRow()" class="text-xs bg-orange-50 hover:bg-orange-100 text-orange-600 font-semibold px-3 py-1.5 rounded-lg border border-orange-200 transition flex items-center gap-1">
                        <i class="fas fa-plus"></i> Tambah Baris Produk
                    </button>
                </div>

                <div id="product-container" class="space-y-3">
                    <?php foreach ($itemsList as$item): ?>
                        <!-- Baris Produk Existing -->
                        <div class="product-row bg-gray-50 p-3 rounded-lg border border-gray-200 relative grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <input type="hidden" name="item_id[]" value="<?= htmlspecialchars($item['id']) ?>">
                            
                            <div class="md:col-span-5">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Produk</label>
                                <input type="text" name="nama_produk[]" value="<?= htmlspecialchars($item['nama_produk']) ?>" required placeholder="Nasi Kotak Ayam Bakar" class="w-full border rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                            </div>
                            <div class="md:col-span-4">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Harga per Item</label>
                                <input type="number" name="harga_produk[]" value="<?= htmlspecialchars($item['harga_produk']) ?>" min="0" required placeholder="25000" class="w-full border rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah</label>
                                <input type="number" name="jumlah[]" value="<?= htmlspecialchars($item['jumlah']) ?>" min="1" required class="w-full border rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                            </div>
                            <div class="md:col-span-1 flex justify-center">
                                <button type="button" onclick="removeProductRow(this)" class="text-gray-400 hover:text-red-600 p-2 text-sm transition" title="Hapus Baris">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex gap-2 pt-4">
                <a href="index.php" class="px-4 py-2 border rounded-lg text-sm text-center flex-1 text-gray-600 hover:bg-gray-100 transition">Batal</a>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold flex-1 hover:bg-orange-700 transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <!-- Script JavaScript untuk Tambah/Hapus Baris Produk secara Dinamis -->
    <script>
        function addProductRow() {
            const container = document.getElementById('product-container');
            const newRow = document.createElement('div');
            newRow.className = 'product-row bg-gray-50 p-3 rounded-lg border border-gray-200 relative grid grid-cols-1 md:grid-cols-12 gap-3 items-end';
            
            newRow.innerHTML = `
                <input type="hidden" name="item_id[]" value="">
                <div class="md:col-span-5">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Produk</label>
                    <input type="text" name="nama_produk[]" required placeholder="Snack Box / Es Teh" class="w-full border rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Harga per Item</label>
                    <input type="number" name="harga_produk[]" min="0" required placeholder="15000" class="w-full border rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah</label>
                    <input type="number" name="jumlah[]" min="1" value="1" required class="w-full border rounded-lg p-2 text-sm bg-white focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div class="md:col-span-1 flex justify-center">
                    <button type="button" onclick="removeProductRow(this)" class="text-gray-400 hover:text-red-600 p-2 text-sm transition" title="Hapus Baris">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(newRow);
        }

        function removeProductRow(button) {
            const container = document.getElementById('product-container');
            const rows = container.getElementsByClassName('product-row');
            
            // Minimal menyisakan 1 baris
            if (rows.length > 1) {
                button.closest('.product-row').remove();
            } else {
                alert('Minimal harus mengisi 1 produk.');
            }
        }
    </script>
</body>
</html>
