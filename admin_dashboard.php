<?php
// admin_dashboard.php
session_start();
require_once 'config.php';

// --- SISTEM LOGIN SEDERHANA ---
$admin_password = GOD_MODE; // Ganti dengan password yang Anda inginkan

if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error_login = "Password salah!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_dashboard.php");
    exit;
}

// Tampilkan form login jika belum login
if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <title>Admin Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-dark d-flex align-items-center justify-content-center" style="height: 100vh;">
        <div class="card shadow" style="width: 350px;">
            <div class="card-body p-4 text-center">
                <h4 class="fw-bold mb-4">God Mode Login</h4>
                <?php if (isset($error_login)) echo "<div class='alert alert-danger py-2'>$error_login</div>"; ?>
                <form method="POST">
                    <input type="password" name="password" class="form-control mb-3 text-center" placeholder="Masukkan Password" required autofocus>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Akses Dasbor</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- QUERY DATA MASTER ---
$stmt = $pdo->query("
    SELECT d.*, 
           SUM(CASE WHEN r.status_review = 'pass' THEN 1 ELSE 0 END) as pass_count,
           SUM(CASE WHEN r.status_review = 'fail' THEN 1 ELSE 0 END) as fail_count,
           COUNT(r.id) as total_review
    FROM datasets d
    LEFT JOIN reviews r ON d.id = r.id_dataset
    WHERE d.status NOT IN ('claimed', 'rejected')
    GROUP BY d.id
    ORDER BY d.created_at DESC
");
$datasets = $stmt->fetchAll();

// --- QUERY REKAP SUBMIT MAHASISWA ---
$stmtSubmit = $pdo->query("
    SELECT npm, nama, COUNT(id) as total_file, SUM(durasi) as total_durasi 
    FROM datasets 
    GROUP BY npm, nama 
    ORDER BY total_file DESC
");
$rekapSubmit = $stmtSubmit->fetchAll();

// --- QUERY REKAP AUDIT MAHASISWA ---
// Asumsi: tabel reviews memiliki kolom 'reviewer_nama' dan 'reviewer_npm'. Sesuaikan jika berbeda.
$stmtAudit = $pdo->query("
    SELECT r.npm as npm, r.nama as nama, COUNT(r.id) as total_audit ,SUM(r.durasi_dataset) as total_durasi
    FROM reviews r
    GROUP BY r.npm, r.nama 
    ORDER BY total_audit DESC
");
$rekapAudit = $stmtAudit->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>God Mode - Diarization Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-danger mb-4 shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#">👑 Admin: Diarization Dataset</a>
        <div class="d-flex">
            <a href="?logout=1" class="btn btn-outline-light btn-sm fw-bold">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            
            <!-- HEADER & FILTER AREA -->
            <div class="row align-items-center mb-4">
                <div class="col-md-5">
                    <h4 class="mb-0 fw-bold">Master Data Audit</h4>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalRekapSubmit">📊 Rekap Submit</button>
                        <button class="btn btn-sm btn-outline-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalRekapAudit">📋 Rekap Audit</button>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="d-flex gap-2 justify-content-md-end mt-3 mt-md-0">
                        <!-- Kotak Pencarian -->
                        <div class="input-group" style="max-width: 300px;">
                            <span class="input-group-text bg-white">🔍</span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Cari Kode, Nama, NPM...">
                        </div>
                        
                        <!-- Dropdown Filter Status -->
                        <select id="statusFilter" class="form-select" style="max-width: 200px;">
                            <option value="all">Semua Status</option>
                            <option value="pending">⏳ Pending Review</option>
                            <option value="conflicted">⚠️ Konflik (Seri)</option>
                            <option value="approved">✅ Approved</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- TABEL DATA -->
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center" id="adminTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode File</th>
                            <th>Pengunggah</th>
                            <th>Kategori</th>
                            <th>Info Audio</th>
                            <th>Status Review</th>
                            <th>Aksi God Mode</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (count($datasets) > 0): ?>
                            <?php foreach ($datasets as $row): 
                                $is_conflict = ($row['pass_count'] > 0 && $row['fail_count'] > 0);
                                
                                // Penentuan Data-Status untuk Filter Javascript
                                $row_status = 'pending';
                                if ($row['status'] === 'approved') {
                                    $row_status = 'approved';
                                } elseif ($is_conflict && $row['status'] !== 'approved') {
                                    $row_status = 'conflicted';
                                }
                            ?>
                            <tr id="row-<?= $row['id'] ?>" data-status="<?= $row_status ?>">
                                <td class="fw-bold text-primary"><a class="text-decoration-none" href="audit_detail.php?id=<?= htmlspecialchars($row['id']) ?>" target="_blank"><?= htmlspecialchars($row['kode_file']) ?></a></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($row['nama']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($row['npm']) ?></small>
                                </td>
                                <td class="text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $row['kategori'])) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= $row['jumlah_speaker'] ?> Spk</span>
                                    <span class="badge bg-info text-dark"><?= number_format($row['durasi'], 1) ?>s</span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'approved'): ?>
                                        <span class="badge bg-success w-100 py-2">✅ APPROVED</span>
                                    <?php else: ?>
                                        <div class="mb-1 text-muted small"><?= $row['total_review'] ?>/2 Reviews</div>
                                        <?php if ($is_conflict): ?>
                                            <span class="badge bg-warning text-dark w-100 py-1">⚠️ KONFLIK (<?= $row['pass_count'] ?>P / <?= $row['fail_count'] ?>F)</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary w-100 py-1">Pending (<?= $row['pass_count'] ?>P / <?= $row['fail_count'] ?>F)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm w-100">
                                        <!--<button onclick="playDataset('<?= $row['kode_file'] ?>')" class="btn btn-warning text-dark fw-bold" title="Playback Audio">🎧 Play</button>-->
                                        <?php if ($row['status'] !== 'approved'): ?>
                                            <button onclick="lihatReview(<?= $row['id'] ?>)" class="btn btn-info text-white fw-bold" title="Lihat Catatan Reviewer">👁️ Cek</button>
                                            <button onclick="aksiAdmin(<?= $row['id'] ?>, 'approve')" class="btn btn-success fw-bold" title="Force Approve">✅ ACC</button>
                                        <?php endif; ?>
                                        
                                        <?php if ($row['status'] === 'approved'): ?>
                                            <a href="admin_action.php?action=download&id=<?= $row['id'] ?>" class="btn btn-primary fw-bold" title="Download ZIP Bundle">📦 ZIP</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($row['status'] !== 'approved'): // Atau sesuaikan dengan kondisi status Anda ?>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="openRejectModal(<?= $row['id'] ?>)">
                                                ❌ Reject / Revisi
                                            </button>
                                        <?php endif; ?>
                                        
                                        <!--<button onclick="aksiAdmin(<?= $row['id'] ?>, 'delete')" class="btn btn-danger fw-bold" title="Hapus Permanen">🗑️ Del</button>-->
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="emptyRow"><td colspan="6" class="py-4 text-muted">Belum ada dataset yang di-submit.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>

<!-- Modal Detail Review (Tidak diubah) -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Catatan Auditor Lapangan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reviewModalBody">
                <div class="text-center"><span class="spinner-border"></span></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="rejectModalLabel">Tolak Dataset</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formReject">
          <div class="modal-body">
              <input type="hidden" name="action" value="reject">
              <input type="hidden" name="id" id="reject_id_dataset">
              
              <div class="mb-3">
                  <label for="catatan" class="form-label fw-bold">Catatan Penolakan / Alasan Revisi:</label>
                  <textarea class="form-control" name="catatan" id="catatan" rows="4" required placeholder="Jelaskan bagian mana yang salah, misal: 'File RTTM tidak sinkron dengan audio' atau 'Kategori salah, seharusnya Talkshow'..." ></textarea>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger">Simpan & Tolak</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalRekapSubmit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Rekap Submit per Mahasiswa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-light">
                        <tr><th>NPM</th><th>Nama Mahasiswa</th><th>Total File</th><th>Total Durasi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($rekapSubmit as $rs): ?>
                        <tr>
                            <td><?= htmlspecialchars($rs['npm']) ?></td>
                            <td class="text-start"><?= htmlspecialchars($rs['nama']) ?></td>
                            <td class="fw-bold text-primary"><?= $rs['total_file'] ?> File</td>
                            <td><?= number_format(($rs['total_durasi']/60), 2) ?> Menit</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRekapAudit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Rekap Audit per Mahasiswa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered text-center align-middle">
                    <thead class="table-light">
                        <tr><th>NPM Auditor</th><th>Nama Auditor</th><th>Total Dataset Diaudit</th><th>Total Durasi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($rekapAudit as $ra): ?>
                        <tr>
                            <td><?= htmlspecialchars($ra['npm'] ?? '-') ?></td>
                            <td class="text-start"><?= htmlspecialchars($ra['nama'] ?? '-') ?></td>
                            <td class="fw-bold text-success"><?= $ra['total_audit'] ?> Audit</td>
                            <td><?= number_format(($ra['total_durasi']/60), 2) ?> Menit</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="playbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark">Playback Dataset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <h6 id="playbackTitle" class="fw-bold mb-3 text-primary"></h6>
                <audio id="audioPlayer" controls class="w-100 shadow-sm rounded">
                    <source id="audioSource" src="" type="audio/wav">
                    Browser Anda tidak mendukung pemutar audio.
                </audio>
                <a id="btnLihatRTTM" href="#" target="_blank" class="btn btn-outline-dark mt-4 w-100 fw-bold">📄 Buka File RTTM</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fungsi untuk membuka modal dan set ID Dataset
function openRejectModal(id) {
    document.getElementById('reject_id_dataset').value = id;
    document.getElementById('catatan').value = ''; // Reset isi textarea
    var rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}

// Event Listener saat form submit
document.getElementById('formReject').addEventListener('submit', function(e) {
    e.preventDefault(); // Mencegah reload halaman bawaan form
    
    let formData = new FormData(this);
    let submitBtn = this.querySelector('button[type="submit"]');
    
    // Ubah status tombol biar mencegah double click
    submitBtn.innerHTML = 'Memproses...';
    submitBtn.disabled = true;

    fetch('admin_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            alert('Dataset berhasil ditolak. Mahasiswa dapat melihat catatan revisi.');
            location.reload(); // Refresh halaman untuk melihat perubahan status
        } else {
            alert('Gagal: ' + (data.message || 'Terjadi kesalahan sistem.'));
            submitBtn.innerHTML = 'Simpan & Tolak';
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan jaringan/server.');
        submitBtn.innerHTML = 'Simpan & Tolak';
        submitBtn.disabled = false;
    });
});

// Logic Playback
const playbackModalUI = new bootstrap.Modal(document.getElementById('playbackModal'));
function playDataset(kodeFile) {
    document.getElementById('playbackTitle').innerText = "File Kode: " + kodeFile;
    
    // PERHATIAN: Sesuaikan path 'uploads/wav/' dan 'uploads/rttm/' dengan letak penyimpanan file aslinya
    document.getElementById('audioSource').src = 'uploads/AUDIO/' + kodeFile + '.wav';
    document.getElementById('btnLihatRTTM').href = 'uploads/ANOTATION/' + kodeFile + '.rttm';
    
    document.getElementById('audioPlayer').load();
    playbackModalUI.show();
}

// Otomatis pause audio jika modal ditutup
document.getElementById('playbackModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('audioPlayer').pause();
});

// Logika Modal dan Aksi
const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));

