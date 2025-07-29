<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ../auth/login.php?error=login_required');
    exit();
}

$user_id = $_SESSION['user_id'];
$youtube_id = $_GET['v'] ?? null;
if (!$youtube_id) die("Error: ID Video tidak valid.");

$stmt = $pdo->prepare(
    "SELECT v.*, u.username as uploader_name FROM videos v JOIN users u ON v.uploader_id = u.id WHERE v.youtube_id = ?"
);
$stmt->execute([$youtube_id]);
$video = $stmt->fetch();
if (!$video) die("Error: Video ini tidak ditemukan di database kami.");
$video_id = $video['id'];

$interaction_stmt = $pdo->prepare("SELECT interaction_type FROM video_interactions WHERE user_id = ? AND video_id = ?");
$interaction_stmt->execute([$user_id, $video_id]);
$user_interaction = $interaction_stmt->fetchColumn();

$subscribe_stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE subscriber_id = ? AND creator_id = ?");
$subscribe_stmt->execute([$user_id, $video['uploader_id']]);
$is_subscribed = $subscribe_stmt->fetch() ? true : false;

$pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$video_id]);

$related_stmt = $pdo->prepare(
    "SELECT v.youtube_id, v.title, v.thumbnail_url, u.username as uploader_name 
     FROM videos v JOIN users u ON v.uploader_id = u.id 
     WHERE v.id != ? ORDER BY v.created_at DESC LIMIT 5"
);
$related_stmt->execute([$video_id]);
$related_videos = $related_stmt->fetchAll();

$comments_stmt = $pdo->prepare(
    "SELECT c.*, u.username 
     FROM comments c JOIN users u ON c.user_id = u.id 
     WHERE c.video_id = ? ORDER BY c.created_at DESC"
);
$comments_stmt->execute([$video_id]);
$comments = $comments_stmt->fetchAll();

$reward_rate = get_setting('reward_rate_per_second', $pdo);

include '../includes/templates/header.php';
?>

<div class="watch-page-grid">
  <div class="watch-main-col">
    <div class="video-player-wrapper">
      <video id="video-player" controls autoplay style="width: 100%; max-width: 100%; aspect-ratio: 16/9;"></video>
      <div id="reward-overlay">
        <span id="status-icon">📢</span> <span id="status-text">Menunggu...</span> |
        ⏱ <span id="valid-seconds">0</span>s |
        💰 $<span id="earnings">0.0000000</span>
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

<script src="https://cdn.dashjs.org/latest/dash.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tubeXData = {
    video_id: <?= json_encode($video_id) ?>,
    youtube_id: <?= json_encode($video['youtube_id']) ?>,
    user_id: <?= json_encode($user_id) ?>,
    reward_rate: <?= json_encode((float)$reward_rate) ?>
  };

  const url = `https://inv-eu3.nadeko.net/api/manifest/dash/id/${tubeXData.youtube_id}?local=true`;
  const player = dashjs.MediaPlayer().create();
  player.initialize(document.querySelector("#video-player"), url, false);

  let validSeconds = 0;
  let earning = 0;
  const rate = tubeXData.reward_rate;

  setInterval(() => {
    const video = document.getElementById('video-player');
    if (!video.paused && video.readyState > 2 && !video.seeking) {
      validSeconds++;
      earning += rate;
      document.getElementById("valid-seconds").textContent = validSeconds;
      document.getElementById("earnings").textContent = earning.toFixed(7);
      document.getElementById("status-text").textContent = "▶️ Menonton";
    } else {
      document.getElementById("status-text").textContent = "⏸️ Pause / Buffering";
    }
  }, 1000);
});
</script>

<?php include '../includes/templates/footer.php'; ?>
