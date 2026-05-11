<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tahap 2: RTTM Editor - Diarization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Menggunakan style bawaan dengan penyesuaian */
        #waveform-container { position: relative; border: 1px solid #ccc; background: #fafafa; border-radius: 4px; cursor: crosshair; }
        #timeline { margin-bottom: 30px; }
        .viz-container { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 20px; overflow-x: hidden; }
        
        /* Layout Track RTTM */
        .channel-label { font-weight: bold; font-size: 13px; text-align: right; padding-right: 15px; }
        .channel-track { position: relative; height: 35px; border-bottom: 1px dashed #ccc; margin-bottom: 8px; width: 100%; }
        .rttm-segment { position: absolute; height: 100%; top: 0; opacity: 0.8; border-radius: 12px; transition: opacity 0.2s; }
        .rttm-segment:hover { opacity: 1; z-index: 10; }
        
        /* Garis Playhead RTTM */
        #rttm-playhead { 
            position: absolute; 
            top: 0; 
            bottom: 0; 
            width: 2px; 
            background-color: #dc3545; 
            z-index: 10; 
            left: 0%;
            pointer-events: none; 
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
        }

        /* Tabel dan Lainnya */
        .table-scroll-container { max-height: 400px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 4px; }
        th { background-color: #0d6efd; color: white; position: sticky; top: 0; z-index: 2; outline: 1px solid #0d6efd; text-align: center; }
        td { text-align: center; vertical-align: middle; }
        .active-row { background-color: #e0f7fa !important; }
        .track-header { font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; color: #2c3e50; border-bottom: 2px solid #0d6efd; display: inline-block; }
        .btn-add { background-color: #28a745; color: white; }
        .btn-add:hover { background-color: #218838; }
        .btn-delete { background-color: #dc3545; color: white; }
        .btn-delete:hover { background-color: #c82333; }
        
        .instructions { background: #e9ecef; padding: 10px 15px; border-left: 4px solid #00c8b6; border-radius: 4px; font-size: 14px; margin-bottom: 20px; }
        code { background: #fff; padding: 2px 6px; border-radius: 4px; font-weight: bold; color: #d63384; }
        .btn-sm { padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">Diarization Dataset</a>
        <span class="navbar-text text-white">Tahap 2: Editor Anotasi Audio</span>
    </div>
</nav>

<div class="container mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div class="alert alert-info border-0 shadow-sm border-start border-info border-4">
                <strong>Cara Penggunaan:</strong> Tekan <code>Spasi</code> untuk Play/Pause. Hover pada gelombang suara untuk melihat *timestamp* presisi (0.001s). Klik dua kali pada gelombang untuk meng-copy *timestamp*.
            </div>

            <div class="row mb-4 align-items-end g-3">
                <div class="col">
                    <label for="audio-upload" class="form-label fw-bold">1. Upload Audio (.wav)</label>
                    <input type="file" class="form-control" id="audio-upload" accept="audio/wav">
                </div>
                <div class="col">
                    <label for="rttm-upload" class="form-label fw-bold">2. Upload RTTM (.rttm)</label>
                    <input type="file" class="form-control" id="rttm-upload" accept=".rttm,.txt">
                </div>
            </div>

            <!-- Waveform UI -->
            <div class="track-header">RTTM</div>
            <div class="viz-container" id="track-edited">
                <div style="text-align: center; color: #888; font-style: italic;">Edited outputs will appear here...</div>
            </div>
            
            <div class="track-header">Audio Analysed</div>
            <div id="waveform-container"></div>
            <div id="timeline"></div>

            <!-- Table Editor -->
            <div class="track-header">Tabel Editor RTTM</div>
            <div class="table-scroll-container">
                <table class="table table-bordered table-hover mb-0" id="rttm-table">
                    <thead>
                        <tr>
                            <th width="15%">Mulai (s)</th>
                            <th width="15%">Selesai (s)</th>
                            <th width="15%">Durasi (s)</th>
                            <th width="30%">Nama Speaker</th>
                            <th width="25%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="rttm-tbody">
                        <tr><td colspan="5" class="text-muted">Data kosong. Silakan upload RTTM atau tambah baris awal.</td></tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    
    <div class="text-end mt-4">
        <button class="btn btn-primary w-100 fw-bold mb-2" id="export-btn">⬇️ Export RTTM</button>
        
        <a href="tahap3.php" class="btn btn-success btn-lg px-5 shadow-sm">Lanjut ke Tahap 3 (Validasi) ➡️</a>
    </div>
</div>

<script type="module">
    import WaveSurfer from 'https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.esm.js';
    import Timeline from 'https://unpkg.com/wavesurfer.js@7/dist/plugins/timeline.esm.js';
    import Hover from 'https://unpkg.com/wavesurfer.js@7/dist/plugins/hover.esm.js';

    // State Variables
    let originalData = [];
    let editedData = [];
    let audioDuration = 0;
    let fileName = "edited";

    // Palette
    const colors = ['#00a8e8', '#e83131', '#28a745', '#ffc107', '#9c27b0', '#ff5722', '#3f51b5', '#009688'];
    
    function getSpeakerColor(speakerName) {
        let hash = 0;
        for (let i = 0; i < speakerName.length; i++) { hash = speakerName.charCodeAt(i) + ((hash << 5) - hash); }
        return colors[Math.abs(hash) % colors.length];
    }

    // Initialize WaveSurfer
    const wavesurfer = WaveSurfer.create({
        container: '#waveform-container',
        waveColor: '#a1c4fd',
        progressColor: '#00c8b6',
        cursorColor: '#333',
        minPxPerSec: 50,
        interact: true,
        plugins: [
            Timeline.create({
                container: '#timeline', timeInterval: 1, primaryLabelInterval: 5, secondaryLabelInterval: 1, formatTimeCallback: (sec) => sec.toFixed(1)
            }),
            Hover.create({
                lineColor: '#ff0000', lineWidth: 2, labelBackground: '#2c3e50', labelColor: '#fff', labelSize: '12px', formatTimeCallback: (sec) => sec.toFixed(3) + ' s'
            })
        ]
    });

    // Handle Audio File Upload
    document.getElementById('audio-upload').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            fileName = file.name.split('.')[0];
            const url = URL.createObjectURL(file);
            wavesurfer.load(url);
        }
    });

    wavesurfer.on('ready', () => {
        audioDuration = wavesurfer.getDuration();
        renderMultiChannelTrack('track-edited', editedData);
        // Render tabel ulang untuk mengaktifkan tombol tambah baris setelah audio dimuat
        renderTable(); 
    });

    // Handle RTTM File Upload
    document.getElementById('rttm-upload').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            const text = event.target.result;
            originalData = parseRTTM(text);
            editedData = JSON.parse(JSON.stringify(originalData));
            
            if (audioDuration > 0) {
                renderMultiChannelTrack('track-edited', editedData);
            }
            renderTable();
        };
        reader.readAsText(file);
    });

    // RTTM Parser
    function parseRTTM(rttmText) {
        const lines = rttmText.split('\n');
        const data = [];
        lines.forEach(line => {
            const parts = line.trim().split(/\s+/);
            if (parts[0] === 'SPEAKER' && parts.length >= 8) {
                const start = parseFloat(parts[3]);
                const duration = parseFloat(parts[4]);
                data.push({ id: crypto.randomUUID(), start: start, end: start + duration, speaker: parts[7] });
            }
        });
        return data.sort((a, b) => a.start - b.start);
    }

    // Render Multi-Channel Visualization (dengan Playhead tersinkron)
    function renderMultiChannelTrack(containerId, data) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        if (audioDuration === 0 || data.length === 0) {
            container.innerHTML = '<div style="text-align: center; color: #888; font-style: italic;">Edited outputs will appear here...</div>';
            return;
        }

        const uniqueSpeakers = [...new Set(data.map(d => d.speaker))].sort();

        // Flex wrapper
        const wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'stretch';

        // Kolom Label
        const labelsCol = document.createElement('div');
        labelsCol.style.width = '120px';
        labelsCol.style.paddingRight = '15px';
        labelsCol.style.flexShrink = '0';

        // Kolom Track
        const tracksCol = document.createElement('div');
        tracksCol.style.flex = '1';
        tracksCol.style.position = 'relative'; 
        tracksCol.style.cursor = 'pointer'; 

        uniqueSpeakers.forEach(speaker => {
            // Label
            const labelDiv = document.createElement('div');
            labelDiv.className = 'channel-label';
            labelDiv.style.color = getSpeakerColor(speaker);
            labelDiv.style.height = '35px';
            labelDiv.style.lineHeight = '35px';
            labelDiv.style.marginBottom = '8px';
            labelDiv.innerText = speaker;
            labelsCol.appendChild(labelDiv);

            // Track Container
            const trackDiv = document.createElement('div');
            trackDiv.className = 'channel-track';

            data.filter(d => d.speaker === speaker).forEach(seg => {
                const startPct = (seg.start / audioDuration) * 100;
                const widthPct = ((seg.end - seg.start) / audioDuration) * 100;

                const segDiv = document.createElement('div');
                segDiv.className = 'rttm-segment';
                segDiv.style.left = `${startPct}%`;
                segDiv.style.width = `${widthPct}%`;
                segDiv.style.backgroundColor = getSpeakerColor(speaker);
                segDiv.title = `${speaker}: ${seg.start.toFixed(3)}s - ${seg.end.toFixed(3)}s`;
                
                trackDiv.appendChild(segDiv);
            });
            tracksCol.appendChild(trackDiv);
        });

        // Garis Playhead
        const playhead = document.createElement('div');
        playhead.id = 'rttm-playhead';
        tracksCol.appendChild(playhead);

        // Click-to-Seek pada area visualisasi RTTM
        tracksCol.addEventListener('click', (e) => {
            const rect = tracksCol.getBoundingClientRect();
            const percentage = (e.clientX - rect.left) / rect.width;
            const targetTime = percentage * audioDuration;
            wavesurfer.setTime(targetTime);
            wavesurfer.play();
        });

        wrapper.appendChild(labelsCol);
        wrapper.appendChild(tracksCol);
        container.appendChild(wrapper);
    }

    // Table Generation & Listeners
    const tbody = document.getElementById('rttm-tbody');

    function renderTable() {
        tbody.innerHTML = '';
        if (editedData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-muted">Data kosong. Silakan upload RTTM atau tambah baris manual (membutuhkan file WAV).</td></tr>`;
            
            const trInit = document.createElement('tr');
            // Cek apakah audio sudah diload untuk mengaktifkan tombol
            const isAudioReady = audioDuration > 0;
            const btnState = isAudioReady ? '' : 'disabled';
            const btnClass = isAudioReady ? 'btn-primary' : 'btn-secondary';
            const btnText = isAudioReady ? '➕ Tambah Baris Awal' : 'Upload Audio (.wav) Terlebih Dahulu';

            trInit.innerHTML = `<td colspan="5"><button class="btn ${btnClass} fw-bold" id="init-row" ${btnState}>${btnText}</button></td>`;
            tbody.appendChild(trInit);
            
            const initBtn = document.getElementById('init-row');
            if (initBtn && isAudioReady) {
                initBtn.addEventListener('click', () => {
                    editedData.push({ id: crypto.randomUUID(), start: 0.000, end: Math.min(2.000, audioDuration), speaker: 'SPEAKER_00' });
                    renderTable();
                    renderMultiChannelTrack('track-edited', editedData);
                });
            }
            return;
        }

        editedData.forEach((segment, index) => {
            const tr = document.createElement('tr');
            tr.dataset.index = index;
            
            tr.innerHTML = `
                <td><input type="number" step="0.001" class="edit-start form-control form-control-sm text-center" value="${segment.start.toFixed(3)}"></td>
                <td><input type="number" step="0.001" class="edit-end form-control form-control-sm text-center" value="${segment.end.toFixed(3)}"></td>
                <td class="duration-cell" style="font-weight: bold;">${(segment.end - segment.start).toFixed(3)}</td>
                <td><input type="text" class="edit-speaker form-control form-control-sm text-center" value="${segment.speaker}"></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-sm btn-add add-above" title="Insert row above">↑ Above</button>
                        <button class="btn-sm btn-add add-below" title="Insert row below">↓ Below</button>
                        <button class="btn-sm btn-delete delete-row" title="Delete row">✕ Del</button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        attachTableListeners();
    }

    function attachTableListeners() {
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach((tr) => {
            const index = parseInt(tr.dataset.index);
            if (isNaN(index)) return;

            const startInput = tr.querySelector('.edit-start');
            const endInput = tr.querySelector('.edit-end');
            const speakerInput = tr.querySelector('.edit-speaker');
            const durationCell = tr.querySelector('.duration-cell');

            const updateData = () => {
                let s = parseFloat(startInput.value) || 0;
                let e = parseFloat(endInput.value) || 0;
                if (e < s) e = s;
                
                editedData[index].start = s;
                editedData[index].end = e;
                editedData[index].speaker = speakerInput.value;
                
                durationCell.innerText = (e - s).toFixed(3);
                renderMultiChannelTrack('track-edited', editedData);
            };

            startInput.addEventListener('input', updateData);
            endInput.addEventListener('input', updateData);
            speakerInput.addEventListener('input', updateData);

            tr.querySelector('.add-above').addEventListener('click', () => {
                let newStart = index > 0 ? editedData[index - 1].end : 0;
                editedData.splice(index, 0, { id: crypto.randomUUID(), start: newStart, end: newStart + 1.0, speaker: 'SPEAKER_00' });
                renderTable();
                renderMultiChannelTrack('track-edited', editedData);
            });

            tr.querySelector('.add-below').addEventListener('click', () => {
                let newStart = editedData[index].end;
                editedData.splice(index + 1, 0, { id: crypto.randomUUID(), start: newStart, end: newStart + 1.0, speaker: 'SPEAKER_00' });
                renderTable();
                renderMultiChannelTrack('track-edited', editedData);
            });

            tr.querySelector('.delete-row').addEventListener('click', () => {
                editedData.splice(index, 1);
                renderTable();
                renderMultiChannelTrack('track-edited', editedData);
            });
        });
    }

    // SINKRONISASI PLAYBACK WAVESURFER
    wavesurfer.on('timeupdate', (currentTime) => {
        // 1. Highlight baris tabel aktif
        const rows = tbody.querySelectorAll('tr');
        editedData.forEach((segment, index) => {
            if (rows[index]) {
                if (currentTime >= segment.start && currentTime <= segment.end) {
                    rows[index].classList.add('active-row');
                } else {
                    rows[index].classList.remove('active-row');
                }
            }
        });

        // 2. Gerakkan Playhead merah di RTTM Visualizer
        const playhead = document.getElementById('rttm-playhead');
        if (playhead && audioDuration > 0) {
            const pct = (currentTime / audioDuration) * 100;
            playhead.style.left = `${pct}%`;
        }
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        if (e.code === 'Space') { e.preventDefault(); wavesurfer.playPause(); }
        else if (e.code === 'ArrowRight') { e.preventDefault(); wavesurfer.skip(5); }
        else if (e.code === 'ArrowLeft') { e.preventDefault(); wavesurfer.skip(-5); }
    });

    // Export
    document.getElementById('export-btn').addEventListener('click', () => {
        if (editedData.length === 0) return alert("Data RTTM masih kosong!");
        let output = "";
        editedData.forEach(seg => {
            const duration = (seg.end - seg.start).toFixed(3);
            const start = seg.start.toFixed(3);
            output += `SPEAKER ${fileName} 1 ${start} ${duration} <NA> <NA> ${seg.speaker} <NA> <NA>\n`;
        });
        const blob = new Blob([output], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${fileName}_edited.rttm`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    // Double Click untuk Copy Time
    document.getElementById('waveform-container').addEventListener('dblclick', () => {
        const preciseTime = wavesurfer.getCurrentTime().toFixed(3);
        navigator.clipboard.writeText(preciseTime).then(() => {
            const toast = document.createElement('div');
            toast.innerText = `⏱ Time ${preciseTime}s copied!`;
            toast.style.cssText = `position: fixed; bottom: 30px; right: 30px; background-color: #00c8b6; color: white; padding: 12px 20px; border-radius: 6px; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.2); z-index: 9999; transition: opacity 0.3s ease-out;`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 2000);
        });
    });

    // Inisialisasi awal (agar tabel menampilkan status kosong di awal)
    renderTable();
</script>
</body>
</html>