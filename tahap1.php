<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tahap 1: Klaim URL - Diarization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Diarization Dataset</a>
        <span class="navbar-text text-white">Tahap 1: Klaim URL Video</span>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h4 class="mb-3">Kunci (Lock) URL YouTube Shorts</h4>
                    <p class="text-muted">Pastikan URL belum diklaim oleh mahasiswa lain. Jika berhasil, sistem akan mengenerate 8 digit Kode Klaim.</p>
                    
                    <form id="formKlaim">
                        <div class="mb-3">
                            <label for="url" class="form-label fw-bold">URL YouTube Shorts</label>
                            <input type="url" class="form-control" id="url" name="url" placeholder="Contoh: https://youtube.com/shorts/EGVBnweFp4I?si=B1-F4owbXo6QSma2" required>
                            <div class="form-text">Tempel (paste) URL secara utuh. Sistem akan otomatis mengenali Kode Video.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="kategori" class="form-label fw-bold">Kategori Video</label>
                            <select class="form-select" id="kategori" name="kategori" required>
                                <option value="" selected disabled>-- Pilih Kategori --</option>
                                <option value="podcast">Podcast</option>
                                <option value="talkshow">Talkshow</option>
                                <option value="entertainment">Entertainment</option>
                                <option value="movie_drama">Movie & Drama</option>
                            </select>
                        </div>
                        
                        <button type="submit" id="btnSubmit" class="btn btn-primary w-100 fw-bold py-2">Proses Klaim Video</button>
                    </form>
                </div>
            </div>
            
            <!-- Tempat menampilkan hasil AJAX -->
            <div id="resultArea"></div>
            
        </div>
    </div>
</div>

<script>
document.getElementById('formKlaim').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btnSubmit = document.getElementById('btnSubmit');
    const resultArea = document.getElementById('resultArea');
    
    // UI State: Loading
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Memproses...';
    resultArea.innerHTML = '';
    
    const formData = new FormData(this);
    
    // Fetch API Vanilla JS
    fetch('proses_tahap1.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = 'Proses Klaim Video';
        
        if (data.status === 'success') {
            resultArea.innerHTML = `
                <div class="alert alert-success shadow-sm border-0 text-center">
                    <h4 class="alert-heading fw-bold mb-3">✅ Klaim Berhasil!</h4>
                    <p class="mb-1">Sistem mengenali Video ID: <code class="fs-5 bg-white px-2 py-1 rounded">${data.youtube_id}</code></p>
                    <hr>
                    <p class="mb-2">Gunakan <strong>Kode Konfirmasi</strong> di bawah ini untuk klaim di <strong>Tahap 4</strong>:</p>
                    <div class="bg-white border rounded p-3 my-3">
                        <span class="display-3 fw-bold text-primary font-monospace" style="letter-spacing: 5px;">${data.kode_klaim}</span>
                    </div>
                    <p class="text-danger small mb-0"><i class="bi bi-exclamation-triangle"></i> Simpan kode ini baik-baik! Anda tidak akan bisa melakukan submit jika kehilangan kode ini.</p>
                </div>
            `;
            // Reset form
            document.getElementById('formKlaim').reset();
        } else {
            resultArea.innerHTML = `
                <div class="alert alert-danger shadow-sm border-0">
                    <strong>❌ Gagal:</strong> ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = 'Proses Klaim Video';
        resultArea.innerHTML = `
            <div class="alert alert-danger shadow-sm border-0">
                <strong>❌ Error:</strong> Terjadi kesalahan komunikasi dengan server.
            </div>
        `;
        console.error('Error:', error);
    });
});
</script>
</body>
</html>