<?php
// admin_action.php
session_start();
require_once 'config.php';

// Proteksi kemanan dasar
if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized Access']));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if (!$action || !$id) {
    die(json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']));
}

try {
    switch ($action) {
        
        // --- LIHAT DETAIL REVIEW ---
        case 'get_reviews':
            $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id_dataset = ? ORDER BY waktu_review DESC");
            $stmt->execute([$id]);
            $reviews = $stmt->fetchAll();
            
            if (count($reviews) === 0) {
                echo "<div class='alert alert-info'>Belum ada mahasiswa yang me-review file ini.</div>";
                exit;
            }
            
            echo "<div class='list-group'>";
            foreach ($reviews as $rev) {
                $badge = $rev['status_review'] === 'pass' ? "<span class='badge bg-success'>✅ Lolos</span>" : "<span class='badge bg-danger'>❌ Gagal</span>";
                echo "<div class='list-group-item'>";
                echo "<div class='d-flex justify-content-between mb-1'><strong>{$rev['nama']} ({$rev['npm']})</strong> $badge</div>";
                if ($rev['status_review'] === 'fail') {
                    echo "<div class='text-danger small mt-2'><strong>Catatan:</strong> " . nl2br(htmlspecialchars($rev['catatan'])) . "</div>";
                }
                echo "</div>";
            }
            echo "</div>";
            exit;

        // --- FORCE APPROVE ---
        case 'approve':
            $update = $pdo->prepare("UPDATE datasets SET status = 'approved' WHERE id = ?");
            $update->execute([$id]);
            echo json_encode(['status' => 'success']);
            exit;

        // --- HAPUS PERMANEN (DATA & FILE) ---
        case 'delete':
            $stmt = $pdo->prepare("SELECT file_wav_path, file_rttm_path FROM datasets WHERE id = ?");
            $stmt->execute([$id]);
            $files = $stmt->fetch();
            
            if ($files) {
                // Hapus file fisik dari server jika ada
                if (file_exists($files['file_wav_path'])) unlink($files['file_wav_path']);
                if (file_exists($files['file_rttm_path'])) unlink($files['file_rttm_path']);
            }
            
            // Delete dari database (Metadata & Reviews otomatis terhapus karena ON DELETE CASCADE)
            $delete = $pdo->prepare("DELETE FROM datasets WHERE id = ?");
            $delete->execute([$id]);
            
            echo json_encode(['status' => 'success']);
            exit;
            
        // --- TOLAK / REJECT DATASET ---
        case 'reject':
            $catatan = trim($_POST['catatan'] ?? '');
            
            if (empty($catatan)) {
                die(json_encode(['status' => 'error', 'message' => 'Catatan penolakan wajib diisi!']));
            }

            // 1. Ambil path file fisik
            $stmt = $pdo->prepare("SELECT file_wav_path, file_rttm_path FROM datasets WHERE id = ?");
            $stmt->execute([$id]);
            $files = $stmt->fetch();
            
            // 2. Hapus file fisik dari server jika ada
            if ($files) {
                if (!empty($files['file_wav_path']) && file_exists($files['file_wav_path'])) {
                    unlink($files['file_wav_path']);
                }
                if (!empty($files['file_rttm_path']) && file_exists($files['file_rttm_path'])) {
                    unlink($files['file_rttm_path']);
                }
            }
            
            // 3. Kosongkan Metadata dan Reviews terkait dataset ini
            $pdo->prepare("DELETE FROM metadata WHERE id_dataset = ?")->execute([$id]);
            
            //membiarkan mahasiswa mendapatkan Hak durasi
            $pdo->prepare("UPDATE reviews SET status_aktif=2 WHERE id_dataset = ?")->execute([$id]);
            //$pdo->prepare("DELETE FROM reviews WHERE id_dataset = ?")->execute([$id]);

            // 4. Update status datasets menjadi rejected, simpan catatan, dan null-kan path file
            $update = $pdo->prepare("UPDATE datasets SET status = 'rejected', catatan_reject = ?, kode_file = NULL, npm = NULL, nama = NULL, durasi = NULL, jumlah_speaker = NULL, file_wav_path = NULL, file_rttm_path = NULL WHERE id = ?");
            $update->execute([$catatan, $id]);
            
            echo json_encode(['status' => 'success']);
            exit;

        // --- DOWNLOAD ZIP BUNDLE ---
        case 'download':
            $stmt = $pdo->prepare("SELECT * FROM datasets WHERE id = ? AND status = 'approved'");
            $stmt->execute([$id]);
            $dataset = $stmt->fetch();
            
            if (!$dataset) {
                die("Dataset tidak ditemukan atau belum di-approve.");
            }

            // Ekstrak Metadata Gender
            $stmtMeta = $pdo->prepare("SELECT speaker, gender FROM metadata WHERE id_dataset = ?");
            $stmtMeta->execute([$id]);
            $metadatas = $stmtMeta->fetchAll();
            
            $speakersArray = [];
            foreach ($metadatas as $meta) {
                $speakersArray[$meta['speaker']] = $meta['gender'];
            }

            // Susun struktur JSON sesuai SOP
            $jsonContent = json_encode([
                "file_id" => $dataset['kode_file'],
                "url" => $dataset['url'],
                "speakers" => $speakersArray
            ], JSON_PRETTY_PRINT);

            $kode_file = $dataset['kode_file'];
            $zipFilename = $kode_file . '_bundle.zip';
            $zipPath = sys_get_temp_dir() . '/' . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                
                // Tambahkan WAV
                if (file_exists($dataset['file_wav_path'])) {
                    $zip->addFile($dataset['file_wav_path'], $kode_file . '.wav');
                }
                // Tambahkan RTTM
                if (file_exists($dataset['file_rttm_path'])) {
                    $zip->addFile($dataset['file_rttm_path'], $kode_file . '.rttm');
                }
                // Tambahkan JSON secara on-the-fly dari string
                $zip->addFromString($kode_file . '_meta.json', $jsonContent);
                
                $zip->close();

                // Paksa browser mengunduh ZIP
                header('Content-Type: application/zip');
                header('Content-disposition: attachment; filename=' . $zipFilename);
                header('Content-Length: ' . filesize($zipPath));
                readfile($zipPath);
                
                // Hapus ZIP sementara dari server setelah diunduh
                unlink($zipPath);
                exit;
            } else {
                die("Gagal membuat file ZIP di server.");
            }

    }
} catch (Exception $e) {
    die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
}
?>