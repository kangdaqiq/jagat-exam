<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        @if($bankSoal)
            Preview Final Soal Guru - {{ $bankSoal->judul }} ({{ $bankSoal->kode_bank }})
        @else
            Portal Preview Soal Guru
        @endif
    </title>

    {{-- KaTeX Lokal & Google Fonts --}}
    <link rel="stylesheet" href="{{ asset('vendor/katex/katex.min.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, Arial, sans-serif;
            color: #1e293b;
            background-color: #f1f5f9;
            font-size: 13.5px;
            line-height: 1.6;
        }

        /* Password Gate Modal / Screen */
        .gate-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        .gate-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .gate-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: #fef2f2;
            color: #dc2626;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            border: 1px solid #fecaca;
        }

        .gate-title {
            font-family: 'Outfit', sans-serif;
            font-size: 21px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .gate-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .gate-paket-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 20px;
            text-align: left;
        }

        .gate-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-align: center;
            text-transform: uppercase;
            border-radius: 12px;
            border: 2px solid #cbd5e1;
            outline: none;
            color: #0f172a;
            transition: border-color 0.15s;
            margin-bottom: 14px;
        }

        .gate-input:focus {
            border-color: #dc2626;
        }

        .gate-btn {
            width: 100%;
            padding: 13px 18px;
            font-size: 14px;
            font-weight: 800;
            color: #ffffff;
            background: #dc2626;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .gate-btn:hover {
            background: #b91c1c;
        }

        .gate-error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #b91c1c;
            font-size: 12.5px;
            font-weight: 700;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 16px;
        }

        /* Top Sticky Action Bar (Screen Only) */
        .top-toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-bottom: 1px solid #1e293b;
        }

        .top-toolbar-inner {
            max-width: 1020px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .toolbar-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toolbar-badge {
            background: #dc2626;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
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
            transition: all 0.15s ease;
            line-height: 1.2;
        }

        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #1d4ed8;
        }

        .btn-studio {
            background-color: #059669;
            color: #ffffff;
        }
        .btn-studio:hover {
            background-color: #047857;
        }

        .btn-lock {
            background-color: #991b1b;
            color: #fee2e2;
        }
        .btn-lock:hover {
            background-color: #7f1d1d;
        }

        .btn-close {
            background-color: #334155;
            color: #f8fafc;
            border: 1px solid #475569;
        }
        .btn-close:hover {
            background-color: #475569;
        }

        /* Main Sheet Container */
        .page-container {
            max-width: 1020px;
            margin: 24px auto 48px;
            background: #ffffff;
            border-radius: 16px;
            padding: 32px 36px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            border: 1px solid #e2e8f0;
        }

        /* Document Header */
        .doc-header {
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 18px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .doc-title-area h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
            margin-bottom: 6px;
        }

        .doc-badges {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-slate { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-emerald { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #b91c1c; }

        /* Metadata Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 18px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .meta-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .meta-value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        /* Legend / Petunjuk Pengecekan */
        .legend-banner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 16px;
            background: #fef2f2;
            border: 1.5px solid #fecaca;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 12.5px;
            color: #7f1d1d;
        }

        .legend-sample {
            font-weight: 800;
            color: #dc2626;
        }

        /* Question Item Block */
        .soal-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .soal-card {
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
            page-break-inside: avoid;
            transition: border-color 0.2s;
        }

        .soal-card.no-key {
            border: 2px solid #f59e0b;
        }

        .soal-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 9px 16px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .soal-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .soal-num {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            background: #e2e8f0;
            padding: 2px 9px;
            border-radius: 6px;
        }

        .soal-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
        }

        .soal-body {
            padding: 16px 18px;
        }

        /* Media Stimulus */
        .stimulus-media {
            margin-bottom: 12px;
        }

        .stimulus-media img {
            max-width: 100%;
            max-height: 280px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 4px;
            background: #f8fafc;
        }

        /* Question Text */
        .pertanyaan-content {
            color: #0f172a;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 14px;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .pertanyaan-content p {
            margin-bottom: 6px;
        }
        .pertanyaan-content p:last-child {
            margin-bottom: 0;
        }
        .pertanyaan-content img {
            max-width: 100%;
            height: auto;
            border-radius: 6px;
            margin: 6px 0;
        }

        /* Options List */
        .opsi-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 12px;
        }

        .opsi-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid transparent;
            color: #334155;
            font-size: 13.5px;
            line-height: 1.55;
            transition: all 0.15s ease;
        }

        .opsi-item.clickable-opsi {
            cursor: pointer;
        }
        .opsi-item.clickable-opsi:hover:not(.is-correct) {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }

        .opsi-label {
            font-weight: 700;
            min-width: 22px;
            flex-shrink: 0;
        }

        .opsi-teks {
            flex: 1;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .opsi-teks p {
            margin: 0;
            display: inline;
        }

        /* JAWABAN BENAR: BOLD DAN WARNA MERAH */
        .opsi-item.is-correct,
        .opsi-item.is-correct .opsi-label,
        .opsi-item.is-correct .opsi-teks,
        .opsi-item.is-correct .opsi-teks * {
            color: #dc2626 !important;
            font-weight: 800 !important;
        }

        .opsi-item.is-correct {
            background-color: #fef2f2;
            border-color: #fca5a5;
        }

        .kunci-tag {
            display: none;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 800 !important;
            color: #dc2626 !important;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            padding: 2px 9px;
            border-radius: 999px;
            margin-left: auto;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .opsi-item.is-correct .kunci-tag {
            display: inline-flex;
        }

        .set-kunci-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 2px 9px;
            border-radius: 999px;
            margin-left: auto;
            flex-shrink: 0;
            white-space: nowrap;
            opacity: 0;
            transition: all 0.15s ease;
        }

        .opsi-item.clickable-opsi:hover:not(.is-correct) .set-kunci-btn {
            opacity: 1;
        }
        .opsi-item.clickable-opsi:hover:not(.is-correct) .set-kunci-btn:hover {
            color: #dc2626;
            border-color: #fca5a5;
            background: #fef2f2;
        }

        .opsi-item.is-correct .set-kunci-btn {
            display: none;
        }

        /* Menjodohkan / Isian / Essay Answer Key Box */
        .kunci-box {
            margin-top: 10px;
            padding: 12px 14px;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
        }

        .kunci-box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 6px;
        }

        .kunci-box-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #991b1b;
        }

        .btn-edit-inline {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 6px;
            border: 1px solid #fca5a5;
            background: #ffffff;
            color: #dc2626;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-edit-inline:hover {
            background: #dc2626;
            color: #ffffff;
        }

        .inline-editor-area {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #fca5a5;
            display: none;
        }

        .inline-input {
            width: 100%;
            padding: 8px 11px;
            border-radius: 8px;
            border: 1.5px solid #fca5a5;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            font-family: inherit;
        }
        .inline-input:focus {
            border-color: #dc2626;
        }

        .jawaban-benar-merah,
        .jawaban-benar-merah * {
            color: #dc2626 !important;
            font-weight: 800 !important;
        }

        /* Pembahasan Box */
        .pembahasan-box {
            margin-top: 10px;
            padding: 10px 14px;
            background: #f8fafc;
            border-left: 3px solid #3b82f6;
            border-radius: 6px;
            font-size: 12.5px;
            color: #334155;
        }

        /* Floating Toast Notification */
        .toast-notif {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            border-left: 4px solid #10b981;
            font-size: 12.5px;
            font-weight: 700;
            display: none;
            align-items: center;
            gap: 8px;
            max-width: 380px;
        }

        /* Arabic RTL Support */
        .font-arabic, .arabic-text {
            font-family: 'Amiri', serif !important;
            font-size: 1.4rem !important;
            line-height: 2.2rem !important;
            direction: rtl;
            text-align: right;
        }

        /* Index Table for Bank Soal List */
        .paket-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .paket-table th, .paket-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 13px;
        }
        .paket-table th {
            background: #f8fafc;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
        }
        .paket-table tr:hover td {
            background: #f8fafc;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff;
                font-size: 12px;
            }
            .no-print {
                display: none !important;
            }
            .page-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
            }
            .opsi-item.is-correct {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

    <script defer src="{{ asset('vendor/katex/katex.min.js') }}"></script>
    <script defer src="{{ asset('vendor/katex/contrib/auto-render.min.js') }}"></script>
</head>
<body>

    {{-- ================================================================= --}}
    {{-- KONDISI 1: BELUM MEMASUKKAN PASSWORD SOALGURU                     --}}
    {{-- ================================================================= --}}
    @if(!($isUnlocked ?? false))
        <div class="gate-wrapper">
            <div class="gate-card">
                <div class="gate-icon">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>

                <h1 class="gate-title">Akses Preview Soal Guru</h1>
                <p class="gate-subtitle">
                    Masukkan kode password khusus guru untuk membuka pratinjau 1 halaman penuh seluruh soal beserta kunci jawaban.
                </p>

                @if($bankSoal)
                    <div class="gate-paket-box">
                        <div style="font-size: 13px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                            {{ $bankSoal->judul }}
                        </div>
                        <div class="doc-badges">
                            <span class="badge badge-blue">{{ $bankSoal->kode_bank }}</span>
                            @if($bankSoal->mapel)
                                <span class="badge badge-slate">{{ $bankSoal->mapel->nama_mapel }}</span>
                            @endif
                            @if($bankSoal->tingkat)
                                <span class="badge badge-slate">Kelas {{ $bankSoal->tingkat }}</span>
                            @endif
                        </div>
                    </div>
                @endif

                @if(!empty($errorMessage))
                    <div class="gate-error">
                        {{ $errorMessage }}
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ $bankSoal ? route('bank-soal.preview-guru', ['bankSoal' => $bankSoal->id]) : route('bank-soal.preview-guru.index') }}"
                >
                    @csrf
                    <input
                        type="password"
                        name="password"
                        class="gate-input"
                        placeholder="Masukkan Kode Password..."
                        autofocus
                        required
                        autocomplete="off"
                    >
                    <button type="submit" class="gate-btn">
                        Buka Preview Full Soal &rarr;
                    </button>
                </form>
            </div>
        </div>

    {{-- ================================================================= --}}
    {{-- KONDISI 2: SUDAH UNLOCK & MODE DAFTAR PAKET (/preview-guru)       --}}
    {{-- ================================================================= --}}
    @elseif(($mode ?? 'detail') === 'index')
        <div class="top-toolbar no-print">
            <div class="top-toolbar-inner">
                <div class="toolbar-title-group">
                    <span class="toolbar-badge">Portal Preview Guru</span>
                    <div>
                        <div style="font-size: 14px; font-weight: 800; color: #ffffff;">
                            Daftar Paket Bank Soal
                        </div>
                        <div style="font-size: 11px; color: #94a3b8;">
                            Pilih paket soal untuk mengecek tampilan 1 halaman penuh beserta kunci jawaban (Bold Merah)
                        </div>
                    </div>
                </div>

                <div class="toolbar-actions">
                    <a href="{{ route('bank-soal.preview-guru.index', ['lock' => 1]) }}" class="btn btn-lock">
                        <span>Kunci Kembali</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="page-container">
            <div class="doc-header">
                <div class="doc-title-area">
                    <h1>Pilih Paket Bank Soal untuk Preview Final</h1>
                    <p style="font-size: 12.5px; color: #64748b;">
                        Total {{ $bankSoals->count() }} paket bank soal terdaftar di sistem.
                    </p>
                </div>
                <div>
                    <input
                        type="text"
                        id="searchPaketInput"
                        onkeyup="filterPaketTable()"
                        placeholder="Cari judul, mapel, atau guru..."
                        style="padding: 9px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-size: 12.5px; min-width: 250px; outline: none;"
                    >
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="paket-table" id="paketTable">
                    <thead>
                        <tr>
                            <th style="width: 48px;">No</th>
                            <th>Judul &amp; Kode Paket</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru Pembuat</th>
                            <th>Kelas / Rombel</th>
                            <th>Jumlah Soal</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bankSoals as $i => $item)
                            <tr class="paket-row">
                                <td style="color: #64748b; font-weight: 700;">{{ $i + 1 }}</td>
                                <td>
                                    <div style="font-weight: 800; color: #0f172a;">{{ $item->judul }}</div>
                                    <span class="badge badge-blue" style="margin-top: 3px;">{{ $item->kode_bank }}</span>
                                </td>
                                <td>{{ $item->mapel?->nama_mapel ?? '-' }}</td>
                                <td>{{ $item->user?->name ?? '-' }}</td>
                                <td>
                                    @if($item->tingkat)
                                        <span class="badge badge-slate">Kelas {{ $item->tingkat }}</span>
                                    @endif
                                    @foreach($item->kelases as $k)
                                        <span class="badge badge-amber">{{ $k->nama_kelas }}</span>
                                    @endforeach
                                </td>
                                <td style="font-weight: 700;">{{ $item->soals_count }} Soal</td>
                                <td style="text-align: right;">
                                    <a
                                        href="{{ route('bank-soal.preview-guru', ['bankSoal' => $item->id]) }}"
                                        class="btn btn-print"
                                        style="background: #dc2626;"
                                    >
                                        <span>Buka Full Soal &rarr;</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 32px; color: #64748b;">
                                    Belum ada paket bank soal tersedia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            function filterPaketTable() {
                const q = (document.getElementById('searchPaketInput')?.value || '').toLowerCase();
                document.querySelectorAll('#paketTable .paket-row').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            }
        </script>

    {{-- ================================================================= --}}
    {{-- KONDISI 3: SUDAH UNLOCK & TAMPILAN 1 HALAMAN FULL SOAL            --}}
    {{-- ================================================================= --}}
    @else
        @php
            $totalSoal = $soals->count();
            $totalBobot = (float) $soals->sum('bobot_nilai');
            $missingKeyNumbers = [];

            foreach ($soals as $s) {
                $hasKey = false;
                if (in_array($s->tipe_soal, ['pilihan_ganda', 'pg_kompleks', 'benar_salah'])) {
                    $hasKey = $s->opsiJawabans->where('is_jawaban_benar', true)->isNotEmpty();
                } elseif ($s->tipe_soal === 'menjodohkan') {
                    $hasKey = $s->opsiJawabans->filter(fn ($o) => filled($o->pasangan_matching))->isNotEmpty();
                } elseif ($s->tipe_soal === 'isian') {
                    $hasKey = filled($s->kunci_isian);
                } elseif ($s->tipe_soal === 'essay') {
                    $hasKey = filled($s->rubrik_essay);
                }
                if (!$hasKey) {
                    $missingKeyNumbers[] = $s->nomor_urut;
                }
            }
        @endphp

        {{-- Floating Toast Notification --}}
        <div id="toastNotif" class="toast-notif no-print">
            <span id="toastNotifText">Kunci jawaban berhasil diperbarui!</span>
        </div>

        {{-- TOP STICKY BAR (Tidak ikut tercetak saat Print) --}}
        <div class="top-toolbar no-print">
            <div class="top-toolbar-inner">
                <div class="toolbar-title-group">
                    <span class="toolbar-badge">Preview Final Guru</span>
                    <div>
                        <div style="font-size: 14px; font-weight: 800; color: #ffffff;">
                            {{ $bankSoal->judul }}
                        </div>
                        <div style="font-size: 11px; color: #94a3b8;">
                            1 Halaman Penuh ({{ $totalSoal }} Butir Soal &bull; Total {{ $totalBobot }} Poin)
                        </div>
                    </div>
                </div>

                <div class="toolbar-actions">
                    <a href="{{ route('bank-soal.preview-guru.index') }}" class="btn btn-close">
                        <span>&larr; Daftar Semua Paket</span>
                    </a>

                    @auth
                        <a
                            href="{{ \App\Filament\Resources\BankSoals\BankSoalResource::getUrl('edit', ['record' => $bankSoal->id]) }}"
                            class="btn btn-studio"
                        >
                            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Buka Studio Editor</span>
                        </a>
                    @endauth

                    <button type="button" onclick="window.print()" class="btn btn-print">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak / Simpan PDF</span>
                    </button>

                    <a href="{{ route('bank-soal.preview-guru', ['bankSoal' => $bankSoal->id, 'lock' => 1]) }}" class="btn btn-lock" title="Kunci kembali halaman ini agar meminta password SOALGURU">
                        <span>Kunci Akses</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="page-container">
            {{-- HEADER DOKUMEN --}}
            <div class="doc-header">
                <div class="doc-title-area">
                    <h1>{{ $bankSoal->judul }}</h1>
                    <div class="doc-badges">
                        <span class="badge badge-blue">{{ $bankSoal->kode_bank }}</span>
                        @if($bankSoal->mapel)
                            <span class="badge badge-slate">{{ $bankSoal->mapel->nama_mapel }}</span>
                        @endif
                        @if($bankSoal->tingkat)
                            <span class="badge badge-slate">Kelas {{ $bankSoal->tingkat }}</span>
                        @endif
                        @if($bankSoal->kelases && $bankSoal->kelases->isNotEmpty())
                            @foreach($bankSoal->kelases as $kls)
                                <span class="badge badge-amber">{{ $kls->nama_kelas }}</span>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div style="text-align: right;">
                    @if(count($missingKeyNumbers) === 0 && $totalSoal > 0)
                        <span class="badge badge-emerald" style="font-size: 12px; padding: 5px 12px;">
                            &#10003; Kunci Jawaban Lengkap ({{ $totalSoal }}/{{ $totalSoal }} Soal)
                        </span>
                    @elseif($totalSoal > 0)
                        <span class="badge badge-red" style="font-size: 12px; padding: 5px 12px;">
                            &#9888; {{ count($missingKeyNumbers) }} Soal Belum Ada Kunci (No: {{ implode(', ', $missingKeyNumbers) }})
                        </span>
                    @endif
                </div>
            </div>

            {{-- RINGKASAN METADATA --}}
            <div class="meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Guru Pembuat</span>
                    <span class="meta-value">{{ $bankSoal->user?->name ?? '-' }}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Mata Pelajaran</span>
                    <span class="meta-value">{{ $bankSoal->mapel?->nama_mapel ?? '-' }}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Jumlah Butir Soal</span>
                    <span class="meta-value">{{ $totalSoal }} Butir Soal</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Akumulasi Bobot Nilai</span>
                    <span class="meta-value" style="color: {{ abs($totalBobot - 100) < 0.5 ? '#059669' : '#d97706' }};">
                        {{ $totalBobot }} Poin {{ $bankSoal->total_bobot ? '/ Target ' . (float) $bankSoal->total_bobot . ' Poin' : '' }}
                    </span>
                </div>
            </div>

            {{-- KETERANGAN PENANDA KUNCI JAWABAN & FITUR EDIT LANGSUNG --}}
            <div class="legend-banner">
                <div>
                    <div>
                        <strong>Petunjuk Pengecekan Final Guru:</strong> Seluruh soal ditampilkan langsung dalam 1 halaman penuh. Jawaban yang benar ditandai dengan <span class="legend-sample">Teks Tebal (Bold) &amp; Berwarna Merah</span>.
                    </div>
                    <div class="no-print" style="margin-top: 4px; font-size: 12px; color: #991b1b;">
                        &#9998; <strong>Edit Cepat Jawaban Benar:</strong> Klik langsung pada baris pilihan jawaban (A/B/C/D/E) atau tombol <em>Edit Jawaban Benar</em> untuk mengubah kunci jawaban secara langsung tanpa meninggalkan halaman ini.
                    </div>
                </div>
                <div style="font-size: 11.5px; font-weight: 700;">
                    Dicetak / Diperiksa: {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>

            {{-- DAFTAR SELURUH BUTIR SOAL (1 HALAMAN FULL) --}}
            <div class="soal-list">
                @forelse($soals as $index => $soal)
                    @php
                        $nomor = $soal->nomor_urut ?: ($index + 1);
                        $tipeLabel = match($soal->tipe_soal) {
                            'pilihan_ganda' => 'Pilihan Ganda',
                            'pg_kompleks' => 'PG Kompleks',
                            'benar_salah' => 'Benar / Salah',
                            'menjodohkan' => 'Menjodohkan',
                            'isian' => 'Isian Singkat',
                            'essay' => 'Essay / Uraian',
                            default => ucfirst(str_replace('_', ' ', $soal->tipe_soal)),
                        };
                        $isMissingKey = in_array($soal->nomor_urut, $missingKeyNumbers);
                    @endphp

                    <div id="soal-card-{{ $soal->id }}" class="soal-card {{ $isMissingKey ? 'no-key' : '' }}">
                        {{-- Header Butir Soal --}}
                        <div class="soal-header">
                            <div class="soal-header-left">
                                <span class="soal-num">No. {{ $nomor }}</span>
                                <span class="badge badge-slate">{{ $tipeLabel }}</span>
                                @if($soal->tingkat_kesulitan)
                                    <span class="badge {{ strtoupper($soal->tingkat_kesulitan) === 'HOTS' ? 'badge-red' : (strtoupper($soal->tingkat_kesulitan) === 'LOTS' ? 'badge-emerald' : 'badge-amber') }}">
                                        {{ strtoupper($soal->tingkat_kesulitan) }}{{ $soal->taksonomi_bloom ? ' (' . $soal->taksonomi_bloom . ')' : '' }}
                                    </span>
                                @endif
                                <span
                                    id="missing-badge-{{ $soal->id }}"
                                    class="badge badge-red"
                                    style="{{ $isMissingKey ? '' : 'display: none;' }}"
                                >
                                    &#9888; BELUM ADA KUNCI JAWABAN
                                </span>
                            </div>

                            <div class="soal-header-right">
                                <span>Bobot: {{ (float) $soal->bobot_nilai }} Poin</span>
                            </div>
                        </div>

                        {{-- Isi Pertanyaan & Opsi Jawaban --}}
                        <div class="soal-body">
                            {{-- Media Stimulus jika ada --}}
                            @if($soal->media_type === 'image' && $soal->media_file)
                                <div class="stimulus-media">
                                    <img src="{{ Storage::url($soal->media_file) }}" alt="Gambar Stimulus Soal {{ $nomor }}" loading="lazy">
                                </div>
                            @elseif($soal->media_type === 'audio' && $soal->media_file)
                                <div class="stimulus-media">
                                    <audio controls preload="none" src="{{ Storage::url($soal->media_file) }}" style="max-width: 360px; width: 100%;"></audio>
                                </div>
                            @elseif($soal->media_type === 'video' && $soal->media_file)
                                <div class="stimulus-media">
                                    <video controls preload="metadata" src="{{ Storage::url($soal->media_file) }}" style="max-width: 480px; width: 100%; border-radius: 8px;"></video>
                                </div>
                            @endif

                            {{-- Teks Pertanyaan --}}
                            <div class="pertanyaan-content {{ $soal->is_rtl ? 'arabic-text' : '' }}">
                                {!! $soal->pertanyaan !!}
                            </div>

                            {{-- OPSI JAWABAN / KUNCI SESUAI TIPE SOAL --}}
                            @if(in_array($soal->tipe_soal, ['pilihan_ganda', 'pg_kompleks', 'benar_salah']))
                                <div class="opsi-list" id="opsi-list-{{ $soal->id }}">
                                    @foreach($soal->opsiJawabans as $opsi)
                                        @php
                                            $isBenar = (bool) $opsi->is_jawaban_benar;
                                        @endphp
                                        <div
                                            id="opsi-item-{{ $opsi->id }}"
                                            data-opsi-id="{{ $opsi->id }}"
                                            onclick="pilihKunciOpsi({{ $soal->id }}, {{ $opsi->id }}, '{{ $soal->tipe_soal }}')"
                                            class="opsi-item clickable-opsi {{ $isBenar ? 'is-correct' : '' }}"
                                            title="Klik untuk menjadikan pilihan {{ $opsi->label }} sebagai jawaban benar"
                                        >
                                            <span class="opsi-label">{{ $opsi->label }}.</span>
                                            <div class="opsi-teks {{ $soal->is_rtl ? 'arabic-text' : '' }}">
                                                @if($opsi->gambar_opsi)
                                                    <div style="margin-bottom: 6px;">
                                                        <img src="{{ Storage::url($opsi->gambar_opsi) }}" alt="Gambar Opsi {{ $opsi->label }}" loading="lazy" style="max-height: 120px; max-width: 100%; object-fit: contain; border-radius: 6px; border: 1px solid #e2e8f0;">
                                                    </div>
                                                @endif
                                                {!! $opsi->teks_opsi !!}
                                            </div>
                                            <span class="kunci-tag">&#10003; JAWABAN BENAR</span>
                                            <span class="set-kunci-btn no-print">
                                                &#9998; {{ $soal->tipe_soal === 'pg_kompleks' ? 'Toggle Kunci' : 'Jadikan Kunci' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                            @elseif($soal->tipe_soal === 'menjodohkan')
                                <div class="kunci-box">
                                    <div class="kunci-box-header">
                                        <span class="kunci-box-title">Pasangan Jawaban Benar (Menjodohkan):</span>
                                        <button type="button" onclick="toggleInlineEditor({{ $soal->id }})" class="btn-edit-inline no-print">
                                            &#9998; Edit Jawaban Benar
                                        </button>
                                    </div>
                                    <div class="opsi-list" style="border-top: none; padding-top: 4px;">
                                        @foreach($soal->opsiJawabans as $mIdx => $opsi)
                                            <div class="opsi-item" style="background: #ffffff; border-color: #fecaca; align-items: center;">
                                                <span class="opsi-label">{{ $mIdx + 1 }}.</span>
                                                <div class="opsi-teks" style="flex: 1;">
                                                    {!! $opsi->teks_opsi !!}
                                                </div>
                                                <span style="color: #64748b; font-weight: 700; padding: 0 8px;">&rarr;</span>
                                                <div id="matching-display-{{ $opsi->id }}" class="jawaban-benar-merah" style="flex: 1;">
                                                    {{ filled($opsi->pasangan_matching) ? $opsi->pasangan_matching : '(Belum diisi)' }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Inline Editor Menjodohkan --}}
                                    <div id="inline-editor-{{ $soal->id }}" class="inline-editor-area no-print">
                                        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px;">
                                            @foreach($soal->opsiJawabans as $mIdx => $opsi)
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <span style="font-size: 12px; font-weight: 700; min-width: 180px; color: #334155;">
                                                        {{ $mIdx + 1 }}. {{ Str::limit(strip_tags($opsi->teks_opsi), 40) }}
                                                    </span>
                                                    <input
                                                        type="text"
                                                        data-matching-opsi-id="{{ $opsi->id }}"
                                                        value="{{ $opsi->pasangan_matching }}"
                                                        class="inline-input matching-input-{{ $soal->id }}"
                                                        placeholder="Ketik pasangan jawaban benar..."
                                                    >
                                                </div>
                                            @endforeach
                                        </div>
                                        <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                            <button type="button" onclick="toggleInlineEditor({{ $soal->id }})" class="btn btn-close" style="padding: 6px 12px;">Batal</button>
                                            <button type="button" onclick="simpanKunciMatching({{ $soal->id }})" class="btn btn-studio" style="padding: 6px 14px; background: #dc2626;">Simpan Jawaban Benar</button>
                                        </div>
                                    </div>
                                </div>

                            @elseif($soal->tipe_soal === 'isian')
                                <div class="kunci-box">
                                    <div class="kunci-box-header">
                                        <span class="kunci-box-title">Kunci Jawaban Isian Singkat:</span>
                                        <button type="button" onclick="toggleInlineEditor({{ $soal->id }})" class="btn-edit-inline no-print">
                                            &#9998; Edit Jawaban Benar
                                        </button>
                                    </div>
                                    <div id="isian-display-{{ $soal->id }}" class="jawaban-benar-merah" style="font-size: 14px;">
                                        {{ filled($soal->kunci_isian) ? $soal->kunci_isian : '(Belum ada kunci jawaban)' }}
                                    </div>

                                    {{-- Inline Editor Isian --}}
                                    <div id="inline-editor-{{ $soal->id }}" class="inline-editor-area no-print">
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <input
                                                type="text"
                                                id="isian-input-{{ $soal->id }}"
                                                value="{{ $soal->kunci_isian }}"
                                                class="inline-input"
                                                placeholder="Ketik kunci jawaban isian singkat yang benar..."
                                            >
                                            <button type="button" onclick="simpanKunciTeks({{ $soal->id }}, 'isian')" class="btn btn-studio" style="background: #dc2626; white-space: nowrap;">
                                                Simpan Kunci
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            @elseif($soal->tipe_soal === 'essay')
                                <div class="kunci-box">
                                    <div class="kunci-box-header">
                                        <span class="kunci-box-title">Kunci Jawaban / Rubrik Penilaian Essay:</span>
                                        <button type="button" onclick="toggleInlineEditor({{ $soal->id }})" class="btn-edit-inline no-print">
                                            &#9998; Edit Jawaban Benar
                                        </button>
                                    </div>
                                    <div id="essay-display-{{ $soal->id }}" class="jawaban-benar-merah" style="font-size: 13.5px; white-space: pre-line;">{{ filled($soal->rubrik_essay) ? $soal->rubrik_essay : '(Belum ada rubrik / kunci jawaban)' }}</div>

                                    {{-- Inline Editor Essay --}}
                                    <div id="inline-editor-{{ $soal->id }}" class="inline-editor-area no-print">
                                        <textarea
                                            id="essay-input-{{ $soal->id }}"
                                            rows="3"
                                            class="inline-input"
                                            style="margin-bottom: 8px;"
                                            placeholder="Ketik kunci jawaban / rubrik penilaian essay..."
                                        >{{ $soal->rubrik_essay }}</textarea>
                                        <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                            <button type="button" onclick="toggleInlineEditor({{ $soal->id }})" class="btn btn-close" style="padding: 6px 12px;">Batal</button>
                                            <button type="button" onclick="simpanKunciTeks({{ $soal->id }}, 'essay')" class="btn btn-studio" style="padding: 6px 14px; background: #dc2626;">
                                                Simpan Kunci
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Pembahasan jika diisi --}}
                            @if(filled(trim(strip_tags($soal->pembahasan ?? ''))))
                                <div class="pembahasan-box">
                                    <strong style="color: #1d4ed8;">Pembahasan:</strong>
                                    <div style="margin-top: 3px;">
                                        {!! $soal->pembahasan !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 48px 20px; color: #64748b; border: 1.5px dashed #cbd5e1; border-radius: 12px;">
                        Belum ada butir soal pada paket bank soal ini.
                    </div>
                @endforelse
            </div>
        </div>

        <script>
            const updateKunciUrl = "{{ route('bank-soal.preview-guru.update-kunci', ['bankSoal' => $bankSoal->id]) }}";
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            let toastTimer = null;

            function showToast(message, isError = false) {
                const toast = document.getElementById('toastNotif');
                const text = document.getElementById('toastNotifText');
                if (!toast || !text) return;

                text.textContent = message;
                toast.style.borderLeftColor = isError ? '#ef4444' : '#10b981';
                toast.style.display = 'flex';

                if (toastTimer) clearTimeout(toastTimer);
                toastTimer = setTimeout(() => {
                    toast.style.display = 'none';
                }, 3000);
            }

            function updateCardKeyStatus(soalId, hasKey) {
                const card = document.getElementById('soal-card-' + soalId);
                const badge = document.getElementById('missing-badge-' + soalId);
                if (card) {
                    card.classList.toggle('no-key', !hasKey);
                }
                if (badge) {
                    badge.style.display = hasKey ? 'none' : '';
                }
            }

            async function pilihKunciOpsi(soalId, opsiId, tipeSoal) {
                const listEl = document.getElementById('opsi-list-' + soalId);
                const clickedEl = document.getElementById('opsi-item-' + opsiId);
                if (!listEl || !clickedEl) return;

                // Optimistic UI update (0ms)
                if (tipeSoal === 'pg_kompleks') {
                    clickedEl.classList.toggle('is-correct');
                } else {
                    listEl.querySelectorAll('.opsi-item').forEach(el => el.classList.remove('is-correct'));
                    clickedEl.classList.add('is-correct');
                }

                try {
                    const res = await fetch(updateKunciUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            soal_id: soalId,
                            opsi_id: opsiId
                        })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        const activeIds = (data.active_opsi_ids || []).map(Number);
                        listEl.querySelectorAll('.opsi-item').forEach(el => {
                            const id = Number(el.getAttribute('data-opsi-id'));
                            el.classList.toggle('is-correct', activeIds.includes(id));
                        });
                        updateCardKeyStatus(soalId, !!data.has_key);
                        showToast('✓ ' + data.message);
                    } else {
                        showToast(data.message || 'Gagal menyimpan perubahan kunci jawaban.', true);
                    }
                } catch (err) {
                    showToast('Terjadi kesalahan jaringan saat menyimpan kunci.', true);
                }
            }

            function toggleInlineEditor(soalId) {
                const editor = document.getElementById('inline-editor-' + soalId);
                if (!editor) return;
                const isHidden = editor.style.display === 'none' || editor.style.display === '';
                editor.style.display = isHidden ? 'block' : 'none';
            }

            async function simpanKunciTeks(soalId, tipe) {
                const payload = { soal_id: soalId };
                if (tipe === 'isian') {
                    payload.kunci_isian = document.getElementById('isian-input-' + soalId)?.value || '';
                } else {
                    payload.rubrik_essay = document.getElementById('essay-input-' + soalId)?.value || '';
                }

                try {
                    const res = await fetch(updateKunciUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        if (tipe === 'isian') {
                            const disp = document.getElementById('isian-display-' + soalId);
                            if (disp) disp.textContent = data.kunci_isian || '(Belum ada kunci jawaban)';
                        } else {
                            const disp = document.getElementById('essay-display-' + soalId);
                            if (disp) disp.textContent = data.rubrik_essay || '(Belum ada rubrik / kunci jawaban)';
                        }
                        updateCardKeyStatus(soalId, !!data.has_key);
                        toggleInlineEditor(soalId);
                        showToast('✓ ' + data.message);
                    } else {
                        showToast(data.message || 'Gagal menyimpan kunci jawaban.', true);
                    }
                } catch (err) {
                    showToast('Terjadi kesalahan jaringan saat menyimpan kunci.', true);
                }
            }

            async function simpanKunciMatching(soalId) {
                const pairs = {};
                document.querySelectorAll('.matching-input-' + soalId).forEach(inp => {
                    const oId = inp.getAttribute('data-matching-opsi-id');
                    if (oId) pairs[oId] = inp.value;
                });

                try {
                    const res = await fetch(updateKunciUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            soal_id: soalId,
                            pairs: pairs
                        })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        Object.keys(pairs).forEach(oId => {
                            const disp = document.getElementById('matching-display-' + oId);
                            if (disp) disp.textContent = pairs[oId].trim() || '(Belum diisi)';
                        });
                        updateCardKeyStatus(soalId, !!data.has_key);
                        toggleInlineEditor(soalId);
                        showToast('✓ ' + data.message);
                    } else {
                        showToast(data.message || 'Gagal menyimpan pasangan kunci.', true);
                    }
                } catch (err) {
                    showToast('Terjadi kesalahan jaringan saat menyimpan kunci.', true);
                }
            }
        </script>
    @endif

    <script>
        function renderAllMath() {
            if (typeof renderMathInElement !== 'function' || !document.body) return;
            renderMathInElement(document.body, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '$', right: '$', display: false},
                    {left: '\\(', right: '\\)', display: false},
                    {left: '\\[', right: '\\]', display: true}
                ],
                throwOnError: false
            });
        }
        document.addEventListener('DOMContentLoaded', renderAllMath);
        window.addEventListener('load', renderAllMath);
    </script>
</body>
</html>
