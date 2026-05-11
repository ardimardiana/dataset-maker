<?php
// proses_tahap1.php
require_once 'config.php';

// Pastikan output selalu JSON
header('Content-Type: application/json');

// Pastikan method adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method.']);
    exit;
}

$url = trim($_POST['url'] ?? '');
$kategori = trim($_POST['kategori'] ?? '');

// Validasi Kosong
if (empty($url) || empty($kategori)) {
    echo json_encode(['status' => 'error', 'message' => 'URL dan Kategori wajib diisi.']);
    exit;
}

// =========================================================
// EKSTRAKSI YOUTUBE ID MENGGUNAKAN REGEX
// Meng-cover format:
// 1. Shorts:  https://www.youtube.com/shorts/226zi4hv4hw
// 2. Pendek:  https://youtu.be/OOkDf7wzE9U?si=hhS4...
// 3. Reguler: https://www.youtube.com/watch?v=5EjjC4yfzyc
// 4. Reguler dengan parameter lain: https://www.youtube.com/watch?foo=bar&v=5EjjC4yfzyc
// =========================================================

// Pattern ini mencari "youtu.be/" ATAU "youtube.com/watch?v=" ATAU "youtube.com/shorts/"
// Kemudian menangkap tepat 11 karakter ID video setelahnya.
preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?(?:.*&)?v=|shorts\/))([a-zA-Z0-9_-]{11})/i', $url, $matches);

if (!isset($matches[1]) || empty($matches[1])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'URL tidak dikenali. Pastikan Anda memasukkan link YouTube yang valid.'
    ]);
    exit;
}

$youtube_id = $matches[1];

try {
    // 1. Cek Duplikasi: Apakah Video ID sudah pernah diklaim?
    $stmt = $pdo->prepare("SELECT id FROM datasets WHERE youtube_id = ?");
    $stmt->execute([$youtube_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Video (ID: '.$youtube_id.') ini sudah diklaim oleh mahasiswa lain. Silakan cari video lain.']);
        exit;
    }

    // 2. Generate 8 Digit Kode Unik (Alphanumeric kapital untuk kemudahan baca)
    do {
        // Menggunakan library random bytes dari PHP 8 untuk kode yang lebih aman
        $bytes = random_bytes(4); 
        $kode_klaim = strtoupper(bin2hex($bytes)); // Menghasilkan 8 karakter hex
        
        // Cek apakah kode kebetulan sama dengan yang sudah ada di database
        $cek_kode = $pdo->prepare("SELECT id FROM datasets WHERE kode_klaim = ?");
        $cek_kode->execute([$kode_klaim]);
    } while ($cek_kode->rowCount() > 0);

    // 3. Insert ke Tabel Datasets
    // Status awal di set ke 'claimed'
    $insert = $pdo->prepare("INSERT INTO datasets (url, youtube_id, kode_klaim, kategori, status) VALUES (?, ?, ?, ?, 'claimed')");
    $insert->execute([$url, $youtube_id, $kode_klaim, $kategori]);

    // Berhasil, kembalikan respons
    echo json_encode([
        'status' => 'success',
        'youtube_id' => $youtube_id,
        'kode_klaim' => $kode_klaim
    ]);

} catch (PDOException $e) {
    // Jika ada error pada eksekusi query
    echo json_encode(['status' => 'error', 'message' => 'Kesalahan database: ' . $e->getMessage()]);
}
?>