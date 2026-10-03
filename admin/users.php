<?php
// admin/users.php
require_once __DIR__ . '/auth.php';

$message = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';

// Handle CRUD Users (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $username     = trim($_POST['username'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $password     = $_POST['password'] ?? '';

        if (!empty($nama_lengkap) && !empty($username) && !empty($password)) {
            // Cek duplikasi username
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE username = ?");
            $checkStmt->execute([$username]);

            if ($checkStmt->fetchColumn() > 0) {
                header("Location: users.php?err=" . urlencode('Username sudah digunakan!'));
                exit;
            }

            $passHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO user (nama_lengkap, username, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_lengkap, $username, $email ?: null, $passHash]);

            header("Location: users.php?msg=" . urlencode('User admin baru berhasil ditambahkan.'));
            exit;
        } else {
            header("Location: users.php?err=" . urlencode('Nama lengkap, username, dan password wajib diisi!'));
            exit;
        }

    } elseif ($action === 'update') {
        $id           = intval($_POST['id'] ?? 0);
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $password     = $_POST['password'] ?? '';

        if ($id > 0 && !empty($nama_lengkap)) {
            if (!empty($password)) {
                // Update dengan password baru
                $passHash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE user SET nama_lengkap=?, email=?, password=? WHERE id=?");
                $stmt->execute([$nama_lengkap, $email ?: null, $passHash, $id]);
            } else {
                // Update tanpa mengubah password
                $stmt = $pdo->prepare("UPDATE user SET nama_lengkap=?, email=? WHERE id=?");
                $stmt->execute([$nama_lengkap, $email ?: null, $id]);
            }

            header("Location: users.php?msg=" . urlencode('Data user admin berhasil diperbarui.'));
            exit;
        }

    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        // Mencegah hapus diri sendiri / user admin pertama jika tinggal 1
        $countUsers = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
        if ($countUsers <= 1) {
            header("Location: users.php?err=" . urlencode('Tidak dapat menghapus user administrator terakhir!'));
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM user WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: users.php?msg=" . urlencode('User admin berhasil dihapus.'));
            exit;
        }
    }
}

// Fetch All Admin Users
$usersStmt = $pdo->query("SELECT id, nama_lengkap, username, email, created_at FROM user ORDER BY id ASC");
$usersList = $usersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Administrator - Dapoer Ela 85</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

    <!-- Header Navigation -->
    <header class="bg-gray-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="bg-orange-600 p-2 rounded-lg text-white font-bold text-lg">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-tight">Manajemen Administrator</h1>
                    <p class="text-xs text-gray-400">Kelola Akun Akses Panel Admin</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="index.php" class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-200 px-3 py-2 rounded-lg border border-gray-700 transition">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="pelanggan.php" class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-200 px-3 py-2 rounded-lg border border-gray-700 transition">
                    <i class="fas fa-users"></i> Master Pelanggan
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

        <!-- Action Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Daftar User Administrator</h2>
                <p class="text-xs text-gray-500">Pengguna yang terdaftar memiliki hak akses penuh ke panel admin.</p>
            </div>
            <button onclick="openModal('create')" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="fas fa-user-plus"></i> Tambah Admin Baru
            </button>
        </div>

        <!-- Table Users -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-600 font-semibold border-b border-gray-200 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">ID</th>
                        <th class="py-3.5 px-4">Nama Lengkap</th>
                        <th class="py-3.5 px-4">Username</th>
                        <th class="py-3.5 px-4">Email</th>
                        <th class="py-3.5 px-4">Tanggal Dibuat</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($usersList as $u): ?>
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="py-3 px-4 text-xs font-mono text-gray-400">#<?= $u['id'] ?></td>
                            <td class="py-3 px-4 font-bold text-gray-900"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                            <td class="py-3 px-4 font-mono text-xs text-orange-600 font-bold"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($u['email'] ?: '-') ?></td>
                            <td class="py-3 px-4 text-gray-400 text-xs"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                            <td class="py-3 px-4 text-center whitespace-nowrap space-x-1">
                                <button onclick='openModal("update", <?= json_encode($u) ?>)' class="bg-gray-100 hover:bg-gray-200 text-amber-600 p-2 rounded-lg text-xs transition" title="Edit Admin">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="inline-block" onsubmit="return confirm('Hapus user admin \'<?= htmlspecialchars($u['username']) ?>\'?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="bg-gray-100 hover:bg-red-50 text-red-600 p-2 rounded-lg text-xs transition" title="Hapus User">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Form -->
    <div id="userModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
            <div class="bg-gray-900 text-white px-5 py-4 flex justify-between items-center">
                <h3 id="modalTitle" class="font-bold text-base">Tambah Admin Baru</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" class="p-5 space-y-4">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId" value="">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_lengkap" id="inputNama" required placeholder="Administrator Utama" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="inputUsername" required placeholder="admin2" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Email (Opsional)</label>
                    <input type="email" name="email" id="inputEmail" placeholder="admin@dapoerela85.com" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1" id="labelPassword">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" id="inputPassword" placeholder="••••••••" class="w-full border rounded-lg p-2 text-sm focus:ring-2 focus:ring-orange-500 focus:outline-none">
                    <p id="hintPassword" class="text-[11px] text-gray-400 mt-1 hidden">Kosongkan jika tidak ingin mengubah password.</p>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Simpan User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('userModal');
            document.getElementById('formAction').value = mode;

            if (mode === 'create') {
                document.getElementById('modalTitle').innerText = 'Tambah Admin Baru';
                document.getElementById('formId').value = '';
                document.getElementById('inputNama').value = '';
                document.getElementById('inputUsername').value = '';
                document.getElementById('inputUsername').readOnly = false;
                document.getElementById('inputEmail').value = '';
                document.getElementById('inputPassword').required = true;
                document.getElementById('labelPassword').innerHTML = 'Password <span class="text-red-500">*</span>';
                document.getElementById('hintPassword').classList.add('hidden');
            } else if (mode === 'update' && data) {
                document.getElementById('modalTitle').innerText = 'Edit Admin #' + data.id;
                document.getElementById('formId').value = data.id;
                document.getElementById('inputNama').value = data.nama_lengkap || '';
                document.getElementById('inputUsername').value = data.username || '';
                document.getElementById('inputUsername').readOnly = true;
                document.getElementById('inputEmail').value = data.email || '';
                document.getElementById('inputPassword').required = false;
                document.getElementById('labelPassword').innerText = 'Ubah Password (Opsional)';
                document.getElementById('hintPassword').classList.remove('hidden');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            const modal = document.getElementById('userModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>
