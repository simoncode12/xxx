<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db_connect.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk berinteraksi.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;
$video_id = (int)($data['video_id'] ?? 0);
$creator_id = (int)($data['creator_id'] ?? 0);

if (!$action) {
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    if ($action === 'like' || $action === 'dislike') {
        if ($video_id <= 0) throw new Exception('Video ID tidak valid.');

        // Cek interaksi sebelumnya
        $stmt = $pdo->prepare("SELECT interaction_type FROM video_interactions WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);
        $previous_interaction = $stmt->fetchColumn();

        // Hapus interaksi lama jika ada
        if ($previous_interaction) {
            $pdo->prepare("DELETE FROM video_interactions WHERE user_id = ? AND video_id = ?")->execute([$user_id, $video_id]);
            // Kurangi count dari interaksi sebelumnya
            $column_to_decrement = $previous_interaction === 'like' ? 'likes' : 'dislikes';
            $pdo->prepare("UPDATE videos SET {$column_to_decrement} = {$column_to_decrement} - 1 WHERE id = ?")->execute([$video_id]);
        }

        // Jika aksi saat ini tidak sama dengan aksi sebelumnya (misal: dari like ke dislike), tambahkan interaksi baru
        if ($previous_interaction !== $action) {
            $pdo->prepare("INSERT INTO video_interactions (user_id, video_id, interaction_type) VALUES (?, ?, ?)")->execute([$user_id, $video_id, $action]);
            // Tambah count untuk interaksi baru
            $column_to_increment = $action === 'like' ? 'likes' : 'dislikes';
            $pdo->prepare("UPDATE videos SET {$column_to_increment} = {$column_to_increment} + 1 WHERE id = ?")->execute([$video_id]);
        }

    } elseif ($action === 'subscribe') {
        if ($creator_id <= 0) throw new Exception('Creator ID tidak valid.');

        // Cek apakah sudah subscribe
        $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE subscriber_id = ? AND creator_id = ?");
        $stmt->execute([$user_id, $creator_id]);
        
        if ($stmt->fetch()) {
            // Jika sudah, unsubscribe
            $pdo->prepare("DELETE FROM subscriptions WHERE subscriber_id = ? AND creator_id = ?")->execute([$user_id, $creator_id]);
        } else {
            // Jika belum, subscribe
            $pdo->prepare("INSERT INTO subscriptions (subscriber_id, creator_id) VALUES (?, ?)")->execute([$user_id, $creator_id]);
        }
    }

    $pdo->commit();

    // Ambil data count terbaru untuk dikirim kembali ke frontend
    $stmt = $pdo->prepare("SELECT likes, dislikes FROM videos WHERE id = ?");
    $stmt->execute([$video_id]);
    $counts = $stmt->fetch();

    echo json_encode(['status' => 'success', 'message' => 'Aksi berhasil.', 'counts' => $counts]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}