/**
 * TubeX Reward & Player Handler
 * * File ini mengelola inisialisasi FluidPlayer, melacak waktu tonton,
 * dan mengirimkan progres ke server untuk kalkulasi reward.
 * * @version 1.2 (Pembaruan terakhir dengan error handling)
 */

// Menggunakan IIFE (Immediately Invoked Function Expression) untuk mengenkapsulasi
// semua logika dan hanya mengekspos fungsi `initTubeXPlayer` ke scope global.
// Ini adalah praktik yang baik untuk menjaga kebersihan kode.
const initTubeXPlayer = (() => {
    
    // Deklarasi variabel yang akan digunakan di seluruh modul
    let player;
    let rewardInterval;
    let lastReportedTime = 0;
    let totalValidSeconds = 0;
    let globalData = {}; // Objek untuk menyimpan data dari PHP (video_id, user_id, dll)

    // Referensi ke elemen-elemen DOM untuk overlay status
    const overlay = {
        statusText: document.getElementById('status-text'),
        validSeconds: document.getElementById('valid-seconds'),
        earnings: document.getElementById('earnings'),
        statusIcon: document.getElementById('status-icon')
    };

    /**
     * Memperbarui teks pada overlay video.
     * @param {string} status - Teks status (e.g., 'Menonton', 'Dijeda').
     * @param {string} icon - Emoji untuk status.
     * @param {number} seconds - Total detik valid.
     * @param {number} earnings - Total pendapatan yang dihitung.
     */
    const updateOverlay = (status, icon, seconds, earnings) => {
        if (overlay.statusText) overlay.statusText.textContent = status;
        if (overlay.statusIcon) overlay.statusIcon.textContent = icon;
        if (overlay.validSeconds) overlay.validSeconds.textContent = Math.floor(seconds);
        if (overlay.earnings) overlay.earnings.textContent = (earnings || 0).toFixed(10);
    };

    /**
     * Mengirim progres waktu tonton ke server via API.
     * Dibuat async untuk menggunakan await dan menangani proses secara non-blocking.
     */
    const sendProgressToServer = async () => {
        const secondsToReport = Math.floor(totalValidSeconds);

        // Hanya kirim data jika ada progres baru sejak laporan terakhir
        // untuk mengurangi beban server.
        if (secondsToReport > lastReportedTime) {
            try {
                const formData = new FormData();
                formData.append('video_id', globalData.video_id);
                formData.append('watched_seconds', secondsToReport);

                const response = await fetch('../api/update_watch_time.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.status === 'success') {
                        // Jika server berhasil menyimpan, update waktu terakhir yang dilaporkan.
                        lastReportedTime = secondsToReport;
                        console.log('Server acknowledged progress at: ' + lastReportedTime + 's');
                    }
                }
            } catch (error) {
                console.error("Gagal mengirim progres ke server:", error);
                // Hentikan interval jika ada masalah koneksi untuk mencegah spam error.
                clearInterval(rewardInterval);
                updateOverlay('Koneksi Gagal', '❌', totalValidSeconds, totalValidSeconds * globalData.reward_rate);
            }
        }
    };

    /**
     * Fungsi utama yang diekspos secara global.
     * Fungsi ini akan dipanggil dari halaman watch.php untuk memulai semuanya.
     * @param {object} data - Objek berisi data dari PHP (video_id, youtube_id, user_id, reward_rate).
     */
    return (data) => {
        globalData = data;
        
        // URL sumber video DASH dari layanan proxy.
        const videoUrl = `https://inv-eu3.nadeko.net/api/manifest/dash/id/${globalData.youtube_id}?local=true`;

        // Konfigurasi lengkap untuk FluidPlayer
        const options = {
            layoutControls: {
                primaryColor: "#e50914", // Warna tema merah seperti YouTube
                posterImage: `https://i.ytimg.com/vi/${globalData.youtube_id}/hqdefault.jpg`, // Thumbnail default
                playButtonShowing: true,
                controlBar: {
                    autoHide: true,
                    autoHideTimeout: 3,
                    animated: true
                },
                playerInitCallback: () => {
                    console.log("Fluid Player berhasil diinisialisasi.");
                }
            },
            vastOptions: {
                // Konfigurasi media untuk memutar sumber video DASH
                media: {
                    sources: [
                        {
                            src: videoUrl,
                            type: 'application/dash+xml', // Tipe MIME yang benar untuk DASH
                            title: 'DASH Source'
                        }
                    ]
                }
            }
        };

        // Inisialisasi FluidPlayer pada elemen dengan id 'video-player'
        player = fluidPlayer('video-player', options);

        // Menambahkan Event Listener pada Player
        player.on('play', () => {
            updateOverlay('Menonton', '▶️', totalValidSeconds, totalValidSeconds * globalData.reward_rate);
            
            // Mulai interval untuk menghitung detik dan mengirim progres.
            rewardInterval = setInterval(() => {
                // Pastikan video benar-benar sedang berjalan.
                if (!player.isPaused()) {
                    totalValidSeconds++;
                    const currentEarnings = totalValidSeconds * globalData.reward_rate;
                    updateOverlay('Menonton', '▶️', totalValidSeconds, currentEarnings);

                    // Kirim update ke server secara periodik (setiap 10 detik).
                    if (totalValidSeconds % 10 === 0) {
                        sendProgressToServer();
                    }
                }
            }, 1000); // Interval berjalan setiap 1 detik.
        });

        player.on('pause', () => {
            clearInterval(rewardInterval); // Hentikan penghitungan saat dijeda.
            updateOverlay('Dijeda', '⏸️', totalValidSeconds, totalValidSeconds * globalData.reward_rate);
            sendProgressToServer(); // Kirim progres terakhir saat di-pause.
        });

        player.on('ended', () => {
            clearInterval(rewardInterval); // Hentikan penghitungan saat video selesai.
            updateOverlay('Selesai', '✅', totalValidSeconds, totalValidSeconds * globalData.reward_rate);
            sendProgressToServer(); // Kirim progres final.
        });

        // PENANGANAN ERROR: Ini bagian penting untuk debugging.
        player.on('error', (e) => {
            console.error('Terjadi Error pada Player:', e);
            // Tampilkan pesan error yang ramah kepada pengguna di dalam container video.
            const playerContainer = document.getElementById('video-player');
            if(playerContainer){
                playerContainer.innerHTML = `
                    <div style="display: flex; justify-content: center; align-items: center; width: 100%; height: 100%; background-color: #000; color: white; text-align: center; padding: 20px;">
                        <div>
                            <p style="font-size: 1.2em; margin-bottom: 10px;">Gagal Memuat Video</p>
                            <p style="font-size: 0.9em; color: #ccc;">Ini bisa disebabkan oleh masalah CORS, video tidak tersedia, atau koneksi internet.<br>Silakan coba refresh halaman atau periksa console (F12) untuk detail teknis.</p>
                        </div>
                    </div>
                `;
            }
        });
    };
})();