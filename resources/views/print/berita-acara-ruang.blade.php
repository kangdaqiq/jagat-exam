<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Pelaksanaan - {{ $meta['nama_ruang'] }} - {{ $meta['nama_ujian'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background-color: #f1f5f9;
            font-size: 13px;
            line-height: 1.45;
        }
        .top-action-bar {
            max-width: 210mm;
            margin: 16px auto 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: #041F45;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(4, 31, 69, 0.18);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-print {
            background-color: #10b981;
            color: white;
        }
        .btn-print:hover {
            background-color: #059669;
        }
        .btn-secondary {
            background-color: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .btn-secondary:hover {
            background-color: rgba(255,255,255,0.25);
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 16px auto;
            background: white;
            padding: 18mm 20mm;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            border-radius: 4px;
            position: relative;
        }
        .header-container {
            display: flex;
            align-items: flex-start;
            margin-bottom: 24px;
            position: relative;
        }
        .header-logo {
            width: 90px;
            flex-shrink: 0;
            text-align: left;
        }
        .header-logo img {
            width: 72px;
            height: auto;
            object-fit: contain;
        }
        .header-text {
            flex: 1;
            text-align: center;
            padding-right: 90px;
        }
        .title-main {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .title-sub {
            font-size: 14px;
            font-weight: bold;
            margin-top: 3px;
            text-transform: uppercase;
        }
        .title-year {
            font-size: 13px;
            font-weight: bold;
            margin-top: 3px;
            text-transform: uppercase;
        }
        .intro-text {
            text-align: justify;
            margin-bottom: 18px;
            line-height: 1.6;
        }
        .bap-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .bap-num {
            width: 24px;
            font-weight: bold;
            flex-shrink: 0;
        }
        .bap-body {
            flex: 1;
        }
        .bap-table {
            width: 100%;
            border-collapse: collapse;
        }
        .bap-table td {
            padding: 4px 0;
            vertical-align: bottom;
        }
        .bap-table td.bap-lbl {
            width: 250px;
            font-size: 13px;
        }
        .bap-table td.bap-sep {
            width: 15px;
            text-align: center;
        }
        .bap-table td.bap-val {
            border-bottom: 1px solid #000;
            font-weight: bold;
            font-size: 13px;
            padding-left: 6px;
        }
        .notes-box {
            border: 1px solid #000;
            min-height: 90px;
            margin-top: 6px;
            padding: 10px 12px;
            font-size: 12.5px;
            line-height: 1.5;
            background: #fff;
        }
        .ttd-section {
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .ttd-table td {
            padding: 8px 4px;
        }
        .ttd-num {
            width: 24px;
            font-weight: bold;
            vertical-align: top;
        }
        .ttd-role {
            width: 140px;
            font-weight: bold;
            vertical-align: top;
        }
        .ttd-person {
            width: 240px;
            vertical-align: top;
        }
        .person-name {
            font-weight: bold;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 180px;
            padding-bottom: 2px;
        }
        .person-nip {
            font-size: 11.5px;
            margin-top: 3px;
            color: #111;
        }
        .ttd-sign {
            width: 220px;
            vertical-align: middle;
            text-align: left;
        }
        .ttd-sign-line {
            display: inline-flex;
            align-items: flex-end;
            width: 100%;
        }
        .ttd-sign-line span {
            font-weight: bold;
            margin-right: 8px;
        }
        .ttd-sign-line hr {
            flex: 1;
            border: none;
            border-bottom: 1px solid #000;
            margin: 0;
        }
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .page {
                margin: 0 auto;
                padding: 12mm 16mm;
                box-shadow: none;
                width: 100%;
                min-height: auto;
            }
        }
    </style>
</head>
<body>

    {{-- TOOLBAR AKSI CETAK --}}
    <div class="top-action-bar no-print">
        <div>
            <div style="font-weight: 800; font-size: 14px;">📝 BERITA ACARA PELAKSANAAN (BAP)</div>
            <div style="font-size: 11px; color: #cbd5e1;">
                {{ $meta['nama_ruang'] }} &bull; Sesi {{ $meta['sesi'] }} &bull; Dicetak setelah ujian selesai oleh Proktor
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            @if(!empty($daftarJadwal) && $daftarJadwal->count() > 0)
                <select onchange="window.location.href=this.value" style="padding: 6px 10px; border-radius: 6px; font-size: 12px; background: #fff; color: #000; font-weight: 600;">
                    <option value="">-- Pilih Sesi / Mapel --</option>
                    @foreach($daftarJadwal as $dj)
                        <option value="{{ route('berita-acara.print', array_merge(request()->except('jadwal_ruang_id', 'ujian_id', 'id'), ['ruang_ujian_id' => $ruang?->id, 'jadwal_ruang_id' => $dj->id, 'ujian_id' => $dj->ujian_id])) }}" {{ ($jadwalRuang?->id === $dj->id) ? 'selected' : '' }}>
                            Sesi {{ $dj->sesi_ke }} ({{ substr($dj->waktu_mulai, 0, 5) }}-{{ substr($dj->waktu_selesai, 0, 5) }}): {{ $dj->ujian?->nama_ujian }}
                        </option>
                    @endforeach
                </select>
            @endif
            @if(!empty($daftarRuang) && $daftarRuang->count() > 1)
                <select onchange="window.location.href=this.value" style="padding: 6px 10px; border-radius: 6px; font-size: 12px; background: #fff; color: #000; font-weight: 600;">
                    <option value="">-- Ganti Ruangan --</option>
                    @foreach($daftarRuang as $dr)
                        <option value="{{ route('berita-acara.print', array_merge(request()->except('ruang_ujian_id'), ['ruang_ujian_id' => $dr->id])) }}" {{ ($ruang?->id === $dr->id) ? 'selected' : '' }}>
                            {{ $dr->nama_ruang }}
                        </option>
                    @endforeach
                </select>
            @endif
            <button type="button" onclick="window.print()" class="btn btn-print">
                🖨️ Cetak / Simpan PDF
            </button>
            <button type="button" onclick="window.close()" class="btn btn-secondary">
                Tutup
            </button>
        </div>
    </div>

    {{-- HALAMAN BERITA ACARA PELAKSANAAN (A4) --}}
    <div class="page">
        {{-- KOP HEADER --}}
        <div class="header-container">
            <div class="header-logo">
                <img src="{{ asset('images/logo/tut-wuri-handayani.png') }}" alt="Tut Wuri Handayani" onerror="this.src='{{ asset('images/logo/logo-sekolah.png') }}'">
            </div>
            <div class="header-text">
                <div class="title-main">BERITA ACARA PELAKSANAAN</div>
                <div class="title-sub">{{ strtoupper($meta['nama_ujian']) }}</div>
                <div class="title-year">TAHUN {{ $meta['tahun_pelajaran'] }}</div>
            </div>
        </div>

        {{-- PARAGRAF PEMBUKA --}}
        <p class="intro-text">
            Pada hari ini <strong>{{ $meta['hari'] }}</strong> tanggal <strong>{{ $meta['tanggal']->format('d') }}</strong> bulan <strong>{{ $meta['bulan'] }}</strong> tahun <strong>{{ $meta['tahun'] }}</strong>, di <strong>{{ $meta['nama_sekolah'] }}</strong> telah diselenggarakan <strong>{{ $meta['nama_ujian'] }}</strong>, dari pukul <strong>{{ $meta['waktu_mulai'] }}</strong> sampai dengan pukul <strong>{{ $meta['waktu_selesai'] }}</strong>
        </p>

        {{-- 1. DATA PELAKSANAAN --}}
        <div class="bap-row">
            <div class="bap-num">1.</div>
            <div class="bap-body">
                <table class="bap-table">
                    <tr>
                        <td class="bap-lbl">Username</td>
                        <td class="bap-sep">:</td>
                        <td class="bap-val">{{ $meta['username_sekolah'] }}</td>
                    </tr>
                    <tr>
                        <td class="bap-lbl">Sekolah/Madrasah</td>
                        <td class="bap-sep">:</td>
                        <td class="bap-val">{{ $meta['nama_sekolah'] }}</td>
                    </tr>
                    <tr>
                        <td class="bap-lbl">ID Server</td>
                        <td class="bap-sep">:</td>
                        <td class="bap-val">
                            <span style="min-width: 180px; display: inline-block;">{{ $meta['id_server'] }}</span>
                            <span style="margin-left: 20px;">Ruang : <strong>{{ strtoupper($meta['nama_ruang']) }}</strong></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="bap-lbl">Sesi</td>
                        <td class="bap-sep">:</td>
                        <td class="bap-val">{{ $meta['sesi'] }}</td>
                    </tr>
                    <tr>
                        <td class="bap-lbl">Jumlah Peserta Seharusnya</td>
                        <td class="bap-sep">:</td>
                        <td class="bap-val">{{ $meta['jumlah_seharusnya'] }}</td>
                    </tr>
                    <tr>
                        <td class="bap-lbl">Jumlah Hadir (Ikut Ujian)</td>
                        <td class="bap-sep">:</td>
                        <td class="bap-val">{{ $meta['jumlah_hadir'] }}</td>
                    </tr>
                    <tr>
                        <td class="bap-lbl">Jumlah Tidak Mengerjakan</td>
                        <td class="bap-sep">:</td>
                        <td class="bap-val">{{ $meta['jumlah_tidak_hadir'] }}</td>
                    </tr>
                    <tr>
                        <td class="bap-lbl">Username Tidak Mengerjakan</td>
                        <td class="bap-sep">:</td>
                        <td class="bap-val">{{ $meta['username_tidak_hadir'] ?: '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- 2. CATATAN SELAMA TES --}}
        <div class="bap-row" style="margin-top: 14px;">
            <div class="bap-num">2.</div>
            <div class="bap-body">
                <div style="font-weight: 500;">Catatan selama Tes :</div>
                <div class="notes-box">
                    {{ $meta['catatan'] }}
                </div>
            </div>
        </div>

        {{-- 3. YANG MEMBUAT BERITA ACARA (TTD PENGAWAS, KETUA UJIAN, KEPALA SEKOLAH) --}}
        <div class="ttd-section">
            <div style="margin-bottom: 8px;">Yang membuat berita acara :</div>

            <table class="ttd-table">
                <thead>
                    <tr>
                        <th style="width: 24px;"></th>
                        <th style="width: 140px;"></th>
                        <th style="width: 250px;"></th>
                        <th style="text-align: center; font-weight: bold; width: 220px; font-size: 13px;">TTD</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="ttd-num">1.</td>
                        <td class="ttd-role">Pengawas</td>
                        <td class="ttd-person">
                            <div class="person-name">{{ $meta['nama_pengawas'] }}</div>
                            <div class="person-nip">NIP. {{ $meta['nip_pengawas'] ?: '-' }}</div>
                        </td>
                        <td class="ttd-sign">
                            <div class="ttd-sign-line">
                                <span>1.</span>
                                <hr>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ttd-num">2.</td>
                        <td class="ttd-role">Ketua Ujian</td>
                        <td class="ttd-person">
                            <div class="person-name">{{ $meta['nama_ketua_ujian'] }}</div>
                            <div class="person-nip">NIP. {{ $meta['nip_ketua_ujian'] ?: '-' }}</div>
                        </td>
                        <td class="ttd-sign">
                            <div class="ttd-sign-line">
                                <span>2.</span>
                                <hr>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="ttd-num">3.</td>
                        <td class="ttd-role">Kepala Sekolah</td>
                        <td class="ttd-person">
                            <div class="person-name">{{ $meta['nama_kepala_sekolah'] }}</div>
                            <div class="person-nip">NIP. {{ $meta['nip_kepala_sekolah'] ?: '-' }}</div>
                        </td>
                        <td class="ttd-sign">
                            <div class="ttd-sign-line">
                                <span>3.</span>
                                <hr>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>

