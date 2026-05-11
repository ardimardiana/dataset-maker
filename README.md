# Dataset Maker 🎧

**Dataset Maker** adalah platform berbasis web yang dirancang untuk memfasilitasi pengumpulan, pengolahan, dan audit dataset (terutama dataset audio/suara) secara terstruktur. Sistem ini membagi proses kerja menjadi 4 tahap utama untuk memastikan kualitas data yang dihasilkan sebelum masuk ke tahap audit final.

## 🚀 Fitur Utama

### 👤 Fitur Mahasiswa (Kontributor)
- **Multi-Stage Workflow**: Proses pengumpulan data yang dibagi menjadi 4 tahap (Tahap 1 - Tahap 4).
- **Claim System**: Mahasiswa dapat mengklaim dataset menggunakan kode unik.
- **Cek Status Dataset**: Fitur pencarian cepat menggunakan `kode_klaim` untuk melihat status progres, apakah dataset diterima (Approved), perlu perbaikan, atau masih dalam antrean.

### 🛡️ Fitur Admin & Auditor
- **Dashboard Admin**: Ringkasan statistik total dataset yang masuk.
- **Playback & Check**: Kemampuan untuk memutar file audio langsung dari dashboard dan memeriksa file RTTM/teks terkait.
- **Sistem Audit**: Melakukan review (Pass/Fail) terhadap dataset yang dikirimkan disertai dengan kolom komentar/masukan.
- **Rekapitulasi Otomatis**:
    - **Rekap Submit**: Daftar mahasiswa beserta total file yang diunggah dan total durasi audio.
    - **Rekap Audit**: Monitoring produktivitas auditor dalam memeriksa dataset.

## 🛠️ Teknologi yang Digunakan
- **Backend**: PHP 7.4+ (Native/Custom Logic)
- **Database**: MySQL (PDO Connection)
- **Frontend**: Bootstrap 5, JavaScript (Vanilla & AJAX)
- **Audio Engine**: HTML5 Audio API untuk fitur playback.

## 📁 Struktur Folder
```text
dataset-maker/
├── admin_dashboard.php   # Halaman utama kendali admin
├── audit.php             # Antarmuka proses audit dataset
├── cek_status.php        # Fitur pencarian status untuk mahasiswa
├── config.php            # Konfigurasi database (PDO)
├── index.php             # Halaman utama & Panduan
├── tahap1.php - tahap4.php # Alur kerja pengumpulan dataset
└── uploads/              # Penyimpanan file audio (wav) dan metadata (rttm)
```

## 📖 Alur Kerja (Workflow)
- **Tahap 1-4**: Mahasiswa menyelesaikan tugas pengolahan dataset sesuai instruksi di setiap tahap.
- **Submit**: Setelah tahap 4 selesai, data akan muncul di Dashboard Admin.
- **Audit**: Admin/Auditor memeriksa kualitas audio dan label. Jika sesuai, status diubah menjadi Approved.
- **Monitoring**: Admin memantau total durasi yang terkumpul melalui fitur Rekap Submit untuk keperluan pelaporan atau pemberian insentif/nilai kepada mahasiswa.

## ✍️ Kontributor
Ardi Mardiana - Universitas Majalengka.

## Dataset
https://huggingface.co/datasets/maleo-ai/maleo-short

## Citation
**BibTeX:**

```bibtex
@article{MardianaMaleoShort2026,
  author  = {Mardiana, Ardi and Muslimah, Dinda Desmonda and Bastian, Ade and Irawan, Eka Tresna},
  title   = {Maleo-Short: An "In-the-Wild" Indonesian Dataset for Speaker Diarization},
  journal = {Jurnal Online Informatika},
  year    = {2026},
  volume  = {11},
  number  = {1},
  pages   = {27-37},
  doi     = {10.15575/join.v11i1.1781},
  url     = {https://join.if.uinsgd.ac.id/index.php/join/article/view/1781}
}
```