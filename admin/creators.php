<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
check_admin_auth();

// Logika untuk Menyetujui Monetisasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_creator_id'])) {
    $creator_to_approve = (int)$_POST['approve_creator_id'];
    $stmt = $pdo->prepare("UPDATE users SET is_monetized = 1 WHERE id = ? AND role = 'creator'");
    $stmt->execute([$creator_to_approve]);
    $_SESSION['message'] = "Monetisasi untuk kreator berhasil disetujui.";
    header("Location: creators.php");
    exit();
}

// Logika untuk Menghapus Kreator
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_creator_id'])) {
    $creator_to_delete = (int)$_POST['delete_creator_id'];
    // PENTING: Tambahkan logika untuk menangani video milik kreator ini.
    // Opsi 1: Hapus semua video milik kreator (berisiko).
    // Opsi 2 (Lebih aman): Set uploader_id video menjadi NULL atau ke akun admin.
    // Di sini kita pilih opsi paling sederhana: hapus user. Pastikan database Anda di-set ON DELETE CASCADE untuk tabel terkait.
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'creator'");
    $stmt->execute([$creator_to_delete]);
    $_SESSION['message'] = "Kreator berhasil dihapus.";
    header("Location: creators.php");
    exit();
}

// Ambil semua data kreator untuk ditampilkan di tabel
$creators = $pdo->query("SELECT id, username, email, is_monetized, created_at FROM users WHERE role = 'creator' ORDER BY created_at DESC")->fetchAll();

include '../includes/templates/header.php';
?>
<div class="admin-container">
    <h1>Manajemen Kreator</h1>
    <a href="creator_form.php" class="btn-primary">Tambah Kreator Baru</a>

    <?php
    // Tampilkan pesan notifikasi jika ada
    if (isset($_SESSION['message'])) {
        echo '<div class="alert success">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']); // Hapus pesan setelah ditampilkan
    }
    ?>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Status Monetisasi</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($creators) > 0): ?>
                <?php foreach ($creators as $creator): ?>
                <tr>
                    <td><?= htmlspecialchars($creator['username']) ?></td>
                    <td><?= htmlspecialchars($creator['email']) ?></td>
                    <td>
                        <?php if ($creator['is_monetized']): ?>
                            <span class="status-active">Aktif</span>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="approve_creator_id" value="<?= $creator['id'] ?>">
                                <button type="submit" class="btn-approve">Setujui Monetisasi</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td class="action-buttons">
                        <a href="creator_form.php?edit_id=<?= $creator['id'] ?>" class="btn-edit">Edit</a>
                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kreator ini? Semua data terkait (video, subscriber) akan ikut terhapus!');" style="display:inline;">
                            <input type="hidden" name="delete_creator_id" value="<?= $creator['id'] ?>">
                            <button type="submit" class="btn-delete">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center;">Belum ada kreator.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
/* CSS ini bisa dipindah ke file style.css utama jika diinginkan */
.btn-primary {
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 5px;
    display: inline-block;
    margin-bottom: 20px;
}
.admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.admin-table th, .admin-table td { padding: 12px; border: 1px solid var(--border-color); text-align: left; }
.admin-table th { background-color: var(--card-bg); }
.status-active { color: #28a745; font-weight: bold; }
.btn-approve { background-color: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
.action-buttons a, .action-buttons button {
    text-decoration: none;
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    margin-right: 5px;
}
.btn-edit { background-color: #ffc107; }
.btn-delete { background-color: #dc3545; }
</style>

<?php include '../includes/templates/footer.php'; ?>