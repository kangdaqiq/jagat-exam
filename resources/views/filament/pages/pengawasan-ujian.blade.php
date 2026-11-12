<div class="space-y-6" wire:poll.5s>

    {{-- 1. CONTROL CENTER & TOKEN CONTROLLER --}}
    @php
        $ujian = $this->ujian;
        $isOngoing = $ujian->status === 'ONGOING';
        $isCompleted = $ujian->status === 'COMPLETED';
        $isScheduled = $ujian->status === 'SCHEDULED';
        $tokenActive = $ujian->isTokenActive();
        $sisaDetikToken = $ujian->sisaDetikToken();
        $menitSisa = floor($sisaDetikToken / 60);
        $detikSisa = $sisaDetikToken % 60;
    @endphp

    <div class="p-5 sm:p-6 bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            {{-- Info Ujian --}}
            <div class="space-y-2 min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                        {{ $ujian->exam_id }}
                    </span>

                    <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-lg {{
                        $ujian->isAsesmenHarian()
                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200/50'
                            : 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400 border border-amber-200/50'
                    }}">
                        {{ $ujian->getTipeLabel() }}
                    </span>

                    @if($isOngoing)
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/50">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            Sesi Sedang Berjalan
                        </span>
                    @elseif($isCompleted)
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200/50">
                            Ujian Selesai
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                            Dijadwalkan
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight" style="font-family: 'Outfit', sans-serif;">
                    {{ $ujian->nama_ujian }}
                </h1>

                <div class="flex items-center gap-y-1.5 gap-x-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap pt-1 font-medium">
                    <span>Mata Pelajaran: <strong class="text-gray-800 dark:text-gray-200 font-semibold">{{ $ujian->mapel?->nama_mapel ?: '-' }}</strong></span>
                    <span>&bull;</span>
                    <span>Kelas: <strong class="text-gray-800 dark:text-gray-200 font-semibold">{{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</strong></span>
                    <span>&bull;</span>
                    <span>Pengawas: <strong class="text-gray-800 dark:text-gray-200 font-semibold">{{ $ujian->guru?->name ?: '-' }}</strong></span>
                    <span>&bull;</span>
                    <span>Alokasi Durasi: <strong class="text-gray-800 dark:text-gray-200 font-semibold">{{ $ujian->durasi_menit ?: 60 }} Menit</strong></span>
                </div>
            </div>

            {{-- Token Monitor Box & Action Buttons --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 shrink-0">
                {{-- Box Token --}}
                <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center min-w-[200px]">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
                        Kode Token Masuk Siswa
                    </span>

                    @if($isOngoing && $ujian->token_ujian)
                        <div class="mt-1 flex items-center gap-2">
                            <span class="font-mono text-2xl font-black tracking-widest text-[#465fff] dark:text-[#7592ff]">
                                {{ $ujian->token_ujian }}
                            </span>
                        </div>

                        <div class="mt-1 flex items-center gap-1.5">
                            @if($tokenActive)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ sprintf('%02d:%02d', $menitSisa, $detikSisa) }} tersisa
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                    Kedaluwarsa
                                </span>
                            @endif
                        </div>
                    @elseif($isCompleted)
                        <span class="mt-1 text-sm font-bold text-gray-400">Sesi Ujian Selesai</span>
                    @else
                        <span class="mt-1 text-sm font-semibold text-gray-500">Ujian Belum Dimulai</span>
                    @endif
                </div>

                {{-- Tombol Aksi Kontrol Pengawas --}}
                <div class="flex flex-col gap-2">
                    @if($ujian->isJadwalBerakhir())
                        <button
                            type="button"
                            disabled
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-gray-200 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-700 cursor-not-allowed"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Rilis Token Ditutup (Waktu Habis)
                        </button>
                    @elseif($isScheduled || ($isOngoing && empty($ujian->token_ujian)))
                        <button
                            type="button"
                            wire:click="mulaiUjian"
                            wire:confirm="Mulai pelaksanaan ujian sekarang dan rilis token masuk 10 menit?"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-theme-xs transition cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Mulai Ujian Sekarang
                        </button>
                    @elseif($isOngoing)
                        <button
                            type="button"
                            wire:click="rilisTokenBaru"
                            wire:confirm="Rilis kode token baru (aktif selama 10 menit ke depan)?"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#465fff] hover:bg-[#3641f5] text-white shadow-theme-xs transition cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Rilis Token Baru (10 Menit)
                        </button>
                    @endif

                    <a
                        href="{{ route('hasil-ujian.berita-acara', $ujian->id) }}"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-200 transition shadow-theme-xs cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cetak Berita Acara & Daftar Hadir
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. STATISTIK PENGAWASAN REALTIME (4 KARTU METRIK) --}}
    @php
        $totalSiswa = $ujian->pesertas()->count();
        $sedangMengerjakan = $ujian->pesertas()->where('status', 'SEDANG_MENGERJAKAN')->count();
        $sudahSelesai = $ujian->pesertas()->where('status', 'SELESAI')->count();
        $belumMulai = $totalSiswa - ($sedangMengerjakan + $sudahSelesai);
        $persenSelesai = $totalSiswa > 0 ? round(($sudahSelesai / $totalSiswa) * 100) : 0;
        $totalPelanggaranSiswa = $ujian->pesertas()->whereHas('ujianSesi', fn($q) => $q->where('jumlah_pelanggaran', '>', 0))->count();
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5 w-full">
        {{-- Card 1: Total Siswa --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#1a2231] shadow-theme-xs">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">
                    Terdaftar
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: 'Outfit', sans-serif;">
                    Total Siswa Ujian
                </span>
                <h3 class="mt-1 font-extrabold text-gray-900 text-2xl lg:text-3xl dark:text-white" style="font-family: 'Outfit', sans-serif;">
                    {{ $totalSiswa }} <span class="text-sm font-semibold text-gray-500">Siswa</span>
                </h3>
                <p class="mt-1 text-xs text-gray-400 font-medium">Dari rombel kelas terpilih</p>
            </div>
        </div>

        {{-- Card 2: Sedang Mengerjakan --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#1a2231] shadow-theme-xs">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                    </span>
                    Live Mengerjakan
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: 'Outfit', sans-serif;">
                    Sedang Mengerjakan
                </span>
                <h3 class="mt-1 font-extrabold text-amber-600 dark:text-amber-400 text-2xl lg:text-3xl" style="font-family: 'Outfit', sans-serif;">
                    {{ $sedangMengerjakan }} <span class="text-sm font-semibold text-gray-500">Siswa Aktif</span>
                </h3>
                <p class="mt-1 text-xs text-gray-400 font-medium">Sedang menjawab butir soal</p>
            </div>
        </div>

        {{-- Card 3: Sudah Selesai --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#1a2231] shadow-theme-xs">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                    {{ $persenSelesai }}% Mengumpulkan
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: 'Outfit', sans-serif;">
                    Sudah Selesai
                </span>
                <h3 class="mt-1 font-extrabold text-emerald-600 dark:text-emerald-400 text-2xl lg:text-3xl" style="font-family: 'Outfit', sans-serif;">
                    {{ $sudahSelesai }} <span class="text-sm font-semibold text-gray-500">/ {{ $totalSiswa }}</span>
                </h3>
                <p class="mt-1 text-xs text-gray-400 font-medium">Lembar jawaban terkirim</p>
            </div>
        </div>

        {{-- Card 4: Belum Masuk --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-[#1a2231] shadow-theme-xs">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    Offline
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" style="font-family: 'Outfit', sans-serif;">
                    Belum Masuk / Mulai
                </span>
                <h3 class="mt-1 font-extrabold text-gray-900 text-2xl lg:text-3xl dark:text-white" style="font-family: 'Outfit', sans-serif;">
                    {{ $belumMulai }} <span class="text-sm font-semibold text-gray-500">Siswa</span>
                </h3>
                <p class="mt-1 text-xs text-gray-400 font-medium">Belum memasukkan token ujian</p>
            </div>
        </div>
    </div>

    {{-- 3. FILTER & PENCARIAN SISWA --}}
    <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs p-4 sm:p-5 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
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
                    class="w-full pl-9 pr-4 py-2.5 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#465fff] transition"
                />
            </div>

            @if($ujian->kelas->count() > 1)
                <select
                    wire:model.live="filterKelas"
                    class="px-3.5 py-2.5 text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#465fff] font-medium"
                >
                    <option value="">Semua Rombel Kelas</option>
                    @foreach($ujian->kelas as $k)
                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        {{-- Filter Status Tabs --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
            <button
                type="button"
                wire:click="$set('filterStatus', 'all')"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{
                    $filterStatus === 'all'
                        ? 'bg-[#465fff] text-white shadow-theme-xs'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                }}"
            >
                Semua ({{ $totalSiswa }})
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'SEDANG_MENGERJAKAN')"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{
                    $filterStatus === 'SEDANG_MENGERJAKAN'
                        ? 'bg-amber-600 text-white shadow-theme-xs'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                }}"
            >
                Mengerjakan ({{ $sedangMengerjakan }})
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'SELESAI')"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{
                    $filterStatus === 'SELESAI'
                        ? 'bg-emerald-600 text-white shadow-theme-xs'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                }}"
            >
                Selesai ({{ $sudahSelesai }})
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'BELUM_MULAI')"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{
                    $filterStatus === 'BELUM_MULAI'
                        ? 'bg-gray-700 text-white shadow-theme-xs'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                }}"
            >
                Belum Mulai ({{ $belumMulai }})
            </button>
            <button
                type="button"
                wire:click="$set('filterStatus', 'PELANGGARAN')"
                class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition {{
                    $filterStatus === 'PELANGGARAN'
                        ? 'bg-rose-600 text-white shadow-theme-xs'
                        : ($totalPelanggaranSiswa > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200')
                }}"
            >
                Pelanggaran ({{ $totalPelanggaranSiswa }})
            </button>
        </div>
    </div>

    {{-- 4. TABEL MONITORING SISWA REALTIME --}}
    @php
        $pesertas = $this->pesertasList;
        $totalSoalBank = $ujian->bankSoal?->soals()->count() ?: 0;
    @endphp

    <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white" style="font-family: 'Outfit', sans-serif;">
                    Daftar Siswa & Status Sesi Pengawasan
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Halaman ini otomatis me-refresh status pengerjaan setiap 5 detik.
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-gray-500 bg-gray-50 dark:bg-gray-800">
                <svg class="w-3.5 h-3.5 text-emerald-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Live Sync 5s
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#f9fafb] dark:bg-[#161f30] border-b border-gray-200 dark:border-gray-800 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">NIS</th>
                        <th class="py-3.5 px-4">Nama Siswa</th>
                        <th class="py-3.5 px-4">Kelas</th>
                        <th class="py-3.5 px-4 text-center">Status Sesi</th>
                        <th class="py-3.5 px-4">Progress Soal</th>
                        <th class="py-3.5 px-4 text-center">Integritas / Tab</th>
                        <th class="py-3.5 px-4">Waktu Mulai</th>
                        <th class="py-3.5 px-4 text-center">Nilai Sementara</th>
                        <th class="py-3.5 px-4 text-center w-60">Aksi Pengawas (Proktor)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/70 font-medium">
                    @forelse($pesertas as $index => $peserta)
                        @php
                            $sesi = $peserta->ujianSesi;
                            $jawabansCount = $sesi ? $sesi->jawabans->filter(fn($j) => !empty($j->jawaban_pilihan) || !empty($j->jawaban_teks))->count() : 0;
                            $persenJawab = $totalSoalBank > 0 ? round(($jawabansCount / $totalSoalBank) * 100) : 0;
                            $jmlPelanggaran = (int) ($sesi?->jumlah_pelanggaran ?? 0);
                        @endphp
                        <tr class="hover:bg-[#f9fafb] dark:hover:bg-[#1f2a3d] transition">
                            <td class="py-3.5 px-4 text-center font-semibold text-gray-400">{{ $index + 1 }}</td>
                            <td class="py-3.5 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                {{ $peserta->siswa?->nis ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white">
                                {{ $peserta->siswa?->nama ?: ($peserta->user?->name ?: '-') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/50">
                                    {{ $peserta->siswa?->kelas?->nama_kelas ?: '-' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($peserta->status === 'SELESAI')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/60">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Selesai
                                    </span>
                                @elseif($peserta->status === 'SEDANG_MENGERJAKAN')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400 border border-amber-200/60">
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

                            {{-- Progress Pengerjaan --}}
                            <td class="py-3.5 px-4">
                                @if($sesi)
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ $jawabansCount }} / {{ $totalSoalBank }} Soal</span>
                                            <span class="text-gray-400">{{ $persenJawab }}%</span>
                                        </div>
                                        <div class="w-full h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $persenJawab }}%"></div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400 italic text-[11px]">-</span>
                                @endif
                            </td>

                            {{-- Integritas / Pelanggaran Pindah Tab --}}
                            <td class="py-3.5 px-4 text-center">
                                @if(!$sesi)
                                    <span class="text-gray-400 italic text-[11px]">-</span>
                                @elseif($jmlPelanggaran >= 3)
                                    <span
                                        title="{{ $sesi->terakhir_pelanggaran_at ? 'Terakhir terdeteksi pukul ' . $sesi->terakhir_pelanggaran_at->format('H:i:s') : 'Sesi terkunci karena pelanggaran >= 3x' }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-rose-600 text-white shadow-2xs"
                                    >
                                        🔒 Terkunci ({{ $jmlPelanggaran }}x Pindah Tab)
                                    </span>
                                @elseif($jmlPelanggaran > 0)
                                    <span
                                        title="{{ $sesi->terakhir_pelanggaran_at ? 'Terakhir terdeteksi pukul ' . $sesi->terakhir_pelanggaran_at->format('H:i:s') : 'Terdeteksi keluar tab/aplikasi' }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-extrabold {{ $jmlPelanggaran >= 3 ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}"
                                    >
                                        <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        {{ $jmlPelanggaran }}x Pindah Tab
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                        Aman
                                    </span>
                                @endif
                            </td>

                            {{-- Waktu Mulai --}}
                            <td class="py-3.5 px-4 font-mono text-[11px] text-gray-600 dark:text-gray-400">
                                {{ $peserta->waktu_mulai ? $peserta->waktu_mulai->format('H:i:s') : '-' }}
                            </td>

                            {{-- Nilai Sementara --}}
                            <td class="py-3.5 px-4 text-center font-bold">
                                @if($peserta->nilai !== null)
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                                        {{ (float) $peserta->nilai }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic text-xs">-</span>
                                @endif
                            </td>

                            {{-- Aksi Pengawas --}}
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1">
                                    {{-- Tambah Waktu +10 Menit (Jika sudah punya sesi) --}}
                                    @if($peserta->ujianSesi)
                                        <button
                                            type="button"
                                            wire:click="tambahWaktuSiswa({{ $peserta->id }}, 10)"
                                            wire:confirm="Tambahkan ekstra waktu +10 menit untuk siswa {{ $peserta->siswa?->nama }}?"
                                            title="Tambah Waktu +10 Menit"
                                            class="px-2 py-1 rounded-lg text-[11px] font-extrabold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/70 transition cursor-pointer"
                                        >
                                            +10m
                                        </button>
                                    @endif

                                    {{-- Reset Login Sesi --}}
                                    <button
                                        type="button"
                                        wire:click="resetSesiSiswa({{ $peserta->id }})"
                                        wire:confirm="Reset login untuk siswa {{ $peserta->siswa?->nama }}? Siswa dapat login kembali dan melanjutkan ujian tanpa kehilangan jawaban."
                                        title="Reset Login / Lanjutkan Ujian (Jawaban Aman)"
                                        class="p-1.5 rounded-lg text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>

                                    {{-- Reset Ujian Total (Hapus Jawaban & Ulang Dari Awal) --}}
                                    <button
                                        type="button"
                                        wire:click="resetUjianSiswaTotal({{ $peserta->id }})"
                                        wire:confirm="PERINGATAN: Reset Ujian Total akan MENGHAPUS SEMUA JAWABAN dan mengatur ulang waktu mulai/selesai siswa {{ $peserta->siswa?->nama }} dari nol. Apakah Anda yakin?"
                                        title="Reset Ujian Total (Ulang Dari Nol & Hapus Jawaban)"
                                        class="p-1.5 rounded-lg text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/40 transition cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>

                                    {{-- Paksa Selesaikan (Jika Masih Mengerjakan) --}}
                                    @if($peserta->status === 'SEDANG_MENGERJAKAN')
                                        <button
                                            type="button"
                                            wire:click="paksaSelesaiSiswa({{ $peserta->id }})"
                                            wire:confirm="Paksa selesaikan ujian untuk siswa {{ $peserta->siswa?->nama }} sekarang?"
                                            title="Paksa Selesaikan Ujian"
                                            class="p-1.5 rounded-lg text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Buka Lembar Jawaban --}}
                                    @if($peserta->ujianSesi)
                                        <button
                                            type="button"
                                            wire:click="bukaLembarJawaban({{ $peserta->id }})"
                                            title="Lihat Lembar Jawaban"
                                            class="p-1.5 rounded-lg text-[#465fff] hover:text-[#3641f5] hover:bg-[#ecf3ff] dark:hover:bg-[#465fff]/20 transition cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-gray-400 italic">
                                Belum ada siswa terdaftar pada sesi ujian ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 5. MODAL LIVE LEMBAR JAWABAN SISWA --}}
    @if($this->selectedPeserta)
        @php
            $sPeserta = $this->selectedPeserta;
            $sSesi = $sPeserta->ujianSesi;
            $jawabans = $sSesi ? $sSesi->jawabans : collect();
        @endphp

        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5">
            <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/70 dark:bg-gray-800/40">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                Lembar Jawaban Realtime
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

                <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4">
                    @forelse($jawabans as $jwb)
                        @php
                            $soal = $jwb->soal;
                            $tipeSoal = $soal?->tipe_soal ?? 'pilihan_ganda';
                            $opsiList = $jwb->getOrderedOpsiWithSelection();
                            $selectedOpsi = collect($opsiList)->where('is_selected', true)->values();
                        @endphp
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20 text-xs space-y-2.5">
                            <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800 font-semibold text-gray-600 dark:text-gray-300">
                                <span>Nomor Soal: {{ $jwb->nomor_urut }}</span>
                                @if($jwb->is_ragu)
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">Ragu-ragu</span>
                                @endif
                            </div>
                            <div class="py-1 text-gray-800 dark:text-gray-200 font-medium text-sm leading-relaxed">
                                {!! $soal?->pertanyaan !!}
                            </div>
                            <div class="pt-1">
                                @if(in_array($tipeSoal, ['pilihan_ganda', 'pg_kompleks', 'benar_salah']))
                                    @if($selectedOpsi->isNotEmpty())
                                        <div class="space-y-1.5">
                                            <div class="text-[11px] font-bold text-gray-500">Jawaban yang Dipilih Siswa:</div>
                                            @foreach($selectedOpsi as $opt)
                                                <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800">
                                                    <span class="w-6 h-6 rounded-lg bg-[#465fff] text-white font-black text-xs flex items-center justify-center shrink-0">
                                                        {{ $opt['label'] }}
                                                    </span>
                                                    <div class="text-xs font-bold text-gray-900 dark:text-white leading-relaxed flex-1">
                                                        {!! $opt['teks_opsi'] !!}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-gray-400 italic">Belum Dijawab</div>
                                    @endif
                                @else
                                    <div class="text-gray-700 dark:text-gray-200">
                                        <strong>Jawaban Siswa:</strong>
                                        {{ is_array($jwb->jawaban_pilihan) ? implode(', ', $jwb->jawaban_pilihan) : ($jwb->jawaban_pilihan ?: ($jwb->jawaban_teks ?: 'Belum Dijawab')) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-gray-400 italic">
                            Belum ada jawaban tersimpan.
                        </div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 flex justify-end">
                    <button
                        type="button"
                        wire:click="tutupLembarJawaban"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gray-900 text-white dark:bg-white dark:text-gray-900 hover:opacity-90 transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

