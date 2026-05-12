<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tahap 4: Submit Dataset - Diarization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Diarization Dataset</a>
        <span class="navbar-text text-white">Tahap 4: Pengemasan & Submit Final</span>
    </div>
</nav>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="mb-3">Submit Dataset Final</h4>
                    <p class="text-muted">Pastikan file <code>.rttm</code> yang diunggah adalah versi final yang telah lolos validasi, dan audio <code>.wav</code> berformat 16kHz, 16-bit PCM, Mono.</p>

                    <div id="alertArea"></div>

                    <form id="formSubmit" enctype="multipart/form-data">
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Kode Klaim (8 Digit)</label>
                                <input type="text" class="form-control form-control-lg font-monospace text-primary fw-bold" name="kode_klaim" placeholder="Contoh: A1B2C3D4" maxlength="8" required>
                                <div class="form-text">Kode yang Anda dapatkan di Tahap 1.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">NPM</label>
                                <input type="text" class="form-control" name="npm" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">File Audio (.wav)</label>
                                <input type="file" class="form-control" name="file_wav" id="wavInput" accept="audio/wav" required>
                                <div class="form-text">Wajib format 16kHz, 16-bit PCM, Mono.</div>
                                <div id="wavError" class="small mt-2 d-none p-2 rounded"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">File Anotasi (.rttm)</label>
                                <input type="file" class="form-control" name="file_rttm" id="rttmInput" accept=".rttm,.txt" required>
                                <div id="rttmError" class="text-danger small mt-2 d-none p-2 bg-danger-subtle border border-danger rounded"></div>
                            </div>
                        </div>

                        <div id="metadataContainer" class="d-none bg-white border rounded p-3 mb-4 shadow-sm">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Metadata Speaker</h6>
                            <div class="row text-center mb-2">
                                <div class="col-6"><strong>Total Durasi:</strong> <span id="labelDurasiWAV" class="badge bg-secondary">0 s</span></div>
                                <div class="col-6"><strong>Jumlah Speaker:</strong> <span id="labelJumlahSpeaker" class="badge bg-secondary">0</span></div>
                            </div>
                            <input type="hidden" name="durasi" id="inputDurasiWav">
                            <input type="hidden" name="jumlah_speaker" id="inputJumlahSpeaker">
                            
                            <div id="genderInputs" class="mt-3">
                                </div>
                        </div>

                        <button type="submit" id="btnSubmit" class="btn btn-success w-100 fw-bold py-2 disabled">🚀 Submit & Unggah Dataset</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// State Validasi
let isWavValid = false;
let isRttmValid = false;

function checkFormValidity() {
    const btnSubmit = document.getElementById('btnSubmit');
    if (isWavValid && isRttmValid) {
        btnSubmit.classList.remove('disabled');
        btnSubmit.disabled = false;
    } else {
        btnSubmit.classList.add('disabled');
        btnSubmit.disabled = true;
    }
}

