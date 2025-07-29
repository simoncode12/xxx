<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 1. Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ../auth/login.php?error=login_required');
    exit();
}

$user_id = $_SESSION['user_id'];
$youtube_id = $_GET['v'] ?? null;

if (!$youtube_id) { die("Error: Video tidak ditemukan."); }

// 2. Ambil data video utama
$stmt = $pdo->prepare("SELECT v.*, u.username as uploader_name FROM videos v JOIN users u ON v.uploader_id = u.id WHERE v.youtube_id = ?");
$stmt->execute([$youtube_id]);
$video = $stmt->fetch();
if (!$video) { die("Error: Video ini tidak ada di database kami."); }
$video_id = $video['id'];

// 3. Ambil data interaksi pengguna
$interaction_stmt = $pdo->prepare("SELECT interaction_type FROM video_interactions WHERE user_id = ? AND video_id = ?");
$interaction_stmt->execute([$user_id, $video_id]);
$user_interaction = $interaction_stmt->fetchColumn();
$subscribe_stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE subscriber_id = ? AND creator_id = ?");
$subscribe_stmt->execute([$user_id, $video['uploader_id']]);
$is_subscribed = $subscribe_stmt->fetch() ? true : false;

// 4. Update view count
$pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$video_id]);

// 5. Ambil daftar "Video Berikutnya" (misal: 5 video terbaru selain yang sedang diputar)
$related_stmt = $pdo->prepare("SELECT v.youtube_id, v.title, v.thumbnail_url, u.username as uploader_name FROM videos v JOIN users u ON v.uploader_id = u.id WHERE v.id != ? ORDER BY v.created_at DESC LIMIT 5");
$related_stmt->execute([$video_id]);
$related_videos = $related_stmt->fetchAll();

// 6. Ambil komentar untuk video ini
$comments_stmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.video_id = ? ORDER BY c.created_at DESC");
$comments_stmt->execute([$video_id]);
$comments = $comments_stmt->fetchAll();

$reward_rate = get_setting('reward_rate_per_second', $pdo);
include '../includes/templates/header.php';
?>

<div class="watch-page-grid">
    <div class="watch-main-col">
        <div class="video-player-wrapper">
            <div id="video-player">
                <div id="reward-overlay">
                    <span id="status-icon">📢</span> <span id="status-text">Menunggu...</span> | ⏱ <span id="valid-seconds">0</span>s | 💰 $<span id="earnings">0.000...</span>
                </div>
            </div>
        </div>

        <div class="video-info">
            <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>
            <div class="video-meta">
                <span><?= number_format($video['views']) ?> views</span>
                <div class="video-actions">
                    <button id="like-btn" class="action-btn <?= ($user_interaction === 'like') ? 'active' : '' ?>" data-video-id="<?= $video['id'] ?>">👍 <span id="like-count"><?= $video['likes'] ?></span></button>
                    <button id="dislike-btn" class="action-btn <?= ($user_interaction === 'dislike') ? 'active' : '' ?>" data-video-id="<?= $video['id'] ?>">👎 <span id="dislike-count"><?= $video['dislikes'] ?></span></button>
                    <button id="subscribe-btn" class="subscribe-btn <?= $is_subscribed ? 'subscribed' : '' ?>" data-creator-id="<?= $video['uploader_id'] ?>"><?= $is_subscribed ? 'Subscribed' : 'Subscribe' ?></button>
                </div>
            </div>
            <hr class="separator">
            <div class="uploader-info">
                <strong>Diunggah oleh:</strong> <a href="#"><?= htmlspecialchars($video['uploader_name']) ?></a>
            </div>
            <p class="video-description"><?= nl2br(htmlspecialchars($video['description'])) ?></p>
        </div>
        
        <div class="comments-section">
            <hr class="separator">
            <h3><?= count($comments) ?> Komentar</h3>
            <form id="comment-form" class="comment-form">
                <textarea name="comment_text" placeholder="Tambahkan komentar..." required></textarea>
                <button type="submit">Kirim</button>
            </form>
            <div id="comments-list" class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <strong class="comment-author"><?= htmlspecialchars($comment['username']) ?></strong>
                        <span class="comment-date"><?= date('d M Y', strtotime($comment['created_at'])) ?></span>
                        <p class="comment-body"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="watch-sidebar-col">
        <h3 class="sidebar-title">Video Berikutnya</h3>
        <?php foreach ($related_videos as $related_video): ?>
            <a href="watch.php?v=<?= $related_video['youtube_id'] ?>" class="related-video-card">
                <img src="<?= htmlspecialchars($related_video['thumbnail_url']) ?>" alt="Thumbnail">
                <div class="related-video-info">
                    <h4><?= htmlspecialchars($related_video['title']) ?></h4>
                    <p><?= htmlspecialchars($related_video['uploader_name']) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.fluidplayer.com/v3/current/fluidplayer.min.js"></script>
