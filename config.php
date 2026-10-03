<?php
// config.php
session_start();

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

// Fungsi Sensor / Masking Nama (Publik)
function maskName($name) {
    $words = explode(' ', trim($name));
    $maskedWords = array_map(function($word) {
        $length = mb_strlen($word);
        if ($length <= 1) return $word;
        return mb_substr($word, 0, 1) . str_repeat('*', max(3, $length - 1));
    }, $words);
    return implode(' ', $maskedWords);
}

// Helper Format Rupiah
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount ?? 0, 0, ',', '.');
}
?>