<?php
// proses_statistik.php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Method']);
    exit;
}

try {
    // 1. Ambil agregasi data per kategori (Hanya yang Approved)
    $stmt = $pdo->query("
        SELECT 
            kategori, 
            COUNT(id) as jumlah_entri, 
            AVG(durasi) as avg_durasi, 
            MIN(jumlah_speaker) as min_spk, 
            MAX(jumlah_speaker) as max_spk,
            SUM(durasi) as total_durasi_kategori
        FROM datasets 
        WHERE status = 'approved' 
        GROUP BY kategori
    ");
    $data_kategori = $stmt->fetchAll();

    // 2. Format struktur data untuk JSON
    $statistik = [
        'last_updated' => date('Y-m-d H:i:s'),
        'total_keseluruhan_durasi' => 0,
        'kategori' => [
            'podcast' => ['jumlah' => 0, 'avg' => 0, 'min_spk' => 0, 'max_spk' => 0],
            'talkshow' => ['jumlah' => 0, 'avg' => 0, 'min_spk' => 0, 'max_spk' => 0],
            'entertainment' => ['jumlah' => 0, 'avg' => 0, 'min_spk' => 0, 'max_spk' => 0],
            'movie_drama' => ['jumlah' => 0, 'avg' => 0, 'min_spk' => 0, 'max_spk' => 0],
        ]
    ];

    $total_all_durasi = 0;

    foreach ($data_kategori as $row) {
        $kat = $row['kategori'];
        $statistik['kategori'][$kat] = [
            'jumlah' => (int)$row['jumlah_entri'],
            'avg' => round((float)$row['avg_durasi'], 2),
            'min_spk' => (int)$row['min_spk'],
            'max_spk' => (int)$row['max_spk']
        ];
        $total_all_durasi += (float)$row['total_durasi_kategori'];
    }

    $statistik['total_keseluruhan_durasi'] = round($total_all_durasi, 2);

    // 3. Simpan ke file JSON
    $json_string = json_encode($statistik, JSON_PRETTY_PRINT);
    $bytes = file_put_contents('stats_cache.json', $json_string);

    if ($bytes === false) {
        throw new Exception("Gagal menulis file stats_cache.json. Cek permission folder.");
    }

    echo json_encode(['status' => 'success', 'last_updated' => $statistik['last_updated']]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>