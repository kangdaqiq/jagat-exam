<div class="min-h-screen relative flex items-center justify-center p-4 sm:p-6 lg:p-10 overflow-hidden font-['Plus_Jakarta_Sans',sans-serif]"
     style="background: radial-gradient(circle at 10% 20%, #0d213a 0%, #081324 50%, #040913 100%);">

    {{-- Glowing Ambient Light Orbs --}}
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full opacity-25 blur-3xl pointer-events-none"
         style="background: radial-gradient(circle, #10b981 0%, transparent 70%);"></div>
    <div class="absolute -bottom-36 -right-36 w-[32rem] h-[32rem] rounded-full opacity-20 blur-3xl pointer-events-none"
         style="background: radial-gradient(circle, #f59e0b 0%, #10b981 40%, transparent 75%);"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[42rem] h-[42rem] rounded-full opacity-10 blur-[130px] pointer-events-none"
         style="background: radial-gradient(circle, #38bdf8 0%, transparent 70%);"></div>

    {{-- Subtle Geometric Grid Overlay --}}
    <div class="absolute inset-0 pointer-events-none opacity-[0.035]"
         style="background-image: radial-gradient(rgba(255, 255, 255, 0.9) 1px, transparent 1px); background-size: 28px 28px;"></div>

    {{-- Main Wrapper Card --}}
    <div class="w-full max-w-5xl relative z-10 bg-white/95 backdrop-blur-xl rounded-3xl shadow-[0_30px_90px_rgba(0,0,0,0.55)] border border-white/20 overflow-hidden grid grid-cols-1 lg:grid-cols-12">

        {{-- ========================================================================= --}}
        {{-- LEFT COLUMN: Identity Showcase & Institution Branding (Visible on Desktop) --}}
        {{-- ========================================================================= --}}
        <div class="hidden lg:flex lg:col-span-5 flex-col justify-between p-9 relative overflow-hidden"
             style="background: linear-gradient(155deg, #09172c 0%, #0b1e38 50%, #05101f 100%); border-right: 1px solid rgba(255,255,255,0.08);">

            {{-- Decorative Accent Elements inside Left Pane --}}
            <div class="absolute -top-20 -right-20 w-52 h-52 rounded-full opacity-20 blur-2xl pointer-events-none"
                 style="background: #10b981;"></div>
            <div class="absolute bottom-0 left-0 w-44 h-44 rounded-full opacity-15 blur-2xl pointer-events-none"
                 style="background: #f59e0b;"></div>

            {{-- School Header Badge --}}
            <div class="relative z-10">
                <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[11px] font-extrabold tracking-wider uppercase text-emerald-300">
                        SMK ASSUNIYAH TUMIJAJAR
                    </span>
                </div>
                <div class="text-[11px] text-slate-400 font-medium mt-1.5 pl-1">
                    Tulang Bawang Barat &bull; Lampung
                </div>
            </div>

            {{-- Center Logo & Title Display --}}
            <div class="relative z-10 my-8 flex flex-col items-center text-center">
                {{-- Logo Container with Clean Backlight Effect --}}
                <div class="w-full max-w-[290px] p-5 rounded-2xl bg-white/[0.04] border border-white/10 backdrop-blur-md shadow-2xl transition duration-300 hover:border-emerald-500/40">
                    <img src="{{ asset('images/logo/examuna-logo.png') }}"
                         alt="Logo Examuna SMK Assuniyah Tumijajar"
                         class="w-full h-auto max-h-36 object-contain drop-shadow-[0_15px_25px_rgba(0,0,0,0.6)]"
                         loading="eager" />
                </div>

                <div class="mt-5">
                    <span class="inline-block px-3 py-1 rounded-md text-[10px] font-black tracking-widest uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        Digital Examination System
                    </span>
                    <h2 class="text-2xl font-black tracking-tight text-white mt-2 font-['Outfit',sans-serif]">
                        Exam<span class="text-emerald-400">una</span> CBT
                    </h2>
                    <p class="text-xs text-slate-300/80 mt-2 leading-relaxed max-w-xs font-normal">
                        Platform asesmen dan evaluasi pembelajaran digital terpadu dengan standar keamanan modern & anti-kecurangan.
                    </p>
                </div>
            </div>

            {{-- Feature Micro-Pills --}}
            <div class="relative z-10 space-y-2.5 pt-4 border-t border-white/10">
                <div class="flex items-center gap-3 text-xs text-slate-200 bg-white/5 px-3 py-2 rounded-xl border border-white/5">
                    <div class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-[11px] leading-tight">Pengawasan Integritas Ujian & Token Dinamis</span>
                </div>

                <div class="flex items-center gap-3 text-xs text-slate-200 bg-white/5 px-3 py-2 rounded-xl border border-white/5">
                    <div class="w-6 h-6 rounded-lg bg-amber-500/20 text-amber-300 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-[11px] leading-tight">Mendukung PG, Isian, Essay AI & Rumus LaTeX</span>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- RIGHT COLUMN: The Interactive Modern Login Form --}}
        {{-- ========================================================================= --}}
        <div class="lg:col-span-7 p-6 sm:p-10 lg:p-12 flex flex-col justify-between bg-white text-slate-800">

            {{-- Top Branding Header for Mobile / Tablet View --}}
            <div class="lg:hidden flex flex-col items-center text-center mb-6 pb-6 border-b border-slate-100">
                <div class="p-2.5 bg-slate-50/80 rounded-2xl border border-slate-200 shadow-sm max-w-[260px] mb-2.5">
                    <img src="{{ asset('images/logo/examuna.png') }}"
                         alt="Logo Examuna"
                         class="w-full h-auto max-h-14 object-contain" />
                </div>
                <span class="text-[10px] font-extrabold uppercase tracking-widest text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                    Digital Examination System
                </span>
                <div class="text-xs font-bold text-slate-600 mt-1">
                    SMK Assuniyah Tumijajar
                </div>
            </div>

            {{-- Form Title Section --}}
            <div>

                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight font-['Outfit',sans-serif]">
                    Masuk
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1 leading-relaxed">
                    Silakan masukkan kredensial akun Anda (NIS untuk Siswa, NIP / Email untuk Guru & Pengawas) untuk mengakses sistem ujian.
                </p>

                {{-- The Form Component --}}
                <form wire:submit.prevent="doLogin" class="mt-7 space-y-4 sm:space-y-5" x-data="{ showPassword: false }">

                    {{-- Input NIS / NIP / Email --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="login_input" class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                                NIS / NIP / Email
                            </label>
                            <span class="text-[11px] text-slate-400 font-medium">Contoh: 10234</span>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>

                            <input
                                id="login_input"
                                type="text"
                                wire:model="login"
                                placeholder="Masukkan NIS atau Email terdaftar..."
                                autocomplete="username"
                                required
                                class="w-full pl-11 pr-4 py-3 sm:py-3.5 bg-slate-50/70 hover:bg-slate-50 focus:bg-white text-slate-900 text-sm font-semibold rounded-xl border border-slate-300 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/15 outline-none transition-all duration-200 shadow-sm"
                            />
                        </div>

                        @error('login')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Input Password with Toggle Eye --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password_input" class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                                Password Akun
                            </label>
                            <span class="text-[11px] text-slate-400 font-medium">Default: <code class="px-1 py-0.5 rounded bg-slate-100 font-mono text-[10px] text-slate-600">password</code></span>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>

                            <input
                                id="password_input"
                                :type="showPassword ? 'text' : 'password'"
                                wire:model="password"
                                placeholder="Masukkan password Anda..."
                                autocomplete="current-password"
                                required
                                class="w-full pl-11 pr-11 py-3 sm:py-3.5 bg-slate-50/70 hover:bg-slate-50 focus:bg-white text-slate-900 text-sm font-semibold rounded-xl border border-slate-300 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/15 outline-none transition-all duration-200 shadow-sm"
                            />

                            {{-- Show/Hide Password Eye Button --}}
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                                title="Tampilkan / Sembunyikan Password"
                                tabindex="-1">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>

                        @error('password')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Remember Me & Info --}}
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                wire:model="remember"
                                class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 transition"
                            />
                            <span class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                                Ingat sesi login
                            </span>
                        </label>

                        <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50/80 px-2 py-0.5 rounded border border-emerald-100">
                            Anti-Nyontek Aktif
                        </span>
                    </div>

                    {{-- Action Button --}}
                    <div class="pt-2">
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 sm:py-4 px-6 rounded-xl font-extrabold text-sm sm:text-base text-white tracking-wide transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/20 hover:shadow-xl hover:shadow-emerald-600/25 active:scale-[0.99] disabled:opacity-75 disabled:cursor-not-allowed"
                            style="background: linear-gradient(135deg, #091a32 0%, #0d2948 40%, #059669 100%);">
                            <span wire:loading.remove class="flex items-center gap-2">
                                Masuk ke Portal Ujian
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Memverifikasi Kredensial...
                            </span>
                        </button>
                    </div>
                </form>

                {{-- Security & Anti-Cheating Note --}}
                <div class="mt-6 p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-start gap-3">
                    <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-700 shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed font-medium m-0">
                        Pastikan Anda menggunakan perangkat yang diizinkan sekolah. Segala aktivitas keluar jendela atau perpindahan tab akan tercatat otomatis pada sistem pengawas.
                    </p>
                </div>
            </div>

            {{-- ===================================================================== --}}
            {{-- FOOTER & CREDIT --}}
            {{-- ===================================================================== --}}
            <div class="mt-8 pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
                <div>
                    <div class="text-xs font-bold text-slate-700 font-['Outfit',sans-serif]">
                        Examuna <span class="text-slate-400 font-normal">&bull;</span> Digital Examination System
                    </div>
                    <div class="text-[11px] text-slate-400 font-medium">
                        SMK Assuniyah Tumijajar &bull; &copy; {{ date('Y') }} All Rights Reserved
                    </div>
                </div>

                {{-- User Requested Credit --}}
                <div class="text-xs font-semibold text-slate-500 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200/70 inline-flex items-center gap-1.5">
                    <span class="text-[11px] text-slate-400">credit:</span>
                    <span class="font-extrabold text-emerald-600 hover:text-emerald-700 tracking-wide">by KangDaqiq</span>
                </div>
            </div>

        </div>
    </div>
</div>
