<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Final Soal Guru - {{ $bankSoal->judul }} ({{ $bankSoal->kode_bank }})</title>

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
            padding: 10px 16px;
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
            padding: 7px 12px;
            border-radius: 8px;
            border: 1px solid transparent;
            color: #334155;
            font-size: 13.5px;
            line-height: 1.55;
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
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 800 !important;
            color: #dc2626 !important;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            padding: 1px 8px;
            border-radius: 999px;
            margin-left: auto;
            flex-shrink: 0;
            white-space: nowrap;
        }

        /* Menjodohkan / Isian / Essay Answer Key Box */
        .kunci-box {
            margin-top: 10px;
            padding: 10px 14px;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
        }

        .kunci-box-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #991b1b;
            margin-bottom: 4px;
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

        /* Arabic RTL Support */
        .font-arabic, .arabic-text {
            font-family: 'Amiri', serif !important;
            font-size: 1.4rem !important;
            line-height: 2.2rem !important;
            direction: rtl;
            text-align: right;
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
                <a
                    href="{{ \App\Filament\Resources\BankSoals\BankSoalResource::getUrl('edit', ['record' => $bankSoal->id]) }}"
                    class="btn btn-studio"
                >
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Buka Studio Editor</span>
                </a>

                <button type="button" onclick="window.print()" class="btn btn-print">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak / Simpan PDF</span>
                </button>

                <button type="button" onclick="window.close()" class="btn btn-close">
                    <span>Tutup Tab &times;</span>
                </button>
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

        {{-- KETERANGAN PENANDA KUNCI JAWABAN --}}
        <div class="legend-banner">
            <div>
                <strong>Petunjuk Pengecekan Final Guru:</strong> Seluruh soal ditampilkan langsung dalam 1 halaman penuh. Jawaban yang benar ditandai dengan <span class="legend-sample">Teks Tebal (Bold) &amp; Berwarna Merah</span>.
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

                <div id="soal-{{ $nomor }}" class="soal-card {{ $isMissingKey ? 'no-key' : '' }}">
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
                            @if($isMissingKey)
                                <span class="badge badge-red">&#9888; BELUM ADA KUNCI JAWABAN</span>
                            @endif
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
                            <div class="opsi-list">
                                @foreach($soal->opsiJawabans as $opsi)
                                    @php
                                        $isBenar = (bool) $opsi->is_jawaban_benar;
                                    @endphp
                                    <div class="opsi-item {{ $isBenar ? 'is-correct' : '' }}">
                                        <span class="opsi-label">{{ $opsi->label }}.</span>
                                        <div class="opsi-teks {{ $soal->is_rtl ? 'arabic-text' : '' }}">
                                            @if($opsi->gambar_opsi)
                                                <div style="margin-bottom: 6px;">
                                                    <img src="{{ Storage::url($opsi->gambar_opsi) }}" alt="Gambar Opsi {{ $opsi->label }}" loading="lazy" style="max-height: 120px; max-width: 100%; object-fit: contain; border-radius: 6px; border: 1px solid #e2e8f0;">
                                                </div>
                                            @endif
                                            {!! $opsi->teks_opsi !!}
                                        </div>
                                        @if($isBenar)
                                            <span class="kunci-tag">&#10003; JAWABAN BENAR</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                        @elseif($soal->tipe_soal === 'menjodohkan')
                            <div class="opsi-list">
                                @foreach($soal->opsiJawabans as $mIdx => $opsi)
                                    <div class="opsi-item" style="background: #f8fafc; border-color: #e2e8f0; align-items: center;">
                                        <span class="opsi-label">{{ $mIdx + 1 }}.</span>
                                        <div class="opsi-teks" style="flex: 1;">
                                            {!! $opsi->teks_opsi !!}
                                        </div>
                                        <span style="color: #64748b; font-weight: 700; padding: 0 8px;">&rarr;</span>
                                        <div class="jawaban-benar-merah" style="flex: 1;">
                                            {{ filled($opsi->pasangan_matching) ? $opsi->pasangan_matching : '(Belum diisi)' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        @elseif($soal->tipe_soal === 'isian')
                            <div class="kunci-box">
                                <div class="kunci-box-title">Kunci Jawaban Isian Singkat:</div>
                                <div class="jawaban-benar-merah" style="font-size: 14px;">
                                    {{ filled($soal->kunci_isian) ? $soal->kunci_isian : '(Belum ada kunci jawaban)' }}
                                </div>
                            </div>

                        @elseif($soal->tipe_soal === 'essay')
                            <div class="kunci-box">
                                <div class="kunci-box-title">Kunci Jawaban / Rubrik Penilaian Essay:</div>
                                <div class="jawaban-benar-merah" style="font-size: 13.5px;">
                                    @if(filled($soal->rubrik_essay))
                                        {!! nl2br(e($soal->rubrik_essay)) !!}
                                    @else
                                        (Belum ada rubrik / kunci jawaban)
                                    @endif
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
