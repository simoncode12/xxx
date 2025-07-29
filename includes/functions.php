<?php
// Fungsi untuk mengambil nilai dari tabel settings
function get_setting($key, $pdo) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}

// Fungsi untuk memperbarui nilai di tabel settings
function update_setting($key, $value, $pdo) {
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    return $stmt->execute([$value, $key]);
}

// Fungsi untuk memeriksa apakah pengguna adalah admin dan sudah login
function check_admin_auth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        // Jika bukan admin, tendang ke halaman login
        header('Location: ../auth/login.php');
        exit();
    }
}