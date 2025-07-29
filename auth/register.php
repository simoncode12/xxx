<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'user'; // Default role is 'user'

    // Validasi sederhana
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter.';
    } else {
        // Cek apakah username atau email sudah ada
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username atau email sudah terdaftar.';
        } else {
            // Hash password untuk keamanan
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Masukkan user baru ke database
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                // Redirect ke halaman login setelah berhasil
                header('Location: login.php?status=registered');
                exit();
            } else {
                $error = 'Terjadi kesalahan. Gagal mendaftar.';
            }
        }
    }
}
include '../includes/templates/header.php';
?>
<div class="auth-container">
    <form method="POST" class="auth-form">
        <h2>Daftar Akun TubeX</h2>
        <p>Buat akun untuk mulai menonton dan mendapatkan reward.</p>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <label>Daftar sebagai:</label>
        <select name="role">
            <option value="user" selected>Penonton (User)</option>
            <option value="creator">Kreator (Creator)</option>
        </select>

        <button type="submit">Daftar</button>
        <p class="auth-switch">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </form>
</div>
<?php include '../includes/templates/footer.php'; ?>