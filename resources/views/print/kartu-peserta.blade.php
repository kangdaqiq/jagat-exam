<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KARTU PESERTA UJIAN CBT - SMK ASSUNIYAH TUMIJAJAR {{ $selectedKelas ? '- Kelas ' . $selectedKelas->nama_kelas : '' }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            background-color: #f1f5f9;
            font-size: 11px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Top Action Bar (Screen Only) */
        .top-action-bar {
            max-width: 210mm;
            margin: 16px auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #041F45;
            color: white;
            padding: 14px 20px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(4, 31, 69, 0.18);
            font-family: system-ui, -apple-system, sans-serif;
        }
        .top-bar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .top-bar-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.15);
        }
        .control-group {
            display: flex;
             flex-direction: column;
            gap: 3px;
        }
        .control-group label {
            font-size: 10px;
            font-weight: 700;
            color: #93c5fd;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .filter-select, .filter-input {
            padding: 6px 10px;
            border-radius: 7px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.12);
            color: white;
            font-size: 12px;
            font-weight: 600;
            outline: none;
        }
        .filter-input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        .filter-select option {
            color: #0f172a;
            background: white;
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

        /* A4 Page Container */
        .page-container {
            max-width: 210mm;
            margin: 0 auto 24px;
            background: white;
            padding: 10mm;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        /* 2 Columns x 4 Rows per A4 Page */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 12px;
        }

        /* Individual Exam Card (Matching Official TKA/ANBK Style) */
        .kartu {
            border: 1px solid #000000;
            background: #ffffff;
            break-inside: avoid;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
        }

        /* Header with Logo Left, Title Center, QR Right */
        .kartu-header {
            border-bottom: 1px solid #000000;
            padding: 6px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }
        .kartu-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .kartu-header-text {
            flex: 1;
            text-align: center;
            line-height: 1.22;
            padding: 0 2px;
        }
        .kartu-header-text .line-1 {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .kartu-header-text .line-2 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .kartu-header-text .line-3 {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .kartu-header-text .line-4 {
            font-size: 9.5px;
            font-weight: 700;
        }
        .kartu-qr {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Body Table */
        .kartu-body {
            padding: 7px 10px 8px;
        }
        .bio-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            line-height: 1.35;
        }
        .bio-table td {
            padding: 1.5px 0;
            vertical-align: top;
            color: #000000;
        }
        .bio-table td.lbl {
            width: 78px;
            font-weight: 400;
        }
        .bio-table td.sep {
            width: 10px;
            text-align: center;
        }
        .bio-table td.val {
            font-weight: 400;
            word-break: break-word;
        }
        .bio-table td.val-bold {
            font-weight: 700;
        }

        @page {
            size: A4 portrait;
            margin: 8mm;
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
                padding: 0;
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    @php
        $judulKegiatan = request('kegiatan', 'Sumatif Akhir Semester Gasal');
        $tahunPelajaran = request('tp', 'Tahun Pelajaran 2026-2027');
        $ruangDefault = request('ruang', 'Ruang 1');
    @endphp

    <div class="top-action-bar no-print">
        <div class="top-bar-row">
            <div>
                <div style="font-weight: 800; font-size: 14px;">Cetak Kartu Peserta Ujian CBT ({{ $siswas->count() }} Siswa)</div>
                <div style="font-size: 11px; color: #cbd5e1;">Format standar A4 (2 kolom &times; 4 baris = 8 kartu per halaman)</div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <a
                    href="{{ route('kartu-peserta.print', array_merge(request()->query(), ['reset_mode' => 'generator'])) }}"
                    onclick="return confirm('Generate password 6 karakter angka acak baru untuk {{ $siswas->count() }} siswa yang tampil di halaman ini?')"
                    class="btn btn-secondary"
                    style="background-color: #2563eb; border-color: #3b82f6;"
                >
                    &#127922; Generate Password 6 Angka
                </a>
                <a
                    href="{{ route('kartu-peserta.print', array_merge(request()->query(), ['reset_mode' => 'nis'])) }}"
                    onclick="return confirm('Reset password ke Default NIS untuk {{ $siswas->count() }} siswa yang tampil di halaman ini?')"
                    class="btn btn-secondary"
                >
                    &#128260; Reset ke Default NIS
                </a>
                <button type="button" onclick="window.print()" class="btn btn-print">
                    &#128424; Cetak Kartu Peserta
                </button>
                <button type="button" onclick="window.close()" class="btn btn-secondary">
                    Tutup
                </button>
            </div>
        </div>

        <form method="GET" action="{{ route('kartu-peserta.print') }}" class="top-bar-controls">
            @if(request()->filled('ids'))
                <input type="hidden" name="ids" value="{{ request('ids') }}">
            @endif

            <div class="control-group">
                <label>Filter Rombel Kelas</label>
                <select name="kelas_id" onchange="this.form.submit()" class="filter-select">
                    <option value="">-- Semua Kelas ({{ $daftarKelas->count() }} Rombel) --</option>
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>
                            Kelas {{ $k->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="control-group">
                <label>Filter Ruang Ujian</label>
                <select name="ruang_ujian_id" onchange="this.form.submit()" class="filter-select">
                    <option value="">-- Semua Ruang --</option>
                    @foreach($daftarRuang as $r)
                        <option value="{{ $r->id }}" @selected(request('ruang_ujian_id') == $r->id)>{{ $r->nama_ruang }}</option>
                    @endforeach
                </select>
            </div>

            <div class="control-group" style="flex: 1; min-width: 200px;">
                <label>Judul Kegiatan Ujian</label>
                <input
                    type="text"
                    name="kegiatan"
                    value="{{ $judulKegiatan }}"
                    oninput="document.querySelectorAll('.js-kegiatan').forEach(el => el.textContent = this.value)"
                    class="filter-input"
                    placeholder="Sumatif Akhir Semester Gasal"
                />
            </div>

            <div class="control-group" style="min-width: 170px;">
                <label>Tahun Pelajaran</label>
                <input
                    type="text"
                    name="tp"
                    value="{{ $tahunPelajaran }}"
                    oninput="document.querySelectorAll('.js-tp').forEach(el => el.textContent = this.value)"
                    class="filter-input"
                    placeholder="Tahun Pelajaran 2026-2027"
                />
            </div>

            <div class="control-group" style="width: 130px;">
                <label>Ruang Ujian</label>
                <input
                    type="text"
                    name="ruang"
                    value="{{ $ruangDefault }}"
                    oninput="document.querySelectorAll('.js-ruang').forEach(el => el.textContent = this.value)"
                    class="filter-input"
                    placeholder="Ruang 1"
                />
            </div>
        </form>
    </div>

    <div class="page-container">
        @if($siswas->isEmpty())
            <div style="padding: 40px; text-align: center; color: #64748b; font-style: italic;">
                Tidak ada data siswa aktif untuk dicetak.
            </div>
        @else
            <div class="cards-grid">
                @foreach($siswas as $index => $siswa)
                    @php
                        // Deterministic 9x9 mini QR-like matrix based on NIS + ID so every student card has a crisp scannable-style QR graphic offline
                        $seed = crc32(($siswa->nis ?: '0') . '-' . $siswa->id);
                    @endphp
                    <div class="kartu">
                        <div class="kartu-header">
                            <img
                                src="{{ asset('images/logo/logo-sekolah.png') }}"
                                alt="Logo SMK Assuniyah Tumijajar"
                                class="kartu-logo"
                                onerror="this.src='{{ asset('images/logo/examuna-logo.png') }}'"
                            >

                            <div class="kartu-header-text">
                                <div class="line-1">KARTU PESERTA</div>
                                <div class="line-2 js-kegiatan">{{ $judulKegiatan }}</div>
                                <div class="line-3">SMK ASSUNIYAH TUMIJAJAR</div>
                                <div class="line-4 js-tp">{{ $tahunPelajaran }}</div>
                            </div>

                            <div class="kartu-qr" aria-hidden="true">
                                <svg width="42" height="42" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges">
                                    {{-- Top-left finder pattern --}}
                                    <path fill="#000" d="M0 0h7v7H0V0zm1 1v5h5V1H1zm1 1h3v3H2V2z"/>
                                    {{-- Top-right finder pattern --}}
                                    <path fill="#000" d="M14 0h7v7h-7V0zm1 1v5h5V1h-5zm1 1h3v3h-3V2z"/>
                                    {{-- Bottom-left finder pattern --}}
                                    <path fill="#000" d="M0 14h7v7H0v-7zm1 1v5h5v-5H1zm1 1h3v3H2v-3z"/>
                                    {{-- Timing & data modules --}}
                                    <rect x="8" y="6" width="1" height="1" fill="#000"/>
                                    <rect x="10" y="6" width="1" height="1" fill="#000"/>
                                    <rect x="12" y="6" width="1" height="1" fill="#000"/>
                                    <rect x="6" y="8" width="1" height="1" fill="#000"/>
                                    <rect x="6" y="10" width="1" height="1" fill="#000"/>
                                    <rect x="6" y="12" width="1" height="1" fill="#000"/>
                                    @for($r = 0; $r < 21; $r++)
                                        @for($c = 0; $c < 21; $c++)
                                            @php
                                                $inFinder = ($r < 8 && $c < 8) || ($r < 8 && $c > 12) || ($r > 12 && $c < 8) || $r === 6 || $c === 6;
                                                $bit = (($seed >> (($r * 7 + $c * 3) % 30)) ^ ($r * 13 + $c * 17 + (int) $siswa->id)) & 1;
                                            @endphp
                                            @if(!$inFinder && $bit)
                                                <rect x="{{ $c }}" y="{{ $r }}" width="1" height="1" fill="#000"/>
                                            @endif
                                        @endfor
                                    @endfor
                                </svg>
                            </div>
                        </div>

                        <div class="kartu-body">
                            <table class="bio-table">
                                <tr>
                                    <td class="lbl">Nama</td>
                                    <td class="sep">:</td>
                                    <td class="val">{{ strtoupper($siswa->nama) }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">NIS</td>
                                    <td class="sep">:</td>
                                    <td class="val">{{ $siswa->nis ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">Kelas</td>
                                    <td class="sep">:</td>
                                    <td class="val">{{ $siswa->kelas?->nama_kelas ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">Username</td>
                                    <td class="sep">:</td>
                                    <td class="val-bold">{{ $siswa->nis ?: ($siswa->user?->email ?: '-') }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">Password</td>
                                    <td class="sep">:</td>
                                    <td class="val-bold">{{ $siswa->password_cbt ?: ($siswa->nis ?: '123456') }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">Ruang</td>
                                    <td class="sep">:</td>
                                    <td class="val {{ $siswa->ruangUjian ? '' : 'js-ruang' }}">{{ $siswa->ruangUjian?->nama_ruang ?: $ruangDefault }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
