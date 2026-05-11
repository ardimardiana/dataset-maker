<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Dataset Diarization</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .hero { background: #0d6efd; color: white; padding: 40px 0; margin-bottom: 30px; border-radius: 0 0 15px 15px; }
    </style>
</head>
<body>

<div class="hero text-center shadow-sm">
    <div class="container">
        <h1 class="display-5 fw-bold">Sistem Manajemen Dataset Diarization</h1>
        <p class="lead">Platform Kurasi dan Anotasi Dataset Akustik (RTTM)</p>
        <p><a href="statistik.php" class="text-decoration-none text-white">📊 Dashboard Statistik</a></p>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <!-- Manual Guide -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white fw-bold">
                    Manual Guide
                </div>
                <div class="card-body">
                    <ol class="list-group list-group-numbered list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-start border-0 pb-1">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Jenis Video Wajib (Pilih salah satu)</div>
                                <ul class="mt-2 text-muted">
                                    <li>a. Podcast</li>
                                    <li>b. Talkshow</li>
                                    <li>c. Entertainment</li>
                                    <li>d. Movie & Drama</li>
                                </ul>
                            </div>
                        </li>
                        <li class="list-group-item border-0 py-1"><span class="ms-2">Video <strong>tidak boleh</strong> berupa video kompilasi.</span></li>
                        <li class="list-group-item border-0 py-1"><span class="ms-2">Minimal pembicara <strong>2 orang</strong>.</span></li>
                        <li class="list-group-item border-0 py-1"><span class="ms-2">Minimal durasi <strong>20 detik</strong>.</span></li>
                    </ol>
                    <div class="alert alert-warning border-start border-4 border-warning mt-4">
                        <strong>Catatan Penting:</strong> Dataset yang dihasilkan diharapkan natural. Kami justru membutuhkan dataset yang <em>"chaos"</em> (mengandung <em>overlap</em>, interupsi, tumpang tindih suara) untuk melatih model AI menghadapi skenario dunia nyata.
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Navigasi -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white fw-bold">
                    Navigasi Tahap
                </div>
                <div class="list-group list-group-flush">
                    <a href="tahap1.php" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                        <span class="badge bg-primary rounded-pill me-3">1</span>
                        <div>
                            <h6 class="mb-0 fw-bold">Klaim URL Video</h6>
                            <small class="text-muted">Kunci URL YouTube Shorts</small>
                        </div>
                    </a>
                    <a href="tahap2.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 ">
                        <span class="badge bg-secondary rounded-pill me-3">2</span>
                        <div>
                            <h6 class="mb-0 fw-bold">Anotasi (Editor RTTM)</h6>
                            <small class="text-muted">Proses Slicing Audio</small>
                        </div>
                    </a>
                    <a href="tahap3.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 ">
                        <span class="badge bg-secondary rounded-pill me-3">3</span>
                        <div>
                            <h6 class="mb-0 fw-bold">Validasi</h6>
                            <small class="text-muted">Sanity Check Komputasi</small>
                        </div>
                    </a>
                    <a href="tahap4.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 ">
                        <span class="badge bg-secondary rounded-pill me-3">4</span>
                        <div>
                            <h6 class="mb-0 fw-bold">Packaging & Submit</h6>
                            <small class="text-muted">Upload WAV & RTTM</small>
                        </div>
                    </a>
                    <a href="audit.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 ">
                        <span class="badge bg-secondary rounded-pill me-3">5</span>
                        <div>
                            <h6 class="mb-0 fw-bold">Audit</h6>
                            <small class="text-muted">Spot-Check/ Audit Silang</small>
                        </div>
                    </a>
                    <!--<a href="tools.php" class="list-group-item list-group-item-action d-flex align-items-center py-3 ">
                        <span class="rounded-pill me-3">🧰</span>
                        <div>
                            <h6 class="mb-0 fw-bold">Tools</h6>
                            <small class="text-muted">Konversi Format Standar</small>
                        </div>
                    </a>-->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>