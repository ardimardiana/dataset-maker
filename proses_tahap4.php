<?php
// proses_tahap4.php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Method']);
    exit;
}

// ==========================================
// FUNGSI VALIDASI BACKEND
// ==========================================

// 1. Fungsi Validasi WAV Header (16kHz, 16-bit, Mono)
function validateWavFile($filepath) {
    $fp = fopen($filepath, 'rb');
    if (!$fp) return "Gagal membaca file audio dari temporary storage.";
    
    // Baca 44 byte pertama (Header Standar WAV)
    $header = fread($fp, 44);
    fclose($fp);
    
    if (strlen($header) < 44) return "File terlalu kecil. Bukan file .wav yang valid.";
    
    $riff = substr($header, 0, 4);
    $wave = substr($header, 8, 4);
    if ($riff !== 'RIFF' || $wave !== 'WAVE') return "Format file tidak dikenali. Pastikan file benar-benar berformat WAV.";
    
    // Unpack data binary menggunakan little-endian format ('v' untuk 16-bit, 'V' untuk 32-bit)
    $numChannels = unpack('v', substr($header, 22, 2))[1];
    $sampleRate = unpack('V', substr($header, 24, 4))[1];
    $bitsPerSample = unpack('v', substr($header, 34, 2))[1];
    
    if ($numChannels !== 1 || $sampleRate !== 16000 || $bitsPerSample !== 16) {
        return "Format Audio Ditolak Sistem! Ditemukan: {$sampleRate}Hz, {$bitsPerSample}-bit, {$numChannels} channel. Wajib: 16000Hz, 16-bit, 1 channel.";
    }
    
    return true; // Valid
}

// 2. Fungsi Validasi RTTM
function validateRttmFile($filepath) {
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return "Gagal membaca file RTTM.";
    
    $speakerSegments = [];
    $MIN_DURATION = 0.2;
    $MAX_DURATION = 30.0;
    
    foreach ($lines as $index => $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = preg_split('/\s+/', $line);
        $baris = $index + 1;
        
        if ($parts[0] !== 'SPEAKER') continue;
        
        if (count($parts) < 8) return "RTTM Error Baris $baris: Format tidak valid (kurang dari 8 kolom).";
        
        $start = $parts[3];
        $duration = $parts[4];
        
        if (!is_numeric($start) || !is_numeric($duration)) {
            return "RTTM Error Baris $baris: Terdapat teks/karakter di kolom waktu/durasi.";
        }
        
        $start = (float)$start;
        $duration = (float)$duration;
        $end = $start + $duration;
        $speaker = $parts[7];
        
        // Cek Komputasi Lapis 1
        if ($duration <= 0) return "RTTM Error Baris $baris: Durasi negatif atau 0 detik terdeteksi.";
        if ($duration < $MIN_DURATION) return "RTTM Error Baris $baris: Micro-segment terdeteksi (< {$MIN_DURATION}s).";
        if ($duration > $MAX_DURATION) return "RTTM Error Baris $baris: Durasi tidak wajar. Segmen kepanjangan (> {$MAX_DURATION}s).";
        
        // Cek Nomenklatur Lapis 2
        if (!preg_match('/^SPEAKER_\d+$/', $speaker)) {
            return "RTTM Error Baris $baris: Nama speaker \"$speaker\" tidak valid. Wajib gunakan format SPEAKER_01, dst.";
        }
        
        // Cek Self-overlap
        if (!isset($speakerSegments[$speaker])) {
            $speakerSegments[$speaker] = [];
        }
        
        foreach ($speakerSegments[$speaker] as $prev) {
            if (($start < $prev['end']) && ($end > $prev['start'])) {
                return "RTTM Error Baris $baris: Self-overlap pada $speaker. Bertabrakan dengan baris {$prev['baris']}.";
            }
        }
        
        $speakerSegments[$speaker][] = [
            'start' => $start,
            'end' => $end,
            'baris' => $baris
        ];
    }
    
    return true; // Valid
}
// ==========================================


