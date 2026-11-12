<div class="min-h-[100dvh] flex flex-col bg-[#f4f7fb] text-slate-800" style="background-color: #f4f7fb; color: #1e293b;" wire:poll.5s>

    {{-- ================================================================= --}}
    {{-- STICKY TOPBAR PWA PENGAWAS (DEEP NAVY & EMERALD)                  --}}
    {{-- ================================================================= --}}
    <header
        class="sticky top-0 z-30 text-white shadow-md"
        style="background: linear-gradient(135deg, #041F45 0%, #072d61 60%, #065f46 100%); color: #ffffff; border-bottom: 1px solid rgba(255,255,255,0.12);"
    >
        <div class="max-w-6xl mx-auto px-4 py-3.5 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                @if($activeUjian || $activeRuang || $activeJadwalRuang)
                    <button
                        type="button"
                        wire:click="kembaliKeDaftarUjian"
                        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 active:scale-95 transition cursor-pointer"
                        style="background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.25); color: #ffffff;"
                        title="Kembali ke Daftar Sesi / Ujian"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                @else
                    <img src="{{ asset('images/logo/examuna-logo-white.png') }}" alt="Examuna" class="h-9 w-auto object-contain shrink-0 drop-shadow-sm" />
                @endif

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0" style="background-color: #34d399;"></span>
                        <span class="text-sm sm:text-base font-black tracking-tight truncate" style="font-family: 'Outfit', sans-serif; color: #ffffff;">
                            @if($activeJadwalRuang)
                                {{ $activeJadwalRuang->ruangUjian?->nama_ruang }} &bull; Sesi {{ $activeJadwalRuang->sesi_ke }}
                            @elseif($activeRuang)
                                {{ $activeRuang->nama_ruang }}
                            @elseif($activeUjian)
                                {{ $activeUjian->nama_ujian }}
                            @else
                                Ruang Pengawas &amp; Proktor CBT
                            @endif
                        </span>
                    </div>
                    <div class="text-[11px] truncate font-medium" style="color: #cbd5e1;">
                        {{ $user?->name }} &bull; {{ $user?->isAdmin() ? 'Administrator' : 'Guru Pengawas' }}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                {{-- Pintasan ke Panel Lengkap --}}

                {{-- Tombol Keluar --}}
                <button
                    type="button"
                    wire:click="logout"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                    style="background: rgba(239,68,68,0.22); border: 1px solid rgba(252,165,165,0.4); color: #fecaca;"
                    title="Keluar dari Portal Pengawas"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="hidden sm:inline">Keluar</span>
                </button>
            </div>
        </div>
    </header>

    {{-- ================================================================= --}}
    {{-- KONTEN UTAMA                                                      --}}
    {{-- ================================================================= --}}
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-5 space-y-5">

        {{-- Notifikasi Aksi Cepat --}}
        @if($flashMessage)
            <div
                class="p-3.5 rounded-2xl border flex items-center justify-between gap-3 text-xs font-bold shadow-xs"
                style="{{ $flashType === 'success' ? 'background-color: #ecfdf5; border-color: #a7f3d0; color: #065f46;' : 'background-color: #eff6ff; border-color: #bfdbfe; color: #1e40af;' }}"
            >
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $flashType === 'success' ? '#10b981' : '#3b82f6' }};"></span>
                    <span>{{ $flashMessage }}</span>
                </div>
                <button type="button" wire:click="$set('flashMessage', null)" class="text-slate-400 hover:text-slate-700 font-black cursor-pointer">
                    &times;
                </button>
            </div>
        @endif



        {{-- ============================================================= --}}
        {{-- MODE 1: PILIH SESI RUANG UJIAN ATAU JADWAL SOAL YANG DIAWASI  --}}
        {{-- ============================================================= --}}
        @if(!$activeUjian && !$activeRuang && !$activeJadwalRuang)
            @php
                $hasSesiMode = $daftarSesiRuang->isNotEmpty() || $filterSesiRuang !== 'semua';
                $countTabRuang = $hasSesiMode ? $daftarSesiRuang->count() : $daftarRuang->count();
            @endphp
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
                <div>
                    <h1 class="text-lg font-black" style="font-family: 'Outfit', sans-serif; color: #041F45;">
                        {{ $tabMode === 'ruang' ? ($hasSesiMode ? 'Pilih Sesi Ruang Ujian untuk Diawasi' : 'Pilih Ruang Ujian untuk Diawasi') : 'Pilih Jadwal Soal Ujian' }}
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $tabMode === 'ruang' ? 'Setiap sesi memiliki riwayat peserta, token, Daftar Hadir, dan BAP tersendiri yang dapat diakses kapanpun.' : 'Ketuk salah satu jadwal ujian di bawah untuk memantau siswa.' }}
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <div class="inline-flex rounded-xl p-1 border border-slate-200" style="background-color: #f1f5f9;">
                        <button
                            type="button"
                            wire:click="$set('tabMode', 'ruang')"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer"
                            style="{{ $tabMode === 'ruang' ? 'background-color: #059669; color: #ffffff; box-shadow: 0 1px 3px rgba(5,150,105,0.25);' : 'background-color: transparent; color: #475569;' }}"
                        >
                            Sesi Ruang ({{ $countTabRuang }})
                        </button>
                        <button
                            type="button"
                            wire:click="$set('tabMode', 'ujian')"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer"
                            style="{{ $tabMode === 'ujian' ? 'background-color: #041F45; color: #ffffff; box-shadow: 0 1px 3px rgba(4,31,69,0.25);' : 'background-color: transparent; color: #475569;' }}"
                        >
                            Per Jadwal Soal ({{ $daftarUjian->count() }})
                        </button>
                    </div>

                    @if($tabMode === 'ruang' && $user?->isGuru())
                        <div class="inline-flex rounded-xl p-1 border border-slate-200" style="background-color: #f1f5f9;">
                            <button
                                type="button"
                                wire:click="$set('scopeRuang', 'saya')"
                                class="px-3 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer"
                                style="{{ $scopeRuang === 'saya' ? 'background-color: #059669; color: #ffffff;' : 'background-color: transparent; color: #475569;' }}"
                            >
                                Tugas Saya ({{ $this->jumlahRuangTugasSaya }})
                            </button>
                            <button
                                type="button"
                                wire:click="$set('scopeRuang', 'semua')"
                                class="px-3 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer"
                                style="{{ $scopeRuang === 'semua' ? 'background-color: #059669; color: #ffffff;' : 'background-color: transparent; color: #475569;' }}"
                            >
                                Semua ({{ $this->jumlahSemuaRuang }})
                            </button>
                        </div>
                    @endif

                    @if($tabMode === 'ujian' && $user?->isGuru())
                        <div class="inline-flex rounded-xl p-1 border border-slate-200" style="background-color: #f1f5f9;">
                            <button
                                type="button"
                                wire:click="$set('scopeUjian', 'saya')"
                                class="px-3 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer"
                                style="{{ $scopeUjian === 'saya' ? 'background-color: #041F45; color: #ffffff;' : 'background-color: transparent; color: #475569;' }}"
                            >
                                Ujian Saya
                            </button>
                            <button
                                type="button"
                                wire:click="$set('scopeUjian', 'semua')"
                                class="px-3 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer"
                                style="{{ $scopeUjian === 'semua' ? 'background-color: #041F45; color: #ffffff;' : 'background-color: transparent; color: #475569;' }}"
                            >
                                Semua
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            @if($tabMode === 'ruang')
                @if($hasSesiMode)
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button
                            type="button"
                            wire:click="$set('filterSesiRuang', 'semua')"
                            class="px-3 py-1.5 rounded-xl text-xs font-extrabold border transition cursor-pointer"
                            style="{{ $filterSesiRuang === 'semua' ? 'background-color: #041F45; color: #ffffff; border-color: #041F45;' : 'background-color: #ffffff; color: #475569; border-color: #e2e8f0;' }}"
                        >
                            Semua Sesi
                        </button>
                        <button
                            type="button"
                            wire:click="$set('filterSesiRuang', 'aktif')"
                            class="px-3 py-1.5 rounded-xl text-xs font-extrabold border transition cursor-pointer"
                            style="{{ $filterSesiRuang === 'aktif' ? 'background-color: #059669; color: #ffffff; border-color: #059669;' : 'background-color: #ffffff; color: #475569; border-color: #e2e8f0;' }}"
                        >
                            Aktif / Dijadwalkan
                        </button>
                        <button
                            type="button"
                            wire:click="$set('filterSesiRuang', 'selesai')"
                            class="px-3 py-1.5 rounded-xl text-xs font-extrabold border transition cursor-pointer"
                            style="{{ $filterSesiRuang === 'selesai' ? 'background-color: #334155; color: #ffffff; border-color: #334155;' : 'background-color: #ffffff; color: #475569; border-color: #e2e8f0;' }}"
                        >
                            Riwayat Selesai (BAP &amp; Daftar Hadir)
                        </button>
                    </div>

                    @if($daftarSesiRuang->isEmpty())
                        <div class="bg-white rounded-3xl border border-dashed border-slate-300 p-10 text-center space-y-3">
                            <div class="text-sm font-extrabold text-slate-700">
                                Tidak ada sesi ruangan untuk filter ini
                            </div>
                            <button
                                type="button"
                                wire:click="$set('filterSesiRuang', 'semua')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition"
                                style="background-color: #041F45; color: #ffffff;"
                            >
                                <span>Tampilkan Semua Sesi</span>
                            </button>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($daftarSesiRuang as $jr)
                                @php
                                    $ruangModel = $jr->ruangUjian;
                                    $tokenAktif = $jr->isTokenActive();
                                    $isSesiSelesai = $jr->status === 'COMPLETED';
                                    $isSesiBerlangsung = $jr->status === 'ONGOING';
                                    $jumlahPesertaSesi = $jr->getSiswasForSession()->count();
                                    $namaPengawasSesi = $jr->pengawas?->name ?: ($ruangModel?->pengawas?->name ?: 'Belum Ditugaskan');
                                    if ($jr->pengawas && $jr->pengawasPendamping) {
                                        $namaPengawasSesi .= ' & ' . $jr->pengawasPendamping->name;
                                    }
                                    $hasBapTersimpan = $jr->beritaAcaras->isNotEmpty();
                                @endphp
                                <div
                                    class="bg-white rounded-2xl p-4 sm:p-5 flex flex-col justify-between gap-4 shadow-xs hover:shadow-md transition"
                                    style="border: {{ $tokenAktif || $isSesiBerlangsung ? '2px solid #10b981' : '1px solid #e2e8f0' }};"
                                >
                                    <div class="space-y-2.5">
                                        <div class="flex items-center justify-between gap-2 flex-wrap">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-700 font-mono">
                                                    {{ $ruangModel?->kode_ruang ?: 'RUANG' }}
                                                </span>
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                    {{ $jr->tanggal?->format('d M Y') }} &bull; Sesi {{ $jr->sesi_ke }}
                                                </span>
                                            </div>

                                            @if($tokenAktif)
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #d1fae5; color: #065f46;">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                                    TOKEN AKTIF
                                                </span>
                                            @elseif($isSesiBerlangsung)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #d1fae5; color: #065f46;">
                                                    Berlangsung
                                                </span>
                                            @elseif($isSesiSelesai)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-slate-200 text-slate-700">
                                                    Selesai (Arsip)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold" style="background-color: #fef3c7; color: #92400e;">
                                                    Dijadwalkan
                                                </span>
                                            @endif
                                        </div>

                                        <div>
                                            <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wide truncate">
                                                {{ $jr->daftar_mapel }}
                                            </div>
                                            <h3 class="text-lg font-black text-slate-900 leading-snug mt-0.5" style="font-family: 'Outfit', sans-serif;">
                                                {{ $ruangModel?->nama_ruang ?: 'Ruang Ujian' }}
                                            </h3>
                                            <div class="text-xs font-bold text-slate-600 mt-0.5 line-clamp-2">
                                                {{ $jr->daftar_nama_ujian }}
                                            </div>
                                        </div>

                                        <div class="text-xs text-slate-600 space-y-1.5 pt-1">
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-400">Jumlah Peserta:</span>
                                                <span class="font-bold text-slate-800">{{ $jumlahPesertaSesi }} Siswa</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-400">Pengawas Ruang:</span>
                                                <span class="font-semibold text-slate-700">{{ $namaPengawasSesi }}</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-400">Jam Jadwal:</span>
                                                <span class="font-extrabold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">
                                                    {{ $jr->label_sesi_waktu }}
                                                </span>
                                            </div>
                                        </div>

                                        @if($jr->token_ruang)
                                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                                <span class="text-[11px] font-bold text-slate-500 uppercase">Token Sesi:</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono text-base font-black tracking-widest" style="color: #041F45;">{{ $jr->token_ruang }}</span>
                                                    <span
                                                        class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                        style="{{ $tokenAktif ? 'background-color: #d1fae5; color: #065f46;' : 'background-color: #ffe4e6; color: #be123c;' }}"
                                                    >
                                                        {{ $jr->sisaWaktuTokenFormatted() }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="space-y-2">
                                        <button
                                            type="button"
                                            wire:click="pilihJadwalRuang({{ $jr->id }})"
                                            class="w-full py-2.5 px-4 rounded-xl text-xs font-extrabold text-white flex items-center justify-center gap-2 shadow-xs active:scale-[0.99] transition cursor-pointer"
                                            style="background: {{ $isSesiSelesai ? 'linear-gradient(135deg, #334155 0%, #475569 100%)' : 'linear-gradient(135deg, #041F45 0%, #059669 100%)' }}; color: #ffffff;"
                                        >
                                            <span>{{ $isSesiSelesai ? 'Buka Riwayat Sesi & Peserta »' : 'Buka Sesi & Rilis Token »' }}</span>
                                        </button>

                                        <div class="grid grid-cols-2 gap-1.5">
                                            <a
                                                href="{{ route('daftar-hadir.print', ['ruang_ujian_id' => $jr->ruang_ujian_id, 'jadwal_ruang_id' => $jr->id, 'ujian_id' => $jr->ujian_id]) }}"
                                                target="_blank"
                                                class="py-1.5 px-2.5 rounded-xl text-[11px] font-extrabold border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-800 text-center transition"
                                            >
                                                Daftar Hadir
                                            </a>
                                            <button
                                                type="button"
                                                wire:click="bukaModalBap({{ $jr->id }})"
                                                class="py-1.5 px-2.5 rounded-xl text-[11px] font-extrabold border text-center transition cursor-pointer"
                                                style="{{ $hasBapTersimpan ? 'background-color: #d1fae5; border-color: #6ee7b7; color: #065f46;' : 'background-color: #ecfdf5; border-color: #a7f3d0; color: #047857;' }}"
                                            >
                                                {{ $hasBapTersimpan ? '✓ BAP Tersimpan' : 'Isi / Cetak BAP' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @elseif($daftarRuang->isEmpty())
                    <div class="bg-white rounded-3xl border border-dashed border-slate-300 p-10 text-center space-y-3">
                        <div class="text-sm font-extrabold text-slate-700">
                            {{ $scopeRuang === 'saya' ? 'Tidak ada jadwal penugasan pengawasan ruang untuk Anda saat ini' : 'Belum ada Ruang Ujian yang dibuat' }}
                        </div>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            {{ $scopeRuang === 'saya' ? 'Jika Anda ingin memantau seluruh ruangan di sekolah, silakan ketuk tombol Semua Ruang di atas.' : 'Admin dapat membuat Ruang Ujian dan membagi siswa di menu Ruang Ujian & Token.' }}
                        </p>
                        <div class="pt-1 flex items-center justify-center gap-2">
                            @if($scopeRuang === 'saya' && $this->jumlahSemuaRuang > 0)
                                <button
                                    type="button"
                                    wire:click="$set('scopeRuang', 'semua')"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition"
                                    style="background-color: #059669; color: #ffffff;"
                                >
                                    <span>Tampilkan Semua Ruang Sekolah &raquo;</span>
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="$set('tabMode', 'ujian')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition"
                                style="background-color: #041F45; color: #ffffff;"
                            >
                                <span>Pantau Berdasarkan Jadwal Soal &raquo;</span>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($daftarRuang as $rg)
                            @php
                                $kelasMix = $rg->siswas->pluck('kelas.nama_kelas')->filter()->unique()->values();
                                $tokenAktif = $rg->isTokenActive();
                            @endphp
                            <div
                                class="bg-white rounded-2xl p-4 sm:p-5 flex flex-col justify-between gap-4 shadow-xs hover:shadow-md transition"
                                style="border: {{ $tokenAktif ? '2px solid #10b981' : '1px solid #e2e8f0' }};"
                            >
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-700 font-mono">
                                            {{ $rg->kode_ruang ?: 'RUANG-CBT' }}
                                        </span>

                                        @if($tokenAktif)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #d1fae5; color: #065f46;">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                                TOKEN AKTIF
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                                Siap Digunakan
                                            </span>
                                        @endif
                                    </div>

                                    <div>
                                        <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wide">
                                            Ruang Ujian
                                        </div>
                                        <h3 class="text-lg font-black text-slate-900 leading-snug mt-0.5" style="font-family: 'Outfit', sans-serif;">
                                            {{ $rg->nama_ruang }}
                                        </h3>
                                    </div>

                                    <div class="text-xs text-slate-600 space-y-1.5 pt-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-400">Jumlah Peserta:</span>
                                            <span class="font-bold text-slate-800">{{ $rg->siswas_count }} Siswa</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-400">Pengawas Ruang:</span>
                                            <span class="font-semibold text-slate-700">{{ $rg->nama_pengawas_tugas }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-400">Jam Jadwal:</span>
                                            <span class="font-extrabold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span>{{ $rg->info_jadwal_waktu }}</span>
                                            </span>
                                        </div>
                                    </div>

                                    @if($rg->token_ruang)
                                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                            <span class="text-[11px] font-bold text-slate-500 uppercase">Token Ruangan:</span>
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono text-base font-black tracking-widest" style="color: #041F45;">{{ $rg->token_ruang }}</span>
                                                <span
                                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                    style="{{ $tokenAktif ? 'background-color: #d1fae5; color: #065f46;' : 'background-color: #ffe4e6; color: #be123c;' }}"
                                                >
                                                    {{ $rg->sisaWaktuTokenFormatted() }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <button
                                    type="button"
                                    wire:click="pilihRuang({{ $rg->id }})"
                                    class="w-full py-2.5 px-4 rounded-xl text-xs font-extrabold text-white flex items-center justify-center gap-2 shadow-xs active:scale-[0.99] transition cursor-pointer"
                                    style="background: linear-gradient(135deg, #041F45 0%, #059669 100%); color: #ffffff;"
                                >
                                    <span>Buka Ruang &amp; Rilis Token &raquo;</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            @elseif($daftarUjian->isEmpty())
                <div class="bg-white rounded-3xl border border-dashed border-slate-300 p-10 text-center space-y-3">
                    <div class="text-sm font-extrabold text-slate-700">Belum ada jadwal ujian ditemukan</div>
                    <p class="text-xs text-slate-500 max-w-md mx-auto">
                        @if($scopeUjian === 'saya')
                            Anda belum memiliki jadwal ujian yang ditugaskan langsung. Klik tombol di bawah untuk melihat seluruh jadwal ujian sekolah.
                        @else
                            Belum ada jadwal ujian yang dibuat pada sistem CBT.
                        @endif
                    </p>
                    @if($scopeUjian === 'saya')
                        <div class="pt-1">
                            <button
                                type="button"
                                wire:click="$set('scopeUjian', 'semua')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold cursor-pointer transition"
                                style="background-color: #041F45; color: #ffffff;"
                            >
                                <span>Tampilkan Semua Ujian Sekolah</span>
                            </button>
                        </div>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($daftarUjian as $uj)
                        @php
                            $ongoing = $uj->status === 'ONGOING';
                            $completed = $uj->status === 'COMPLETED';
                        @endphp
                        <div
                            class="bg-white rounded-2xl p-4 sm:p-5 flex flex-col justify-between gap-4 shadow-xs hover:shadow-md transition"
                            style="border: {{ $ongoing ? '2px solid #10b981' : '1px solid #e2e8f0' }};"
                        >
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-700 font-mono">
                                        {{ $uj->exam_id }}
                                    </span>

                                    @if($ongoing)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #d1fae5; color: #065f46;">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                            SESI BERJALAN
                                        </span>
                                    @elseif($completed)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                            Selesai
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold" style="background-color: #fef3c7; color: #92400e;">
                                            Dijadwalkan
                                        </span>
                                    @endif
                                </div>

                                <div>
                                    <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wide">
                                        {{ $uj->mapel?->nama_mapel ?: 'Mata Pelajaran' }}
                                    </div>
                                    <h3 class="text-base font-black text-slate-900 leading-snug mt-0.5" style="font-family: 'Outfit', sans-serif;">
                                        {{ $uj->nama_ujian }}
                                    </h3>
                                </div>

                                <div class="text-xs text-slate-600 space-y-1 pt-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Rombel Kelas:</span>
                                        <span class="font-bold text-slate-800">{{ $uj->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Guru Pengampu:</span>
                                        <span class="font-semibold text-slate-700">{{ $uj->guru?->name ?: '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400">Peserta Aktif:</span>
                                        <span class="font-bold text-slate-800">
                                            {{ $uj->sedang_mengerjakan_count }} Mengerjakan &bull; {{ $uj->selesai_count }}/{{ $uj->pesertas_count }} Selesai
                                        </span>
                                    </div>
                                </div>

                                @if($ongoing && $uj->token_ujian)
                                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-slate-500 uppercase">Token Saat Ini:</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-base font-black tracking-widest" style="color: #041F45;">{{ $uj->token_ujian }}</span>
                                            <span
                                                class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                style="{{ $uj->isTokenActive() ? 'background-color: #d1fae5; color: #065f46;' : 'background-color: #ffe4e6; color: #be123c;' }}"
                                            >
                                                {{ $uj->sisaWaktuTokenFormatted() }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <button
                                type="button"
                                wire:click="pilihUjian({{ $uj->id }})"
                                class="w-full py-2.5 px-4 rounded-xl text-xs font-extrabold text-white flex items-center justify-center gap-2 shadow-xs active:scale-[0.99] transition cursor-pointer"
                                style="background: linear-gradient(135deg, #041F45 0%, #059669 100%); color: #ffffff;"
                            >
                                <span>Buka Ruang Pengawasan &raquo;</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

        {{-- ============================================================= --}}
        {{-- MODE 2A: PENGAWASAN PER SESI RUANG / RUANG UJIAN              --}}
        {{-- ============================================================= --}}
        @elseif($activeJadwalRuang || $activeRuang)
            @php
                $isSessionMode = (bool) $activeJadwalRuang;
                $roomObj = $activeJadwalRuang?->ruangUjian ?: $activeRuang;

                $tokenSource = $activeJadwalRuang ?: $activeRuang;
                $tokenActive = $tokenSource->isTokenActive();
                $sisaDetikToken = $tokenSource->sisaDetikToken();
                $menitSisa = floor($sisaDetikToken / 60);
                $detikSisa = $sisaDetikToken % 60;

                $jadwalRuangBelumMulai = $tokenSource->isBelumMasukJadwal();
                $jadwalRuangHabis = $isSessionMode ? $activeJadwalRuang->isJadwalBerakhir() : $activeRuang->isSemuaJadwalBerakhir();
                $jadwalBerikutnya = $isSessionMode
                    ? ($activeJadwalRuang->waktu_mulai ? substr((string) $activeJadwalRuang->waktu_mulai, 0, 5) . ' WIB' : null)
                    : $activeRuang->jadwal_berikutnya;

                $activeUjiansInRoom = $isSessionMode ? $activeJadwalRuang->getAllUjians() : $activeRuang->getActiveUjiansForRoom();
                $allRows = $isSessionMode ? $pesertasSesiRuang : $pesertasRuang;
                $kelasInRoom = ($isSessionMode ? $activeJadwalRuang->getSiswasForSession() : $activeRuang->siswas)
                    ->pluck('kelas')->filter()->unique('id')->values();

                $totalSiswa = $allRows->count();
                if ($this->filterStatus !== 'all' || ! empty($this->search) || ! empty($this->filterKelas)) {
                    $totalSiswa = $isSessionMode
                        ? $activeJadwalRuang->getSiswasForSession()->count()
                        : $activeRuang->siswas->count();
                }

                $sedangMengerjakan = $allRows->where('status', 'SEDANG_MENGERJAKAN')->count();
                $sudahSelesai = $allRows->where('status', 'SELESAI')->count();
                $belumMulai = max(0, $totalSiswa - ($sedangMengerjakan + $sudahSelesai));
                $totalPelanggaranSiswa = $allRows->filter(fn ($r) => ((int) ($r->sesi?->jumlah_pelanggaran ?? 0)) > 0)->count();

                $pengawasNama = $isSessionMode
                    ? ($activeJadwalRuang->pengawas?->name . ($activeJadwalRuang->pengawasPendamping ? ' & ' . $activeJadwalRuang->pengawasPendamping->name : ''))
                    : $activeRuang->nama_pengawas_tugas;
                $jamJadwalLabel = $isSessionMode
                    ? ($activeJadwalRuang->tanggal?->format('d M Y') . ' • ' . $activeJadwalRuang->label_sesi_waktu)
                    : $activeRuang->info_jadwal_waktu;
                $printQuery = $isSessionMode
                    ? ['ruang_ujian_id' => $roomObj->id, 'jadwal_ruang_id' => $activeJadwalRuang->id, 'ujian_id' => $activeJadwalRuang->ujian_id]
                    : ['ruang_ujian_id' => $roomObj->id];
            @endphp

            {{-- 1. KARTU KONTROL TOKEN RUANGAN --}}
            <div class="bg-white rounded-3xl border border-slate-200 p-4 sm:p-6 shadow-xs">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <div class="space-y-2 min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <button
                                type="button"
                                wire:click="kembaliKeDaftarUjian"
                                class="inline-flex items-center gap-1 text-xs font-extrabold text-slate-700 bg-slate-100 hover:bg-slate-200 px-3 py-1 rounded-lg transition cursor-pointer"
                            >
                                &larr; Daftar Sesi Ruang
                            </button>
                            <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                                {{ $roomObj->kode_ruang ?: 'RUANG-UJIAN' }}
                            </span>
                            @if($isSessionMode)
                                <span class="text-xs font-extrabold px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    Sesi {{ $activeJadwalRuang->sesi_ke }}
                                </span>
                            @endif
                            @if($tokenActive)
                                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold px-2.5 py-1 rounded-lg" style="background-color: #d1fae5; color: #065f46;">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                    TOKEN SESI AKTIF
                                </span>
                            @elseif($isSessionMode && $activeJadwalRuang->status === 'COMPLETED')
                                <span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-200 text-slate-700">
                                    SESI SELESAI (ARSIP)
                                </span>
                            @elseif($jadwalRuangBelumMulai)
                                <span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-lg" style="background-color: #fef3c7; color: #92400e;">
                                    BELUM MASUK JADWAL
                                </span>
                            @elseif($jadwalRuangHabis)
                                <span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-200 text-slate-700">
                                    JADWAL SELESAI
                                </span>
                            @else
                                <span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-lg" style="background-color: #e0f2fe; color: #0369a1;">
                                    SIAP RILIS TOKEN
                                </span>
                            @endif
                        </div>

                        <h2 class="text-xl sm:text-2xl font-black" style="font-family: 'Outfit', sans-serif; color: #041F45;">
                            {{ $roomObj->nama_ruang }}
                            @if($isSessionMode)
                                <span class="text-base font-bold text-emerald-700">&mdash; {{ $activeJadwalRuang->daftar_nama_ujian }}</span>
                            @endif
                        </h2>

                        <div class="flex items-center gap-x-3 gap-y-1 text-xs text-slate-500 flex-wrap font-medium">
                            <span>Peserta: <strong class="text-slate-800">{{ $totalSiswa }} Siswa</strong></span>
                            <span>&bull;</span>
                            <span>Pengawas: <strong class="text-slate-800">{{ $pengawasNama ?: 'Belum Ditugaskan' }}</strong></span>
                            <span>&bull;</span>
                            <span>Jam Jadwal: <strong class="text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">{{ $jamJadwalLabel }}</strong></span>
                            <span>&bull;</span>
                            <span>Mapel: <strong class="text-emerald-700">{{ $activeUjiansInRoom->map(fn($u) => ($u->mapel?->nama_mapel ?: $u->nama_ujian))->unique()->join(', ') ?: 'Sesuai Jadwal Kelas' }}</strong></span>
                        </div>
                    </div>

                    {{-- Panel Token Ruangan & Tombol Aksi --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                        <div class="p-4 rounded-2xl bg-slate-50 border-2 border-slate-200 flex flex-col items-center justify-center min-w-[215px]">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                Kode Token {{ $roomObj->nama_ruang }}
                            </span>

                            @if($tokenSource->token_ruang)
                                <div class="mt-1 font-mono text-3xl font-black tracking-[0.2em] select-all" style="color: #041F45;">
                                    {{ $tokenSource->token_ruang }}
                                </div>
                                <div class="mt-1">
                                    @if($tokenActive)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #d1fae5; color: #065f46;">
                                            Aktif {{ sprintf('%02d:%02d', $menitSisa, $detikSisa) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #ffe4e6; color: #be123c;">
                                            Token Kedaluwarsa
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="mt-1 text-sm font-bold text-slate-500">Belum Dirilis</span>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2">
                            @if($isSessionMode)
                                @if($activeJadwalRuang->status !== 'COMPLETED')
                                    @if($jadwalRuangHabis)
                                        <button
                                            type="button"
                                            disabled
                                            class="px-4 py-3 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 cursor-not-allowed opacity-75"
                                            style="background-color: #e2e8f0; color: #64748b; border: 1px solid #cbd5e1;"
                                        >
                                            <span>Rilis Token Ditutup (Waktu Habis)</span>
                                        </button>
                                    @elseif($jadwalRuangBelumMulai)
                                        <button
                                            type="button"
                                            disabled
                                            class="px-4 py-3 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 cursor-not-allowed opacity-80"
                                            style="background-color: #f8fafc; color: #64748b; border: 1px dashed #cbd5e1;"
                                        >
                                            <span>Belum Masuk Jadwal {{ $jadwalBerikutnya ? '(' . $jadwalBerikutnya . ')' : '' }}</span>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="rilisTokenSesiRuang"
                                            wire:confirm="Rilis kode token baru untuk {{ $roomObj->nama_ruang }} Sesi {{ $activeJadwalRuang->sesi_ke }} (berlaku 10 menit)?"
                                            class="px-4 py-3 rounded-xl text-xs font-extrabold shadow-sm transition cursor-pointer flex items-center justify-center gap-2"
                                            style="background-color: #059669; color: #ffffff;"
                                        >
                                            <span>{{ $tokenSource->token_ruang ? 'Rilis Token Baru (10m)' : 'Rilis Token Sesi (10m)' }}</span>
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="selesaikanSesiRuang"
                                        wire:confirm="Tandai sesi ini selesai? Data peserta, Daftar Hadir, dan BAP sesi ini tetap tersimpan dan dapat diakses kapanpun."
                                        class="px-3.5 py-2 rounded-xl text-xs font-extrabold border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer text-center"
                                    >
                                        Tandai Sesi Selesai
                                    </button>
                                @endif
                            @else
                                @if($jadwalRuangHabis)
                                    <button
                                        type="button"
                                        disabled
                                        class="px-4 py-3 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 cursor-not-allowed opacity-75"
                                        style="background-color: #e2e8f0; color: #64748b; border: 1px solid #cbd5e1;"
                                    >
                                        <span>Rilis Token Ditutup (Waktu Habis)</span>
                                    </button>
                                @elseif($jadwalRuangBelumMulai)
                                    <button
                                        type="button"
                                        disabled
                                        class="px-4 py-3 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 cursor-not-allowed opacity-80"
                                        style="background-color: #f8fafc; color: #64748b; border: 1px dashed #cbd5e1;"
                                        title="Waktu pelaksanaan ujian belum dimulai{{ $jadwalBerikutnya ? ' (Mulai: ' . $jadwalBerikutnya . ')' : '' }}"
                                    >
                                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Belum Masuk Jadwal {{ $jadwalBerikutnya ? '(' . $jadwalBerikutnya . ')' : '' }}</span>
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="rilisTokenRuang"
                                        wire:confirm="Rilis kode token 6 karakter baru untuk {{ $roomObj->nama_ruang }} (aktif 10 menit untuk seluruh siswa lintas kelas di ruangan ini)?"
                                        class="px-4 py-3 rounded-xl text-xs font-extrabold shadow-sm transition cursor-pointer flex items-center justify-center gap-2"
                                        style="background-color: #059669; color: #ffffff;"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                        <span>{{ $roomObj->token_ruang ? 'Rilis Token Baru (10m)' : 'Rilis Token Ruangan (10m)' }}</span>
                                    </button>
                                @endif
                            @endif

                            <a
                                href="{{ route('kartu-peserta.print', ['ruang_ujian_id' => $roomObj->id]) }}"
                                target="_blank"
                                class="px-3.5 py-2.5 rounded-xl text-xs font-extrabold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition cursor-pointer flex items-center justify-center gap-1.5"
                            >
                                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>Cetak Kartu</span>
                            </a>

                            <a
                                href="{{ route('daftar-hadir.print', $printQuery) }}"
                                target="_blank"
                                class="px-3.5 py-2.5 rounded-xl text-xs font-extrabold border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-800 transition cursor-pointer flex items-center justify-center gap-1.5"
                                title="Cetak Daftar Hadir Peserta Sesi Ruangan"
                            >
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Daftar Hadir</span>
                            </a>

                            @if($isSessionMode)
                                <button
                                    type="button"
                                    wire:click="bukaModalBap({{ $activeJadwalRuang->id }})"
                                    class="px-3.5 py-2.5 rounded-xl text-xs font-extrabold border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 transition cursor-pointer flex items-center justify-center gap-1.5"
                                >
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    <span>Isi &amp; Cetak BAP</span>
                                </button>
                            @else
                                <a
                                    href="{{ route('berita-acara.print', $printQuery) }}"
                                    target="_blank"
                                    class="px-3.5 py-2.5 rounded-xl text-xs font-extrabold border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 transition cursor-pointer flex items-center justify-center gap-1.5"
                                    title="Cetak Berita Acara Pelaksanaan (Setelah Ujian Selesai)"
                                >
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    <span>Berita Acara (BAP)</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. RINGKASAN METRIK REALTIME RUANGAN --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase text-slate-400">Peserta Sesi</div>
                    <div class="mt-1 text-2xl font-black text-slate-900" style="font-family: 'Outfit', sans-serif;">
                        {{ $totalSiswa }} <span class="text-xs font-semibold text-slate-500">Siswa</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-amber-200 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase text-amber-600">Sedang Mengerjakan</div>
                    <div class="mt-1 text-2xl font-black text-amber-600" style="font-family: 'Outfit', sans-serif;">
                        {{ $sedangMengerjakan }} <span class="text-xs font-semibold text-slate-500">Aktif</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-emerald-200 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase text-emerald-600">Sudah Selesai</div>
                    <div class="mt-1 text-2xl font-black text-emerald-600" style="font-family: 'Outfit', sans-serif;">
                        {{ $sudahSelesai }} <span class="text-xs font-semibold text-slate-500">/ {{ $totalSiswa }}</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border shadow-2xs" style="{{ $totalPelanggaranSiswa > 0 ? 'border-color: #fda4af; background-color: #fff1f2;' : 'border-color: #e2e8f0;' }}">
                    <div class="text-[11px] font-bold uppercase" style="color: {{ $totalPelanggaranSiswa > 0 ? '#e11d48' : '#94a3b8' }};">Pelanggaran Tab</div>
                    <div class="mt-1 text-2xl font-black" style="font-family: 'Outfit', sans-serif; color: {{ $totalPelanggaranSiswa > 0 ? '#e11d48' : '#1e293b' }};">
                        {{ $totalPelanggaranSiswa }} <span class="text-xs font-semibold text-slate-500">Siswa</span>
                    </div>
                </div>
            </div>

            {{-- 3. FILTER & PENCARIAN SISWA DI RUANGAN --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-3.5 space-y-3 shadow-2xs">
                <div class="flex flex-col sm:flex-row gap-2.5">
                    <div class="relative flex-1">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama atau NIS siswa di ruangan ini..."
                            class="w-full h-10 pl-3.5 pr-4 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-900 focus:outline-none"
                        />
                    </div>

                    @if($kelasInRoom->count() > 1)
                        <select
                            wire:model.live="filterKelas"
                            class="h-10 px-3 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 text-slate-800"
                        >
                            <option value="">Semua Kelas di Sesi Ini ({{ $kelasInRoom->count() }} Kelas)</option>
                            @foreach($kelasInRoom as $k)
                                <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="grid grid-cols-2 sm:flex sm:items-center sm:flex-wrap gap-1.5">
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'all')"
                        class="px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'all' ? 'background-color: #041F45; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;' }}"
                    >
                        Semua ({{ $totalSiswa }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'SEDANG_MENGERJAKAN')"
                        class="px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'SEDANG_MENGERJAKAN' ? 'background-color: #d97706; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;' }}"
                    >
                        Mengerjakan ({{ $sedangMengerjakan }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'BELUM_MULAI')"
                        class="px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'BELUM_MULAI' ? 'background-color: #334155; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;' }}"
                    >
                        Belum Mulai ({{ $belumMulai }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'SELESAI')"
                        class="px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'SELESAI' ? 'background-color: #059669; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;' }}"
                    >
                        Selesai ({{ $sudahSelesai }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'PELANGGARAN')"
                        class="col-span-2 sm:col-span-1 px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'PELANGGARAN' ? 'background-color: #e11d48; color: #ffffff;' : ($totalPelanggaranSiswa > 0 ? 'background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3;' : 'background-color: #f1f5f9; color: #475569;') }}"
                    >
                        Pelanggaran Tab ({{ $totalPelanggaranSiswa }})
                    </button>
                </div>
            </div>

            {{-- 4. DAFTAR SISWA LINTAS KELAS DI RUANGAN --}}
            <div class="space-y-2.5">
                @forelse($allRows as $index => $row)
                    @php
                        $siswaItem = $row->siswa;
                        $peserta = $row->peserta;
                        $ujianItem = $row->ujian;
                        $sesi = $row->sesi;
                        $totalSoalSiswa = $ujianItem?->bankSoal?->soals_count ?? ($ujianItem?->bankSoal?->soals()->count() ?: 0);
                        $jawabansCount = $sesi ? $sesi->jawabans->filter(fn($j) => !empty($j->jawaban_pilihan) || !empty($j->jawaban_teks))->count() : 0;
                        $persenJawab = $totalSoalSiswa > 0 ? round(($jawabansCount / $totalSoalSiswa) * 100) : 0;
                        $jmlPelanggaran = (int) ($sesi?->jumlah_pelanggaran ?? 0);
                    @endphp

                    <div
                        class="bg-white rounded-2xl p-4 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-4"
                        style="border: {{ $jmlPelanggaran > 0 ? '1.5px solid #fda4af' : '1px solid #e2e8f0' }};"
                    >
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 font-black text-xs flex items-center justify-center shrink-0">
                                {{ $index + 1 }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-extrabold text-sm text-slate-900 truncate">
                                        {{ $siswaItem->nama }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Kelas {{ $siswaItem->kelas?->nama_kelas ?: '-' }}
                                    </span>
                                    @if($ujianItem)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Soal: {{ $ujianItem->mapel?->nama_mapel ?: $ujianItem->nama_ujian }}
                                        </span>
                                    @endif
                                    @if(($row->total_subtests ?? 1) > 1)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Subtest: {{ ($row->subtests_peserta ?? collect())->where('status', 'SELESAI')->count() }}/{{ $row->total_subtests }} Selesai
                                        </span>
                                    @endif
                                    <span class="font-mono text-[11px] font-semibold text-slate-400">
                                        NIS: {{ $siswaItem->nis ?: '-' }}
                                    </span>
                                </div>

                                <div class="mt-2 flex items-center gap-2 flex-wrap">
                                    @if($row->status === 'SELESAI')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold" style="background-color: #d1fae5; color: #065f46;">
                                            &#10003; Selesai {{ $peserta?->nilai !== null ? '• Nilai: ' . (float) $peserta->nilai : '' }}
                                        </span>
                                    @elseif($row->status === 'SEDANG_MENGERJAKAN')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold" style="background-color: #fef3c7; color: #92400e;">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                            Sedang Mengerjakan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500">
                                            Belum Mulai
                                        </span>
                                    @endif

                                    @if($jmlPelanggaran >= 3)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold animate-pulse" style="background-color: #991b1b; color: #ffffff;">
                                            🔒 TERKUNCI ({{ $jmlPelanggaran }}x Pindah Tab)
                                        </span>
                                    @elseif($jmlPelanggaran > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #ffe4e6; color: #9f1239; border: 1px solid #fda4af;">
                                            ⚠️ {{ $jmlPelanggaran }}x Pindah Tab
                                        </span>
                                    @elseif($sesi)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold" style="background-color: #ecfdf5; color: #047857;">
                                            Integritas Aman
                                        </span>
                                    @endif
                                </div>

                                @if($sesi)
                                    <div class="mt-2.5 max-w-md">
                                        <div class="flex items-center justify-between text-[11px] mb-1">
                                            <span class="font-bold text-slate-600">Progress: {{ $jawabansCount }} / {{ $totalSoalSiswa }} Soal</span>
                                            <span class="font-extrabold text-emerald-700">{{ $persenJawab }}%</span>
                                        </div>
                                        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-emerald-500 rounded-full transition-all" style="width: {{ $persenJawab }}%; background-color: #10b981;"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($peserta && $sesi)
                            <div class="grid {{ $row->status === 'SEDANG_MENGERJAKAN' ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-3' }} lg:flex lg:items-center gap-2 pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-100 shrink-0">
                                <button
                                    type="button"
                                    wire:click="resetSesiSiswa({{ $peserta->id }})"
                                    wire:confirm="Reset login & buka kunci sesi untuk {{ $siswaItem->nama }}? Jawaban yang sudah dikerjakan tetap aman."
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 lg:py-2 rounded-xl text-xs font-extrabold active:scale-95 transition cursor-pointer"
                                    style="{{ $jmlPelanggaran >= 3 ? 'background-color: #dc2626; color: #ffffff; border: 1px solid #b91c1c;' : 'background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a;' }}"
                                >
                                    <span>{{ $jmlPelanggaran >= 3 ? 'Buka Kunci & Reset' : 'Reset Login' }}</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="tambahWaktuSiswa({{ $peserta->id }}, 10)"
                                    wire:confirm="Tambahkan waktu pengerjaan +10 menit untuk {{ $siswaItem->nama }}?"
                                    class="inline-flex items-center justify-center gap-1 px-3 py-2.5 lg:py-2 rounded-xl text-xs font-extrabold active:scale-95 transition cursor-pointer"
                                    style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;"
                                >
                                    <span>+10m Waktu</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="bukaLembarJawaban({{ $peserta->id }})"
                                    class="inline-flex items-center justify-center gap-1 px-3 py-2.5 lg:py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer"
                                >
                                    <span>Jawaban</span>
                                </button>

                                @if($row->status === 'SEDANG_MENGERJAKAN')
                                    <button
                                        type="button"
                                        wire:click="paksaSelesaiSiswa({{ $peserta->id }})"
                                        wire:confirm="Selesaikan paksa ujian untuk {{ $siswaItem->nama }} sekarang?"
                                        class="inline-flex items-center justify-center gap-1 px-3 py-2.5 lg:py-2 rounded-xl text-xs font-extrabold active:scale-95 transition cursor-pointer"
                                        style="background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3;"
                                    >
                                        <span>Selesaikan</span>
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center justify-end pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100 shrink-0">
                                <button
                                    type="button"
                                    wire:click="resetLoginSiswaById({{ $siswaItem->id }})"
                                    wire:confirm="Reset kunci login perangkat untuk {{ $siswaItem->nama }}?"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold active:scale-95 transition cursor-pointer"
                                    style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;"
                                >
                                    <span>Reset Login</span>
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-xs text-slate-400 italic">
                        Tidak ada data peserta di ruangan ini yang sesuai dengan filter pencarian.
                    </div>
                @endforelse
            </div>

        {{-- ============================================================= --}}
        {{-- MODE 2B: RUANG KONTROL PER JADWAL SOAL UJIAN                  --}}
        {{-- ============================================================= --}}
        @else
            @php
                $isOngoing = $activeUjian->status === 'ONGOING';
                $isCompleted = $activeUjian->status === 'COMPLETED';
                $isScheduled = $activeUjian->status === 'SCHEDULED';
                $jadwalHabis = $activeUjian->isJadwalBerakhir();
                $tokenActive = $activeUjian->isTokenActive();
                $sisaDetikToken = $activeUjian->sisaDetikToken();
                $menitSisa = floor($sisaDetikToken / 60);
                $detikSisa = $sisaDetikToken % 60;

                $totalSiswa = $activeUjian->pesertas()->count();
                $sedangMengerjakan = $activeUjian->pesertas()->where('status', 'SEDANG_MENGERJAKAN')->count();
                $sudahSelesai = $activeUjian->pesertas()->where('status', 'SELESAI')->count();
                $belumMulai = max(0, $totalSiswa - ($sedangMengerjakan + $sudahSelesai));
                $totalPelanggaranSiswa = $activeUjian->pesertas()->whereHas('ujianSesi', fn($q) => $q->where('jumlah_pelanggaran', '>', 0))->count();
                $totalSoalBank = $activeUjian->bankSoal?->soals_count ?? ($activeUjian->bankSoal?->soals()->count() ?: 0);
            @endphp

            {{-- 1. KARTU KONTROL TOKEN & SESI UJIAN --}}
            <div class="bg-white rounded-3xl border border-slate-200 p-4 sm:p-6 shadow-xs">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <button
                                type="button"
                                wire:click="kembaliKeDaftarUjian"
                                class="inline-flex items-center gap-1 text-xs font-extrabold text-slate-700 bg-slate-100 hover:bg-slate-200 px-3 py-1 rounded-lg transition cursor-pointer"
                            >
                                &larr; Daftar Ujian
                            </button>
                            <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                                {{ $activeUjian->exam_id }}
                            </span>
                            @if($isOngoing)
                                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold px-2.5 py-1 rounded-lg" style="background-color: #d1fae5; color: #065f46;">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                    SESI BERJALAN
                                </span>
                            @elseif($isCompleted)
                                <span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-200 text-slate-700">
                                    UJIAN SELESAI
                                </span>
                            @else
                                <span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-lg" style="background-color: #fef3c7; color: #92400e;">
                                    DIJADWALKAN
                                </span>
                            @endif
                        </div>

                        <h2 class="text-xl sm:text-2xl font-black" style="font-family: 'Outfit', sans-serif; color: #041F45;">
                            {{ $activeUjian->nama_ujian }}
                        </h2>

                        <div class="flex items-center gap-x-3 gap-y-1 text-xs text-slate-500 flex-wrap font-medium">
                            <span>Mapel: <strong class="text-slate-800">{{ $activeUjian->mapel?->nama_mapel ?: '-' }}</strong></span>
                            <span>&bull;</span>
                            <span>Kelas: <strong class="text-slate-800">{{ $activeUjian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}</strong></span>
                            <span>&bull;</span>
                            <span>Durasi: <strong class="text-slate-800">{{ $activeUjian->durasi_menit ?: 60 }} Menit ({{ $totalSoalBank }} Soal)</strong></span>
                        </div>
                    </div>

                    {{-- Panel Token Besar & Tombol Aksi Token --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                        <div class="p-4 rounded-2xl bg-slate-50 border-2 border-slate-200 flex flex-col items-center justify-center min-w-[210px]">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                Kode Token Masuk Siswa
                            </span>

                            @if($isOngoing && $activeUjian->token_ujian)
                                <div class="mt-1 font-mono text-3xl font-black tracking-[0.2em] select-all" style="color: #041F45;">
                                    {{ $activeUjian->token_ujian }}
                                </div>
                                <div class="mt-1">
                                    @if($tokenActive)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #d1fae5; color: #065f46;">
                                            Aktif {{ sprintf('%02d:%02d', $menitSisa, $detikSisa) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #ffe4e6; color: #be123c;">
                                            Token Kedaluwarsa (10m Habis)
                                        </span>
                                    @endif
                                </div>
                            @elseif($isCompleted)
                                <span class="mt-1 text-sm font-bold text-slate-400">Ujian Telah Ditutup</span>
                            @else
                                <span class="mt-1 text-sm font-bold text-slate-500">Belum Dibuka</span>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2">
                            @if($jadwalHabis)
                                <button
                                    type="button"
                                    disabled
                                    class="px-4 py-3 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 cursor-not-allowed opacity-75"
                                    style="background-color: #e2e8f0; color: #64748b; border: 1px solid #cbd5e1;"
                                    title="Batas jadwal waktu ujian telah berakhir"
                                >
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span>Rilis Token Ditutup (Waktu Habis)</span>
                                </button>
                            @elseif($isScheduled || ($isOngoing && empty($activeUjian->token_ujian)))
                                <button
                                    type="button"
                                    wire:click="mulaiUjian"
                                    wire:confirm="Mulai pelaksanaan ujian sekarang dan rilis token 10 menit?"
                                    class="px-4 py-3 rounded-xl text-xs font-extrabold shadow-sm transition cursor-pointer flex items-center justify-center gap-2"
                                    style="background-color: #059669; color: #ffffff;"
                                >
                                    <span>Mulai Ujian &amp; Rilis Token</span>
                                </button>
                            @elseif($isOngoing)
                                <button
                                    type="button"
                                    wire:click="rilisTokenBaru"
                                    wire:confirm="Generate kode token 6 karakter baru untuk 10 menit ke depan?"
                                    class="px-4 py-3 rounded-xl text-xs font-extrabold shadow-sm transition cursor-pointer flex items-center justify-center gap-2"
                                    style="background-color: #041F45; color: #ffffff;"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Rilis Token Baru (10m)</span>
                                </button>
                            @endif

                            <a
                                href="{{ route('hasil-ujian.berita-acara', $activeUjian->id) }}"
                                target="_blank"
                                class="px-4 py-2.5 rounded-xl text-xs font-extrabold border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition cursor-pointer flex items-center justify-center gap-1.5"
                            >
                                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>Berita Acara & Daftar Hadir</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. RINGKASAN METRIK REALTIME (4 KARTU KOMPAK) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase text-slate-400">Total Peserta</div>
                    <div class="mt-1 text-2xl font-black text-slate-900" style="font-family: 'Outfit', sans-serif;">
                        {{ $totalSiswa }} <span class="text-xs font-semibold text-slate-500">Siswa</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-amber-200 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase text-amber-600">Sedang Mengerjakan</div>
                    <div class="mt-1 text-2xl font-black text-amber-600" style="font-family: 'Outfit', sans-serif;">
                        {{ $sedangMengerjakan }} <span class="text-xs font-semibold text-slate-500">Aktif</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border border-emerald-200 shadow-2xs">
                    <div class="text-[11px] font-bold uppercase text-emerald-600">Sudah Selesai</div>
                    <div class="mt-1 text-2xl font-black text-emerald-600" style="font-family: 'Outfit', sans-serif;">
                        {{ $sudahSelesai }} <span class="text-xs font-semibold text-slate-500">/ {{ $totalSiswa }}</span>
                    </div>
                </div>

                <div class="bg-white p-3.5 rounded-2xl border shadow-2xs" style="{{ $totalPelanggaranSiswa > 0 ? 'border-color: #fda4af; background-color: #fff1f2;' : 'border-color: #e2e8f0;' }}">
                    <div class="text-[11px] font-bold uppercase" style="color: {{ $totalPelanggaranSiswa > 0 ? '#e11d48' : '#94a3b8' }};">Pelanggaran Tab</div>
                    <div class="mt-1 text-2xl font-black" style="font-family: 'Outfit', sans-serif; color: {{ $totalPelanggaranSiswa > 0 ? '#e11d48' : '#1e293b' }};">
                        {{ $totalPelanggaranSiswa }} <span class="text-xs font-semibold text-slate-500">Siswa</span>
                    </div>
                </div>
            </div>

            {{-- 3. FILTER & PENCARIAN SISWA --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-3.5 space-y-3 shadow-2xs">
                <div class="flex flex-col sm:flex-row gap-2.5">
                    <div class="relative flex-1">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama atau NIS siswa..."
                            class="w-full h-10 pl-3.5 pr-4 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-slate-900 focus:outline-none"
                        />
                    </div>

                    @if($activeUjian->kelas->count() > 1)
                        <select
                            wire:model.live="filterKelas"
                            class="h-10 px-3 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50 text-slate-800"
                        >
                            <option value="">Semua Rombel Kelas</option>
                            @foreach($activeUjian->kelas as $k)
                                <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="grid grid-cols-2 sm:flex sm:items-center sm:flex-wrap gap-1.5">
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'all')"
                        class="px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'all' ? 'background-color: #041F45; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;' }}"
                    >
                        Semua ({{ $totalSiswa }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'SEDANG_MENGERJAKAN')"
                        class="px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'SEDANG_MENGERJAKAN' ? 'background-color: #d97706; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;' }}"
                    >
                        Mengerjakan ({{ $sedangMengerjakan }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'BELUM_MULAI')"
                        class="px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'BELUM_MULAI' ? 'background-color: #334155; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;' }}"
                    >
                        Belum Mulai ({{ $belumMulai }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'SELESAI')"
                        class="px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'SELESAI' ? 'background-color: #059669; color: #ffffff;' : 'background-color: #f1f5f9; color: #475569;' }}"
                    >
                        Selesai ({{ $sudahSelesai }})
                    </button>
                    <button
                        type="button"
                        wire:click="$set('filterStatus', 'PELANGGARAN')"
                        class="col-span-2 sm:col-span-1 px-3 py-2 sm:py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer text-center"
                        style="{{ $filterStatus === 'PELANGGARAN' ? 'background-color: #e11d48; color: #ffffff;' : ($totalPelanggaranSiswa > 0 ? 'background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3;' : 'background-color: #f1f5f9; color: #475569;') }}"
                    >
                        Pelanggaran Tab ({{ $totalPelanggaranSiswa }})
                    </button>
                </div>
            </div>

            {{-- 4. DAFTAR SISWA REALTIME --}}
            <div class="space-y-2.5">
                @forelse($pesertas as $index => $peserta)
                    @php
                        $sesi = $peserta->ujianSesi;
                        $jawabansCount = $sesi ? $sesi->jawabans->filter(fn($j) => !empty($j->jawaban_pilihan) || !empty($j->jawaban_teks))->count() : 0;
                        $persenJawab = $totalSoalBank > 0 ? round(($jawabansCount / $totalSoalBank) * 100) : 0;
                        $jmlPelanggaran = (int) ($sesi?->jumlah_pelanggaran ?? 0);
                    @endphp

                    <div
                        class="bg-white rounded-2xl p-4 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-4"
                        style="border: {{ $jmlPelanggaran > 0 ? '1.5px solid #fda4af' : '1px solid #e2e8f0' }};"
                    >
                        {{-- Identitas & Status Siswa --}}
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 font-black text-xs flex items-center justify-center shrink-0">
                                {{ $index + 1 }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-extrabold text-sm text-slate-900 truncate">
                                        {{ $peserta->siswa?->nama ?: ($peserta->user?->name ?: '-') }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ $peserta->siswa?->kelas?->nama_kelas ?: '-' }}
                                    </span>
                                    <span class="font-mono text-[11px] font-semibold text-slate-400">
                                        NIS: {{ $peserta->siswa?->nis ?: '-' }}
                                    </span>
                                </div>

                                {{-- Baris Status & Pelanggaran --}}
                                <div class="mt-2 flex items-center gap-2 flex-wrap">
                                    @if($peserta->status === 'SELESAI')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold" style="background-color: #d1fae5; color: #065f46;">
                                            &#10003; Selesai {{ $peserta->nilai !== null ? '• Nilai: ' . (float) $peserta->nilai : '' }}
                                        </span>
                                    @elseif($peserta->status === 'SEDANG_MENGERJAKAN')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold" style="background-color: #fef3c7; color: #92400e;">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                            Sedang Mengerjakan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500">
                                            Belum Mulai
                                        </span>
                                    @endif

                                    @if($jmlPelanggaran >= 3)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold animate-pulse" style="background-color: #991b1b; color: #ffffff;">
                                            🔒 TERKUNCI ({{ $jmlPelanggaran }}x Pindah Tab)
                                        </span>
                                    @elseif($jmlPelanggaran > 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold" style="background-color: #ffe4e6; color: #9f1239; border: 1px solid #fda4af;">
                                            ⚠️ {{ $jmlPelanggaran }}x Pindah Tab
                                            @if($sesi?->terakhir_pelanggaran_at)
                                                ({{ $sesi->terakhir_pelanggaran_at->format('H:i') }})
                                            @endif
                                        </span>
                                    @elseif($sesi)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold" style="background-color: #ecfdf5; color: #047857;">
                                            Integritas Aman
                                        </span>
                                    @endif
                                </div>

                                {{-- Progress Bar Soal --}}
                                @if($sesi)
                                    <div class="mt-2.5 max-w-md">
                                        <div class="flex items-center justify-between text-[11px] mb-1">
                                            <span class="font-bold text-slate-600">Progress: {{ $jawabansCount }} / {{ $totalSoalBank }} Soal</span>
                                            <span class="font-extrabold text-emerald-700">{{ $persenJawab }}%</span>
                                        </div>
                                        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-emerald-500 rounded-full transition-all" style="width: {{ $persenJawab }}%; background-color: #10b981;"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Tombol Aksi Cepat Proktor --}}
                        @if($sesi)
                            <div class="grid {{ $peserta->status === 'SEDANG_MENGERJAKAN' ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-3' }} lg:flex lg:items-center gap-2 pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-100 shrink-0">
                                <button
                                    type="button"
                                    wire:click="resetSesiSiswa({{ $peserta->id }})"
                                    wire:confirm="Reset login & buka kunci sesi untuk {{ $peserta->siswa?->nama }}? Jawaban yang sudah dikerjakan tetap aman."
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 lg:py-2 rounded-xl text-xs font-extrabold active:scale-95 transition cursor-pointer"
                                    style="{{ $jmlPelanggaran >= 3 ? 'background-color: #dc2626; color: #ffffff; border: 1px solid #b91c1c;' : 'background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a;' }}"
                                >
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>{{ $jmlPelanggaran >= 3 ? 'Buka Kunci & Reset' : 'Reset Login' }}</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="tambahWaktuSiswa({{ $peserta->id }}, 10)"
                                    wire:confirm="Tambahkan waktu pengerjaan +10 menit untuk {{ $peserta->siswa?->nama }}?"
                                    class="inline-flex items-center justify-center gap-1 px-3 py-2.5 lg:py-2 rounded-xl text-xs font-extrabold active:scale-95 transition cursor-pointer"
                                    style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;"
                                    title="Tambah Waktu +10 Menit"
                                >
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>+10m Waktu</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="bukaLembarJawaban({{ $peserta->id }})"
                                    class="inline-flex items-center justify-center gap-1 px-3 py-2.5 lg:py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer"
                                    title="Lihat Jawaban Siswa"
                                >
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Jawaban</span>
                                </button>

                                @if($peserta->status === 'SEDANG_MENGERJAKAN')
                                    <button
                                        type="button"
                                        wire:click="paksaSelesaiSiswa({{ $peserta->id }})"
                                        wire:confirm="Selesaikan paksa ujian untuk {{ $peserta->siswa?->nama }} sekarang?"
                                        class="inline-flex items-center justify-center gap-1 px-3 py-2.5 lg:py-2 rounded-xl text-xs font-extrabold active:scale-95 transition cursor-pointer"
                                        style="background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3;"
                                    >
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Selesaikan</span>
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center justify-end pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100 shrink-0">
                                <button
                                    type="button"
                                    wire:click="resetSesiSiswa({{ $peserta->id }})"
                                    wire:confirm="Reset login akun untuk {{ $peserta->siswa?->nama }}?"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold active:scale-95 transition cursor-pointer"
                                    style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;"
                                >
                                    <svg class="w-3.5 h-3.5 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Reset Login</span>
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-xs text-slate-400 italic">
                        Tidak ada data peserta yang sesuai dengan filter pencarian.
                    </div>
                @endforelse
            </div>
        @endif
    </main>

    {{-- ================================================================= --}}
    {{-- MODAL LEMBAR JAWABAN LIVE SISWA                                   --}}
    {{-- ================================================================= --}}
    @if($this->selectedPeserta)
        @php
            $sPeserta = $this->selectedPeserta;
            $sSesi = $sPeserta->ujianSesi;
            $jawabans = $sSesi ? $sSesi->jawabans : collect();
        @endphp
        <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="bg-white rounded-t-3xl sm:rounded-3xl max-w-2xl w-full max-h-[88vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">
                            Pantau Jawaban Live
                        </span>
                        <h3 class="text-base font-black text-slate-900 mt-1" style="font-family: 'Outfit', sans-serif;">
                            {{ $sPeserta->siswa?->nama ?: $sPeserta->user?->name }} ({{ $sPeserta->siswa?->nis }})
                        </h3>
                    </div>
                    <button type="button" wire:click="tutupLembarJawaban" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-3 text-xs">
                    @forelse($jawabans as $jwb)
                        @php
                            $soal = $jwb->soal;
                            $tipeSoal = $soal?->tipe_soal ?? 'pilihan_ganda';
                            $opsiList = $jwb->getOrderedOpsiWithSelection();
                            $selectedOpsi = collect($opsiList)->where('is_selected', true)->values();
                            $hasAnswered = $selectedOpsi->isNotEmpty() || filled($jwb->jawaban_teks) || (is_array($jwb->jawaban_pilihan) && count($jwb->jawaban_pilihan) > 0);
                        @endphp
                        <div class="p-4 rounded-2xl border bg-white shadow-2xs space-y-2.5" style="border-color: {{ $hasAnswered ? '#cbd5e1' : '#e2e8f0' }};">
                            {{-- Header Nomor & Status --}}
                            <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-lg font-black text-xs" style="background-color: #041F45; color: #ffffff;">
                                        Soal No. {{ $jwb->nomor_urut }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                                        {{ str_replace('_', ' ', $tipeSoal) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    @if($jwb->is_ragu)
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-extrabold">Ragu-ragu</span>
                                    @endif
                                    @if($hasAnswered)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" style="background-color: #d1fae5; color: #065f46;">
                                            Sudah Dijawab
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-bold">
                                            Belum Dijawab
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Teks Pertanyaan Soal --}}
                            <div class="text-slate-800 text-xs sm:text-sm leading-relaxed font-medium prose prose-sm max-w-none">
                                {!! $soal?->pertanyaan ?: '<span class="italic text-slate-400">(Teks soal tidak ditemukan)</span>' !!}
                            </div>

                            {{-- Detail Jawaban yang Dipilih Siswa --}}
                            <div class="pt-1">
                                @if(in_array($tipeSoal, ['pilihan_ganda', 'pg_kompleks', 'benar_salah']))
                                    @if($selectedOpsi->isNotEmpty())
                                        <div class="space-y-1.5">
                                            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                                Jawaban yang Dipilih Siswa:
                                            </div>
                                            @foreach($selectedOpsi as $opt)
                                                <div class="flex items-start gap-2.5 p-2.5 rounded-xl border" style="background-color: #eff6ff; border-color: #93c5fd;">
                                                    <span class="w-6 h-6 rounded-lg font-black text-xs flex items-center justify-center shrink-0" style="background-color: #2563eb; color: #ffffff;">
                                                        {{ $opt['label'] }}
                                                    </span>
                                                    <div class="text-xs font-bold text-slate-900 leading-relaxed flex-1">
                                                        {!! $opt['teks_opsi'] !!}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-400 italic text-xs">
                                            Siswa belum memilih jawaban pada soal ini.
                                        </div>
                                    @endif
                                @elseif($tipeSoal === 'menjodohkan')
                                    @php
                                        $matchingAns = is_array($jwb->jawaban_pilihan) ? $jwb->jawaban_pilihan : [];
                                    @endphp
                                    <div class="space-y-1.5">
                                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                            Pasangan Jawaban Siswa:
                                        </div>
                                        @foreach($opsiList as $idx => $opt)
                                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-2 text-xs">
                                                <span class="font-medium text-slate-700">{!! $opt['teks_opsi'] !!}</span>
                                                <span class="font-extrabold text-blue-700">&rarr; {{ $matchingAns[$idx] ?? 'Belum dipilih' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="space-y-1">
                                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                            Jawaban Uraian / Isian Siswa:
                                        </div>
                                        <div class="p-2.5 rounded-xl border bg-blue-50/50 border-blue-200 text-slate-900 font-semibold text-xs whitespace-pre-line">
                                            {{ filled($jwb->jawaban_teks) ? $jwb->jawaban_teks : 'Belum dijawab' }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 italic">Belum ada jawaban tersimpan.</div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-slate-100 bg-slate-50">
                    <button type="button" wire:click="tutupLembarJawaban" class="w-full py-2.5 rounded-xl text-white text-xs font-extrabold cursor-pointer" style="background-color: #041F45; color: #ffffff;">
                        Tutup Lembar Jawaban
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL ISI / EDIT & CETAK BERITA ACARA PELAKSANAAN (BAP) PER SESI --}}
    @if($bapJadwalRuangId)
        @php
            $jrBap = \App\Models\JadwalRuang::with('ruangUjian')->find($bapJadwalRuangId);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-bold uppercase text-emerald-700">Berita Acara Pelaksanaan (BAP) Sesi</div>
                        <h3 class="text-base font-black text-slate-900">
                            {{ $jrBap?->ruangUjian?->nama_ruang }} &bull; Sesi {{ $jrBap?->sesi_ke }} ({{ $jrBap?->tanggal?->format('d M Y') }})
                        </h3>
                    </div>
                    <button type="button" wire:click="tutupModalBap" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 font-bold text-slate-600 cursor-pointer">&times;</button>
                </div>

                <form wire:submit.prevent="simpanBap" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Ujian / Asesmen</label>
                        <input type="text" wire:model="bapForm.nama_ujian" required class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 font-semibold" />
                    </div>

                    <div class="grid grid-cols-3 gap-2.5">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Sesi Ke</label>
                            <input type="text" wire:model="bapForm.sesi" required class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 font-semibold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Waktu Mulai</label>
                            <input type="text" wire:model="bapForm.waktu_mulai" required class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 font-semibold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Waktu Selesai</label>
                            <input type="text" wire:model="bapForm.waktu_selesai" required class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 font-semibold" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2.5">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Peserta Seharusnya</label>
                            <input type="number" min="0" wire:model="bapForm.jumlah_peserta_seharusnya" required class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-emerald-700 mb-1">Hadir (Ikut Ujian)</label>
                            <input type="number" min="0" wire:model="bapForm.jumlah_hadir" required class="w-full px-3 py-2 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-rose-700 mb-1">Tidak Hadir</label>
                            <input type="number" min="0" wire:model="bapForm.jumlah_tidak_hadir" required class="w-full px-3 py-2 rounded-xl border border-rose-200 bg-rose-50 text-rose-900 font-bold" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">NIS / Username Siswa Tidak Hadir (pisahkan koma)</label>
                        <input type="text" wire:model="bapForm.usernames_tidak_hadir" placeholder="Contoh: 2026001, 2026005 (kosongkan jika hadir semua)" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-900" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Catatan Selama Pelaksanaan</label>
                        <textarea rows="3" wire:model="bapForm.catatan" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-900"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Pengawas</label>
                            <input type="text" wire:model="bapForm.nama_pengawas" required class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">NIP Pengawas</label>
                            <input type="text" wire:model="bapForm.nip_pengawas" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-900" />
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap">
                        @if($jrBap)
                            <a
                                href="{{ route('berita-acara.print', ['ruang_ujian_id' => $jrBap->ruang_ujian_id, 'jadwal_ruang_id' => $jrBap->id, 'ujian_id' => $jrBap->ujian_id]) }}"
                                target="_blank"
                                class="px-4 py-2.5 rounded-xl text-xs font-extrabold border border-slate-300 bg-white hover:bg-slate-50 text-slate-700"
                            >
                                Cetak / Lihat PDF BAP
                            </a>
                        @endif
                        <div class="flex items-center gap-2 ml-auto">
                            <button type="button" wire:click="tutupModalBap" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-600 cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-extrabold text-white cursor-pointer" style="background-color: #059669; color: #ffffff;">
                                Simpan BAP Sesi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
