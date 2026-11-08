<div class="min-h-[100dvh] w-full flex flex-col bg-[#f4f7fb] text-slate-800 font-['Plus_Jakarta_Sans',sans-serif] select-none">

    {{-- ===================================================================== --}}
    {{-- TOPBAR SISWA (DEEP NAVY & EMERALD ACCENT)                             --}}
    {{-- ===================================================================== --}}
    <header
        class="sticky top-0 z-40 shadow-md border-b border-white/10"
        style="background: linear-gradient(135deg, #041F45 0%, #072d61 60%, #065f46 100%); color: #ffffff;"
    >
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-3 flex-wrap">
            {{-- Logo & Identitas Sekolah --}}
            <div class="flex items-center gap-3">
                <img
                    src="{{ asset('images/logo/examuna-logo-white.png') }}"
                    alt="Examuna - SMK Assuniyah Tumijajar"
                    class="h-9 sm:h-10 w-auto object-contain drop-shadow-sm"
                />
                <div class="hidden sm:block pl-3 border-l border-white/15">
                    <div class="text-xs font-extrabold tracking-wide uppercase text-emerald-300 leading-tight">
                        Portal Peserta CBT
                    </div>
                    <div class="text-[11px] text-sky-100/85 font-medium leading-tight">
                        SMK Assuniyah Tumijajar
                    </div>
                </div>
            </div>

            {{-- Info Siswa & Tombol Keluar --}}
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-white/10 border border-white/15 backdrop-blur-md">
                    <div
                        class="w-7 h-7 rounded-lg font-extrabold text-xs flex items-center justify-center shrink-0 shadow-xs"
                        style="background-color: #10b981; color: #041F45;"
                    >
                        {{ strtoupper(substr($siswa?->nama ?: $user->name, 0, 1)) }}
                    </div>
                    <div class="text-left">
                        <div class="text-xs font-extrabold text-white leading-tight truncate max-w-[140px] sm:max-w-[220px]">
                            {{ $siswa?->nama ?: $user->name }}
                        </div>
                        <div class="text-[10px] font-semibold text-emerald-200 leading-tight">
                            NIS: {{ $siswa?->nis ?: ($user->nip ?: '-') }} &bull; {{ $siswa?->kelas?->nama_kelas ?: 'Siswa' }}
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="logout"
                    title="Keluar dari akun"
                    class="px-3 py-2 rounded-xl text-xs font-extrabold bg-rose-500/20 hover:bg-rose-500/30 text-rose-100 border border-rose-400/30 transition cursor-pointer inline-flex items-center gap-1.5 active:scale-95"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Keluar</span>
                </button>
            </div>
        </div>
    </header>

    {{-- ===================================================================== --}}
    {{-- KONTEN UTAMA                                                          --}}
    {{-- ===================================================================== --}}
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-7">

        {{-- 1. HERO CARD: INPUT KODE TOKEN UJIAN --}}
        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-[0_12px_36px_-10px_rgba(4,31,69,0.08)] overflow-hidden">
            <div
                class="relative px-6 py-6 sm:py-7 text-center overflow-hidden"
                style="background: linear-gradient(135deg, #041F45 0%, #072d61 55%, #065f46 100%); color: #ffffff;"
            >
                <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full bg-emerald-400/15 blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-16 -left-16 w-48 h-48 rounded-full bg-sky-400/15 blur-2xl pointer-events-none"></div>

                <div class="relative z-10 max-w-xl mx-auto">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center shadow-inner">
                        <svg class="w-6 h-6 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <span class="inline-block px-3 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-emerald-400/15 text-emerald-200 border border-emerald-400/30 mb-1.5">
                        Verifikasi Token Ruangan
                    </span>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight font-['Outfit',sans-serif] text-white">
                        Masuk Sesi Ujian dengan Token
                    </h1>
                    <p class="text-xs sm:text-sm text-sky-100/90 mt-1.5 leading-relaxed">
                        Minta <strong>6 karakter Kode Token Akses</strong> kepada Guru Pengawas di ruangan ujian Anda untuk membuka lembar soal.
                    </p>
                </div>
            </div>

            <div class="p-5 sm:p-7 max-w-lg mx-auto w-full">
                <form wire:submit.prevent="submitToken" class="space-y-4">
                    <div class="text-center space-y-2">
                        <label for="token_ujian_input" class="block text-xs font-extrabold uppercase tracking-wider text-slate-600">
                            Ketik Kode Token Ujian (6 Karakter)
                        </label>
                        <input
                            id="token_ujian_input"
                            type="text"
                            wire:model="token_input"
                            maxlength="10"
                            placeholder="CONTOH: A7K9P2"
                            autocomplete="off"
                            autocapitalize="characters"
                            spellcheck="false"
                            autofocus
                            oninput="this.value = this.value.toUpperCase()"
                            class="w-full text-center font-mono text-2xl sm:text-3xl font-black tracking-[0.25em] uppercase py-3.5 px-4 rounded-2xl border-2 bg-slate-50/90 focus:bg-white text-[#041F45] placeholder:text-slate-300 placeholder:tracking-normal placeholder:text-lg focus:border-[#041F45] focus:ring-4 focus:ring-emerald-500/15 outline-none transition"
                            style="border-color: {{ $errors->has('token_input') ? '#f43f5e' : '#cbd5e1' }};"
                        />
                    </div>

                    @error('token_input')
                        <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs sm:text-sm font-bold flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full py-3.5 px-6 rounded-2xl font-extrabold text-sm sm:text-base text-white shadow-[0_8px_20px_-4px_rgba(4,31,69,0.35)] hover:shadow-[0_12px_24px_-4px_rgba(4,31,69,0.45)] active:scale-[0.99] transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-70"
                        style="background: linear-gradient(135deg, #041F45 0%, #07336b 55%, #059669 100%);"
                    >
                        <span wire:loading.remove wire:target="submitToken">Mulai Kerjakan Ujian &raquo;</span>
                        <span wire:loading wire:target="submitToken">Memverifikasi Token &amp; Membuka Soal...</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- 2. DAFTAR UJIAN KELAS HARI INI / TERJADWAL --}}
        <div class="space-y-3.5">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base sm:text-lg font-black text-[#041F45] font-['Outfit',sans-serif] flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <span>Agenda Ujian Rombel {{ $siswa?->kelas?->nama_kelas ?: 'Anda' }}</span>
                </h2>
                <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-white border border-slate-200 text-slate-600 shadow-2xs">
                    {{ count($daftarUjian) }} Ujian Terjadwal
                </span>
            </div>

            @if($daftarUjian->isEmpty())
                <div class="p-8 sm:p-10 text-center bg-white rounded-3xl border border-dashed border-slate-300 text-slate-500">
                    <p class="text-sm font-bold text-slate-700">Belum ada jadwal ujian aktif untuk rombel kelas Anda hari ini.</p>
                    <p class="text-xs text-slate-400 mt-1">Guru atau Admin akan menjadwalkan ujian dan memberikan kode token di kelas.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($daftarUjian as $uj)
                        @php
                            $isOngoing = $uj->status === 'ONGOING';
                            $sesiAktif = isset($sesiAktifMap) ? ($sesiAktifMap[$uj->id] ?? null) : null;
                            $jmlSoal = $uj->bankSoal?->soals_count ?? ($uj->bankSoal?->soals()->count() ?: 0);
                        @endphp
                        <div
                            class="bg-white rounded-2xl p-5 flex flex-col justify-between gap-4 transition shadow-xs hover:shadow-md"
                            style="border: {{ $isOngoing ? '2px solid #059669' : '1px solid #e2e8f0' }};"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2.5 flex-wrap">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-[#041F45]">
                                            {{ $uj->mapel?->nama_mapel ?: 'Mata Pelajaran' }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-50 text-slate-600 border border-slate-200/80">
                                            {{ $uj->getTipeLabel() }}
                                        </span>
                                    </div>

                                    @if($isOngoing)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                            SESI DIBUKA
                                        </span>
                                    @elseif($uj->status === 'SCHEDULED')
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                            Dijadwalkan
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                            Selesai
                                        </span>
                                    @endif
                                </div>

                                <h3 class="text-base font-black text-[#041F45] font-['Outfit',sans-serif] leading-snug">
                                    {{ $uj->nama_ujian }}
                                </h3>

                                <div class="mt-3 space-y-1.5 text-xs text-slate-500 font-medium">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span>Guru: <strong class="text-slate-700">{{ $uj->guru?->name ?: 'Guru Pengawas' }}</strong></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Durasi: <strong class="text-slate-700">{{ $uj->durasi_menit ?: 60 }} Menit</strong> &bull; {{ $jmlSoal }} Butir Soal</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-100 text-xs">
                                @if($isOngoing && $sesiAktif)
                                    <button
                                        type="button"
                                        wire:click="lanjutkanUjian({{ $uj->id }})"
                                        class="w-full py-2.5 px-4 rounded-xl text-xs font-extrabold text-white flex items-center justify-center gap-1.5 shadow-sm active:scale-[0.99] transition cursor-pointer"
                                        style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);"
                                    >
                                        <span>Lanjutkan Mengerjakan Ujian &raquo;</span>
                                    </button>
                                @elseif($isOngoing)
                                    <div class="text-emerald-700 font-bold flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Masukkan kode token di atas untuk memulai</span>
                                    </div>
                                @elseif($uj->status === 'SCHEDULED')
                                    <span class="text-slate-400 font-medium">
                                        Menunggu Guru Pengawas memulai sesi di kelas
                                    </span>
                                @else
                                    <span class="text-slate-400">
                                        Sesi ujian telah ditutup
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 3. RIWAYAT UJIAN YANG SUDAH SELESAI --}}
        @if($riwayatUjian->isNotEmpty())
            <div class="space-y-3 pt-2">
                <h2 class="text-base font-black text-[#041F45] font-['Outfit',sans-serif]">
                    Riwayat Lembar Ujian yang Sudah Selesai
                </h2>
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] uppercase tracking-wider text-slate-500 font-extrabold">
                                    <th class="py-3 px-4">Nama Ujian</th>
                                    <th class="py-3 px-4">Mata Pelajaran</th>
                                    <th class="py-3 px-4">Waktu Selesai</th>
                                    <th class="py-3 px-4 text-center">Nilai</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($riwayatUjian as $rw)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3 px-4 font-bold text-slate-800">
                                            {{ $rw->ujian?->nama_ujian ?: 'Ujian CBT' }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-600 font-medium">
                                            {{ $rw->ujian?->mapel?->nama_mapel ?: '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-500">
                                            {{ $rw->waktu_selesai ? $rw->waktu_selesai->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-center font-black text-[#041F45]">
                                            {{ $rw->nilai !== null ? (float) $rw->nilai : '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800">
                                                Terkirim &#10003;
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </main>

    {{-- FOOTER --}}
    <footer class="py-4 px-6 text-center text-xs text-slate-400 border-t border-slate-200/80 bg-white">
        <strong class="text-[#041F45] font-extrabold">Examuna CBT</strong> &bull; SMK Assuniyah Tumijajar &bull; {{ date('Y') }}
    </footer>
</div>
