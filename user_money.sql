-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 29 Jul 2025 pada 08.19
-- Versi server: 11.4.7-MariaDB-deb12
-- Versi PHP: 8.3.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `user_money`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('reward_rate_per_second', '0.0000001');

-- --------------------------------------------------------

--
-- Struktur dari tabel `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','creator','admin') NOT NULL DEFAULT 'user',
  `balance` decimal(20,10) NOT NULL DEFAULT 0.0000000000,
  `creator_earnings` decimal(20,10) NOT NULL DEFAULT 0.0000000000,
  `is_monetized` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `balance`, `creator_earnings`, `is_monetized`, `created_at`) VALUES
(1, 'anonym', 'ari513270@gmail.com', '$2y$10$l8VmEkPUIrri0oQ6caxaaeNZRdXuu0xmOIGanKNcBUyESw4CLUpPi', 'creator', 0.0000000000, 0.0000000000, 0, '2025-07-28 23:43:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `youtube_id` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT 'Durasi dalam detik',
  `uploader_id` int(11) NOT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `likes` int(11) NOT NULL DEFAULT 0,
  `dislikes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `videos`
--

INSERT INTO `videos` (`id`, `youtube_id`, `title`, `description`, `thumbnail_url`, `duration`, `uploader_id`, `views`, `likes`, `dislikes`, `created_at`) VALUES
(1, 'e-eGqDkaCbQ', 'DJ TIKTOK TERBARU 2025 || JIKA TIDAK HARI INI MUNGKIN MINGGU DEPAN || FULL SONG DJ EDITRA TAMBA❗❗❗', 'DJ TIKTOK TERBARU 2025 || JIKA TIDAK HARI INI MUNGKIN MINGGU DEPAN || FULL SONG DJ EDITRA TAMBA❗❗❗\r\n\r\nDISCLAIMER:AKUN INI BUKAN AKUN REMIXER ASLI, KAMI HANYA ME REUPLOAD UNTUK IKUT MERAMAIKAN\r\n\r\n JIKA ADA YANG KEBERATAN DENGAN VIDIO INI  BISA HUBUNGI 🙏KAMI SIAP UNTUK MENGHAPUS NYA, APA BILA ANDA TERTARIK KERJA SAMA DENGAN SANGAT SENANG HATI KAMI BERSEDIA🙏\r\n\r\n╔═╦╗╔╦╗╔═╦═╦╦╦╦╗╔═╗\r\n║╚╣║║║╚╣╚╣╔╣╔╣║╚╣═╣ \r\n╠╗║╚╝║║╠╗║╚╣║║║║║═╣\r\n╚═╩══╩═╩═╩═╩╝╚╩═╩═╝\r\n\r\n\r\n🔸Like👍\r\n🔸Share↗\r\n🔸Coment📣\r\n🔸Aktifkan Loncengnya🔔\r\n\r\nBismillah, Buat Yang Subscribe Semoga Rezekinya Lancar \r\n\r\n#djslowbassfullalbum​ #djslowbassterbaru2025​  #djterbaru2025​ #djamarcm​​ #djterbaru​​ #djcampuran​​ #djjedagjedug​​ #djviral​​ #djtiktok​​ #djfullbass​​ #djfyptiktok​​ #jedagjedug​​  #djmengkane​​ #djslowbass​​ #djtiktokterbaru​​ #djtiktok2024​​ #djterbaru2024​​\r\n\r\nTHANKS FOR WATCHING', 'https://i.ytimg.com/vi/e-eGqDkaCbQ/hqdefault.jpg', 3908, 1, 23, 0, 0, '2025-07-28 23:44:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `video_interactions`
--

CREATE TABLE `video_interactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `interaction_type` enum('like','dislike') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `watch_stats`
--

CREATE TABLE `watch_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `watched_seconds` int(11) NOT NULL DEFAULT 0,
  `last_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `video_id` (`video_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indeks untuk tabel `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscription_unique` (`subscriber_id`,`creator_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `youtube_id` (`youtube_id`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- Indeks untuk tabel `video_interactions`
--
ALTER TABLE `video_interactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_video_interaction` (`user_id`,`video_id`),
  ADD KEY `video_id` (`video_id`);

--
-- Indeks untuk tabel `watch_stats`
--
ALTER TABLE `watch_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_video_unique` (`user_id`,`video_id`),
  ADD KEY `video_id` (`video_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `video_interactions`
--
ALTER TABLE `video_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `watch_stats`
--
ALTER TABLE `watch_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`subscriber_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `video_interactions`
--
ALTER TABLE `video_interactions`
  ADD CONSTRAINT `video_interactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_interactions_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `watch_stats`
--
ALTER TABLE `watch_stats`
  ADD CONSTRAINT `watch_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `watch_stats_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
