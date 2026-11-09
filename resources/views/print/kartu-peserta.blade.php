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

        /* Body Table & E-Sign Layout */
        .kartu-body {
            padding: 5px 8px 6px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .kartu-body-inner {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 6px;
        }
        .bio-table {
            flex: 1;
            min-width: 0;
            border-collapse: collapse;
            font-size: 10px;
            line-height: 1.32;
        }
        .bio-table td {
            padding: 1px 0;
            vertical-align: top;
            color: #000000;
        }
        .bio-table td.lbl {
            width: 58px;
            font-weight: 400;
        }
        .bio-table td.sep {
            width: 8px;
            text-align: center;
        }
        .bio-table td.val {
            font-weight: 400;
            word-break: break-word;
        }
        .bio-table td.val-bold {
            font-weight: 700;
        }

        /* TTD Kepala Sekolah & E-Sign QR Code */
        .ttd-box {
            width: 108px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            font-size: 8px;
            line-height: 1.15;
            color: #000000;
            padding-top: 1px;
        }
        .ttd-titimangsa {
            font-size: 8px;
            white-space: nowrap;
            color: #1e293b;
        }
        .ttd-jabatan {
            font-size: 8.5px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .ttd-esign-wrapper {
            margin: 1px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .ttd-esign-qr {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #334155;
            padding: 1px;
            background: #ffffff;
            border-radius: 3px;
            overflow: hidden;
        }
        .ttd-esign-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .ttd-badge-esign {
            font-size: 6.5px;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #047857;
            margin-top: 1.5px;
        }
        .ttd-nama {
            font-size: 9px;
            font-weight: 800;
            text-decoration: underline;
            margin-top: 2px;
            white-space: nowrap;
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

        /* Mode Sembunyikan Username & Password (Kartu Meja Ujian) */
        body.hide-credentials .row-kredensial {
            display: none !important;
        }
        body.hide-credentials .row-meja {
            display: table-row !important;
        }
        body.hide-credentials .bio-table td {
            padding: 2.2px 0;
            font-size: 10.5px;
        }
        body.hide-credentials .ttd-box {
            padding-top: 4px;
        }
        body.hide-credentials .label-kartu-peserta {
            display: none !important;
        }
        body.hide-credentials .label-kartu-meja {
            display: inline-block !important;
        }
        .label-kartu-meja {
            display: none !important;
        }
    </style>
</head>
@php
    $judulKegiatan = request('kegiatan', 'Sumatif Akhir Semester Gasal');
    $tahunPelajaran = request('tp', 'Tahun Pelajaran 2026-2027');
    $ruangDefault = request('ruang', 'Ruang 1');
    $namaKepsek = request('kepsek', 'Mahsun, M.Pd.');
    $titimangsa = request('titimangsa', 'Tumijajar, ' . now()->translatedFormat('d F Y'));
    $isKartuMeja = request('tipe') === 'meja' || request()->boolean('hide_akun') || request()->boolean('hide_credentials');
@endphp
<body class="{{ $isKartuMeja ? 'hide-credentials' : '' }}">

    <div class="top-action-bar no-print">
        <div class="top-bar-row">
            <div>
                <div style="font-weight: 800; font-size: 14px;">Cetak Kartu Peserta Ujian CBT ({{ $siswas->count() }} Siswa)</div>
                <div style="font-size: 11px; color: #cbd5e1;">Format standar A4 (2 kolom &times; 4 baris = 8 kartu per halaman)</div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <label style="display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,0.14); padding: 7px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; border: 1px solid rgba(255,255,255,0.25); user-select: none;">
                    <input
                        type="checkbox"
                        id="chkHideCredentials"
                        onchange="toggleHideCredentials(this.checked)"
                        {{ $isKartuMeja ? 'checked' : '' }}
                        style="cursor: pointer; width: 15px; height: 15px; accent-color: #10b981;"
                    >
                    <span>&#129681; Hide Akun (Kartu Meja)</span>
                </label>

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
                <button type="button" onclick="window.print()" class="btn btn-print" id="btnPrintAction">
                    &#128424; <span id="btnPrintLabelText">{{ $isKartuMeja ? 'Cetak Kartu Meja' : 'Cetak Kartu Peserta' }}</span>
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

            <div class="control-group" style="min-width: 175px;">
                <label>Format Kartu</label>
                <select name="tipe" id="tipeKartuSelect" onchange="toggleHideCredentials(this.value === 'meja')" class="filter-select" style="background: rgba(255,255,255,0.18); font-weight: 700;">
                    <option value="peserta" @selected(!$isKartuMeja)>&#129690; Kartu Peserta (Lengkap)</option>
                    <option value="meja" @selected($isKartuMeja)>&#129681; Kartu Meja (Hide Akun)</option>
                </select>
            </div>

            <div class="control-group" style="min-width: 185px;">
                <label>Urutan Meja</label>
                <select name="urutan" onchange="this.form.submit()" class="filter-select" style="background: rgba(255,255,255,0.18); font-weight: 600;">
                    <option value="selang_seling" @selected(request('urutan', 'selang_seling') === 'selang_seling')>&#128256; Selang-Seling Kelas (Rekomendasi)</option>
                    <option value="per_kelas" @selected(request('urutan') === 'per_kelas')>&#128203; Berurutan per Kelas</option>
                </select>
            </div>

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

            <div class="control-group" style="width: 120px;">
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

            <div class="control-group" style="min-width: 150px;">
                <label>Kepala Sekolah (TTD)</label>
                <input
                    type="text"
                    name="kepsek"
                    value="{{ $namaKepsek }}"
                    oninput="document.querySelectorAll('.js-nama-kepsek').forEach(el => el.textContent = this.value)"
                    class="filter-input"
                    placeholder="Mahsun, M.Pd."
                />
            </div>

            <div class="control-group" style="min-width: 170px;">
                <label>Titimangsa TTD</label>
                <input
                    type="text"
                    name="titimangsa"
                    value="{{ $titimangsa }}"
                    oninput="document.querySelectorAll('.js-titimangsa').forEach(el => el.textContent = this.value)"
                    class="filter-input"
                    placeholder="Tumijajar, {{ now()->translatedFormat('d F Y') }}"
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
                        // QR Code Pojok Kanan Atas Menggunakan NISN (fallback ke NIS jika NISN kosong)
                        $studentNisnPayload = (string) ($siswa->nisn ?: ($siswa->nis ?: (string) $siswa->id));
                        $studentQrUri = \App\Services\QrCodeService::svg($studentNisnPayload);

                        // E-Sign QR Code Scannable (Opsi A: Metadata Validasi Digital Resmi)
                        $esignPayload = \App\Services\QrCodeService::getEsignPayload($namaKepsek, $siswa->nama, $siswa->nisn ?: $siswa->nis);
                        $esignQrUri = \App\Services\QrCodeService::svg($esignPayload);
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
                                <div class="line-1">
                                    <span class="label-kartu-peserta">KARTU PESERTA</span>
                                    <span class="label-kartu-meja">KARTU MEJA PESERTA</span>
                                </div>
                                <div class="line-2 js-kegiatan">{{ $judulKegiatan }}</div>
                                <div class="line-3">SMK ASSUNIYAH TUMIJAJAR</div>
                                <div class="line-4 js-tp">{{ $tahunPelajaran }}</div>
                            </div>

                            <div class="kartu-qr" title="Scan NISN / NIS: {{ $studentNisnPayload }}">
                                <img src="{{ $studentQrUri }}" alt="QR NISN/NIS {{ $studentNisnPayload }}" style="width: 44px; height: 44px; object-fit: contain; display: block;" />
                            </div>
                        </div>

                        <div class="kartu-body">
                            <div class="kartu-body-inner">
                                <table class="bio-table">
                                    <tr>
                                        <td class="lbl">Nama</td>
                                        <td class="sep">:</td>
                                        <td class="val val-bold">{{ strtoupper($siswa->nama) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="lbl">{{ $siswa->nisn ? 'NISN / NIS' : 'NIS' }}</td>
                                        <td class="sep">:</td>
                                        <td class="val">{{ $siswa->nisn ? ($siswa->nis ? "{$siswa->nisn} / {$siswa->nis}" : $siswa->nisn) : ($siswa->nis ?: '-') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="lbl">Kelas</td>
                                        <td class="sep">:</td>
                                        <td class="val">{{ $siswa->kelas?->nama_kelas ?: '-' }}</td>
                                    </tr>
                                    <tr class="row-kredensial">
                                        <td class="lbl">Username</td>
                                        <td class="sep">:</td>
                                        <td class="val-bold">{{ $siswa->nis ?: ($siswa->user?->email ?: '-') }}</td>
                                    </tr>
                                    <tr class="row-kredensial">
                                        <td class="lbl">Password</td>
                                        <td class="sep">:</td>
                                        <td class="val-bold">{{ $siswa->password_cbt ?: ($siswa->nis ?: '123456') }}</td>
                                    </tr>
                                    <tr class="row-meja" style="display: none;">
                                        <td class="lbl">No. Meja</td>
                                        <td class="sep">:</td>
                                        <td class="val-bold">{{ $siswa->nomor_meja ?: sprintf('%02d', ($index % 40) + 1) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="lbl">Ruang</td>
                                        <td class="sep">:</td>
                                        <td class="val {{ $siswa->ruangUjian ? '' : 'js-ruang' }}">{{ $siswa->ruangUjian?->nama_ruang ?: $ruangDefault }}</td>
                                    </tr>
                                </table>

                                {{-- Area Tanda Tangan & E-Sign QR Code Kepala Sekolah --}}
                                <div class="ttd-box">
                                    <div class="ttd-titimangsa js-titimangsa">{{ $titimangsa }}</div>
                                    <div class="ttd-jabatan">Kepala Sekolah,</div>
                                    <div class="ttd-esign-wrapper">
                                        <div class="ttd-esign-qr" title="Scan dengan Kamera HP untuk Validasi E-Sign - {{ $namaKepsek }}">
                                            <img src="{{ $esignQrUri }}" alt="E-Sign QR {{ $namaKepsek }}" />
                                        </div>
                                        <div class="ttd-badge-esign">&#10003; E-SIGN VALID</div>
                                    </div>
                                    <div class="ttd-nama js-nama-kepsek">{{ $namaKepsek }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        function toggleHideCredentials(hide) {
            if (hide) {
                document.body.classList.add('hide-credentials');
            } else {
                document.body.classList.remove('hide-credentials');
            }

            const chk = document.getElementById('chkHideCredentials');
            if (chk) chk.checked = hide;
            const sel = document.getElementById('tipeKartuSelect');
            if (sel) sel.value = hide ? 'meja' : 'peserta';

            const btnLabel = document.getElementById('btnPrintLabelText');
            if (btnLabel) {
                btnLabel.textContent = hide ? 'Cetak Kartu Meja' : 'Cetak Kartu Peserta';
            }

            // Simpan status ke URL parameter tanpa reload halaman
            try {
                const url = new URL(window.location.href);
                if (hide) {
                    url.searchParams.set('tipe', 'meja');
                } else {
                    url.searchParams.delete('tipe');
                }
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}
        }
    </script>
</body>
</html>
