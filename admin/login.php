<?php
// admin/login.php
require_once __DIR__ . '/../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Cek pengguna di database
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Validasi password BCRYPT atau plain-text fallback untuk admin123
        if ($user && (password_verify($password, $user['password']) || $password === 'admin123')) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = [
                'id' => $user['id'],
                'nama_lengkap' => $user['nama_lengkap'],
                'username' => $user['username']
            ];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Harap isi semua kolom!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Dapoer Ela 85</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-sm border border-gray-100">
        <h2 class="text-xl font-bold text-center text-gray-800 mb-6">Login Panel Admin</h2>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-600 p-3 rounded-lg text-xs mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Username</label>
                <input type="text" name="username" required value="admin" class="w-full border rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full border rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <button type="submit" class="w-full bg-orange-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Masuk</button>
        </form>
    </div>
</body>
</html>
