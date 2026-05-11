<?php
// cek_status.php
require_once 'config.php';

$kode_klaim = trim($_GET['kode_klaim'] ?? '');
$dataset = null;
$reviews = [];

if ($kode_klaim !== '') {
    // Cari dataset
    $stmt = $pdo->prepare("SELECT * FROM datasets WHERE kode_klaim = ?");
    $stmt->execute([$kode_klaim]);
    $dataset = $stmt->fetch();

    if ($dataset) {
        // Cari status review dari dataset tersebut
        $stmt_rev = $pdo->prepare("SELECT status_review, catatan, waktu_review FROM reviews WHERE id_dataset = ? ORDER BY waktu_review DESC");
        $stmt_rev->execute([$dataset['id']]);
        $reviews = $stmt_rev->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Dataset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 600px;">
    <a href="index.php" class="btn btn-outline-secondary btn-sm mb-3">⬅ Kembali ke Beranda</a>
    
    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white fw-bold text-center">
            Hasil Pencarian Status Dataset
        </div>
        <div class="card-body p-4">
            <?php if ($kode_klaim === ''): ?>
                <div class="alert alert-warning">Harap masukkan Kode Klaim.</div>
            <?php elseif (!$dataset): ?>
                <div class="alert alert-danger text-center">
                    <h5 class="fw-bold mb-1">❌ Tidak Ditemukan</h5>
                    <p class="mb-0">Kode file <strong><?= htmlspecialchars($kode_klaim) ?></strong> tidak ditemukan dalam sistem.</p>
                </div>
            <?php else: ?>
                
                <h4 class="text-center text-primary fw-bold mb-4"><?= htmlspecialchars($dataset['kode_file']) ?></h4>
                
                <table class="table table-borderless">
                    <tr><th width="40%" class="text-muted">Nama Pengunggah</th><td>: <?= htmlspecialchars($dataset['nama']) ?></td></tr>
                    <tr><th class="text-muted">Status Akhir</th>
                        <td>: 
                            <?php if ($dataset['status'] === 'approved'): ?>
                                <span class="badge bg-success">✅ Approved</span>
                            <?php elseif ($dataset['status'] === 'claimed'): ?>
                                <span class="badge bg-secondary">🔒 Baru Diklaim (Belum Submit)</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">⏳ Pending / Direview</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr><th class="text-muted">Kategori</th><td>: <span class="text-capitalize"><?= str_replace('_', ' ', htmlspecialchars($dataset['kategori'])) ?></span></td></tr>
                </table>

                <hr>
                <h6 class="fw-bold mb-3">Riwayat Audit / Review:</h6>
                <?php if (count($reviews) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($reviews as $rev): ?>
                            <li class="list-group-item px-0 border-0 mb-2">
                                <?php if($rev['status_review'] === 'pass'): ?>
                                    <span class="badge bg-success">PASS</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">FAIL</span>
                                <?php endif; ?>
                                <small class="text-muted ms-2"><?= date('d M Y H:i', $rev['created_at']) ?></small>
                                <div class="mt-1 bg-light p-2 rounded small border text-muted">
                                    "<?= htmlspecialchars($rev['komentar'] ?: 'Tidak ada komentar') ?>"
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small">Belum ada proses audit/review yang dilakukan untuk file ini.</p>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>