function lihatReview(idDataset) {
    reviewModal.show();
    document.getElementById('reviewModalBody').innerHTML = '<div class="text-center"><span class="spinner-border"></span></div>';
    fetch(`admin_action.php?action=get_reviews&id=${idDataset}`)
        .then(res => res.text())
        .then(html => {
            document.getElementById('reviewModalBody').innerHTML = html;
        });
}

function aksiAdmin(idDataset, actionType) {
    let pesan = actionType === 'approve' ? "Anda yakin ingin meloloskan (Approve) dataset ini secara paksa?" : "⚠️ PERINGATAN: Anda yakin ingin MENGHAPUS PERMANEN dataset beserta file WAV dan RTTM-nya?";
    
    if (confirm(pesan)) {
        const formData = new FormData();
        formData.append('action', actionType);
        formData.append('id', idDataset);

        fetch('admin_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                location.reload(); 
            } else {
                alert('Gagal: ' + data.message);
            }
        });
    }
}

// --- LOGIKA FILTER REAL-TIME ---
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const tableRows = document.querySelectorAll('#tableBody tr:not(#emptyRow)');

function filterTable() {
    const searchText = searchInput.value.toLowerCase();
    const statusVal = statusFilter.value;

    tableRows.forEach(row => {
        const rowText = row.innerText.toLowerCase();
        const rowStatus = row.getAttribute('data-status');

        // Cek kecocokan teks dan status
        const matchSearch = rowText.includes(searchText);
        const matchStatus = (statusVal === 'all') || (rowStatus === statusVal);

        if (matchSearch && matchStatus) {
            row.style.display = ''; // Tampilkan
        } else {
            row.style.display = 'none'; // Sembunyikan
        }
    });
}

// Pasang event listener pada input dan dropdown
searchInput.addEventListener('input', filterTable);
statusFilter.addEventListener('change', filterTable);
</script>
</body>
</html>