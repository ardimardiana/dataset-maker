<?php
// cek_status.php
require_once 'config.php';

$url = urldecode(trim($_GET['youtube_id'] ?? ''));

$youtube_id = NULL;
$dataset = null;

preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?(?:.*&)?v=|shorts\/))([a-zA-Z0-9_-]{11})/i', $url, $matches);

if (!isset($matches[1]) || empty($matches[1])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'URL tidak dikenali. Pastikan Anda memasukkan link YouTube yang valid.'
    ]);
    exit;
}

$youtube_id = $matches[1];

if ($youtube_id !== '') {
    // Cari dataset
    $stmt = $pdo->prepare("SELECT * FROM datasets WHERE youtube_id = ?");
    $stmt->execute([$youtube_id]);
    $dataset = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Informasi Dataset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 600px;">
    <a href="index.php" class="btn btn-outline-secondary btn-sm mb-3">⬅ Kembali ke Beranda</a>
    
    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white fw-bold text-center">
            Hasil Pencarian Informasi Dataset
        </div>
        <div class="card-body p-4">
            <?php if ($youtube_id === ''): ?>
                <div class="alert alert-warning">Harap masukkan Kode Klaim.</div>
            <?php elseif (!$dataset): ?>
                <div class="alert alert-danger text-center">
                    <h5 class="fw-bold mb-1">❌ Tidak Ditemukan</h5>
                    <p class="mb-0">Kode file <strong><?= htmlspecialchars($youtube_id) ?></strong> tidak ditemukan dalam sistem.</p>
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
                            <?php elseif ($dataset['status'] === 'rejected'): ?>
                                <span class="badge bg-danger">❌ Ditolak / Perlu Revisi</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">⏳ Pending / Direview</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr><th class="text-muted">Kategori</th><td>: <span class="text-capitalize"><?= str_replace('_', ' ', htmlspecialchars($dataset['kategori'])) ?></span></td></tr>
                    <tr><th class="text-muted">Kode Klaim</th><td>: <span class="text-capitalize"><?= str_replace('_', ' ', htmlspecialchars($dataset['kode_klaim'])) ?></span></td></tr>
                </table>

            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>