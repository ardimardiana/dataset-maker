<?php
// proses_audit.php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak valid.']);
    exit;
}

$id_dataset = intval($_POST['id_dataset'] ?? 0);
$npm_reviewer = trim($_POST['npm_reviewer'] ?? '');
$nama_reviewer = trim($_POST['nama_reviewer'] ?? '');
$status_review = $_POST['status_review'] ?? '';
$catatan = trim($_POST['catatan'] ?? '');

if (empty($id_dataset) || empty($npm_reviewer) || empty($nama_reviewer) || empty($status_review)) {
    echo json_encode(['status' => 'error', 'message' => 'NPM, Nama, dan Rekomendasi wajib diisi.']);
    exit;
}

if ($status_review === 'fail' && empty($catatan)) {
    echo json_encode(['status' => 'error', 'message' => 'Catatan wajib diisi jika rekomendasi Anda adalah Gagal.']);
    exit;
}

try {
    // 1. Ambil data uploader asli
    $stmt = $pdo->prepare("SELECT npm, status, durasi FROM datasets WHERE id = ?");
    $stmt->execute([$id_dataset]);
    $dataset = $stmt->fetch();

    if (!$dataset) {
        throw new Exception("Dataset tidak ditemukan.");
    }

    // Aturan 1: Tidak boleh review punya sendiri
    if (strtolower($dataset['npm']) === strtolower($npm_reviewer)) {
        throw new Exception("Sistem menolak: Anda tidak diizinkan melakukan audit pada dataset milik Anda sendiri.");
    }

    // Aturan 2: Tidak boleh review 2 kali di file yang sama
    $cek_review = $pdo->prepare("SELECT id FROM reviews WHERE id_dataset = ? AND npm = ?");
    $cek_review->execute([$id_dataset, $npm_reviewer]);
    if ($cek_review->rowCount() > 0) {
        throw new Exception("Anda sudah pernah memberikan rekomendasi untuk dataset ini.");
    }

    // Lolos validasi, masukkan ke tabel reviews beserta durasi datasetnya
    $insert = $pdo->prepare("INSERT INTO reviews (id_dataset, npm, nama, status_review, catatan, durasi_dataset) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->execute([$id_dataset, $npm_reviewer, $nama_reviewer, $status_review, $catatan, $dataset['durasi']]);

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>