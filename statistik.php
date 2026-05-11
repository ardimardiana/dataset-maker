<?php
// statistik.php

$json_file = 'stats_cache.json';
$stats = null;

// Cek apakah file JSON sudah ada
if (file_exists($json_file)) {
    $json_data = file_get_contents($json_file);
    $stats = json_decode($json_data, true);
}

// Fungsi bantuan untuk memformat durasi (detik ke Jam/Menit/Detik)
function formatDurasi($detik) {
    $hours = floor($detik / 3600);
    $mins = floor(($detik / 60) % 60);
    $secs = $detik % 60;
    
    $res = '';
    if ($hours > 0) $res .= "$hours Jam ";
    if ($mins > 0) $res .= "$mins Menit ";
    $res .= round($secs, 1) . " Detik";
    
    return trim($res);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Dataset - Diarization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Diarization Dataset</a>
        <span class="navbar-text text-white">Statistik Koleksi (Approved)</span>
    </div>
</nav>

<div class="container mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">📊 Dashboard Statistik</h3>
            <small class="text-muted">
                Pembaruan terakhir: <span id="lastUpdatedText" class="fw-bold text-primary"><?= $stats ? date('d M Y, H:i:s', strtotime($stats['last_updated'])) : 'Belum pernah dikalkulasi' ?></span>
            </small>
        </div>
        <div>
            <button id="btnUpdate" class="btn btn-primary fw-bold shadow-sm">
                🔄 Kalkulasi Ulang dari Database
            </button>
        </div>
    </div>

    <!-- Alert Area untuk Hasil Update -->
    <div id="alertArea"></div>

    <?php if (!$stats): ?>
        <div class="alert alert-warning text-center border-0 shadow-sm p-5">
            <h4 class="fw-bold">Data Statistik Belum Tersedia</h4>
            <p>Silakan klik tombol "Kalkulasi Ulang" di atas untuk men-generate data pertama kali dari database.</p>
        </div>
    <?php else: ?>
        
        <!-- Kartu Total Durasi -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-success text-white border-0 shadow text-center py-4 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold text-uppercase mb-2 opacity-75">Total Durasi Dataset Tersimpan</h5>
                        <h1 class="display-4 fw-bold mb-0">
                            <?= formatDurasi($stats['total_keseluruhan_durasi']) ?>
                        </h1>
                        <p class="mt-2 mb-0 opacity-75">(<?= number_format($stats['total_keseluruhan_durasi'], 2) ?> detik)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Detail per Kategori -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-center mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="py-3">Kategori Video</th>
                                <th class="py-3">Jumlah Entri</th>
                                <th class="py-3">Durasi Rata-rata</th>
                                <th class="py-3">Pembicara (Min / Max)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $kategori_labels = [
                                'podcast' => '🎙️ Podcast',
                                'talkshow' => '🛋️ Talkshow',
                                'entertainment' => '🎭 Entertainment',
                                'movie_drama' => '🎬 Movie & Drama'
                            ];
                            
                            foreach ($stats['kategori'] as $kat_key => $val): 
                            ?>
                            <tr>
                                <td class="fw-bold text-start ps-4 fs-5"><?= $kategori_labels[$kat_key] ?></td>
                                <td>
                                    <span class="badge bg-primary rounded-pill fs-6 px-3 py-2"><?= $val['jumlah'] ?> File</span>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= number_format($val['avg'], 2) ?> detik</div>
                                    <small class="text-muted"><?= formatDurasi($val['avg']) ?></small>
                                </td>
                                <td>
                                    <?php if ($val['jumlah'] > 0): ?>
                                        <span class="fw-bold"><?= $val['min_spk'] ?></span> s/d <span class="fw-bold"><?= $val['max_spk'] ?></span> Orang
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<script>
document.getElementById('btnUpdate').addEventListener('click', function() {
    const btn = this;
    const alertArea = document.getElementById('alertArea');
    
    // Konfirmasi sebelum memberatkan database
    if (!confirm("Proses ini akan mengkalkulasi ulang seluruh dataset yang 'Approved' dari database. Lanjutkan?")) {
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menghitung...';
    
    fetch('proses_statistik.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alertArea.innerHTML = `
                <div class="alert alert-success border-0 shadow-sm fw-bold text-center">
                    ✅ Statistik berhasil diperbarui! Memuat ulang halaman...
                </div>
            `;
            // Reload halaman untuk menampilkan data JSON yang baru
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alertArea.innerHTML = `<div class="alert alert-danger border-0 shadow-sm">❌ Gagal: ${data.message}</div>`;
            btn.disabled = false;
            btn.innerHTML = '🔄 Kalkulasi Ulang dari Database';
        }
    })
    .catch(error => {
        alertArea.innerHTML = `<div class="alert alert-danger border-0 shadow-sm">❌ Terjadi kesalahan jaringan.</div>`;
        btn.disabled = false;
        btn.innerHTML = '🔄 Kalkulasi Ulang dari Database';
        console.error(error);
    });
});
</script>
</body>
</html>