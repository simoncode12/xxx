<?php
session_start();
require_once '../includes/db_connect.php';

$error = '';
$message = '';

if (isset($_GET['status']) && $_GET['status'] === 'registered') {
    $message = 'Pendaftaran berhasil! Silakan login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verifikasi user dan password
        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect ke halaman utama setelah login
            header('Location: ../index.php');
            exit();
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
include '../includes/templates/header.php';
?>
<div class="auth-container">
    <form method="POST" class="auth-form">
        <h2>Login ke TubeX</h2>
        
        <?php if ($message): ?>
            <div class="alert success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <p class="auth-switch">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
    </form>
</div>
<?php include '../includes/templates/footer.php'; ?>