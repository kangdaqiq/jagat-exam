<div
    x-data="{ showPassword: false }"
    class="min-h-[100dvh] w-full relative flex flex-col justify-between lg:items-center lg:justify-center bg-[#f4f7fb] lg:p-6 overflow-x-hidden select-none"
>
    {{-- Mobile & Desktop Top Hero Header --}}
    <div
        class="lg:hidden absolute top-0 inset-x-0 h-72 rounded-b-[2.5rem] shadow-lg overflow-hidden pointer-events-none z-0"
        style="background: linear-gradient(135deg, #041F45 0%, #072d61 55%, #056c4e 100%);"
    >
        <div class="absolute -top-16 -right-12 w-52 h-52 rounded-full bg-emerald-400/20 blur-2xl"></div>
        <div class="absolute bottom-0 -left-12 w-48 h-48 rounded-full bg-sky-400/15 blur-2xl"></div>
    </div>

    {{-- Mobile Header Branding --}}
    <div class="lg:hidden relative z-10 px-5 pt-6 pb-3 flex flex-col items-center text-center">
        <div class="w-full max-w-md flex items-center justify-center gap-2 mb-3">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 backdrop-blur-md">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[10px] font-extrabold tracking-wider uppercase text-white/95">
                    Examuna PWA
                </span>
            </div>
        </div>

        <div class="flex items-center justify-center gap-3">
            <img src="{{ asset('images/logo/pwa-pengawas-icon.png') }}"
                 alt="Examuna Icon"
                 class="h-14 w-14 rounded-2xl object-cover shadow-lg border border-white/20" />
            <img src="{{ asset('images/logo/examuna-logo-white.png') }}"
                 alt="Examuna"
                 class="h-16 w-auto object-contain drop-shadow-[0_6px_16px_rgba(0,0,0,0.28)]" />
        </div>
        <p class="text-[11px] text-sky-100/90 font-semibold mt-2">
            Portal Mobile Pengawas, Rilis Token &amp; Kontrol Sesi Siswa Real-time
        </p>
    </div>

    {{-- Main Card Container --}}
    <div class="w-full max-w-md lg:max-w-4xl mx-auto px-4 sm:px-6 lg:px-0 pb-6 lg:pb-0 flex-1 lg:flex-initial flex flex-col justify-between lg:block relative z-10">
        <div class="w-full bg-white rounded-3xl shadow-[0_14px_40px_-10px_rgba(4,31,69,0.12)] border border-slate-200/80 overflow-hidden grid grid-cols-1 lg:grid-cols-12">

            {{-- Left Column (Desktop Only) --}}
            <div
                class="hidden lg:flex lg:col-span-5 flex-col justify-between p-8 relative overflow-hidden text-white"
                style="background: linear-gradient(135deg, #041F45 0%, #072d61 55%, #065f46 100%); color: #ffffff;"
            >

                <div class="relative z-10 my-6 flex flex-col items-center text-center">
                    <img src="{{ asset('images/logo/pwa-pengawas-icon.png') }}"
                         alt="Examuna Icon"
                         class="w-16 h-16 rounded-2xl object-cover mb-3 shadow-xl border border-white/20" />
                    <img src="{{ asset('images/logo/examuna-logo-white.png') }}"
                         alt="Examuna"
                         class="w-full max-w-[200px] h-auto object-contain mx-auto drop-shadow-lg" />
                    <h2 class="mt-4 text-lg font-black tracking-tight" style="font-family: 'Outfit', sans-serif;">
                        Kontrol Ruang Ujian dalam Genggaman
                    </h2>
                    <p class="text-xs text-slate-200/85 mt-1.5 leading-relaxed">
                        Pantau progress siswa secara langsung, rilis token 10 menit, deteksi kecurangan pindah tab, dan reset login siswa dari smartphone Anda.
                    </p>
                </div>

                <div class="relative z-10 space-y-2 pt-4 border-t border-white/10 text-xs">
                    <div class="flex items-center gap-2.5 text-emerald-200 font-semibold">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Rilis &amp; Perbarui Token 6 Karakter</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-emerald-200 font-semibold">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Pantau Pelanggaran Tab &amp; Progress Live</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-emerald-200 font-semibold">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Reset Sesi &amp; Tambah Waktu +10 Menit</span>
                    </div>
                </div>
            </div>

            {{-- Right Column: Login Form --}}
            <div class="lg:col-span-7 p-5 sm:p-8 lg:p-10 flex flex-col justify-between bg-white">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-5 pb-3.5 border-b border-slate-100">
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 mb-1">
                                Khusus Guru &amp; Proktor
                            </span>
                            <h1 class="text-xl sm:text-2xl font-black text-[#041F45] tracking-tight" style="font-family: 'Outfit', sans-serif;">
                                Masuk Ruang Pengawas
                            </h1>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">
                                Gunakan akun <strong>NIP / Email Guru</strong> Anda untuk memantau kelas.
                            </p>
                        </div>

                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-700 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>

                    <form wire:submit.prevent="doLogin" class="space-y-4">
                        <div>
                            <label for="pengawas_login" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                                NIP / Username / Email Guru
                            </label>
                            <input
                                id="pengawas_login"
                                type="text"
                                wire:model="login"
                                placeholder="Ketik NIP atau Email Guru..."
                                autocomplete="username"
                                autocapitalize="none"
                                required
                                class="w-full h-12 px-4 bg-slate-50 focus:bg-white text-slate-900 placeholder:text-slate-400 text-base sm:text-sm font-semibold rounded-xl border @error('login') border-rose-400 bg-rose-50/30 @else border-slate-200 focus:border-[#041F45] @enderror focus:ring-4 focus:ring-emerald-500/15 outline-none transition"
                            />
                            @error('login')
                                <div class="mt-2 p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div>
                            <label for="pengawas_password" class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <input
                                    id="pengawas_password"
                                    :type="showPassword ? 'text' : 'password'"
                                    wire:model="password"
                                    placeholder="Masukkan kata sandi..."
                                    autocomplete="current-password"
                                    required
                                    class="w-full h-12 pl-4 pr-12 bg-slate-50 focus:bg-white text-slate-900 placeholder:text-slate-400 text-base sm:text-sm font-semibold rounded-xl border @error('password') border-rose-400 bg-rose-50/30 @else border-slate-200 focus:border-[#041F45] @enderror focus:ring-4 focus:ring-emerald-500/15 outline-none transition"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 w-12 flex items-center justify-center text-slate-400 hover:text-[#041F45]"
                                    tabindex="-1"
                                >
                                    <span class="text-[11px] font-bold" x-text="showPassword ? 'Tutup' : 'Lihat'">Lihat</span>
                                </button>
                            </div>
                            @error('password')
                                <div class="mt-2 p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between pt-0.5">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="remember" class="w-4 h-4 rounded border-slate-300 text-[#041F45] focus:ring-emerald-500" />
                                <span class="text-xs font-semibold text-slate-600">Tetap masuk di perangkat ini</span>
                            </label>
                            <a href="{{ route('login') }}" class="text-xs font-bold text-slate-500 hover:text-[#041F45]">
                                Login Siswa &rarr;
                            </a>
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full h-12 px-6 rounded-xl font-bold text-sm text-white tracking-wide transition flex items-center justify-center gap-2 shadow-lg cursor-pointer"
                            style="background: linear-gradient(135deg, #041F45 0%, #07336b 55%, #059669 100%);"
                        >
                            <span wire:loading.remove wire:target="doLogin">Masuk Portal Pengawas &raquo;</span>
                            <span wire:loading wire:target="doLogin">Memverifikasi Akun Guru...</span>
                        </button>
                    </form>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-medium">
                    <span>Examuna Pengawas</span>
                    <span>SMK Assuniyah Tumijajar</span>
                </div>
            </div>
        </div>
    </div>
</div>

