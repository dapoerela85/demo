<?php
// admin/auth.php
require_once __DIR__ . '/../config.php';

// Cek apakah session login admin aktif
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Perbarui waktu aktivitas terakhir admin agar session tidak kadaluarsa
$_SESSION['last_activity'] = time();
?>
