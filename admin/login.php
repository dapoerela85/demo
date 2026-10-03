<?php
// admin/login.php
require_once __DIR__ . '/../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau Password salah!';
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
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-sm">
        <h2 class="text-xl font-bold text-center text-gray-800 mb-6">Login Panel Admin</h2>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-600 p-3 rounded-lg text-xs mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Username</label>
                <input type="text" name="username" required class="w-full border rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Password</label>
                <input type="password" name="password" required class="w-full border rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <button type="submit" class="w-full bg-orange-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-orange-700">Masuk</button>
        </form>
    </div>
</body>
</html>
