<?php
// admin/produk-edit.php
require_once __DIR__ . '/auth.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: produk.php');
    exit;
}

// Folder Upload
$uploadDir = __DIR__ . '/../uploads/produk/';

// Fetch Data Produk
$stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    die("<div style='padding:20px; font-family:sans-serif; color:red;'>Produk tidak ditemukan. <a href='produk.php'>Kembali ke Master Produk</a></div>");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk    = trim($_POST['nama_produk'] ?? '');
    $kemasan_produk = trim($_POST['kemasan_produk'] ?? '');
    $stok_produk    = floatval($_POST['stok_produk'] ?? 0);
    $berat_produk   = floatval($_POST['berat_produk'] ?? 0);
    $harga_produk   = floatval($_POST['harga_produk'] ?? 0);
    $varian_produk  = trim($_POST['varian_produk'] ?? '');
    $status         = $_POST['status'] ?? 'published';
    $img_name       = $produk['img_produk'];

    // Update Gambar jika ada upload baru
    if (!empty($_FILES['img_produk']['name'])) {
        $ext = strtolower(pathinfo($_FILES['img_produk']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            // Hapus gambar lama
            if ($img_name && file_exists($uploadDir . $img_name)) {
                @unlink($uploadDir . $img_name);
            }
            $img_name = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['img_produk']['tmp_name'], $uploadDir . $img_name);
        } else {
            $error = 'Format gambar harus JPG, PNG, atau WEBP.';
        }
    }

    if (empty($error) && !empty($nama_produk)) {
        $updateStmt = $pdo->prepare("UPDATE produk SET nama_produk=?, kemasan_produk=?, stok_produk=?, berat_produk=?, harga_produk=?, img_produk=?, varian_produk=?, status=? WHERE id=?");
        $updateStmt->execute([$nama_produk, $kemasan_produk ?: null, $stok_produk, $berat_produk, $harga_produk, $img_name, $varian_produk ?: null, $status, $id]);

        header("Location: produk.php?msg=" . urlencode('Data produk berhasil diperbarui.'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk #<?= htmlspecialchars($produk['id']) ?> - Admin Dapoer Ela 85</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-6 font-sans">
    <div class="max-w-xl mx-auto bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-800">Edit Produk #<?= htmlspecialchars($produk['id']) ?></h2>
            <a href="produk.php" class="text-xs text-gray-600 hover:text-orange-600 font-semibold flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Kembali ke Master Produk
            </a>
        </div>

        <?php if ($error): ?>
            <div class="bg-rose-100 border border-rose-200 text-rose-800 p-3 rounded-lg text-xs"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Produk <span class="text-red-500">*</span></label>
                <input type="text" name="nama_produk" value="<?= htmlspecialchars($produk['nama_produk']) ?>" required class="w-full border rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Kemasan</label>
                    <input type="text" name="kemasan_produk" value="<?= htmlspecialchars($produk['kemasan_produk'] ?? '') ?>" placeholder="Pouch / Toples 1L" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Varian Rasa</label>
                    <input type="text" name="varian_produk" value="<?= htmlspecialchars($produk['varian_produk'] ?? '') ?>" placeholder="Pedas / Original" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Harga (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="harga_produk" value="<?= htmlspecialchars($produk['harga_produk']) ?>" required min="0" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Berat (Gram)</label>
                    <input type="number" name="berat_produk" value="<?= htmlspecialchars($produk['berat_produk']) ?>" min="0" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Stok (Pcs)</label>
                    <input type="number" name="stok_produk" value="<?= htmlspecialchars($produk['stok_produk']) ?>" min="0" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Gambar Produk</label>
                <?php if (!empty($produk['img_produk']) && file_exists($uploadDir . $produk['img_produk'])): ?>
                    <div class="flex items-center gap-3 mb-2 p-2 bg-gray-50 rounded-lg border">
                        <img src="../uploads/produk/<?= htmlspecialchars($produk['img_produk']) ?>" class="w-12 h-12 object-cover rounded-lg border">
                        <span class="text-xs text-gray-500">Gambar terpasang saat ini</span>
                    </div>
                <?php endif; ?>
                <input type="file" name="img_produk" accept="image/*" class="w-full border rounded-lg p-1.5 text-xs focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status Publikasi</label>
                <select name="status" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none font-semibold">
                    <option value="published" <?= $produk['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $produk['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>

            <div class="flex gap-2 pt-3 border-t border-gray-100">
                <a href="produk.php" class="px-4 py-2 border rounded-lg text-sm text-center flex-1 text-gray-600 hover:bg-gray-100 transition">Batal</a>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold flex-1 hover:bg-orange-700 transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</body>
</html>
