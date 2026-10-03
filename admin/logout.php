<?php
// admin/logout.php

// Pastikan session sudah diinisialisasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua data session
$_SESSION = array();

// Jika menggunakan cookie session, hapus cookie session tersebut
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan session secara penuh
session_destroy();

// Redirect kembali ke halaman login admin
header("Location: login.php");
exit;
?>