// 1. LOGIKA VALIDASI AUDIO (.WAV) - 16kHz, 16-bit, Mono
document.getElementById('wavInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const wavError = document.getElementById('wavError');
    isWavValid = false;
    wavError.classList.add('d-none');
    checkFormValidity();

    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(evt) {
        const buffer = evt.target.result;
        
        // Pastikan file memiliki ukuran header minimum
        if(buffer.byteLength < 44) {
            showWavError('❌ File terlalu kecil. Bukan file .wav yang valid.');
            return;
        }

        const view = new DataView(buffer);
        
        // Cek struktur RIFF & WAVE
        const riff = String.fromCharCode(...new Uint8Array(buffer, 0, 4));
        const format = String.fromCharCode(...new Uint8Array(buffer, 8, 4));
        if (riff !== 'RIFF' || format !== 'WAVE') {
            showWavError('❌ Format file tidak dikenali. Pastikan file benar-benar berformat WAV.');
            return;
        }

        // Baca spesifikasi audio dari byte offset standar WAV
        const numChannels = view.getUint16(22, true);      // Offset 22: Jumlah Channel
        const sampleRate = view.getUint32(24, true);      // Offset 24: Sample Rate
        const bitsPerSample = view.getUint16(34, true);   // Offset 34: Bit Depth

        if (numChannels === 1 && sampleRate === 16000 && bitsPerSample === 16) {
            isWavValid = true;
            
            // --- KODE BARU: Hitung Durasi WAV ---
            // Offset 28 adalah Byte Rate (Bytes per second)
            const byteRate = view.getUint32(28, true); 
            // Kurangi 44 byte (header) dari total size file untuk mendapat ukuran data audio murni
            const durasiWav = (file.size - 44) / byteRate; 

            // Update UI dan hidden input dengan durasi dari WAV
            document.getElementById('labelDurasiWAV').innerText = durasiWav.toFixed(2) + ' detik';
            document.getElementById('inputDurasiWav').value = durasiWav.toFixed(2);
            // ------------------------------------

            wavError.innerHTML = '✅ Format Audio Sesuai. Durasi: <strong>' + durasiWav.toFixed(2) + ' detik</strong>';
            wavError.className = 'small mt-2 p-2 rounded bg-success-subtle text-success border border-success';
            checkFormValidity();        
            
        } else {
            showWavError(`❌ Format Audio Salah!<br>Ditemukan: <strong>${sampleRate}Hz, ${bitsPerSample}-bit, ${numChannels} channel</strong>.<br>Wajib: <strong>16000Hz, 16-bit, 1 channel</strong>.`);
        }
    };
    
    // Kita hanya perlu membaca 44 byte pertama (header) untuk mengecek spesifikasi, sangat cepat!
    reader.readAsArrayBuffer(file.slice(0, 44));
});

function showWavError(msg) {
    const wavError = document.getElementById('wavError');
    wavError.innerHTML = msg;
    wavError.className = 'small mt-2 p-2 rounded bg-danger-subtle text-danger border border-danger';
}

