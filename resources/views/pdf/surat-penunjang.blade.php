<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: black; line-height: 1.15; margin: 0; padding: 0; }
        @page { margin: 1cm 2cm; }

        /* KOP SURAT - Dibuat lebih formal & presisi */
        .kop-table { width: 100%; border-bottom: 2px solid black; border-top: none; padding-bottom: 2px; margin-bottom: 2px; }
        .kop-border-bottom { border-bottom: 1px solid black; width: 100%; margin-bottom: 10px; }
        
        .logo-undip { width: 75px; }
        .header-text { text-align: center; vertical-align: middle; }
        .header-text h3 { font-size: 13pt; margin: 0; padding: 0; font-weight: bold; }
        .header-text h2 { font-size: 15pt; margin: 0; padding: 0; font-weight: bold; }
        .header-text p { font-size: 9pt; margin: 0; padding: 0; }

        /* JUDUL SURAT */
        .title-section { text-align: center; margin-top: 15px; }
        .title-section h3 { text-decoration: underline; margin-bottom: 0; font-weight: bold; }
        .title-section p { margin-top: 2px; font-weight: normal; }

        /* ISI SURAT */
        .content { margin-top: 20px; text-align: justify; }
        .identity-table { margin-left: 30px; margin-top: 10px; margin-bottom: 10px; width: 100%; }
        .identity-table td { vertical-align: top; padding: 1px 0; }

        /* FOOTER / TTD */
        .footer-container { margin-top: 30px; width: 100%; }
        .ttd-box { float: right; width: 300px; text-align: left; }
        /* Ukuran TTD disesuaikan agar Nama & NIP di dalam gambar terlihat jelas */
        .ttd-image-file { width: 220px; height: auto; margin-top: 5px; }
    </style>
</head>
<body>
    <table class="kop-table">
        <tr>
            <td width="15%" style="text-align: left;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo.png'))) }}" class="logo-undip">
            </td>
            <td width="85%" class="header-text">
                <h3>KEMENTRIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h3>
                <h2>UNIVERSITAS DIPONEGORO</h2>
                <h3>FAKULTAS HUKUM</h3>
                <p>Jalan dr. Antonius Suroyo Kampus Universitas Diponegoro Tembalang Semarang Kode Pos 50275</p>
                <p>Tel. (024) 76918201 Faks. (024) 76918206 www.fh.undip.ac.id email: fh@live.undip.ac.id</p>
            </td>
        </tr>
    </table>
    <div class="kop-border-bottom"></div>

    <div class="title-section">
        <h3>SURAT TUGAS</h3>
        <p>Nomor: {{ $surat->nomor_surat }}</p>
    </div>

    <div class="content">
        <p>Pimpinan Fakultas Hukum Universitas Diponegoro dengan ini memberikan tugas kepada :</p>
        
        <table class="identity-table">
            <tr>
                <td width="100">Nama</td>
                <td width="10">:</td>
                <td><strong>{{ $surat->user->name }}</strong></td>
            </tr>
            <tr>
                <td>NIP</td>
                <td>:</td>
                <td>{{ $surat->user->profile->nip ?? '-' }}</td>
            </tr>
            <tr>
                <td>Pangkat/Gol</td>
                <td>:</td>
                <td>{{ $surat->user->profile->golongan ?? '..........................' }}</td>
            </tr>
        </table>

        <p>dalam rangka mengikuti kegiatan <strong>{{ $surat->keteranganEssai->kolom_1 }}</strong>, yang dilaksanakan pada tanggal <strong>{{ \Carbon\Carbon::parse($surat->keteranganEssai->kolom_2)->translatedFormat('d F Y') }}</strong>.</p>
        
        <p>Demikian agar dilaksanakan dengan sebaik-baiknya dan penuh tanggung jawab.</p>
    </div>

    <div class="footer-container">
        <div class="ttd-box">
            <p>Dekan,</p>
            <div class="ttd-content">
                @php
                    // Ambil file langsung dari folder public/images/ sesuai permintaan lo
                    $ttdPath = public_path('images/ttd_digital.png');
                    $ttdBase64 = file_exists($ttdPath) ? base64_encode(file_get_contents($ttdPath)) : null;
                @endphp

                @if($ttdBase64)
                    {{-- Gambar ini sudah include Nama dan NIP Dekan --}}
                    <img src="data:image/png;base64,{{ $ttdBase64 }}" class="ttd-image-file">
                @else
                    <p style="color: red; font-size: 8pt; margin-top: 20px;">(File ttd_digital.png tidak ditemukan di public/images/)</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>