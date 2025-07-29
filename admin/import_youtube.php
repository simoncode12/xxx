<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
check_admin_auth();

// Definisikan API Key Anda di sini atau di file konfigurasi terpisah
define('YOUTUBE_API_KEY', 'AIzaSyDLulaJ1eKOY2Ly-MdpkxG2smQF2KbkB1E');

$error = '';
$video_data = null;

// Langkah 1: Ambil data dari YouTube saat URL di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_video'])) {
    $youtube_url = trim($_POST['youtube_url']);
    
    // Ekstrak Video ID dari berbagai format URL YouTube
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $youtube_url, $match);
    $youtube_id = $match[1] ?? null;

    if ($youtube_id) {
        $api_url = "https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails&id={$youtube_id}&key=" . YOUTUBE_API_KEY;
        
        $response = @file_get_contents($api_url);
        $data = json_decode($response, true);

        if ($data && !empty($data['items'])) {
            $item = $data['items'][0];
            
            // Konversi durasi ISO 8601 (misal: PT5M3S) ke detik
            $duration_iso = $item['contentDetails']['duration'];
            $interval = new DateInterval($duration_iso);
            $duration_seconds = $interval->h * 3600 + $interval->i * 60 + $interval->s;

            $video_data = [
                'youtube_id'    => $youtube_id,
                'title'         => $item['snippet']['title'],
                'description'   => $item['snippet']['description'],
                'thumbnail_url' => $item['snippet']['thumbnails']['high']['url'], // Ambil thumbnail kualitas tinggi
                'duration'      => $duration_seconds
            ];
        } else {
            $error = "Gagal mengambil data video. Pastikan URL valid dan API Key benar.";
        }
    } else {
        $error = "URL YouTube tidak valid.";
    }
}

// Langkah 2: Simpan data ke database setelah dikonfirmasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_video'])) {
    // Ambil data dari form konfirmasi
    $youtube_id = $_POST['youtube_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $thumbnail_url = $_POST['thumbnail_url'];
    $duration = (int)$_POST['duration'];
    $uploader_id = (int)$_POST['uploader_id'];

    if (!empty($youtube_id) && !empty($title) && !empty($uploader_id)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO videos (youtube_id, title, description, thumbnail_url, duration, uploader_id) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$youtube_id, $title, $description, $thumbnail_url, $duration, $uploader_id]);
            $message = "Video '".htmlspecialchars($title)."' berhasil diimpor!";
        } catch (PDOException $e) {
            $error = ($e->errorInfo[1] == 1062) ? "Gagal: Video ini sudah ada di database." : "Error: " . $e->getMessage();
        }
    } else {
        $error = "Judul dan Kreator wajib diisi.";
    }
}

// Ambil daftar kreator untuk dropdown
$creators = $pdo->query("SELECT id, username FROM users WHERE role = 'creator'")->fetchAll();

include '../includes/templates/header.php';
?>
<div class="admin-container">
    <h1>Import Video dari YouTube (Otomatis)</h1>
    <?php if (isset($message)): ?><div class="alert success"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>

    <form method="POST" class="import-form">
        <label for="youtube_url">Masukkan URL Video YouTube</label>
        <input type="text" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=xxxxxxxxxxx" required>
        <button type="submit" name="fetch_video">Ambil Data Video</button>
    </form>
    
    <hr>

    <?php if ($video_data): ?>
    <h2>Konfirmasi Data Video</h2>
    <form method="POST" class="import-form">
        <input type="hidden" name="youtube_id" value="<?= htmlspecialchars($video_data['youtube_id']) ?>">
        <input type="hidden" name="thumbnail_url" value="<?= htmlspecialchars($video_data['thumbnail_url']) ?>">
        <input type="hidden" name="duration" value="<?= $video_data['duration'] ?>">
        
        <img src="<?= htmlspecialchars($video_data['thumbnail_url']) ?>" alt="Thumbnail" style="max-width: 320px; border-radius: 8px; margin-bottom: 15px;">
        
        <label for="title">Judul Video</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($video_data['title']) ?>" required>
        
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="6"><?= htmlspecialchars($video_data['description']) ?></textarea>
        
        <p><strong>Durasi:</strong> <?= floor($video_data['duration'] / 60) . gmdate(":i:s", $video_data['duration'] % 60) ?></p>

        <label for="uploader_id">Pilih Kreator (Uploader)</label>
        <select id="uploader_id" name="uploader_id" required>
            <option value="">-- Pilih Kreator --</option>
            <?php foreach ($creators as $creator): ?>
                <option value="<?= $creator['id'] ?>"><?= htmlspecialchars($creator['username']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="save_video">Simpan Video ke Database</button>
    </form>
    <?php endif; ?>
</div>
<?php include '../includes/templates/footer.php'; ?>