// 2. LOGIKA VALIDASI RTTM (Diadaptasi dari Tahap 3)
document.getElementById('rttmInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const metadataContainer = document.getElementById('metadataContainer');
    const genderInputs = document.getElementById('genderInputs');
    const rttmError = document.getElementById('rttmError');
    
    isRttmValid = false;
    metadataContainer.classList.add('d-none');
    rttmError.classList.add('d-none');
    checkFormValidity();

    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(evt) {
        const text = evt.target.result;
        const lines = text.split('\n');
        
        let errors = [];
        const MIN_DURATION = 0.2;
        const MAX_DURATION = 30.0;
        let speakerSegments = {}; 
        
        let uniqueSpeakers = new Set();
        let maxEndTime = 0;

        lines.forEach((line, index) => {
            if(!line.trim()) return; 
            const parts = line.trim().split(/\s+/);
            const baris = index + 1;

            if(parts[0] !== 'SPEAKER') return;

            if(parts.length < 8) {
                errors.push(`Baris ${baris}: Format kurang dari 8 kolom.`);
                return;
            }

            const start = parseFloat(parts[3]);
            const duration = parseFloat(parts[4]);

            if(isNaN(start) || isNaN(duration)) {
                errors.push(`Baris ${baris}: Teks di kolom angka waktu/durasi.`);
                return;
            }

            const end = start + duration;
            const speaker = parts[7];

            // Cek Tahap 3
            if(duration <= 0) errors.push(`Baris ${baris}: Durasi negatif/nol.`);
            else if(duration < MIN_DURATION) errors.push(`Baris ${baris}: Micro-segment (< 0.2s).`);
            if(duration > MAX_DURATION) errors.push(`Baris ${baris}: Segmen terlalu panjang (> 30s).`);

            if (!speakerSegments[speaker]) speakerSegments[speaker] = [];
            speakerSegments[speaker].forEach(prev => {
                if ((start < prev.end) && (end > prev.start)) {
                    errors.push(`Baris ${baris}: Self-overlap pada ${speaker} dgn baris ${prev.baris}.`);
                }
            });
            speakerSegments[speaker].push({start: start, end: end, baris: baris});

            /*const regexNomenklatur = /^SPEAKER_\d+$/;
            if(!regexNomenklatur.test(speaker)) {
                errors.push(`Baris ${baris}: Nomenklatur "${speaker}" salah. (Harus SPEAKER_XX)`);
            }*/

            // Jika tidak error, simpan untuk metadata (menggabungkan fungsi tahap 4 yang asli)
            uniqueSpeakers.add(speaker);
            if (end > maxEndTime) {
                maxEndTime = end;
            }
        });

        // Keputusan
        if(errors.length > 0) {
            let errorHtml = '<strong>❌ RTTM Ditolak (Gagal Validasi Tahap 3):</strong><ul class="mb-0 ps-3">';
            // Batasi tampilan max 5 error agar tidak memenuhi layar
            errors.slice(0, 5).forEach(err => errorHtml += `<li>${err}</li>`);
            if(errors.length > 5) errorHtml += `<li><em>...dan ${errors.length - 5} error lainnya.</em></li>`;
            errorHtml += '</ul><div class="mt-1 small">Silakan perbaiki RTTM Anda kembali.</div>';
            
            rttmError.innerHTML = errorHtml;
            rttmError.classList.remove('d-none');
            // Nilai isRttmValid tetap false
        } else {
            // RTTM Valid, Lanjutkan eksekusi UI untuk Metadata Gender
            isRttmValid = true;
            rttmError.classList.add('d-none');

            const speakersArray = Array.from(uniqueSpeakers).sort();
            
            //document.getElementById('labelDurasi').innerText = maxEndTime.toFixed(2) + ' detik';
            document.getElementById('labelJumlahSpeaker').innerText = speakersArray.length;
            //document.getElementById('inputDurasi').value = maxEndTime.toFixed(2);
            document.getElementById('inputJumlahSpeaker').value = speakersArray.length;

            genderInputs.innerHTML = '';
            speakersArray.forEach(spk => {
                genderInputs.innerHTML += `
                    <div class="row align-items-center mb-2">
                        <div class="col-4 text-end fw-bold">${spk}</div>
                        <div class="col-8">
                            <select class="form-select form-select-sm" name="gender[${spk}]" required>
                                <option value="" selected disabled>Pilih Gender...</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                `;
            });

            metadataContainer.classList.remove('d-none');
            checkFormValidity();
        }
    };
    reader.readAsText(file);
});

// Submit Form (Upload)
document.getElementById('formSubmit').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    const alertArea = document.getElementById('alertArea');
    
    // Double check sebelum dikirim (just in case)
    if(!isWavValid || !isRttmValid) {
        alert("Mohon pastikan format audio dan RTTM sudah valid!");
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Mengunggah File... (Mohon Tunggu)';
    alertArea.innerHTML = '';

    const formData = new FormData(this);

    fetch('proses_tahap4.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById('formSubmit').reset();
            document.getElementById('metadataContainer').classList.add('d-none');
            
            // Reset state validasi
            isWavValid = false;
            isRttmValid = false;
            document.getElementById('wavError').classList.add('d-none');
            checkFormValidity();

            alertArea.innerHTML = `
                <div class="alert alert-success fw-bold text-center border-0 shadow-sm">
                    ✅ Berhasil! Dataset telah diunggah dengan kode file: <strong>${data.kode_file}</strong>. File Anda masuk ke antrean Audit Silang.
                </div>
            `;
            btn.innerHTML = '🚀 Submit & Unggah Dataset';
        } else {
            alertArea.innerHTML = `<div class="alert alert-danger border-0 shadow-sm">❌ Gagal: ${data.message}</div>`;
            btn.disabled = false;
            btn.innerHTML = '🚀 Submit & Unggah Dataset';
        }
    })
    .catch(error => {
        alertArea.innerHTML = `<div class="alert alert-danger border-0 shadow-sm">❌ Error sistem.</div>`;
        btn.disabled = false;
        btn.innerHTML = '🚀 Submit & Unggah Dataset';
        console.error(error);
    });
});
</script>
</body>
</html>