<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara & Daftar Hadir - {{ $ujian->nama_ujian }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #111827;
            background-color: #f1f5f9;
            font-size: 12px;
            line-height: 1.5;
        }
        .top-action-bar {
            max-width: 210mm;
            margin: 16px auto 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            background: #041F45;
            color: white;
            padding: 12px 20px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(4, 31, 69, 0.18);
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
            background-color: rgba(255,255,255,0.14);
            color: white;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .btn-secondary:hover {
            background-color: rgba(255,255,255,0.22);
        }
        .page {
            max-width: 210mm;
            min-height: 297mm;
            margin: 16px auto;
            background: white;
            padding: 18mm 20mm;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            border-radius: 4px;
        }
        .page-break {
            page-break-before: always;
            break-before: page;
        }
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px double #111827;
            padding-bottom: 12px;
            margin-bottom: 18px;
            gap: 16px;
        }
        .doc-header-center {
            flex: 1;
            text-align: center;
        }
        .doc-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 17px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #041F45;
        }
        .doc-header h2 {
            font-size: 13px;
            font-weight: 700;
            color: #1f2937;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .doc-header p {
            font-size: 11px;
            color: #4b5563;
            margin-top: 2px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 18px;
        }
        .info-table td {
            padding: 5px 8px;
            font-size: 12px;
            vertical-align: top;
        }
        .info-table td.label {
            width: 190px;
            font-weight: 600;
            color: #374151;
        }
        .info-table td.sep {
            width: 15px;
            text-align: center;
        }
        .info-table td.val {
            font-weight: 700;
            color: #111827;
        }
        .stats-box {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 16px 0;
        }
        .stat-card {
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 14px;
            text-align: center;
            background: #f8fafc;
        }
        .stat-card .lbl {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            letter-spacing: 0.04em;
        }
        .stat-card .num {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #041F45;
            margin: 16px 0 8px;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 4px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 10px;
            color: #1e293b;
            text-align: center;
        }
        .sign-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 32px;
            page-break-inside: avoid;
        }
        .sign-box {
            text-align: center;
            font-size: 12px;
        }
        .sign-space {
            height: 68px;
        }
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .page {
                margin: 0;
                padding: 12mm 15mm;
                box-shadow: none;
                max-width: 100%;
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    @php
        $totalTerdaftar = $pesertas->count();
        $pesertaHadir = $pesertas->whereIn('status', ['SEDANG_MENGERJAKAN', 'SELESAI']);
        $pesertaTidakHadir = $pesertas->where('status', 'BELUM_MULAI');
        $jumlahHadir = $pesertaHadir->count();
        $jumlahTidakHadir = $pesertaTidakHadir->count();
        $pesertaPelanggaran = $pesertas->filter(fn($p) => ($p->ujianSesi?->jumlah_pelanggaran ?? 0) > 0);
        $tanggalUjian = $ujian->tanggal ?: now();
        $hariIndonesia = match($tanggalUjian->dayOfWeek) {
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => '',
        };
    @endphp

    {{-- TOOLBAR CETAK --}}
    <div class="top-action-bar no-print">
        <div>
            <div style="font-weight: 800; font-size: 14px;">Berita Acara &amp; Daftar Hadir Ujian CBT</div>
            <div style="font-size: 11px; color: #cbd5e1;">{{ $ujian->nama_ujian }} &bull; {{ $ujian->kelas->pluck('nama_kelas')->join(', ') }}</div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('hasil-ujian.print', $ujian->id) }}" class="btn btn-secondary">
                Lihat Rekap Nilai
            </a>
            <button type="button" onclick="window.print()" class="btn btn-print">
                &#128424; Cetak / Simpan PDF (A4)
            </button>
            <button type="button" onclick="window.close()" class="btn btn-secondary">
                Tutup
            </button>
        </div>
    </div>

    {{-- ===================================================================== --}}
    {{-- HALAMAN 1: BERITA ACARA PELAKSANAAN UJIAN                             --}}
    {{-- ===================================================================== --}}
    <div class="page">
        <div class="doc-header">
            <img src="{{ asset('images/logo/examuna-logo.png') }}" alt="Logo" style="height: 46px; width: auto; object-fit: contain;" onerror="this.style.display='none'">
            <div class="doc-header-center">
                <h1>BERITA ACARA PELAKSANAAN UJIAN CBT</h1>
                <h2>{{ $ujian->getTipeLabel() }}</h2>
                <p>Sistem Ujian Berbasis Komputer (Examuna CBT) &bull; Kode Agenda: <strong>{{ $ujian->exam_id }}</strong></p>
            </div>
            <div style="width: 46px;"></div>
        </div>

        <p style="text-align: justify; margin-bottom: 10px;">
            Pada hari ini <strong>{{ $hariIndonesia }}</strong>, tanggal <strong>{{ $tanggalUjian->format('d') }}</strong> bulan <strong>{{ $tanggalUjian->translatedFormat('F') }}</strong> tahun <strong>{{ $tanggalUjian->format('Y') }}</strong>, telah dilaksanakan <strong>{{ $ujian->nama_ujian }}</strong> berbasis komputer (CBT) dengan rincian pelaksanaan sebagai berikut:
        </p>

        <table class="info-table">
            <tr>
                <td class="label">1.Kode &amp; Nama Agenda Ujian</td>
                <td class="sep">:</td>
                <td class="val">{{ $ujian->exam_id }} &mdash; {{ $ujian->nama_ujian }}</td>
            </tr>
            <tr>
                <td class="label">2. Mata Pelajaran</td>
                <td class="sep">:</td>
                <td class="val">{{ $ujian->mapel?->nama_mapel ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">3. Paket Bank Soal</td>
                <td class="sep">:</td>
                <td class="val">{{ $ujian->bankSoal?->judul ?: '-' }} ({{ $ujian->bankSoal?->kode_bank ?: '-' }})</td>
            </tr>
            <tr>
                <td class="label">4. Kelas / Rombel Peserta</td>
                <td class="sep">:</td>
                <td class="val">{{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">5. Waktu &amp; Durasi Ujian</td>
                <td class="sep">:</td>
                <td class="val">
                    {{ $ujian->waktu_mulai ? substr($ujian->waktu_mulai, 0, 5) : '07:30' }} s/d {{ $ujian->waktu_selesai ? substr($ujian->waktu_selesai, 0, 5) : 'Selesai' }} WIB ({{ $ujian->durasi_menit ?: 60 }} Menit)
                </td>
            </tr>
            <tr>
                <td class="label">6. Guru Pengawas / Proktor</td>
                <td class="sep">:</td>
                <td class="val">{{ $ujian->guru?->name ?: '-' }}</td>
            </tr>
        </table>

        <div class="stats-box">
            <div class="stat-card">
                <div class="lbl">Peserta Terdaftar</div>
                <div class="num">{{ $totalTerdaftar }} Siswa</div>
            </div>
            <div class="stat-card">
                <div class="lbl">Peserta Hadir / Mengikuti</div>
                <div class="num" style="color: #047857;">{{ $jumlahHadir }} Siswa</div>
            </div>
            <div class="stat-card">
                <div class="lbl">Peserta Tidak Hadir</div>
                <div class="num" style="color: {{ $jumlahTidakHadir > 0 ? '#be123c' : '#0f172a' }};">{{ $jumlahTidakHadir }} Siswa</div>
            </div>
        </div>

        <div class="section-title">A. Daftar Peserta Tidak Hadir</div>
        @if($pesertaTidakHadir->isEmpty())
            <p style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; font-style: italic; font-size: 11px;">
                NIHIL (Seluruh peserta terdaftar hadir dan mengikuti ujian).
            </p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 36px;">No</th>
                        <th style="width: 100px;">NIS</th>
                        <th>Nama Lengkap Siswa</th>
                        <th style="width: 90px;">Kelas</th>
                        <th style="width: 180px;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pesertaTidakHadir->values() as $idx => $absen)
                        <tr>
                            <td style="text-align: center;">{{ $idx + 1 }}</td>
                            <td style="text-align: center; font-family: monospace;">{{ $absen->siswa?->nis ?: '-' }}</td>
                            <td><strong>{{ $absen->siswa?->nama ?: '-' }}</strong></td>
                            <td style="text-align: center;">{{ $absen->siswa?->kelas?->nama_kelas ?: '-' }}</td>
                            <td>Tidak Hadir / Belum Login</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="section-title">B. Catatan Kejadian / Log Integritas Pelaksanaan Ujian</div>
        @if($pesertaPelanggaran->isEmpty())
            <p style="padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #f8fafc; font-size: 11px;">
                Pelaksanaan ujian berjalan dengan <strong>aman, tertib, dan lancar</strong> tanpa ditemukan indikasi pelanggaran berpindah tab/aplikasi.
            </p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 36px;">No</th>
                        <th style="width: 100px;">NIS</th>
                        <th>Nama Siswa</th>
                        <th style="width: 90px;">Kelas</th>
                        <th style="width: 140px;">Jumlah Pindah Tab</th>
                        <th style="width: 130px;">Terakhir Terdeteksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pesertaPelanggaran->values() as $idx => $plg)
                        <tr>
                            <td style="text-align: center;">{{ $idx + 1 }}</td>
                            <td style="text-align: center; font-family: monospace;">{{ $plg->siswa?->nis ?: '-' }}</td>
                            <td><strong>{{ $plg->siswa?->nama ?: '-' }}</strong></td>
                            <td style="text-align: center;">{{ $plg->siswa?->kelas?->nama_kelas ?: '-' }}</td>
                            <td style="text-align: center; font-weight: 700; color: #be123c;">{{ $plg->ujianSesi?->jumlah_pelanggaran }}x Pindah Tab</td>
                            <td style="text-align: center;">{{ $plg->ujianSesi?->terakhir_pelanggaran_at ? $plg->ujianSesi->terakhir_pelanggaran_at->format('H:i:s') . ' WIB' : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p style="margin-top: 16px; font-size: 11px; text-align: justify;">
            Demikian Berita Acara Pelaksanaan Ujian Berbasis Komputer (CBT) ini dibuat dengan sesungguhnya untuk dapat dipergunakan sebagaimana mestinya.
        </p>

        <div class="sign-grid">
            <div class="sign-box">
                <div>Mengetahui,</div>
                <div style="font-weight: 700;">Ketua Panitia / Koordinator Ujian</div>
                <div class="sign-space"></div>
                <div style="font-weight: 700; text-decoration: underline;">( ........................................................ )</div>
                <div style="font-size: 11px; color: #4b5563;">NIP. ....................................................</div>
            </div>
            <div class="sign-box">
                <div>{{ $tanggalUjian->translatedFormat('d F Y') }}</div>
                <div style="font-weight: 700;">Guru Pengawas / Proktor Ruangan</div>
                <div class="sign-space"></div>
                <div style="font-weight: 800; text-decoration: underline;">{{ $ujian->guru?->name ?: '( ........................................................ )' }}</div>
                <div style="font-size: 11px; color: #4b5563;">NIP. {{ $ujian->guru?->nip ?: '....................................................' }}</div>
            </div>
        </div>
    </div>

    {{-- ===================================================================== --}}
    {{-- HALAMAN 2: DAFTAR HADIR PESERTA UJIAN                                 --}}
    {{-- ===================================================================== --}}
    <div class="page page-break">
        <div class="doc-header">
            <img src="{{ asset('images/logo/examuna-logo.png') }}" alt="Logo" style="height: 44px; width: auto; object-fit: contain;" onerror="this.style.display='none'">
            <div class="doc-header-center">
                <h1>DAFTAR HADIR PESERTA UJIAN CBT</h1>
                <h2>{{ $ujian->nama_ujian }}</h2>
                <p>
                    Mapel: <strong>{{ $ujian->mapel?->nama_mapel ?: '-' }}</strong> &bull;
                    Kelas: <strong>{{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</strong> &bull;
                    Tanggal: <strong>{{ $tanggalUjian->format('d/m/Y') }}</strong>
                </p>
            </div>
            <div style="width: 44px;"></div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th style="width: 90px;">NIS</th>
                    <th>Nama Lengkap Peserta</th>
                    <th style="width: 75px;">Kelas</th>
                    <th style="width: 75px;">Mulai</th>
                    <th style="width: 75px;">Selesai</th>
                    <th style="width: 85px;">Status</th>
                    <th style="width: 155px;">Tanda Tangan Peserta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pesertas as $index => $p)
                    @php
                        $nomor = $index + 1;
                        $isGanjil = ($nomor % 2 === 1);
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 700;">{{ $nomor }}</td>
                        <td style="text-align: center; font-family: monospace; font-weight: 600;">{{ $p->siswa?->nis ?: '-' }}</td>
                        <td style="font-weight: 600;">{{ $p->siswa?->nama ?: ($p->user?->name ?: '-') }}</td>
                        <td style="text-align: center;">{{ $p->siswa?->kelas?->nama_kelas ?: '-' }}</td>
                        <td style="text-align: center;">{{ $p->waktu_mulai ? $p->waktu_mulai->format('H:i') : '-' }}</td>
                        <td style="text-align: center;">{{ $p->waktu_selesai ? $p->waktu_selesai->format('H:i') : '-' }}</td>
                        <td style="text-align: center; font-weight: 700; font-size: 10px;">
                            @if($p->status === 'SELESAI')
                                <span style="color: #047857;">HADIR (SELESAI)</span>
                            @elseif($p->status === 'SEDANG_MENGERJAKAN')
                                <span style="color: #b45309;">HADIR (AKTIF)</span>
                            @else
                                <span style="color: #be123c;">TIDAK HADIR</span>
                            @endif
                        </td>
                        <td style="height: 30px; padding: 4px 10px; font-size: 10px; color: #475569;">
                            <div style="text-align: {{ $isGanjil ? 'left' : 'center' }};">
                                {{ $nomor }}. .........................
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 24px; color: #64748b; font-style: italic;">
                            Belum ada data peserta terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="sign-grid">
            <div class="sign-box">
                <div>Rekapitulasi Kehadiran:</div>
                <div style="margin-top: 6px; font-size: 11px;">
                    Terdaftar: <strong>{{ $totalTerdaftar }}</strong> &bull;
                    Hadir: <strong>{{ $jumlahHadir }}</strong> &bull;
                    Tidak Hadir: <strong>{{ $jumlahTidakHadir }}</strong>
                </div>
            </div>
            <div class="sign-box">
                <div>{{ $tanggalUjian->translatedFormat('d F Y') }}</div>
                <div style="font-weight: 700;">Guru Pengawas / Proktor</div>
                <div class="sign-space"></div>
                <div style="font-weight: 800; text-decoration: underline;">{{ $ujian->guru?->name ?: '( ........................................................ )' }}</div>
                <div style="font-size: 11px; color: #4b5563;">NIP. {{ $ujian->guru?->nip ?: '....................................................' }}</div>
            </div>
        </div>
    </div>
</body>
</html>

