<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
check_admin_auth(); // Fungsi untuk cek apakah user adalah admin

// Handle update reward rate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reward_rate'])) {
    $new_rate = filter_var($_POST['reward_rate'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    update_setting('reward_rate_per_second', $new_rate, $pdo);
    $message = "Rate reward berhasil diperbarui!";
}

// Ambil statistik
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_videos = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$total_watch_hours = $pdo->query("SELECT SUM(watched_seconds) FROM watch_stats")->fetchColumn() / 3600;
$current_rate = get_setting('reward_rate_per_second', $pdo);

include '../includes/templates/header.php';
?>
<div class="admin-container">
    <h1>Admin Dashboard</h1>
    <?php if (isset($message)): ?>
        <div class="alert success"><?= $message ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <h2>Total Pengguna</h2>
            <p><?= $total_users ?></p>
        </div>
        <div class="stat-card">
            <h2>Total Video</h2>
            <p><?= $total_videos ?></p>
        </div>
        <div class="stat-card">
            <h2>Total Jam Tayang</h2>
            <p><?= number_format($total_watch_hours, 2) ?> jam</p>
        </div>
    </div>

    <div class="admin-section">
        <h2>Pengaturan Reward</h2>
        <form method="POST">
            <label for="reward_rate">Rate Reward per Detik ($)</label>
            <input type="text" id="reward_rate" name="reward_rate" value="<?= htmlspecialchars($current_rate) ?>">
            <button type="submit">Simpan Pengaturan</button>
        </form>
    </div>
     <div class="admin-section">
        <h2>Manajemen Video</h2>
        <a href="import_youtube.php" class="button">Import Video Baru</a>
    </div>
</div>
<?php include '../includes/templates/footer.php'; ?>