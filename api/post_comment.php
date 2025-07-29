<?php
// api/post_comment.php
session_start();
header('Content-Type: application/json');

require_once '../includes/db_connect.php';

// Validasi dasar
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$video_id = (int)($data['video_id'] ?? 0);
$comment_text = trim($data['comment_text'] ?? '');

if ($video_id <= 0 || empty($comment_text)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (video_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->execute([$video_id, $user_id, $comment_text]);
    $comment_id = $pdo->lastInsertId();

    // Kirim kembali data komentar baru untuk ditampilkan di frontend
    echo json_encode([
        'status' => 'success',
        'comment' => [
            'id' => $comment_id,
            'username' => $_SESSION['username'], // Ambil username dari session
            'comment_text' => htmlspecialchars($comment_text),
            'created_at' => date('d M Y H:i')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan komentar.']);
}