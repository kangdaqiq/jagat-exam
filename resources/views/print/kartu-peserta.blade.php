<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Peserta Ujian CBT {{ $selectedKelas ? '- Kelas ' . $selectedKelas->nama_kelas : '' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #0f172a;
            background-color: #f1f5f9;
            font-size: 11px;
        }
        .top-action-bar {
            max-width: 210mm;
            margin: 16px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
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
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print {
            background-color: #10b981;
            color: white;
        }
        .btn-secondary {
            background-color: rgba(255,255,255,0.14);
            color: white;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .filter-select {
            padding: 7px 12px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.12);
            color: white;
            font-size: 12px;
            font-weight: 700;
        }
        .filter-select option {
            color: #0f172a;
            background: white;
        }
        .page-container {
            max-width: 210mm;
            margin: 0 auto 24px;
            background: white;
            padding: 12mm;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            border-radius: 4px;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .kartu {
            border: 1.5px solid #041F45;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
            break-inside: avoid;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
        }
        .kartu-header {
            background: linear-gradient(135deg, #041F45 0%, #064e3b 100%);
            color: #ffffff;
            padding: 7px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 2px solid #10b981;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .kartu-header-text {
            flex: 1;
            min-width: 0;
        }
        .kartu-header-title {
            font-family: 'Outfit', sans-serif;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            line-height: 1.2;
        }
        .kartu-header-sub {
            font-size: 9px;
            color: #a7f3d0;
            font-weight: 600;
        }
        .kartu-body {
            padding: 9px 11px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .bio-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .bio-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        .bio-table td.lbl {
            width: 72px;
            color: #475569;
            font-weight: 600;
        }
        .bio-table td.sep {
            width: 10px;
        }
        .bio-table td.val {
            font-weight: 800;
            color: #0f172a;
        }
        .cred-box {
            margin-top: 2px;
            padding: 6px 9px;
            border-radius: 7px;
            background: #f8fafc;
            border: 1px dashed #64748b;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .cred-item .c-lbl {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
        }
        .cred-item .c-val {
            font-family: monospace;
            font-size: 12px;
            font-weight: 800;
            color: #041F45;
            letter-spacing: 0.05em;
        }
        .kartu-footer {
            padding: 5px 11px 7px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 8.5px;
            color: #475569;
        }
        @media print {
            body {
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .page-container {
                margin: 0;
                padding: 5mm;
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="top-action-bar no-print">
        <div>
            <div style="font-weight: 800; font-size: 14px;">Cetak Kartu Login Peserta Ujian CBT ({{ $siswas->count() }} Siswa)</div>
            <div style="font-size: 11px; color: #cbd5e1;">Siap cetak ukuran kertas A4 (8 kartu per halaman)</div>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <form method="GET" action="{{ route('kartu-peserta.print') }}" style="display: inline-flex; align-items: center; gap: 6px;">
                <select name="kelas_id" onchange="this.form.submit()" class="filter-select">
                    <option value="">-- Semua Kelas ({{ $daftarKelas->count() }} Rombel) --</option>
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>
                            Kelas {{ $k->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </form>

            <button type="button" onclick="window.print()" class="btn btn-print">
                &#128424; Cetak Kartu Peserta
            </button>
            <button type="button" onclick="window.close()" class="btn btn-secondary">
                Tutup
            </button>
        </div>
    </div>

    <div class="page-container">
        @if($siswas->isEmpty())
            <div style="padding: 40px; text-align: center; color: #64748b; font-style: italic;">
                Tidak ada data siswa aktif untuk dicetak.
            </div>
        @else
            <div class="cards-grid">
                @foreach($siswas as $index => $siswa)
                    <div class="kartu">
                        <div class="kartu-header">
                            <img src="{{ asset('images/logo/examuna-logo-white.png') }}" alt="Logo" style="height: 24px; width: auto; object-fit: contain;" onerror="this.style.display='none'">
                            <div class="kartu-header-text">
                                <div class="kartu-header-title">KARTU PESERTA UJIAN CBT</div>
                                <div class="kartu-header-sub">{{ $siswa->school_name ?: 'PORTAL ASESMEN DIGITAL EXAMUNA' }}</div>
                            </div>
                            <span style="font-family: monospace; font-size: 10px; font-weight: 800; background: rgba(255,255,255,0.16); padding: 2px 6px; border-radius: 5px;">
                                #{{ sprintf('%03d', $index + 1) }}
                            </span>
                        </div>

                        <div class="kartu-body">
                            <table class="bio-table">
                                <tr>
                                    <td class="lbl">Nama Siswa</td>
                                    <td class="sep">:</td>
                                    <td class="val">{{ \Illuminate\Support\Str::limit($siswa->nama, 30) }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">Kelas / Rombel</td>
                                    <td class="sep">:</td>
                                    <td class="val">{{ $siswa->kelas?->nama_kelas ?: '-' }}</td>
                                </tr>
                            </table>

                            <div class="cred-box">
                                <div class="cred-item">
                                    <div class="c-lbl">Username / NIS</div>
                                    <div class="c-val">{{ $siswa->nis }}</div>
                                </div>
                                <div class="cred-item">
                                    <div class="c-lbl">Password Default</div>
                                    <div class="c-val">{{ $siswa->nis }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="kartu-footer">
                            <span>Login: <strong>{{ request()->getHost() }}/login</strong></span>
                            <span style="font-weight: 700; color: #047857;">&#10003; Peserta Aktif</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>

