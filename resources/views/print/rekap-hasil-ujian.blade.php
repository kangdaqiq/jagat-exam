<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Hasil Ujian - {{ $ujian->nama_ujian }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #111827;
            background-color: #f3f4f6;
            font-size: 12px;
            line-height: 1.4;
        }
        .page {
            max-width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 20mm;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .top-action-bar {
            max-width: 210mm;
            margin: 15px auto 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #1e293b;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-print {
            background-color: #2563eb;
            color: white;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }
        .btn-csv {
            background-color: #059669;
            color: white;
        }
        .btn-csv:hover {
            background-color: #047857;
        }
        .btn-close {
            background-color: #475569;
            color: white;
        }
        .btn-close:hover {
            background-color: #334155;
        }

        /* HEADER DOKUMEN */
        .doc-header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .doc-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .doc-header h2 {
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 4px;
        }
        .doc-header p {
            font-size: 11px;
            color: #6b7280;
        }

        /* META INFO */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 20px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 11px;
        }
        .meta-item {
            display: flex;
        }
        .meta-label {
            width: 130px;
            color: #4b5563;
            font-weight: 600;
        }
        .meta-value {
            font-weight: 700;
            color: #111827;
        }

        /* STATS SUMMARY */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .stat-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 12px;
            text-align: center;
            background: #ffffff;
        }
        .stat-card .title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 2px;
        }
        .stat-card .val {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #111827;
        }

        /* TABEL NILAI */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 7px 10px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: monospace;
        }
        .font-bold {
            font-weight: 700;
        }

        /* TANDA TANGAN */
        .ttd-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .ttd-box {
            width: 200px;
            text-align: center;
            font-size: 11px;
        }
        .ttd-space {
            height: 60px;
        }

        @media print {
            body {
                background: white;
            }
            .page {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Bar (Hidden on Print) --}}
    <div class="top-action-bar no-print">
        <div>
            <span style="font-weight:700; font-size:14px;">Preview Dokumen Cetak Rekap Nilai CBT</span>
            <span style="color:#94a3b8; font-size:12px; margin-left:8px;">Format A4 Resmi</span>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('hasil-ujian.export-csv', $ujian->id) }}" class="btn btn-csv">
                Download CSV / Excel
            </a>
            <button onclick="window.print()" class="btn btn-print">
                Print Sekarang (Ctrl + P)
            </button>
            <button onclick="window.close()" class="btn btn-close">
                Tutup
            </button>
        </div>
    </div>

    {{-- Kertas A4 --}}
    <div class="page">
        {{-- Header Dokumen --}}
        <div class="doc-header">
            <h1>REKAPITULASI DAFTAR NILAI SISWA CBT</h1>
            <h2>{{ $ujian->nama_ujian }}</h2>
            <p>Sistem Ujian Berbasis Komputer &middot; Dokumen Rekap Resmi</p>
        </div>

        {{-- Meta Info --}}
        <div class="meta-grid">
            <div class="meta-item">
                <span class="meta-label">ID Ujian:</span>
                <span class="meta-value font-mono">{{ $ujian->exam_id }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Mata Pelajaran:</span>
                <span class="meta-value">{{ $ujian->mapel?->nama_mapel ?: '-' }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Tipe Pelaksanaan:</span>
                <span class="meta-value">{{ $ujian->getTipeLabel() }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Rombel Kelas:</span>
                <span class="meta-value">{{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Guru Pengawas:</span>
                <span class="meta-value">{{ $ujian->guru?->name ?: '-' }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Tanggal Pelaksanaan:</span>
                <span class="meta-value">{{ $ujian->tanggal ? $ujian->tanggal->format('d F Y') : '-' }}</span>
            </div>
        </div>

        {{-- Stats Ringkasan --}}
        @php
            $totalPeserta = $pesertas->count();
            $selesai = $pesertas->where('status', 'SELESAI')->count();
            $avg = $ujian->rata_rata_nilai;
            $min = $ujian->nilai_terendah;
            $max = $ujian->nilai_tertinggi;
        @endphp

        <div class="stats-grid">
            <div class="stat-card">
                <div class="title">Total Siswa</div>
                <div class="val">{{ $totalPeserta }}</div>
            </div>
            <div class="stat-card">
                <div class="title">Sudah Selesai</div>
                <div class="val" style="color:#059669;">{{ $selesai }}</div>
            </div>
            <div class="stat-card">
                <div class="title">Rata-Rata Nilai</div>
                <div class="val" style="color:#2563eb;">{{ $avg !== null ? number_format($avg, 1) : '-' }}</div>
            </div>
            <div class="stat-card">
                <div class="title">Rentang (Min - Max)</div>
                <div class="val" style="font-size:15px; padding-top:2px;">
                    {{ $min !== null ? (float)$min : '-' }} &ndash; {{ $max !== null ? (float)$max : '-' }}
                </div>
            </div>
        </div>

        {{-- Tabel Siswa --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">No</th>
                    <th style="width: 85px;">NIS</th>
                    <th>Nama Lengkap Siswa</th>
                    <th style="width: 100px;">Kelas</th>
                    <th style="width: 85px;" class="text-center">Status</th>
                    <th style="width: 80px;" class="text-center">Waktu Mulai</th>
                    <th style="width: 80px;" class="text-center">Waktu Selesai</th>
                    <th style="width: 80px;" class="text-center">Nilai Akhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pesertas as $idx => $p)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-mono font-bold">{{ $p->siswa?->nis ?: '-' }}</td>
                        <td style="font-weight: 600;">{{ $p->siswa?->nama ?: ($p->user?->name ?: '-') }}</td>
                        <td>{{ $p->siswa?->kelas?->nama_kelas ?: '-' }}</td>
                        <td class="text-center">
                            @if($p->status === 'SELESAI')
                                <span style="color:#059669; font-weight:700;">SELESAI</span>
                            @elseif($p->status === 'SEDANG_MENGERJAKAN')
                                <span style="color:#d97706; font-weight:700;">MENGERJAKAN</span>
                            @else
                                <span style="color:#6b7280;">BELUM MULAI</span>
                            @endif
                        </td>
                        <td class="text-center font-mono" style="font-size:11px;">
                            {{ $p->waktu_mulai ? $p->waktu_mulai->format('H:i:s') : '-' }}
                        </td>
                        <td class="text-center font-mono" style="font-size:11px;">
                            {{ $p->waktu_selesai ? $p->waktu_selesai->format('H:i:s') : '-' }}
                        </td>
                        <td class="text-center font-bold" style="font-size: 13px;">
                            {{ $p->nilai !== null ? (float) $p->nilai : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px; color: #6b7280;">
                            Belum ada peserta terdaftar untuk ujian ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Area Tanda Tangan --}}
        <div class="ttd-container">
            <div class="ttd-box">
                <p>Mengetahui,</p>
                <p>Kepala Sekolah</p>
                <div class="ttd-space"></div>
                <p style="font-weight:700; text-decoration: underline;">( .................................... )</p>
                <p>NIP. ................................</p>
            </div>
            <div class="ttd-box">
                <p>{{ $ujian->school_name ?: 'Tempat Pelaksanaan' }}, {{ now()->format('d M Y') }}</p>
                <p>Guru Pengawas / Pengampu,</p>
                <div class="ttd-space"></div>
                <p style="font-weight:700; text-decoration: underline;">{{ $ujian->guru?->name ?: '( .................................... )' }}</p>
                <p>NIP. {{ $ujian->guru?->nip ?: '................................' }}</p>
            </div>
        </div>
    </div>

    {{-- Auto trigger print dialog --}}
    <script>
        // Otomatis buka dialog print saat halaman siap (bisa dibatalkan dengan ESC)
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 400);
        });
    </script>
</body>
</html>
