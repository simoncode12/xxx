<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Ambil semua video dari database untuk ditampilkan
$stmt = $pdo->query(
    "SELECT v.youtube_id, v.title, v.views, u.username as uploader_name 
     FROM videos v 
     JOIN users u ON v.uploader_id = u.id 
     ORDER BY v.created_at DESC"
);
$videos = $stmt->fetchAll();

include 'includes/templates/header.php';
?>

<div class="main-container">
    <h1 class="page-title">Video Terbaru</h1>
    <div class="video-grid">
        <?php if (count($videos) > 0): ?>
            <?php foreach ($videos as $video): ?>
                <a href="videos/watch.php?v=<?= htmlspecialchars($video['youtube_id']) ?>" class="video-card">
                    <img src="https://i.ytimg.com/vi/<?= htmlspecialchars($video['youtube_id']) ?>/hqdefault.jpg" alt="Thumbnail" class="video-thumbnail">
                    <div class="video-card-info">
                        <h3 class="video-card-title"><?= htmlspecialchars($video['title']) ?></h3>
                        <p class="video-card-uploader"><?= htmlspecialchars($video['uploader_name']) ?></p>
                        <span class="video-card-views"><?= number_format($video['views']) ?> views</span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Belum ada video yang diunggah. Silakan import video melalui panel admin.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/templates/footer.php'; ?>