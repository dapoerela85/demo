<?php
// config.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
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

// Helper badge status
function renderStatusBadge($status) {
    if (strtolower($status) === 'lunas') {
        return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800"><i class="fas fa-check-circle mr-1"></i> Lunas</span>';
    }
    return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800"><i class="fas fa-clock mr-1"></i> Belum Lunas</span>';
}
?>
