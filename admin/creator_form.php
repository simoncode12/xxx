<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
check_admin_auth();

$error = '';
$creator_data = [
    'id' => null,
    'username' => '',
    'email' => ''
];
$is_edit_mode = false;

// Cek apakah ini mode Edit
if (isset($_GET['edit_id'])) {
    $is_edit_mode = true;
    $creator_id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ? AND role = 'creator'");
    $stmt->execute([$creator_id]);
    $creator_data = $stmt->fetch();
    if (!$creator_data) {
        // Jika ID tidak ditemukan, redirect
        header("Location: creators.php");
        exit();
    }
}

// Proses form saat disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Password hanya wajib untuk user baru

    // Validasi dasar
    if (empty($username) || empty($email)) {
        $error = "Username dan Email wajib diisi.";
    } elseif (!$is_edit_mode && empty($password)) {
        $error = "Password wajib diisi untuk kreator baru.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        try {
            if ($is_edit_mode) {
                // --- LOGIKA UNTUK UPDATE ---
                $creator_id = (int)$_POST['creator_id'];
                if (!empty($password)) {
                    // Jika password diisi, update passwordnya
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $hashed_password, $creator_id]);
                } else {
                    // Jika password kosong, jangan update password
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $creator_id]);
                }
                $_SESSION['message'] = "Data kreator berhasil diperbarui.";
            } else {
                // --- LOGIKA UNTUK INSERT (TAMBAH BARU) ---
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'creator')");
                $stmt->execute([$username, $email, $hashed_password]);
                $_SESSION['message'] = "Kreator baru berhasil ditambahkan.";
            }
            // Redirect kembali ke halaman daftar kreator
            header("Location: creators.php");
            exit();
        } catch (PDOException $e) {
            // Tangani error jika username atau email sudah ada
            if ($e->errorInfo[1] == 1062) {
                $error = "Gagal: Username atau email sudah terdaftar.";
            } else {
                $error = "Terjadi kesalahan database: " . $e->getMessage();
            }
        }
    }
    // Jika ada error, isi kembali data yang sudah diinput
    $creator_data['username'] = $username;
    $creator_data['email'] = $email;
}

include '../includes/templates/header.php';
?>
<div class="admin-container">
    <h1><?= $is_edit_mode ? 'Edit Kreator' : 'Tambah Kreator Baru' ?></h1>
    <a href="creators.php">Kembali ke Daftar Kreator</a>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="import-form" style="margin-top: 20px;">
        <?php if ($is_edit_mode): ?>
            <input type="hidden" name="creator_id" value="<?= $creator_data['id'] ?>">
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($creator_data['username']) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($creator_data['email']) ?>" required>
        
        <label for="password">Password</label>
        <input type="password" id="password" name="password" <?= $is_edit_mode ? '' : 'required' ?>>
        <?php if ($is_edit_mode): ?>
            <small>Kosongkan jika tidak ingin mengubah password.</small>
        <?php endif; ?>

        <button type="submit" style="margin-top: 20px;"><?= $is_edit_mode ? 'Update Data' : 'Simpan Kreator' ?></button>
    </form>
</div>
<?php include '../includes/templates/footer.php'; ?>