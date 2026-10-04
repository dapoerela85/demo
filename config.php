<?php
// config.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set durasi session menjadi 30 hari (2.592.000 detik) agar tidak logout otomatis
$session_lifetime = 2592000; 

ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.cookie_lifetime', $session_lifetime);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$db_host = 'db.fr-roub1.bengt.wasmernet.com';
$db_port = '20184';
$db_name = 'dapoerela85_db';
$db_user = 'user_53b82568';
$db_pass = 'pw_Ia6e5i9rEcpczY7FbmbNPrgIId49MZah';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

if (!function_exists('maskName')) {
    function maskName($name) {
        $words = explode(' ', trim($name ?? ''));
        $maskedWords = array_map(function($word) {
            $length = mb_strlen($word);
            if ($length <= 1) return $word;
            return mb_substr($word, 0, 1) . str_repeat('*', max(3, $length - 1));
        }, $words);
        return implode(' ', $maskedWords);
    }
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($amount) {
        return 'Rp ' . number_format($amount ?? 0, 0, ',', '.');
    }
}

if (!function_exists('renderStatusBadge')) {
    function renderStatusBadge($status) {
        if (strtolower($status) === 'lunas' || strtolower($status) === 'aktif') {
            return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">' . ucfirst($status) . '</span>';
        }
        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">' . ucfirst($status) . '</span>';
    }
}

function syncTotalPelanggan($pdo, $pelanggan_id) {
    if (!$pelanggan_id) return;
    $stmt = $pdo->prepare("SELECT SUM(harga_produk * jumlah) FROM penjualan WHERE pelanggan_id = ?");
    $stmt->execute([$pelanggan_id]);
    $total = $stmt->fetchColumn() ?? 0;

    $update = $pdo->prepare("UPDATE pelanggan SET total = ? WHERE id = ?");
    $update->execute([$total, $pelanggan_id]);
}
?>