$kode_klaim = trim($_POST['kode_klaim'] ?? '');
$npm = trim($_POST['npm'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$durasi = floatval($_POST['durasi'] ?? 0);
$jumlah_speaker = intval($_POST['jumlah_speaker'] ?? 0);
$genders = $_POST['gender'] ?? [];

if (empty($kode_klaim) || empty($npm) || empty($nama) || empty($_FILES['file_wav']['name']) || empty($_FILES['file_rttm']['name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Semua form dan file wajib diisi.']);
    exit;
}

try {
    // 0. HARD VALIDASI FILE SEBELUM MEMBUKA DATABASE (Mencegah user nakal)
    $tmp_wav = $_FILES['file_wav']['tmp_name'];
    $tmp_rttm = $_FILES['file_rttm']['tmp_name'];

    // Eksekusi fungsi validasi Audio
    $wavValidation = validateWavFile($tmp_wav);
    if ($wavValidation !== true) {
        throw new Exception($wavValidation);
    }

    // Eksekusi fungsi validasi RTTM
    $rttmValidation = validateRttmFile($tmp_rttm);
    if ($rttmValidation !== true) {
        throw new Exception($rttmValidation);
    }

    // Jika lolos validasi, baru mulai transaksi database
    $pdo->beginTransaction();

    // 1. Validasi Kode Klaim
    $stmt = $pdo->prepare("SELECT id, kategori, status FROM datasets WHERE kode_klaim = ? FOR UPDATE");
    $stmt->execute([$kode_klaim]);
    $dataset = $stmt->fetch();

    if (!$dataset) {
        throw new Exception("Kode Klaim tidak ditemukan di database.");
    }
    if ($dataset['status'] !== 'claimed') {
        throw new Exception("Kode Klaim ini sudah pernah digunakan untuk submit data.");
    }

    // 2. Generate Kode File (Contoh: POD_001, TLK_002)
    $id_dataset = $dataset['id'];
    $kategori = $dataset['kategori'];
    $prefix = 'DAT'; // Default
    switch ($kategori) {
        case 'podcast': $prefix = 'POD'; break;
        case 'talkshow': $prefix = 'TLK'; break;
        case 'entertainment': $prefix = 'ENT'; break;
        case 'movie_drama': $prefix = 'MOV'; break;
    }
    // Format id dengan leading zeros: POD_001
    $kode_file = sprintf("%s_%03d", $prefix, $id_dataset);

    // 3. Proses Upload File
    $uploadDirAUD = 'uploads/AUDIO/';
    if (!is_dir($uploadDirAUD)) {
        mkdir($uploadDirAUD, 0755, true);
    }
    
    $uploadDirANO = 'uploads/ANOTATION/';
    if (!is_dir($uploadDirANO)) {
        mkdir($uploadDirANO, 0755, true);
    }

    $wav_ext = pathinfo($_FILES['file_wav']['name'], PATHINFO_EXTENSION);
    $rttm_ext = pathinfo($_FILES['file_rttm']['name'], PATHINFO_EXTENSION);
    
    // Keamanan sederhana ekstensi
    if (strtolower($wav_ext) !== 'wav' || !in_array(strtolower($rttm_ext), ['rttm', 'txt'])) {
        throw new Exception("Ekstensi file tidak valid.");
    }

    $wav_filename = $kode_file . '.wav';
    $rttm_filename = $kode_file . '.rttm';

    $wav_path = $uploadDirAUD . $wav_filename;
    $rttm_path = $uploadDirANO . $rttm_filename;

    if (!move_uploaded_file($tmp_wav, $wav_path)) {
        throw new Exception("Gagal menyimpan file WAV ke server.");
    }
    if (!move_uploaded_file($tmp_rttm, $rttm_path)) {
        // Rollback WAV yang udah terupload jika RTTM gagal
        unlink($wav_path); 
        throw new Exception("Gagal menyimpan file RTTM ke server.");
    }

    // 4. Update Tabel Datasets
    $update = $pdo->prepare("UPDATE datasets SET kode_file = ?, npm = ?, nama = ?, durasi = ?, jumlah_speaker = ?, file_wav_path = ?, file_rttm_path = ?, status = 'pending_review' WHERE id = ?");
    $update->execute([$kode_file, $npm, $nama, $durasi, $jumlah_speaker, $wav_path, $rttm_path, $id_dataset]);

    // 5. Insert Tabel Metadata (Gender)
    if (!empty($genders)) {
        $insertMeta = $pdo->prepare("INSERT INTO metadata (id_dataset, speaker, gender) VALUES (?, ?, ?)");
        foreach ($genders as $spk_name => $gen) {
            $insertMeta->execute([$id_dataset, $spk_name, $gen]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'kode_file' => $kode_file
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>