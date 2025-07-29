<?php
// Set header untuk output JSON
header('Content-Type: application/json');
session_start();

// 1. Termasuk file-file yang diperlukan
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 2. Validasi Keamanan & Akses Awal
// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit();
}

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Anda harus login.']);
    exit();
}

// 3. Ambil dan bersihkan data input dari frontend
$user_id = $_SESSION['user_id'];
$video_id = filter_input(INPUT_POST, 'video_id', FILTER_VALIDATE_INT);
$new_watched_seconds = filter_input(INPUT_POST, 'watched_seconds', FILTER_VALIDATE_INT);

// Validasi data input
if (!$video_id || $new_watched_seconds === false || $new_watched_seconds < 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Data input tidak valid.']);
    exit();
}

try {
    // 4. Mulai transaksi database untuk menjaga integritas data
    $pdo->beginTransaction();

    // 5. Langkah Kunci Anti-Penyalahgunaan:
    // Ambil progress tontonan TERAKHIR yang TERSIMPAN di database untuk user dan video ini.
    $stmt = $pdo->prepare("SELECT watched_seconds FROM watch_stats WHERE user_id = ? AND video_id = ?");
    $stmt->execute([$user_id, $video_id]);
    $last_stat = $stmt->fetch();
    
    // Jika ada data, gunakan, jika tidak, mulai dari 0.
    $db_watched_seconds = $last_stat ? (int)$last_stat['watched_seconds'] : 0;

    // 6. Hitung selisih detik BARU yang akan diberi reward.
    // Ini mencegah pengguna mendapat reward untuk bagian video yang sama berulang kali.
    $seconds_to_reward = $new_watched_seconds - $db_watched_seconds;

    // Hanya proses jika ada detik baru yang ditonton (dan untuk mencegah data aneh/negatif)
    if ($seconds_to_reward > 0) {
        
        // 7. Periksa status monetisasi kreator
        $stmt_creator = $pdo->prepare(
            "SELECT u.id as uploader_id, u.is_monetized 
             FROM videos v 
             JOIN users u ON v.uploader_id = u.id 
             WHERE v.id = ?"
        );
        $stmt_creator->execute([$video_id]);
        $creator = $stmt_creator->fetch();

        // Jika kreator ada dan statusnya dimonetisasi, berikan reward
        if ($creator && (bool)$creator['is_monetized']) {
            $reward_rate = (float)get_setting('reward_rate_per_second', $pdo);
            $earned_amount = $seconds_to_reward * $reward_rate;

            // Update saldo penonton (reward untuk menonton)
            $update_viewer_stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $update_viewer_stmt->execute([$earned_amount, $user_id]);

            // Update saldo kreator (pendapatan dari video)
            $update_creator_stmt = $pdo->prepare("UPDATE users SET creator_earnings = creator_earnings + ? WHERE id = ?");
            $update_creator_stmt->execute([$earned_amount, $creator['uploader_id']]);
        }

        // 8. Selalu simpan/update progres tontonan terbaru ke `watch_stats`.
        // Menggunakan ON DUPLICATE KEY UPDATE lebih efisien daripada SELECT lalu INSERT/UPDATE.
        // Ini akan membuat record baru jika belum ada, atau memperbarui yang sudah ada.
        $stat_stmt = $pdo->prepare(
            "INSERT INTO watch_stats (user_id, video_id, watched_seconds) 
             VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE watched_seconds = ?"
        );
        // Nilai yang diupdate adalah total detik baru, bukan penambahannya.
        $stat_stmt->execute([$user_id, $video_id, $new_watched_seconds, $new_watched_seconds]);
    }

    // 9. Jika semua query berhasil, commit transaksi
    $pdo->commit();
    
    // 10. Kirim respon sukses ke frontend
    echo json_encode([
        'status' => 'success', 
        'message' => 'Progress disimpan.', 
        'rewarded_seconds' => $seconds_to_reward > 0 ? $seconds_to_reward : 0
    ]);

} catch (Exception $e) {
    // Jika terjadi error di salah satu langkah, batalkan semua perubahan
    $pdo->rollBack();
    
    // Log error untuk developer (jangan tampilkan ke user di production)
    error_log("Watch Time Update Error: " . $e->getMessage());
    
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}