<script src="../assets/js/reward_handler.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi Player & Reward
    const tubeXData = { video_id: <?= json_encode($video_id) ?>, youtube_id: <?= json_encode($video['youtube_id']) ?>, user_id: <?= json_encode($user_id) ?>, reward_rate: <?= json_encode((float)$reward_rate) ?> };
    initTubeXPlayer(tubeXData);

    // Handler untuk Like, Dislike, Subscribe (kode sama seperti sebelumnya)
    const likeBtn = document.getElementById('like-btn'), dislikeBtn = document.getElementById('dislike-btn'), subscribeBtn = document.getElementById('subscribe-btn');
    const handleInteraction = async (action, videoId, creatorId) => { /* ... kode interaksi Anda ... */ };
    if (likeBtn) { likeBtn.addEventListener('click', () => handleInteraction('like', likeBtn.dataset.videoId, null)); }
    if (dislikeBtn) { dislikeBtn.addEventListener('click', () => handleInteraction('dislike', dislikeBtn.dataset.videoId, null)); }
    if (subscribeBtn) { subscribeBtn.addEventListener('click', () => handleInteraction('subscribe', null, subscribeBtn.dataset.creatorId)); }
    
    // Handler untuk Form Komentar (AJAX)
    const commentForm = document.getElementById('comment-form');
    commentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const textArea = commentForm.querySelector('textarea');
        const commentText = textArea.value.trim();
        
        if (commentText === '') return;

        try {
            const response = await fetch('../api/post_comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: tubeXData.video_id, comment_text: commentText })
            });
            const result = await response.json();
            
            if (result.status === 'success') {
                textArea.value = ''; // Kosongkan textarea
                // Tambahkan komentar baru ke atas daftar tanpa refresh
                const commentsList = document.getElementById('comments-list');
                const newComment = document.createElement('div');
                newComment.className = 'comment-item';
                newComment.innerHTML = `<strong class="comment-author">${result.comment.username}</strong> <span class="comment-date">${result.comment.created_at}</span> <p class="comment-body">${result.comment.comment_text}</p>`;
                commentsList.prepend(newComment);
            } else {
                alert('Gagal mengirim komentar: ' + result.message);
            }
        } catch (error) {
            console.error('Submit comment failed:', error);
            alert('Terjadi kesalahan jaringan.');
        }
    });
});
</script>

<style>
/* Style untuk layout, komentar, dan video terkait. Bisa dipindah ke style.css */
.watch-page-grid {
    display: grid;
    grid-template-columns: 1fr; /* Default 1 kolom untuk mobile */
    gap: 24px;
    max-width: 1600px;
    margin: 20px auto;
    padding: 0 20px;
}
@media (min-width: 1024px) {
    .watch-page-grid { grid-template-columns: minmax(0, 2.5fr) minmax(0, 1fr); }
}
.separator { border: none; height: 1px; background-color: var(--border-color); margin: 20px 0; }

/* Komentar */
.comments-section { margin-top: 24px; }
.comment-form textarea { width: 100%; background: var(--dark-bg); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 5px; padding: 10px; min-height: 60px; }
.comment-form button { background: var(--primary-color); color: white; border: none; padding: 10px 15px; border-radius: 20px; cursor: pointer; float: right; margin-top: 10px; }
.comments-list { margin-top: 20px; clear: both; }
.comment-item { margin-bottom: 20px; }
.comment-author { font-size: 1.1em; }
.comment-date { font-size: 0.8em; color: var(--text-secondary); margin-left: 10px; }
.comment-body { margin-top: 5px; }

/* Sidebar Video Berikutnya */
.sidebar-title { margin-bottom: 15px; }
.related-video-card { display: flex; gap: 15px; margin-bottom: 15px; text-decoration: none; }
.related-video-card img { width: 168px; height: 94px; object-fit: cover; border-radius: 5px; flex-shrink: 0; }
.related-video-info h4 { margin: 0 0 5px 0; color: var(--text-primary); font-size: 1em; }
.related-video-info p { margin: 0; color: var(--text-secondary); font-size: 0.9em; }
</style>

<?php include '../includes/templates/footer.php'; ?>
