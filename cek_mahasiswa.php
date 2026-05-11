<?php
// cek_mahasiswa.php
require_once 'config.php';

// Ambil parameter NPM (bisa dari name="npm" atau name="youtube_id" jika form belum diubah)
$npm = $_GET['npm'] ?? $_GET['youtube_id'] ?? '';
$npm = trim($npm);

if (empty($npm)) {
    echo "<script>alert('NPM tidak boleh kosong!'); window.location.href='index.php';</script>";
    exit;
}

try {
    // 1. Ambil Data Datasets yang disubmit oleh mahasiswa ini
    $stmt_ds = $pdo->prepare("SELECT * FROM datasets WHERE npm = ? ORDER BY id DESC");
    $stmt_ds->execute([$npm]);
    $datasets = $stmt_ds->fetchAll();

    $total_durasi_dataset = 0;
    $nama_mahasiswa = "";

    foreach ($datasets as $ds) {
        if (empty($nama_mahasiswa) && !empty($ds['nama'])) {
            $nama_mahasiswa = $ds['nama'];
        }
        // Hitung durasi (hanya yang tidak di-reject)
        if ($ds['status'] !== 'rejected') {
            $total_durasi_dataset += (float) $ds['durasi'];
        }
    }

    // 2. Ambil Data Reviews yang dilakukan oleh mahasiswa ini
    // Melakukan JOIN ringan ke datasets untuk mendapatkan kode_file
    $stmt_rev = $pdo->prepare("
        SELECT r.*, d.kode_file 
        FROM reviews r 
        LEFT JOIN datasets d ON r.id_dataset = d.id 
        WHERE r.npm = ? 
        ORDER BY r.id DESC
    ");
    $stmt_rev->execute([$npm]);
    $reviews = $stmt_rev->fetchAll();

    $total_durasi_review = 0;
    foreach ($reviews as $rev) {
        if (empty($nama_mahasiswa) && !empty($rev['nama'])) {
            $nama_mahasiswa = $rev['nama'];
        }
        // Menggunakan kolom durasi_dataset yang sudah didenormalisasi sebelumnya
        $total_durasi_review += (float) $rev['durasi_dataset'];
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Mahasiswa - Dataset Maker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
    </style>
</head>
<body>

<div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Progress Mahasiswa</h2>
            <p class="text-muted">NPM: <strong><?= htmlspecialchars($npm) ?></strong> <?= $nama_mahasiswa ? " | Nama: <strong>".htmlspecialchars($nama_mahasiswa)."</strong>" : "" ?></p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm border-start border-5 border-primary h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1">Total Submisi Dataset</h6>
                    <h3 class="fw-bold mb-0"><?= count($datasets) ?> File</h3>
                    <div class="mt-2 text-primary fw-bold">
                        <i class="bi bi-clock-history"></i> Total Durasi Valid: <?= number_format(($total_durasi_dataset/60), 2) ?> Menit
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm border-start border-5 border-success h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1">Total Review Dilakukan</h6>
                    <h3 class="fw-bold mb-0"><?= count($reviews) ?> File</h3>
                    <div class="mt-2 text-success fw-bold">
                        <i class="bi bi-check-circle"></i> Durasi File Direview: <?= number_format(($total_durasi_review/60), 2) ?> Menit
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold py-3">
            <i class="bi bi-cloud-upload text-primary me-2"></i> Riwayat Submisi Dataset
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">No</th>
                            <th>Kode File</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Catatan Admin/Sistem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($datasets) > 0): ?>
                            <?php $no=1; foreach($datasets as $ds): ?>
                            <tr>
                                <td class="ps-3"><?= $no++ ?></td>
                                <td><span class="badge bg-light" title="<?= htmlspecialchars($ds['kode_file'] ?? 'N/A') ?>">🎵</span></td>
                                <td><?= htmlspecialchars($ds['durasi'] ?? 0) ?> detik</td>
                                <td>
                                    <?php 
                                        if($ds['status'] == 'approved') echo '<span class="badge bg-success">Approved</span>';
                                        elseif($ds['status'] == 'rejected') echo '<span class="badge bg-danger">Rejected</span>';
                                        else echo '<span class="badge bg-warning text-dark">Pending</span>';
                                    ?>
                                </td>
                                <td class="text-danger small">
                                    <?= htmlspecialchars($ds['catatan_reject'] ?? '-') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data submisi dataset.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold py-3">
            <i class="bi bi-search text-success me-2"></i> Riwayat Audit / Review
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">No</th>
                            <th>Kode File Dataset</th>
                            <th>Durasi Dataset</th>
                            <th>Rekomendasi Anda</th>
                            <th>Catatan Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($reviews) > 0): ?>
                            <?php $no=1; foreach($reviews as $rev): ?>
                            <tr>
                                <td class="ps-3"><?= $no++ ?></td>
                                <td><span class="badge bg-light" title="<?= htmlspecialchars($rev['kode_file'] ?? 'Dataset Dihapus') ?>">🎶</span></td>
                                <td><?= htmlspecialchars($rev['durasi_dataset'] ?? 0) ?> detik</td>
                                <td>
                                    <?php if($rev['status_review'] == 'pass'): ?>
                                        <span class="badge bg-primary"><i class="bi bi-check"></i> 👌</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><i class="bi bi-x"></i> 🤌</span>
                                    <?php endif; ?>
                                </td>
                                <td><small title="<?= nl2br(htmlspecialchars($rev['catatan'] ?? '-')) ?>">♾</small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat review.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>