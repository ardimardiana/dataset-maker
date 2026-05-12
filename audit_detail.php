<?php
// audit_detail.php
require_once 'config.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT id, url, kode_file, file_wav_path, file_rttm_path FROM datasets WHERE id = ? AND status = 'pending_review'"); 
$stmt->execute([$id]);
$dataset = $stmt->fetch();

if (!$dataset) {
    die("<div style='padding:20px; font-family:sans-serif;'>Dataset tidak ditemukan atau sudah selesai diaudit. <a href='audit.php'>Kembali</a></div>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review: <?= $dataset['kode_file'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    #waveform-container { position: relative; border: 1px solid #ccc; background: #fafafa; border-radius: 4px; }
    #timeline { margin-bottom: 20px; }
    .viz-container { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; overflow-x: auto; }
    
    /* Layout Track Baru */
    .channel-track { position: relative; height: 35px; border-bottom: 1px dashed #ccc; margin-bottom: 8px; width: 100%; }
    .rttm-segment { position: absolute; height: 100%; top: 0; opacity: 0.8; }
    .track-header { font-size: 16px; font-weight: bold; margin-bottom: 10px; color: #2c3e50; border-bottom: 2px solid #0d6efd; display: inline-block; }
    
    /* Garis Playhead (Cursor Berjalan) pada RTTM */
    #rttm-playhead { 
        position: absolute; 
        top: 0; 
        bottom: 0; 
        width: 2px; 
        background-color: #dc3545; /* Warna Merah */
        z-index: 10; 
        left: 0%;
        pointer-events: none; /* Agar tidak menghalangi klik pada balok RTTM */
        box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
    }
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="audit.php">⬅️ Kembali ke Daftar</a>
        <span class="navbar-text text-white">Reviewing: <strong><?= $dataset['kode_file'] ?></strong></span>
    </div>
</nav>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Panel Kiri: Player Read-Only -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">URL Video</label>
                        <div class="form-text"><a href="<?=$dataset['url']?>" target="_blank">Lihat</a></div>
                    </div>
                    
                    <div class="track-header">Visualisasi Speaker (RTTM)</div>
                    <div class="viz-container mb-4" id="track-original">
                        <div class="text-center text-muted" id="loading-text">Memuat data...</div>
                    </div>

                    <div class="track-header">Kontrol Audio (Spasi = Play/Pause)</div>
                    <div id="waveform-container"></div>
                    <div id="timeline"></div>
                </div>
            </div>
        </div>

        <!-- Panel Kanan: Form Keputusan -->
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white fw-bold">Form Rekomendasi Audit</div>
                <div class="card-body">
                    <form id="formReview">
                        <input type="hidden" name="id_dataset" value="<?= $dataset['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">NPM Anda (Auditor)</label>
                            <input type="text" class="form-control" name="npm_reviewer" required>
                            <div class="form-text">Jika sistem mendeteksi ini adalah dataset Anda sendiri, review akan ditolak.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Anda</label>
                            <input type="text" class="form-control" name="nama_reviewer" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Rekomendasi Keputusan</label>
                            <div class="form-check text-success fw-bold mb-2">
                                <input class="form-check-input" type="radio" name="status_review" id="radioPass" value="pass" required>
                                <label class="form-check-label" for="radioPass">✅ Lolos (Akurat & Rapi)</label>
                            </div>
                            <div class="form-check text-danger fw-bold">
                                <input class="form-check-input" type="radio" name="status_review" id="radioFail" value="fail">
                                <label class="form-check-label" for="radioFail">❌ Gagal (Terdapat Kesalahan)</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Catatan Temuan</label>
                            <textarea class="form-control" name="catatan" rows="3" placeholder="Contoh: Menit 0:45 ada suara overlap yang tidak dipotong..."></textarea>
                            <div class="form-text">Wajib diisi jika rekomendasi "Gagal".</div>
                        </div>

                        <button type="submit" id="btnSubmit" class="btn btn-primary w-100 fw-bold">Kirim Rekomendasi Review</button>
                    </form>
                    <div id="alertArea" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module">
    import WaveSurfer from 'https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.esm.js';
    import Timeline from 'https://unpkg.com/wavesurfer.js@7/dist/plugins/timeline.esm.js';
    import Hover from 'https://unpkg.com/wavesurfer.js@7/dist/plugins/hover.esm.js';

    const audioUrl = '<?= $dataset['file_wav_path'] ?>';
    const rttmUrl = '<?= $dataset['file_rttm_path'] ?>';
    
    // Konfigurasi Warna
    const colors = ['#00a8e8', '#e83131', '#28a745', '#ffc107', '#9c27b0', '#ff5722'];
    function getSpeakerColor(speakerName) {
        let hash = 0;
        for (let i = 0; i < speakerName.length; i++) { hash = speakerName.charCodeAt(i) + ((hash << 5) - hash); }
        return colors[Math.abs(hash) % colors.length];
    }

    // Inisialisasi WaveSurfer
    const wavesurfer = WaveSurfer.create({
        container: '#waveform-container',
        waveColor: '#a1c4fd',
        progressColor: '#00c8b6',
        cursorColor: '#333',
        minPxPerSec: 50,
        url: audioUrl,
        plugins: [
            Timeline.create({ container: '#timeline' }),
            Hover.create({ lineColor: '#ff0000', lineWidth: 2, labelBackground: '#2c3e50', labelColor: '#fff', formatTimeCallback: (sec) => sec.toFixed(3) + ' s' })
        ]
    });

    let audioDuration = 0;

    wavesurfer.on('ready', () => {
        audioDuration = wavesurfer.getDuration();
        
        fetch(rttmUrl)
            .then(response => response.text())
            .then(text => {
                const data = parseRTTM(text);
                renderMultiChannelTrack('track-original', data);
    
                // --- KODE SINKRONISASI SCROLL ---
                const wsWrapper = wavesurfer.getWrapper();
                const rttmContainer = document.getElementById('track-original');
                
                // Sinkronisasi dari WaveSurfer ke RTTM
                wsWrapper.addEventListener('scroll', () => {
                    rttmContainer.scrollLeft = wsWrapper.scrollLeft;
                });
    
                // Sinkronisasi dari RTTM ke WaveSurfer
                rttmContainer.addEventListener('scroll', () => {
                    wsWrapper.scrollLeft = rttmContainer.scrollLeft;
                });
            });
    });

    // SINKRONISASI: Perbarui garis RTTM setiap kali audio dimainkan
    wavesurfer.on('timeupdate', (currentTime) => {
        const playhead = document.getElementById('rttm-playhead');
        if (playhead && audioDuration > 0) {
            const pct = (currentTime / audioDuration) * 100;
            playhead.style.left = `${pct}%`;
        }
    });

    function parseRTTM(text) {
        const lines = text.split('\n');
        const data = [];
        lines.forEach(line => {
            const parts = line.trim().split(/\s+/);
            if (parts[0] === 'SPEAKER' && parts.length >= 8) {
                const start = parseFloat(parts[3]);
                const duration = parseFloat(parts[4]);
                data.push({ start: start, end: start + duration, speaker: parts[7] });
            }
        });
        return data;
    }

    function renderMultiChannelTrack(containerId, data) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        if (data.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">Data RTTM Kosong</div>';
            return;
        }

        const uniqueSpeakers = [...new Set(data.map(d => d.speaker))].sort();

        // Flex wrapper untuk memisahkan kolom label dan kolom track
        const wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'stretch';

        // Kolom Label (Kiri)
        const labelsCol = document.createElement('div');
        labelsCol.style.width = '120px';
        labelsCol.style.paddingRight = '15px';
        labelsCol.style.flexShrink = '0';
        // TAMBAHKAN 4 BARIS INI AGAR LABEL STICKY:
        labelsCol.style.position = 'sticky';
        labelsCol.style.left = '0';
        labelsCol.style.backgroundColor = '#fff';
        labelsCol.style.zIndex = '5';
        
        // Kolom Track (Kanan)
        const tracksCol = document.createElement('div');
        // HAPUS ATAU GANTI baris tracksCol.style.flex = '1'; MENJADI:
        const wsContainer = document.getElementById('waveform-container');
        const minPx = 50; // Harus sama dengan nilai minPxPerSec di WaveSurfer
        const trackWidth = Math.max(wsContainer.clientWidth, audioDuration * minPx);
        
        tracksCol.style.width = trackWidth + 'px';
        tracksCol.style.minWidth = trackWidth + 'px';
        tracksCol.style.position = 'relative'; 
        tracksCol.style.cursor = 'pointer';

        uniqueSpeakers.forEach(speaker => {
            // Render Label
            const labelDiv = document.createElement('div');
            labelDiv.style.fontWeight = 'bold';
            labelDiv.style.fontSize = '13px';
            labelDiv.style.textAlign = 'right';
            labelDiv.style.color = getSpeakerColor(speaker);
            labelDiv.style.height = '35px';
            labelDiv.style.lineHeight = '35px';
            labelDiv.style.marginBottom = '8px';
            labelDiv.innerText = speaker;
            labelsCol.appendChild(labelDiv);

            // Render Track Box
            const trackDiv = document.createElement('div');
            trackDiv.className = 'channel-track';

            // Loop untuk setiap segmen dari speaker tersebut
            data.filter(d => d.speaker === speaker).forEach(seg => {
                const startPct = (seg.start / audioDuration) * 100;
                const widthPct = ((seg.end - seg.start) / audioDuration) * 100;
            
                const segDiv = document.createElement('div');
                segDiv.className = 'rttm-segment';
                segDiv.style.left = `${startPct}%`;
                segDiv.style.width = `${widthPct}%`;
                segDiv.style.backgroundColor = getSpeakerColor(speaker);
                
                // Informasi timestamp saat di-hover (diperjelas)
                segDiv.title = `Speaker: ${speaker} | Mulai: ${seg.start.toFixed(3)}s | Selesai: ${seg.end.toFixed(3)}s`;
                
                // Event click khusus segmen: meloncat ke awal waktu segmen
                segDiv.addEventListener('click', (e) => {
                    e.stopPropagation(); // Mencegah klik diteruskan ke kontainer tracksCol
                    wavesurfer.setTime(seg.start); // Set pemutar tepat ke waktu awal segmen
                    wavesurfer.play();
                });
                
                trackDiv.appendChild(segDiv);
            });
            tracksCol.appendChild(trackDiv);
        });

        // Buat Garis Playhead
        const playhead = document.createElement('div');
        playhead.id = 'rttm-playhead';
        tracksCol.appendChild(playhead);

        // EVENT LISTENER: Click-to-Seek pada area track RTTM
        tracksCol.addEventListener('click', (e) => {
            const rect = tracksCol.getBoundingClientRect();
            // Menghitung posisi klik relatif terhadap lebar kolom track
            const clickX = e.clientX - rect.left; 
            const percentage = clickX / rect.width;
            
            // Mengubah waktu pada WaveSurfer
            const targetTime = percentage * audioDuration;
            wavesurfer.setTime(targetTime);
            wavesurfer.play(); // Opsional: langsung diputar setelah diklik
        });

        wrapper.appendChild(labelsCol);
        wrapper.appendChild(tracksCol);
        container.appendChild(wrapper);
    }

    // Play/Pause via Spasi
    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        if (e.code === 'Space') { e.preventDefault(); wavesurfer.playPause(); }
    });

    // Handle Submit Review (Kode Anda tetap di sini)
    document.getElementById('formReview').addEventListener('submit', function(e) {
        e.preventDefault();
        const alertArea = document.getElementById('alertArea');
        const btn = document.getElementById('btnSubmit');
        
        btn.disabled = true;
        btn.innerHTML = 'Memproses...';

        fetch('proses_audit.php', { method: 'POST', body: new FormData(this) })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alertArea.innerHTML = `<div class="alert alert-success border-0 shadow-sm fw-bold">✅ Review berhasil dikirim. Membawa Anda kembali...</div>`;
                setTimeout(() => window.location.href = 'audit.php', 2000);
            } else {
                alertArea.innerHTML = `<div class="alert alert-danger border-0 shadow-sm">❌ ${data.message}</div>`;
                btn.disabled = false;
                btn.innerHTML = 'Kirim Rekomendasi Review';
            }
        })
        .catch(err => {
            alertArea.innerHTML = `<div class="alert alert-danger border-0 shadow-sm">❌ Terjadi kesalahan jaringan.</div>`;
            btn.disabled = false;
            btn.innerHTML = 'Kirim Rekomendasi Review';
        });
    });
</script>
</body>
</html>