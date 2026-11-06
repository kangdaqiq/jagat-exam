<div class="space-y-6">

    {{-- 1. HEADER INFORMASI AGENDA UJIAN --}}
    <div class="p-6 bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        {{ $this->hasilUjian->exam_id }}
                    </span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-md {{
                        $this->hasilUjian->isAsesmenHarian() ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300' : (
                        $this->hasilUjian->isAsesmenTengahSemester() ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                        )
                    }}">
                        {{ $this->hasilUjian->getTipeLabel() }}
                    </span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                        {{ $this->hasilUjian->status === 'COMPLETED' ? 'Ujian Selesai' : ($this->hasilUjian->status === 'ONGOING' ? 'Sesi Sedang Berlangsung' : 'Dijadwalkan') }}
                    </span>
                </div>

                <h1 class="text-2xl font-black text-gray-900 dark:text-white">
                    {{ $this->hasilUjian->nama_ujian }}
                </h1>

                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap pt-1">
                    <span>Mata Pelajaran: <strong class="text-gray-800 dark:text-gray-200">{{ $this->hasilUjian->mapel?->nama_mapel ?: '-' }}</strong></span>
                    <span>&bull;</span>
                    <span>Kelas: <strong class="text-gray-800 dark:text-gray-200">{{ $this->hasilUjian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</strong></span>
                    <span>&bull;</span>
                    <span>Guru Pengawas: <strong class="text-gray-800 dark:text-gray-200">{{ $this->hasilUjian->guru?->name ?: '-' }}</strong></span>
                    <span>&bull;</span>
                    <span>Tanggal: <strong class="text-gray-800 dark:text-gray-200">{{ $this->hasilUjian->tanggal ? $this->hasilUjian->tanggal->format('d M Y') : 'Fleksibel' }}</strong></span>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start md:self-center">
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-200 transition shadow-2xs"
                >
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak / Print Rekap
                </button>
            </div>
        </div>
    </div>

    {{-- 2. STATS RINGKASAN NILAI KELAS --}}
    @php
        $totalPeserta = $this->hasilUjian->pesertas()->count();
        $selesaiPeserta = $this->hasilUjian->pesertas()->where('status', 'SELESAI')->count();
        $persenSelesai = $totalPeserta > 0 ? round(($selesaiPeserta / $totalPeserta) * 100) : 0;
        $rataRata = $this->hasilUjian->rata_rata_nilai;
        $nilaiMax = $this->hasilUjian->nilai_tertinggi;
        $nilaiMin = $this->hasilUjian->nilai_terendah;
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Total Peserta --}}
        <div class="p-4 bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Peserta</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-gray-900 dark:text-white">{{ $totalPeserta }}</span>
                <span class="text-xs text-gray-500">Siswa Terdaftar</span>
            </div>
        </div>

        {{-- Siswa Selesai --}}
        <div class="p-4 bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sudah Selesai</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $selesaiPeserta }}</span>
                <span class="text-xs text-emerald-600 font-bold">({{ $persenSelesai }}%)</span>
            </div>
        </div>

        {{-- Nilai Rata-rata --}}
        <div class="p-4 bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Rata-rata Nilai</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-blue-600 dark:text-blue-400">
                    {{ $rataRata !== null ? number_format($rataRata, 1) : '-' }}
                </span>
                <span class="text-xs text-gray-400">Skala 100</span>
            </div>
        </div>

        {{-- Tertinggi / Terendah --}}
        <div class="p-4 bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Min &bull; Max</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-gray-900 dark:text-white">
                    {{ $nilaiMin !== null ? (float)$nilaiMin : '-' }}
                </span>
                <span class="text-xs text-gray-400">s/d</span>
                <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                    {{ $nilaiMax !== null ? (float)$nilaiMax : '-' }}
                </span>
            </div>
        </div>
    </div>

    {{-- 3. FILTER & PENCARIAN SISWA --}}
    <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs p-4 flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2 w-full md:w-auto">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama atau NIS siswa..."
                class="w-full md:w-80 px-3.5 py-2 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
        </div>

        <div class="flex items-center gap-1.5 self-start md:self-center overflow-x-auto w-full md:w-auto">
            <button
                type="button"
                wire:click="$set('filterStatus', 'all')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'all' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
            >
                Semua Siswa
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'SELESAI')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'SELESAI' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
            >
                Selesai ({{ $selesaiPeserta }})
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'SEDANG_MENGERJAKAN')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'SEDANG_MENGERJAKAN' ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
            >
                Sedang Mengerjakan
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'BELUM_MULAI')"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $filterStatus === 'BELUM_MULAI' ? 'bg-gray-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
            >
                Belum Mulai
            </button>
        </div>
    </div>

    {{-- 4. TABEL DAFTAR SISWA & NILAI --}}
    <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#f9fafb] dark:bg-[#161f30] border-b border-gray-200 dark:border-gray-800 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">NIS</th>
                        <th class="py-3 px-4">Nama Siswa</th>
                        <th class="py-3 px-4">Kelas</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4">Waktu Mulai</th>
                        <th class="py-3 px-4">Waktu Selesai</th>
                        <th class="py-3 px-4 text-center font-black">Nilai Akhir</th>
                        <th class="py-3 px-4 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->pesertasList as $index => $peserta)
                        <tr class="hover:bg-[#f9fafb] dark:hover:bg-[#161f30] transition border-b border-gray-100 dark:border-gray-800/60">
                            <td class="py-3 px-4 text-center font-semibold text-gray-400">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white">{{ $peserta->siswa?->nis ?: '-' }}</td>
                            <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">
                                {{ $peserta->siswa?->nama ?: $peserta->user?->name }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                    {{ $peserta->siswa?->kelas?->nama_kelas ?: '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($peserta->status === 'SELESAI')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        SELESAI &#10003;
                                    </span>
                                @elseif($peserta->status === 'SEDANG_MENGERJAKAN')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 animate-pulse">
                                        MENGERJAKAN
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        BELUM MULAI
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-400 font-mono">
                                {{ $peserta->waktu_mulai ? $peserta->waktu_mulai->format('H:i:s') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-400 font-mono">
                                {{ $peserta->waktu_selesai ? $peserta->waktu_selesai->format('H:i:s') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($peserta->nilai !== null)
                                    <span class="text-base font-black px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                        {{ (float) $peserta->nilai }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($peserta->ujianSesi)
                                    <button
                                        type="button"
                                        wire:click="bukaLembarJawaban({{ $peserta->id }})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-bold text-xs bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-950/60 dark:text-primary-300 transition"
                                    >
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Lembar Jawaban
                                    </button>
                                @else
                                    <span class="text-gray-400 italic text-[11px]">Belum Ada Sesi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-gray-400 italic">
                                Tidak ada data siswa yang cocok dengan pencarian atau filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 5. MODAL INTERAKTIF: LEMBAR JAWABAN & ANALISIS BUTIR SOAL SISWA --}}
    @if($this->selectedPeserta)
        @php
            $sPeserta = $this->selectedPeserta;
            $sSesi = $sPeserta->ujianSesi;
            $jawabans = $sSesi ? $sSesi->jawabans : collect();
        @endphp

        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">

                {{-- Header Modal --}}
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/70 dark:bg-gray-800/40">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                Lembar Jawaban Siswa
                            </span>
                            <span class="text-xs text-gray-400">&bull;</span>
                            <span class="text-xs font-semibold text-gray-500">
                                NIS: {{ $sPeserta->siswa?->nis }} &bull; {{ $sPeserta->siswa?->kelas?->nama_kelas }}
                            </span>
                        </div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white mt-1">
                            {{ $sPeserta->siswa?->nama ?: $sPeserta->user?->name }}
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Total Nilai</span>
                            <span class="text-xl font-black text-primary-600 dark:text-primary-400">
                                {{ (float) ($sSesi?->skor_total ?? $sPeserta->nilai ?? 0) }} Poin
                            </span>
                        </div>

                        <button
                            type="button"
                            wire:click="tutupLembarJawaban"
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                        >
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body Modal: Daftar Soal & Jawaban Siswa --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    @forelse($jawabans as $idx => $jwb)
                        @php
                            $soal = $jwb->soal;
                            $isBenar = $jwb->is_benar;
                        @endphp
                        <div class="p-5 rounded-2xl border transition {{
                            $isBenar === true
                                ? 'border-emerald-200 bg-emerald-50/30 dark:border-emerald-900/60 dark:bg-emerald-950/10'
                                : ($isBenar === false
                                    ? 'border-red-200 bg-red-50/30 dark:border-red-900/60 dark:bg-red-950/10'
                                    : 'border-gray-200 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-800/20')
                        }}">
                            {{-- Header Butir Soal --}}
                            <div class="flex items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-gray-900 text-white dark:bg-white dark:text-gray-900 text-xs font-black flex items-center justify-center">
                                        {{ $jwb->nomor_urut }}
                                    </span>
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">
                                        {{ str_replace('_', ' ', $soal?->tipe_soal ?? 'Soal') }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($isBenar === true)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            BENAR (+{{ (float) $jwb->skor_didapat }})
                                        </span>
                                    @elseif($isBenar === false)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300">
                                            SALAH (0 Poin)
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                            KOREKSI MANUAL
                                        </span>
                                    @endif

                                    <span class="text-xs text-gray-400">
                                        Bobot: {{ (float) ($sesi ? $sesi->getEffectiveBobotForJawaban($jwb) : ($soal?->bobot_nilai ?? 1)) }} Poin
                                    </span>
                                </div>
                            </div>

                            {{-- Pertanyaan Soal --}}
                            <div class="py-3 text-sm text-gray-900 dark:text-gray-100 leading-relaxed font-medium">
                                {!! $soal?->pertanyaan !!}
                            </div>

                            {{-- Rincian Jawaban Siswa vs Kunci --}}
                            <div class="mt-2 p-3.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 space-y-2 text-xs">
                                {{-- Kasus 1: Pilihan Ganda / Benar Salah --}}
                                @if(in_array($soal?->tipe_soal, ['pilihan_ganda', 'benar_salah']))
                                    @php
                                        $opsiTerpilih = is_array($jwb->jawaban_pilihan) ? ($jwb->jawaban_pilihan[0] ?? null) : $jwb->jawaban_pilihan;
                                        $kunciBenar = $soal?->opsiJawabans->where('is_jawaban_benar', true)->first();
                                        $teksOpsiTerpilih = null;
                                        if ($opsiTerpilih && !empty($jwb->urutan_opsi) && is_array($jwb->urutan_opsi)) {
                                            $idx = ord(strtoupper($opsiTerpilih)) - 65;
                                            $idOpsi = $jwb->urutan_opsi[$idx] ?? null;
                                            $opsiModel = $soal?->opsiJawabans->firstWhere('id', $idOpsi);
                                            if ($opsiModel) {
                                                $teksOpsiTerpilih = strip_tags($opsiModel->teks_opsi);
                                            }
                                        } elseif ($opsiTerpilih) {
                                            $opsiModel = $soal?->opsiJawabans->firstWhere('label', $opsiTerpilih);
                                            if ($opsiModel) {
                                                $teksOpsiTerpilih = strip_tags($opsiModel->teks_opsi);
                                            }
                                        }
                                    @endphp
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 font-semibold">Jawaban Siswa:</span>
                                        <span class="font-bold text-sm {{ $isBenar ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $opsiTerpilih ?: 'Tidak Menjawab (Kosong)' }}
                                            @if($teksOpsiTerpilih)
                                                <span class="text-xs font-normal text-gray-500">({{ $teksOpsiTerpilih }})</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-800">
                                        <span class="text-gray-500 font-semibold">Kunci Jawaban Benar:</span>
                                        <span class="font-bold text-sm text-emerald-600">
                                            {{ $kunciBenar?->label ?: '-' }} {!! $kunciBenar ? "({$kunciBenar->teks_opsi})" : '' !!}
                                        </span>
                                    </div>

                                {{-- Kasus 2: PG Kompleks --}}
                                @elseif($soal?->tipe_soal === 'pg_kompleks')
                                    @php
                                        $arrSiswa = is_array($jwb->jawaban_pilihan) ? $jwb->jawaban_pilihan : [];
                                        $arrKunci = $soal?->opsiJawabans->where('is_jawaban_benar', true)->pluck('label')->toArray() ?: [];
                                    @endphp
                                    <div>
                                        <span class="text-gray-500 font-semibold block mb-1">Pilihan Siswa:</span>
                                        <span class="font-bold {{ $isBenar ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ count($arrSiswa) > 0 ? implode(', ', $arrSiswa) : 'Tidak Menjawab' }}
                                        </span>
                                    </div>
                                    <div class="pt-1 border-t border-gray-100 dark:border-gray-800">
                                        <span class="text-gray-500 font-semibold block mb-1">Kunci Jawaban Benar:</span>
                                        <span class="font-bold text-emerald-600">
                                            {{ implode(', ', $arrKunci) }}
                                        </span>
                                    </div>

                                {{-- Kasus 3: Isian Singkat --}}
                                @elseif($soal?->tipe_soal === 'isian')
                                    <div>
                                        <span class="text-gray-500 font-semibold block mb-1">Teks Jawaban Siswa:</span>
                                        <span class="font-bold text-sm {{ $isBenar ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $jwb->jawaban_teks ?: 'Tidak Menjawab' }}
                                        </span>
                                    </div>
                                    <div class="pt-1 border-t border-gray-100 dark:border-gray-800">
                                        <span class="text-gray-500 font-semibold block mb-1">Kunci Isian:</span>
                                        <span class="font-bold text-sm text-emerald-600">
                                            {{ $soal?->kunci_isian ?: '-' }}
                                        </span>
                                    </div>

                                {{-- Kasus 4: Essay / Uraian --}}
                                @elseif($soal?->tipe_soal === 'essay')
                                    <div class="space-y-2">
                                        <div>
                                            <span class="text-gray-500 font-semibold block mb-1">Lembar Jawaban Siswa:</span>
                                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white leading-relaxed font-sans whitespace-pre-wrap">
                                                {{ $jwb->jawaban_teks ?: 'Siswa tidak menuliskan jawaban uraian.' }}
                                            </div>
                                        </div>

                                        @if($soal?->rubrik_essay)
                                            <div class="pt-2 text-xs text-gray-500">
                                                <strong>Rubrik Penilaian:</strong> {{ $soal->rubrik_essay }}
                                            </div>
                                        @endif

                                        {{-- Input Koreksi Nilai Essay oleh Guru --}}
                                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
                                            @php
                                                $maxBobotEssay = $sesi ? $sesi->getEffectiveBobotForJawaban($jwb) : (float) ($soal->bobot_nilai ?? 10);
                                            @endphp
                                            <span class="font-bold text-gray-700 dark:text-gray-300">
                                                Beri Nilai Essay (Maks: {{ (float) $maxBobotEssay }} Poin):
                                            </span>
                                            <div class="flex items-center gap-2" x-data="{ skor: '{{ (float) $jwb->skor_didapat }}' }">
                                                <input
                                                    type="number"
                                                    step="0.25"
                                                    min="0"
                                                    max="{{ (float) $maxBobotEssay }}"
                                                    x-model="skor"
                                                    class="w-20 px-2 py-1 text-center font-bold text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800"
                                                />
                                                <button
                                                    type="button"
                                                    @click="$wire.updateSkorEssay({{ $jwb->id }}, parseFloat(skor))"
                                                    class="px-3 py-1 rounded-lg text-xs font-bold bg-primary-600 text-white hover:bg-primary-700 transition"
                                                >
                                                    Simpan Skor
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Pembahasan Soal jika ada --}}
                            @if($soal?->pembahasan)
                                <div class="mt-2.5 p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 text-xs text-blue-900 dark:text-blue-200">
                                    <strong class="font-bold text-blue-800 dark:text-blue-300">Pembahasan:</strong>
                                    <div class="mt-1 leading-relaxed">{!! $soal->pembahasan !!}</div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-12 text-center text-gray-400 italic">
                            Belum ada rekaman jawaban untuk sesi pengerjaan ini.
                        </div>
                    @endforelse
                </div>

                {{-- Footer Modal --}}
                <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 flex justify-end">
                    <button
                        type="button"
                        wire:click="tutupLembarJawaban"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-gray-900 text-white dark:bg-white dark:text-gray-900 hover:opacity-90 transition"
                    >
                        Tutup Lembar Jawaban
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

