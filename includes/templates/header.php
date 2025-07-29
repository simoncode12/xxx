<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TubeX - Tonton & Dapatkan Reward</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="main-header">
        <a href="/index.php" class="logo">TubeX</a>
        <nav class="user-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Halo, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="/admin/dashboard.php">Admin Panel</a>
                <?php endif; ?>
                <a href="/auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="/auth/login.php">Login</a>
                <a href="/auth/register.php">Daftar</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>