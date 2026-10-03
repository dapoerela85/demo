<?php
// admin/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan ke config.php di folder utama (parent directory)
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>