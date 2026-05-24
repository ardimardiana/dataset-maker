<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tahap 3: Validasi RTTM - Diarization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Menambahkan scroll agar jika error banyak, halaman tidak memanjang berlebihan */
        .log-list {
            max-height: 250px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Diarization Dataset</a>
        <span class="navbar-text text-white">Tahap 3: Validasi Lapis 1 & 2</span>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-3">Sanity Check File RTTM</h4>
                    <p class="text-muted">Upload file <code>.rttm</code> yang sudah Anda edit dari Tahap 2 untuk divalidasi oleh sistem. File yang belum lolos validasi tidak boleh di-submit di Tahap 4.</p>
                    
                    <div class="mb-4">
                        <label for="rttmCheckFile" class="form-label fw-bold">Pilih File .rttm Anda</label>
                        <input class="form-control form-control-lg" type="file" id="rttmCheckFile" accept=".rttm,.txt">
                    </div>

                    <div id="validationResult" class="d-none">
                        <h5 class="fw-bold border-bottom pb-2 mb-3">Hasil Validasi:</h5>
                        
                        <div class="d-flex align-items-center mb-2">
                            <span id="icon-lapis1" class="me-2 fs-4">⏳</span>
                            <div>
                                <h6 class="mb-0 fw-bold">Lapis 1: Komputasi & Format Akustik</h6>
                                <small class="text-muted">Cek Kolom, Tipe Angka, Durasi (Min/Max), & Self-overlap</small>
                            </div>
                        </div>
                        <ul id="log-lapis1" class="text-danger small ms-4 log-list"></ul>

                        <div class="d-flex align-items-center mb-2 mt-3">
                            <span id="icon-lapis2" class="me-2 fs-4">⏳</span>
                            <div>
                                <h6 class="mb-0 fw-bold">Lapis 2: Nomenklatur Speaker</h6>
                                <small class="text-muted"><!--Format harus SPEAKER_01, SPEAKER_02, dst.--> Nice</small>
                            </div>
                        </div>
                        <ul id="log-lapis2" class="text-danger small ms-4 log-list"></ul>
                        
                        <div id="finalDecision" class="mt-4"></div>
                    </div>

                </div>
            </div>
            
            <div class="text-end">
                <a href="tahap4.php" id="btnNext" class="btn btn-success btn-lg px-5 shadow-sm disabled">Lanjut ke Tahap 4 (Submit) ➡️</a>
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
<h3>Details</h3>

<p>The Rich Transcription Time Marked (RTTM) files are space-delimited text files containing one turn per line defined by NIST - National Institute of Standards and Technology. Each line containing ten fields:
</p>
<p><code>type</code> Type: segment type; should always by SPEAKER.
</p>
<p><code>file</code> File ID: file name; basename of the recording minus extension (e.g., rec1_a).
</p>
<p><code>chnl</code> Channel ID: channel (1-indexed) that turn is on; should always be 1.
</p>
<p><code>tbeg</code> Turn Onset &ndash; onset of turn in seconds from beginning of recording.
</p>
<p><code>tdur</code> Turn Duration &ndash; duration of turn in seconds.
</p>
<p><code>ortho</code> Orthography Field &ndash; should always by &lt;NA&gt;.
</p>
<p><code>stype</code> Speaker Type &ndash; should always be &lt;NA&gt;.
</p>
<p><code>name</code> Speaker Name &ndash; name of speaker of turn; should be unique within scope of each file.
</p>
<p><code>conf</code> Confidence Score &ndash; system confidence (probability) that information is correct; should always be &lt;NA&gt;.
</p>
<p><code>slat</code> Signal Lookahead Time &ndash; should always be &lt;NA&gt;.
</p>
<h4>EG:</h4>
<p><code>SPEAKER FILE_ID 1 0.930 1.652 &lt;NA&gt; &lt;NA&gt; SPEAKER_01 &lt;NA&gt; &lt;NA&gt;</code></p>
<quote>sumber: https://search.r-project.org/CRAN/refmans/voice/html/read_rttm.html</quote>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('rttmCheckFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const resultBox = document.getElementById('validationResult');
    resultBox.classList.remove('d-none');
    
    // Reset UI
    document.getElementById('icon-lapis1').innerHTML = '⏳';
    document.getElementById('icon-lapis2').innerHTML = '⏳';
    document.getElementById('log-lapis1').innerHTML = '';
    document.getElementById('log-lapis2').innerHTML = '';
    document.getElementById('btnNext').classList.add('disabled');
    document.getElementById('finalDecision').innerHTML = '';

    const reader = new FileReader();
    reader.onload = function(evt) {
        const text = evt.target.result;
        const lines = text.split('\n');
        
        let errorsLapis1 = [];
        let errorsLapis2 = [];
        
        // Parameter Validasi (Sama dengan script Python)
        const MIN_DURATION = 0.2;
        const MAX_DURATION = 30.0;
        let speakerSegments = {}; // Menyimpan semua segmen per speaker: { speaker_id: [{start, end, baris}] }
        
        lines.forEach((line, index) => {
            if(!line.trim()) return; 
            const parts = line.trim().split(/\s+/);
            const baris = index + 1;

            // Pastikan baris diawali 'SPEAKER' dan memiliki panjang kolom yang cukup
            if(parts[0] !== 'SPEAKER') return;

            if(parts.length < 8) {
                errorsLapis1.push(`Baris ${baris}: Format RTTM tidak valid (kurang dari 8 kolom).`);
                return; // Lanjut ke baris berikutnya
            }

            const start = parseFloat(parts[3]);
            const duration = parseFloat(parts[4]);

            // Cek apakah waktu dan durasi benar-benar angka (mengikuti ValueError di Python)
            if(isNaN(start) || isNaN(duration)) {
                errorsLapis1.push(`Baris ${baris}: Format Angka Tidak Valid. Terdapat teks/karakter di kolom waktu/durasi.`);
                return;
            }

            const end = start + duration;
            const speaker = parts[7];

            // --- VALIDASI LAPIS 1 (Komputasi) ---
            
            // 1. Cek Durasi Negatif/Nol
            if(duration <= 0) {
                errorsLapis1.push(`Baris ${baris}: Durasi negatif atau 0 detik terdeteksi (${duration}s).`);
            } 
            // 2. Cek Micro-segment
            else if(duration < MIN_DURATION) {
                errorsLapis1.push(`Baris ${baris}: Micro-segment terdeteksi. Durasi terlalu pendek (${duration}s).`);
            }

            // 3. Cek Segmen Kepanjangan (Diadaptasi dari Python)
            if(duration > MAX_DURATION) {
                errorsLapis1.push(`Baris ${baris}: Durasi tidak wajar. Segmen kepanjangan (${duration}s tanpa jeda).`);
            }

            // 4. Cek Self-overlap secara ketat (Diadaptasi dari Python)
            if (!speakerSegments[speaker]) {
                speakerSegments[speaker] = [];
            }

            // Iterasi semua riwayat segmen milik speaker ini untuk cek tabrakan
            speakerSegments[speaker].forEach(prev => {
                // Syarat overlap: Mulai sebelum segmen lama selesai AND Selesai setelah segmen lama mulai
                if ((start < prev.end) && (end > prev.start)) {
                    errorsLapis1.push(`Baris ${baris}: Self-overlap pada <strong>${speaker}</strong>. Bertabrakan dengan baris ${prev.baris} (${prev.start}s - ${prev.end}s).`);
                }
            });

            // Simpan riwayat segmen saat ini
            speakerSegments[speaker].push({
                start: start, 
                end: end, 
                baris: baris
            });


            /*// --- VALIDASI LAPIS 2 (Nomenklatur) ---
            const regexNomenklatur = /^SPEAKER_\d+$/;
            if(!regexNomenklatur.test(speaker)) {
                errorsLapis2.push(`Baris ${baris}: Nama speaker <strong>"${speaker}"</strong> tidak valid. Wajib gunakan format SPEAKER_01, SPEAKER_02, dst.`);
            }*/
        });
        
        // --- TAMBAHAN: VALIDASI MINIMAL 3 SPEAKER ---
        const uniqueSpeakers = Object.keys(speakerSegments);
        // Pastikan file tidak kosong (length > 0) dan jumlah speaker kurang dari 3
        if (uniqueSpeakers.length > 0 && uniqueSpeakers.length < 3) {
            errorsLapis1.push(`<strong>Galat Dataset:</strong> File ini hanya memiliki ${uniqueSpeakers.length} pembicara unik (${uniqueSpeakers.join(', ')}). Syarat minimal adalah 3 orang pembicara.`);
        }

        // Tampilkan Hasil Lapis 1
        if(errorsLapis1.length > 0) {
            document.getElementById('icon-lapis1').innerHTML = '❌';
            errorsLapis1.forEach(err => {
                document.getElementById('log-lapis1').innerHTML += `<li>${err}</li>`;
            });
        } else {
            document.getElementById('icon-lapis1').innerHTML = '✅';
            document.getElementById('log-lapis1').innerHTML = '<li class="text-success list-unstyled">Tidak ada galat komputasi.</li>';
        }

        // Tampilkan Hasil Lapis 2
        if(errorsLapis2.length > 0) {
            document.getElementById('icon-lapis2').innerHTML = '❌';
            errorsLapis2.forEach(err => {
                document.getElementById('log-lapis2').innerHTML += `<li>${err}</li>`;
            });
        } else {
            document.getElementById('icon-lapis2').innerHTML = '✅';
            document.getElementById('log-lapis2').innerHTML = '<li class="text-success list-unstyled">Nomenklatur sesuai standar.</li>';
        }

        // Keputusan Akhir
        if(errorsLapis1.length === 0 && errorsLapis2.length === 0) {
            document.getElementById('finalDecision').innerHTML = `
                <div class="alert alert-success fw-bold text-center border-0 shadow-sm">
                    🎉 Selamat! File .rttm Anda bersih dan lolos validasi.
                </div>`;
            document.getElementById('btnNext').classList.remove('disabled');
        } else {
            document.getElementById('finalDecision').innerHTML = `
                <div class="alert alert-danger fw-bold text-center border-0 shadow-sm">
                    ⚠️ Ditemukan anomali. Silakan kembali ke Tahap 2 (Editor) untuk memperbaiki galat di atas, lalu upload ulang file Anda di sini.
                </div>`;
        }
    };
    reader.readAsText(file);
});
</script>
</body>
</html>