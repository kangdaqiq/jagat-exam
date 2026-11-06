<div class="space-y-6">

    {{-- PRINT STYLES --}}
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                font-size: 11pt !important;
            }
            .print-only {
                display: block !important;
            }
            .card-print {
                border: 1px solid #d1d5db !important;
                box-shadow: none !important;
                background: white !important;
                break-inside: avoid;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #e5e7eb !important;
                padding: 6px 8px !important;
                font-size: 10pt !important;
            }
            th {
                background-color: #f3f4f6 !important;
            }
        }
        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>

    {{-- KOP LAPORAN RESMI SAAT DI-PRINT --}}
    <div class="print-only mb-6 text-center border-b-2 border-gray-900 pb-4">
        <h2 class="text-xl font-bold uppercase tracking-wider text-gray-900">REKAPITULASI HASIL PELAKSANAAN UJIAN CBT</h2>
        <h3 class="text-base font-semibold text-gray-800">{{ $this->hasilUjian->nama_ujian }}</h3>
        <p class="text-xs text-gray-600 mt-1">
            Mata Pelajaran: {{ $this->hasilUjian->mapel?->nama_mapel ?: '-' }} |
            Kelas: {{ $this->hasilUjian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }} |
            Guru Pengampu: {{ $this->hasilUjian->guru?->name ?: '-' }} |
            Tanggal: {{ $this->hasilUjian->tanggal ? $this->hasilUjian->tanggal->format('d/m/Y') : '-' }}
        </p>
    </div>

    {{-- 1. HEADER INFORMASI AGENDA UJIAN --}}
    <div class="p-5 sm:p-6 bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="space-y-2">
                {{-- Badges --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                        {{ $this->hasilUjian->exam_id }}
                    </span>

                    <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-lg {{
                        $this->hasilUjian->isAsesmenHarian()
                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200/50 dark:border-blue-500/20'
                            : ($this->hasilUjian->isAsesmenTengahSemester()
                                ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400 border border-amber-200/50 dark:border-amber-500/20'
                                : 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400 border border-rose-200/50 dark:border-rose-500/20')
                    }}">
                        {{ $this->hasilUjian->getTipeLabel() }}
                    </span>

                    @if($this->hasilUjian->status === 'COMPLETED')
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Ujian Selesai
                        </span>
                    @elseif($this->hasilUjian->status === 'ONGOING')
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/20">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            Sesi Berlangsung
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                            Dijadwalkan
                        </span>
                    @endif
                </div>

                {{-- Judul Ujian --}}
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight" style="font-family: 'Outfit', sans-serif;">
                    {{ $this->hasilUjian->nama_ujian }}
                </h1>

                {{-- Meta Information --}}
                <div class="flex items-center gap-y-1.5 gap-x-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap pt-1 font-medium">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Mapel: <strong class="text-gray-800 dark:text-gray-200 font-semibold">{{ $this->hasilUjian->mapel?->nama_mapel ?: '-' }}</strong>
                    </span>
                    <span>&bull;</span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Kelas: <strong class="text-gray-800 dark:text-gray-200 font-semibold">{{ $this->hasilUjian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</strong>
                    </span>
                    <span>&bull;</span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Guru Pengawas: <strong class="text-gray-800 dark:text-gray-200 font-semibold">{{ $this->hasilUjian->guru?->name ?: '-' }}</strong>
                    </span>
                    <span>&bull;</span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Tanggal: <strong class="text-gray-800 dark:text-gray-200 font-semibold">{{ $this->hasilUjian->tanggal ? $this->hasilUjian->tanggal->format('d M Y') : 'Fleksibel' }}</strong>
                    </span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="no-print flex items-center gap-2.5 self-start lg:self-center flex-wrap">
                <a
                    href="{{ route('hasil-ujian.export-csv', $this->hasilUjian->id) }}"
                    download
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-theme-xs hover:shadow-theme-sm cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export Excel / CSV
                </a>

                <a
                    href="{{ route('hasil-ujian.print', $this->hasilUjian->id) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-200 transition shadow-theme-xs cursor-pointer"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak / Print Rekap
                </a>
            </div>
        </div>
    </div>

    {{-- 2. STATS RINGKASAN METRIK KELAS (TAILADMIN STYLE) --}}
    @php
        $totalPeserta = $this->hasilUjian->pesertas()->count();
        $selesaiPeserta = $this->hasilUjian->pesertas()->where('status', 'SELESAI')->count();
        $mengerjakanPeserta = $this->hasilUjian->pesertas()->where('status', 'SEDANG_MENGERJAKAN')->count();
        $belumMulaiPeserta = $totalPeserta - ($selesaiPeserta + $mengerjakanPeserta);
        $persenSelesai = $totalPeserta > 0 ? round(($selesaiPeserta / $totalPeserta) * 100) : 0;
        $rataRata = $this->hasilUjian->rata_rata_nilai;
        $nilaiMax = $this->hasilUjian->nilai_tertinggi;
        $nilaiMin = $this->hasilUjian->nilai_terendah;
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5 w-full">
        {{-- Card 1: Total Peserta --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#1a2231] shadow-theme-xs transition hover:shadow-theme-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    Terdaftar
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: 'Outfit', sans-serif;">
                    Total Peserta
                </span>
                <h3 class="mt-1 font-extrabold text-gray-900 text-2xl lg:text-3xl dark:text-white" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">
                    {{ $totalPeserta }} <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Siswa</span>
                </h3>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 font-medium">
                    Total rombel kelas terpilih
                </p>
            </div>
        </div>

        {{-- Card 2: Sudah Selesai --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#1a2231] shadow-theme-xs transition hover:shadow-theme-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    {{ $persenSelesai }}% Selesai
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: 'Outfit', sans-serif;">
                    Sudah Selesai
                </span>
                <h3 class="mt-1 font-extrabold text-emerald-600 dark:text-emerald-400 text-2xl lg:text-3xl" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">
                    {{ $selesaiPeserta }} <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">/ {{ $totalPeserta }}</span>
                </h3>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 font-medium">
                    {{ $mengerjakanPeserta > 0 ? "{$mengerjakanPeserta} siswa sedang mengerjakan" : 'Semua sesi terpantau' }}
                </p>
            </div>
        </div>

        {{-- Card 3: Rata-Rata Nilai --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#1a2231] shadow-theme-xs transition hover:shadow-theme-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-[#ecf3ff] text-[#465fff] dark:bg-[#465fff]/15 dark:text-[#7592ff]">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-[#ecf3ff] px-2.5 py-0.5 text-xs font-semibold text-[#465fff] dark:bg-[#465fff]/15 dark:text-[#7592ff]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#465fff]"></span>
                    Skala 100
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: 'Outfit', sans-serif;">
                    Rata-Rata Nilai
                </span>
                <h3 class="mt-1 font-extrabold text-[#465fff] dark:text-[#7592ff] text-2xl lg:text-3xl" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">
                    {{ $rataRata !== null ? number_format($rataRata, 1) : '-' }}
                </h3>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 font-medium">
                    Kalkulasi otomatis bobot butir
                </p>
            </div>
        </div>

        {{-- Card 4: Min / Max --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#1a2231] shadow-theme-xs transition hover:shadow-theme-md">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    Sebaran Nilai
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: 'Outfit', sans-serif;">
                    Min &bull; Max
                </span>
                <h3 class="mt-1 font-extrabold text-gray-900 text-2xl lg:text-3xl dark:text-white" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">
                    {{ $nilaiMin !== null ? (float)$nilaiMin : '-' }}
                    <span class="text-sm font-normal text-gray-400 px-1">s/d</span>
                    <span class="text-emerald-600 dark:text-emerald-400">{{ $nilaiMax !== null ? (float)$nilaiMax : '-' }}</span>
                </h3>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 font-medium">
                    Nilai terendah & tertinggi siswa
                </p>
            </div>
        </div>
    </div>

    {{-- 3. FILTER, PENCARIAN & KELAS (NO PRINT) --}}
    <div class="no-print bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs p-4 sm:p-5 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        {{-- Input Pencarian & Dropdown Kelas --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <div class="relative w-full sm:w-80">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama atau NIS siswa..."
                    class="w-full pl-9 pr-4 py-2.5 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#465fff] transition"
                />
            </div>

            {{-- Dropdown Filter Kelas jika lebih dari 1 kelas --}}
            @if($this->hasilUjian->kelas->count() > 1)
                <select
                    wire:model.live="filterKelas"
                    class="px-3.5 py-2.5 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#465fff] transition font-medium"
                >
                    <option value="">Semua Rombel Kelas</option>
                    @foreach($this->hasilUjian->kelas as $k)
                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            @endif

            {{-- Tombol Reset jika filter aktif --}}
            @if(!empty($this->search) || $this->filterStatus !== 'all' || !empty($this->filterKelas))
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition self-start sm:self-center"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Reset Filter
                </button>
            @endif
        </div>

        {{-- Filter Status Tabs / Pills --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
            <button
                type="button"
                wire:click="$set('filterStatus', 'all')"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{
                    $filterStatus === 'all'
                        ? 'bg-[#465fff] text-white shadow-theme-xs'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                }}"
            >
                Semua ({{ $totalPeserta }})
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'SELESAI')"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{
                    $filterStatus === 'SELESAI'
                        ? 'bg-emerald-600 text-white shadow-theme-xs'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                }}"
            >
                Selesai ({{ $selesaiPeserta }})
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'SEDANG_MENGERJAKAN')"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{
                    $filterStatus === 'SEDANG_MENGERJAKAN'
                        ? 'bg-amber-600 text-white shadow-theme-xs'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                }}"
            >
                Sedang Mengerjakan ({{ $mengerjakanPeserta }})
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'BELUM_MULAI')"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{
                    $filterStatus === 'BELUM_MULAI'
                        ? 'bg-gray-700 text-white shadow-theme-xs'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                }}"
            >
                Belum Mulai ({{ $belumMulaiPeserta }})
            </button>
        </div>
    </div>

    {{-- 4. TABEL DAFTAR SISWA & NILAI (TAILADMIN CARD TABLE) --}}
    @php
        $pesertas = $this->pesertasList;
    @endphp

    <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs overflow-hidden card-print">
        {{-- Table Card Header --}}
        <div class="no-print px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white" style="font-family: 'Outfit', sans-serif;">
                    Daftar Rekap Nilai Siswa
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Menampilkan {{ $pesertas->count() }} siswa terdaftar
                </p>
            </div>
            <div class="text-xs text-gray-400">
                Klik kolom <span class="font-semibold text-gray-700 dark:text-gray-300">Nama</span>, <span class="font-semibold text-gray-700 dark:text-gray-300">NIS</span>, atau <span class="font-semibold text-gray-700 dark:text-gray-300">Nilai Akhir</span> untuk mengurutkan.
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#f9fafb] dark:bg-[#161f30] border-b border-gray-200 dark:border-gray-800 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4 cursor-pointer hover:text-gray-900 dark:hover:text-white transition" wire:click="toggleSort('nis')">
                            <span class="inline-flex items-center gap-1">
                                NIS
                                @if($sortBy === 'nis')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </span>
                        </th>
                        <th class="py-3.5 px-4 cursor-pointer hover:text-gray-900 dark:hover:text-white transition" wire:click="toggleSort('nama')">
                            <span class="inline-flex items-center gap-1">
                                Nama Lengkap Siswa
                                @if($sortBy === 'nama')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </span>
                        </th>
                        <th class="py-3.5 px-4">Kelas</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4">Waktu Mulai</th>
                        <th class="py-3.5 px-4">Waktu Selesai</th>
                        <th class="py-3.5 px-4 text-center font-black cursor-pointer hover:text-gray-900 dark:hover:text-white transition" wire:click="toggleSort('nilai')">
                            <span class="inline-flex items-center justify-center gap-1">
                                Nilai Akhir
                                @if($sortBy === 'nilai')
                                    <span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </span>
                        </th>
                        <th class="no-print py-3.5 px-4 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/80 font-medium">
                    @forelse($pesertas as $index => $peserta)
                        <tr class="hover:bg-[#f9fafb] dark:hover:bg-[#1f2a3d] transition">
                            <td class="py-3.5 px-4 text-center font-semibold text-gray-400">{{ $index + 1 }}</td>
                            <td class="py-3.5 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                {{ $peserta->siswa?->nis ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white">
                                {{ $peserta->siswa?->nama ?: ($peserta->user?->name ?: '-') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/50 dark:border-blue-500/20">
                                    {{ $peserta->siswa?->kelas?->nama_kelas ?: '-' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($peserta->status === 'SELESAI')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Selesai
                                    </span>
                                @elseif($peserta->status === 'SEDANG_MENGERJAKAN')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400 border border-amber-200/60 dark:border-amber-500/20">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                        </span>
                                        Mengerjakan
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        Belum Mulai
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 dark:text-gray-400 font-mono text-[11px]">
                                {{ $peserta->waktu_mulai ? $peserta->waktu_mulai->format('H:i:s') : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-600 dark:text-gray-400 font-mono text-[11px]">
                                {{ $peserta->waktu_selesai ? $peserta->waktu_selesai->format('H:i:s') : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($peserta->nilai !== null)
                                    @php
                                        $skor = (float) $peserta->nilai;
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-black {{
                                        $skor >= 75
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-500/20'
                                            : ($skor >= 60
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200/60 dark:border-blue-500/20'
                                                : 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400 border border-amber-200/60 dark:border-amber-500/20')
                                    }}">
                                        {{ $skor }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic text-xs">-</span>
                                @endif
                            </td>
                            <td class="no-print py-3.5 px-4 text-center">
                                @if($peserta->ujianSesi)
                                    <button
                                        type="button"
                                        wire:click="bukaLembarJawaban({{ $peserta->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-[#ecf3ff] text-[#465fff] hover:bg-[#465fff] hover:text-white dark:bg-[#465fff]/15 dark:text-[#7592ff] dark:hover:bg-[#465fff] dark:hover:text-white transition duration-150 shadow-2xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
                                Tidak ada data siswa yang cocok dengan kriteria pencarian atau filter yang dipilih.
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
            <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">

                {{-- Header Modal --}}
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/70 dark:bg-gray-800/40">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                Lembar Jawaban Siswa
                            </span>
                            <span class="text-xs text-gray-400">&bull;</span>
                            <span class="text-xs font-semibold text-gray-500">
                                NIS: {{ $sPeserta->siswa?->nis }} &bull; {{ $sPeserta->siswa?->kelas?->nama_kelas }}
                            </span>
                        </div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white mt-1" style="font-family: 'Outfit', sans-serif;">
                            {{ $sPeserta->siswa?->nama ?: $sPeserta->user?->name }}
                        </h3>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Total Nilai</span>
                            <span class="text-xl font-black text-[#465fff] dark:text-[#7592ff]">
                                {{ (float) ($sSesi?->skor_total ?? $sPeserta->nilai ?? 0) }} Poin
                            </span>
                        </div>

                        <button
                            type="button"
                            wire:click="tutupLembarJawaban"
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body Modal: Daftar Soal & Jawaban Siswa --}}
                <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5">
                    @forelse($jawabans as $idx => $jwb)
                        @php
                            $soal = $jwb->soal;
                            $isBenar = $jwb->is_benar;
                        @endphp
                        <div class="p-5 rounded-2xl border transition {{
                            $isBenar === true
                                ? 'border-emerald-200 bg-emerald-50/30 dark:border-emerald-900/60 dark:bg-emerald-950/10'
                                : ($isBenar === false
                                    ? 'border-rose-200 bg-rose-50/30 dark:border-rose-900/60 dark:bg-rose-950/10'
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
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                            SALAH (0 Poin)
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                            KOREKSI MANUAL
                                        </span>
                                    @endif

                                    <span class="text-xs text-gray-400">
                                        Bobot: {{ (float) ($sSesi ? $sSesi->getEffectiveBobotForJawaban($jwb) : ($soal?->bobot_nilai ?? 1)) }} Poin
                                    </span>
                                </div>
                            </div>

                            {{-- Pertanyaan Soal --}}
                            <div class="py-3 text-sm text-gray-900 dark:text-gray-100 leading-relaxed font-medium">
                                {!! $soal?->pertanyaan !!}
                            </div>

                            {{-- Rincian Jawaban Siswa vs Kunci --}}
                            <div class="mt-2 p-3.5 rounded-xl bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 space-y-2 text-xs">
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
                                        <span class="font-bold text-sm {{ $isBenar ? 'text-emerald-600' : 'text-rose-600' }}">
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
                                        <span class="font-bold {{ $isBenar ? 'text-emerald-600' : 'text-rose-600' }}">
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
                                        <span class="font-bold text-sm {{ $isBenar ? 'text-emerald-600' : 'text-rose-600' }}">
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
                                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white leading-relaxed font-sans whitespace-pre-wrap">
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
                                                $maxBobotEssay = $sSesi ? $sSesi->getEffectiveBobotForJawaban($jwb) : (float) ($soal?->bobot_nilai ?? 10);
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
                                                    class="w-20 px-2 py-1 text-center font-bold text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                                />
                                                <button
                                                    type="button"
                                                    @click="$wire.updateSkorEssay({{ $jwb->id }}, parseFloat(skor))"
                                                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-[#465fff] text-white hover:bg-[#3b51db] transition"
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
                        class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gray-900 text-white dark:bg-white dark:text-gray-900 hover:opacity-90 transition shadow-theme-xs"
                    >
                        Tutup Lembar Jawaban
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
