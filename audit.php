<?php
// audit.php
require_once 'config.php';

// Ambil data yang statusnya pending_review, dan hitung sudah berapa orang yang mereview
$stmt = $pdo->query("
    SELECT d.id, d.kode_file, d.kategori, d.durasi, COUNT(r.id) as total_review 
    FROM datasets d 
    LEFT JOIN reviews r ON d.id = r.id_dataset 
    WHERE d.status = 'pending_review' 
    GROUP BY d.id
    ORDER BY total_review ASC, d.created_at ASC
");
$datasets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misi Audit - Diarization Dataset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Diarization Dataset</a>
        <span class="navbar-text text-white">Misi Audit Silang Akustik</span>
    </div>
</nav>

<div class="container mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="mb-3">Daftar Antrean Dataset (Spot-Check)</h4>
            <p class="text-muted">Pilih salah satu dataset di bawah ini untuk dilakukan Audit Silang. Anda tidak dapat me-review dataset milik Anda sendiri.</p>
            
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode File</th>
                            <th>Kategori</th>
                            <th>Durasi</th>
                            <th>Total Reviewer</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($datasets) > 0): ?>
                            <?php foreach ($datasets as $row): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($row['kode_file']) ?></td>
                                <td class="text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $row['kategori'])) ?></td>
                                <td><?= number_format($row['durasi'], 2) ?> detik</td>
                                <td>
                                    <span class="badge bg-<?= $row['total_review'] >= 2 ? 'success' : 'warning text-dark' ?> rounded-pill">
                                        <?= $row['total_review'] ?> / 2 Orang
                                    </span>
                                </td>
                                <td>
                                    <a href="audit_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary fw-bold">🔍 Lakukan Review</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-4 text-muted">🎉 Tidak ada dataset yang mengantre untuk di-review saat ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>
</body>
</html>