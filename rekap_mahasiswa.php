<?php
// rekap_mahasiswa.php
require_once 'config.php';

// Inisialisasi variabel
$npms_input = $_POST['npms'] ?? '';
$results = [];
$pesanError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($npms_input))) {
    // 1. Parsing Input Textarea
    // Pisahkan string berdasarkan baris baru (\n atau \r\n) menjadi array
    $npm_array = preg_split('/\r\n|\r|\n/', trim($npms_input));
    $npm_list = [];
    
    foreach($npm_array as $n) {
        $n = trim($n);
        if (!empty($n)) {
            $npm_list[] = $n;
        }
    }
    
    // Hapus duplikasi jika ada NPM yang tertulis lebih dari satu kali
    $npm_list = array_unique($npm_list);

    if (count($npm_list) > 0) {
        // Buat placeholder ?, ?, ? untuk disisipkan ke dalam query IN() PDO
        $inQuery = implode(',', array_fill(0, count($npm_list), '?'));
        
        // 2. Query Rekap Submit (Menghitung dari tabel datasets)
        $querySubmit = "SELECT npm, MAX(nama) as nama, COUNT(id) as total_submit 
                        FROM datasets 
                        WHERE npm IN ($inQuery) AND kode_file IS NOT NULL 
                        GROUP BY npm";
        $stmtSubmit = $pdo->prepare($querySubmit);
        $stmtSubmit->execute($npm_list);
        $submits = $stmtSubmit->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Query Rekap Audit
        // CATATAN: Silakan sesuaikan nama tabel 'audit' dan kolom 'npm' 
        // dengan skema tabel audit/cross_audit yang Anda gunakan.
        $queryAudit = "SELECT npm AS auditor_npm, COUNT(id) as total_audit 
                       FROM audit 
                       WHERE npm IN ($inQuery) AND status = 'completed'
                       GROUP BY npm";
        try {
            $stmtAudit = $pdo->prepare($queryAudit);
            $stmtAudit->execute($npm_list);
            $audits = $stmtAudit->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fallback jika tabel audit belum ada atau nama kolom berbeda
            $audits = [];
            $pesanError = "Peringatan: Cek kembali nama tabel audit pada baris 41. Error PDO: " . $e->getMessage();
        }

        // 4. Penggabungan Data (Merge)
        // Set kerangka default untuk semua NPM yang dicari
        foreach ($npm_list as $npm) {
            $results[$npm] = [
                'npm' => $npm,
                'nama' => '-', // Default jika belum pernah submit
                'total_submit' => 0,
                'total_audit' => 0
            ];
        }
        
        // Isi dengan data submit
        foreach ($submits as $row) {
            $results[$row['npm']]['nama'] = $row['nama'];
            $results[$row['npm']]['total_submit'] = $row['total_submit'];
        }
        
        // Isi dengan data audit
        foreach ($audits as $row) {
            $npm_auditor = $row['auditor_npm'];
            if(isset($results[$npm_auditor])) {
                $results[$npm_auditor]['total_audit'] = $row['total_audit'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Mahasiswa - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="admin_dashboard.php">Diarization Dataset Admin</a>
        <span class="navbar-text text-white">Rekap Submit & Audit Mahasiswa</span>
    </div>
</nav>

<div class="container mb-5">
    <div class="row justify-content-center mb-4">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="mb-3">Cek Rekapitulasi Berdasarkan NPM</h4>
                    <p class="text-muted">Masukkan daftar NPM mahasiswa di bawah ini. <strong>Satu NPM per baris</strong>, tidak perlu menggunakan tanda koma.</p>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <textarea name="npms" class="form-control font-monospace form-control-lg bg-light" rows="6" placeholder="231411001&#10;231411002&#10;231411003" required><?= htmlspecialchars($npms_input) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary fw-bold px-4">🔍 Tarik Data Rekap</button>
                        <a href="rekap_mahasiswa.php" class="btn btn-outline-secondary ms-2">Reset Form</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <?php if (!empty($pesanError)): ?>
                <div class="alert alert-warning border-0 shadow-sm"><?= $pesanError ?></div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="mb-3 border-bottom pb-2">Hasil Rekapitulasi (<?= count($results) ?> Mahasiswa)</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle text-center m-0">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="15%">NPM</th>
                                    <th width="40%" class="text-start">Nama Mahasiswa</th>
                                    <th width="20%">Total Submit</th>
                                    <th width="20%">Total Audit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($results) > 0): ?>
                                    <?php $no = 1; foreach ($results as $res): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($res['npm']) ?></td>
                                            <td class="text-start fw-semibold"><?= htmlspecialchars($res['nama']) ?></td>
                                            <td>
                                                <?php if($res['total_submit'] > 0): ?>
                                                    <span class="badge bg-success fs-6 px-3"><?= $res['total_submit'] ?> File</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger px-3">0 File</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($res['total_audit'] > 0): ?>
                                                    <span class="badge bg-success fs-6 px-3"><?= $res['total_audit'] ?> Audit</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger px-3">0 Audit</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-muted py-4">Tidak ada data NPM yang diproses.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>