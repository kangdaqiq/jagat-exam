<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Hadir Peserta - {{ $meta['nama_ruang'] }} - {{ $meta['nama_ujian'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
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
            font-size: 12px;
            line-height: 1.35;
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
            padding: 15mm 20mm;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            border-radius: 4px;
            position: relative;
        }
        .header-container {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            position: relative;
        }
        .header-logo {
            width: 80px;
            flex-shrink: 0;
            text-align: left;
        }
        .header-logo img {
            width: 68px;
            height: auto;
            object-fit: contain;
        }
        .header-text {
            flex: 1;
            text-align: center;
            padding-right: 80px; /* Balance logo */
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
            margin-top: 2px;
            text-transform: uppercase;
        }
        .title-year {
            font-size: 13px;
            font-weight: bold;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 14px;
            font-size: 11px;
        }
        .meta-table td {
            padding: 3px 2px;
            vertical-align: bottom;
        }
        .underline-val {
            border-bottom: 1px solid #000;
            font-weight: bold;
            padding-left: 4px !important;
        }
        .underline-inline {
            border-bottom: 1px solid #000;
            font-weight: bold;
            padding: 0 6px;
            display: inline-block;
        }
        table.hadir-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 4px;
        }
        table.hadir-table th, table.hadir-table td {
            border: 1px solid #000;
            padding: 5px 6px;
        }
        table.hadir-table th {
            background-color: #d1d5db;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }
        .ttd-cell {
            font-size: 10.5px;
            height: 28px;
            vertical-align: middle;
        }
        .footer-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 14px;
            page-break-inside: avoid;
            font-size: 11px;
        }
        .footer-left {
            width: 58%;
        }
        .footer-right {
            width: 38%;
            text-align: center;
        }
        .notes-title {
            font-weight: bold;
            font-style: italic;
            margin-bottom: 2px;
        }
        .notes-list {
            margin-left: 18px;
            margin-bottom: 10px;
            font-size: 10.5px;
            line-height: 1.3;
        }
        .stats-box-table {
            width: 100%;
            border: 1.5px solid #000;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .stats-box-table td {
            padding: 3px 6px;
            border: none;
        }
        .ttd-grid {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
        }
        .ttd-col {
            text-align: center;
            font-size: 11px;
        }
        .ttd-space {
            height: 55px;
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
                padding: 10mm 15mm;
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
            <div style="font-weight: 800; font-size: 14px;">📋 CETAK DAFTAR HADIR PESERTA RUANGAN</div>
            <div style="font-size: 11px; color: #cbd5e1;">
                {{ $meta['nama_ruang'] }} &bull; {{ $meta['nama_ujian'] }} &bull; Dicetak sebelum ujian dimulai
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            @if(!empty($daftarJadwal) && $daftarJadwal->count() > 0)
                <select onchange="window.location.href=this.value" style="padding: 6px 10px; border-radius: 6px; font-size: 12px; background: #fff; color: #000; font-weight: 600;">
                    <option value="">-- Pilih Sesi / Mapel --</option>
                    @foreach($daftarJadwal as $dj)
                        <option value="{{ route('daftar-hadir.print', array_merge(request()->except('jadwal_ruang_id', 'ujian_id'), ['ruang_ujian_id' => $ruang?->id, 'jadwal_ruang_id' => $dj->id, 'ujian_id' => $dj->ujian_id])) }}" {{ ($jadwalRuang?->id === $dj->id) ? 'selected' : '' }}>
                            {{ $dj->tanggal?->format('d/m/Y') }} &bull; Sesi {{ $dj->sesi_ke }} ({{ substr($dj->waktu_mulai, 0, 5) }}-{{ substr($dj->waktu_selesai, 0, 5) }}): {{ $dj->daftar_nama_ujian }}
                        </option>
                    @endforeach
                </select>
            @endif
            @if(!empty($daftarRuang) && $daftarRuang->count() > 1)
                <select onchange="window.location.href=this.value" style="padding: 6px 10px; border-radius: 6px; font-size: 12px; background: #fff; color: #000; font-weight: 600;">
                    <option value="">-- Ganti Ruangan --</option>
                    @foreach($daftarRuang as $dr)
                        <option value="{{ route('daftar-hadir.print', array_merge(request()->except('ruang_ujian_id', 'jadwal_ruang_id', 'ujian_id', 'id'), ['ruang_ujian_id' => $dr->id])) }}" {{ ($ruang?->id === $dr->id) ? 'selected' : '' }}>
                            {{ $dr->nama_ruang }} ({{ $dr->siswas()->count() }} Siswa)
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

    {{-- HALAMAN DAFTAR HADIR (A4) --}}
    <div class="page">
        {{-- KOP HEADER --}}
        <div class="header-container">
            <div class="header-logo">
                <img src="{{ asset('images/logo/tut-wuri-handayani.png') }}" alt="Tut Wuri Handayani" onerror="this.src='{{ asset('images/logo/logo-sekolah.png') }}'">
            </div>
            <div class="header-text">
                <div class="title-main">DAFTAR HADIR PESERTA</div>
                <div class="title-sub">{{ strtoupper($meta['nama_ujian']) }}</div>
                <div class="title-year">TAHUN {{ $meta['tahun_pelajaran'] }}</div>
            </div>
        </div>

        {{-- METADATA RUANGAN (2 KOLOM RAPI) --}}
        <table class="meta-table">
            <tr>
                <td style="width: 140px; font-weight: bold;">KOTA/KABUPATEN</td>
                <td style="width: 10px;">:</td>
                <td class="underline-val" style="width: 250px;">{{ $meta['kota'] }}</td>
                <td style="width: 70px; padding-left: 20px; font-weight: bold;">KODE</td>
                <td style="width: 10px;">:</td>
                <td class="underline-val">{{ $meta['kode_kota'] }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">SEKOLAH/MADRASAH</td>
                <td>:</td>
                <td class="underline-val">{{ $meta['sekolah'] }}</td>
                <td style="padding-left: 20px; font-weight: bold;">KODE</td>
                <td>:</td>
                <td class="underline-val">{{ $meta['kode_sekolah'] }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">ID SERVER / RUANG</td>
                <td>:</td>
                <td class="underline-val">{{ $meta['id_server'] }} / {{ strtoupper($meta['nama_ruang']) }}</td>
                <td style="padding-left: 20px; font-weight: bold;">SESI</td>
                <td>:</td>
                <td class="underline-val">{{ $meta['sesi'] }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">HARI</td>
                <td>:</td>
                <td class="underline-val">{{ $meta['hari'] }}</td>
                <td colspan="3">
                    <span style="font-weight: bold;">TANGGAL : </span> <span class="underline-inline">{{ $meta['tanggal'] }}</span>
                    <span style="font-weight: bold; margin-left: 15px;">PUKUL : </span> <span class="underline-inline">{{ $meta['pukul'] }}</span>
                </td>
            </tr>
        </table>

        {{-- TABEL DAFTAR HADIR: NOMOR, NIS, NAMA, TTD, KETERANGAN --}}
        <table class="hadir-table">
            <thead>
                <tr>
                    <th style="width: 38px;">No.</th>
                    <th style="width: 120px;">NIS</th>
                    <th>Nama Peserta</th>
                    <th style="width: 230px;">Tanda Tangan</th>
                    <th style="width: 110px;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswas as $idx => $s)
                    @php $num = $idx + 1; @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 600;">{{ $num }}</td>
                        <td style="text-align: center; font-family: monospace; font-size: 11px;">
                            {{ $s->nis ?: ($s->user?->nip ?: '-') }}
                        </td>
                        <td style="font-weight: 600;">{{ $s->nama }}</td>
                        <td class="ttd-cell">
                            @if($num % 2 !== 0)
                                <div style="text-align: left; padding-left: 8px;">
                                    <strong>{{ $num }}.</strong> ....................................
                                </div>
                            @else
                                <div style="text-align: right; padding-right: 8px;">
                                    <strong>{{ $num }}.</strong> ....................................
                                </div>
                            @endif
                        </td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 14px; font-style: italic; color: #64748b;">
                            Belum ada siswa yang ditempatkan di ruangan ini. Silakan atur penempatan peserta pada menu Ruang Ujian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- FOOTER CATATAN & TANDA TANGAN --}}
        <div class="footer-container">
            <div class="footer-left">
                <div class="notes-title">Keterangan :</div>
                <ol class="notes-list">
                    <li>Dibuat rangkap 3 (tiga), masing-masing untuk sekolah, kota/kab dan Provinsi.</li>
                    <li>Pengawas ruang menyilang Nama Peserta yang tidak hadir.</li>
                    <li>Daftar hadir untuk pusat di upload melalui laman ujian.</li>
                </ol>

                <table class="stats-box-table">
                    <tr>
                        <td style="width: 240px;">Jumlah Peserta yang Seharusnya Hadir</td>
                        <td style="width: 10px;">:</td>
                        <td style="text-align: right; font-weight: bold; padding-right: 8px;">{{ $siswas->count() }} peserta</td>
                    </tr>
                    <tr>
                        <td>Jumlah Peserta yang Tidak Hadir</td>
                        <td>:</td>
                        <td style="text-align: right; font-weight: bold; padding-right: 8px;">______ peserta</td>
                    </tr>
                    <tr style="border-top: 1px solid #000;">
                        <td>Jumlah Peserta Hadir</td>
                        <td>:</td>
                        <td style="text-align: right; font-weight: bold; padding-right: 8px;">______ peserta</td>
                    </tr>
                </table>
            </div>

            <div class="footer-right">
                <div class="ttd-grid">
                    <div class="ttd-col">
                        <div style="font-weight: bold;">Proktor</div>
                        <div class="ttd-space"></div>
                        <div>( {{ $meta['nama_proktor'] }} )</div>
                    </div>
                    <div class="ttd-col">
                        <div style="font-weight: bold;">Pengawas</div>
                        <div class="ttd-space"></div>
                        <div>( {{ $meta['nama_pengawas'] }} )</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

