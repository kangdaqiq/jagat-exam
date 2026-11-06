<div
    x-data="{
        showPassword: false,
        showHelp: false,
        isAndroidApp: false,
        batteryLevel: null,
        init() {
            if (window.AndroidExam) {
                this.isAndroidApp = true;
                if (typeof window.AndroidExam.getBatteryLevel === 'function') {
                    const lvl = window.AndroidExam.getBatteryLevel();
                    if (lvl !== null && lvl !== undefined && lvl >= 0) {
                        this.batteryLevel = lvl;
                    }
                }
            }
        }
    }"
    class="min-h-[100dvh] w-full relative flex flex-col lg:items-center lg:justify-center bg-[#f4f7fb] lg:p-6 xl:p-8 overflow-x-hidden font-['Plus_Jakarta_Sans',sans-serif] select-none"
>

    {{-- ===================================================================== --}}
    {{-- AMBIENT BACKGROUND (Desktop & Mobile Top Hero Mesh)                   --}}
    {{-- ===================================================================== --}}
    {{-- Mobile Top Hero Background (Curved Deep Navy & Emerald Header) --}}
    <div class="lg:hidden absolute top-0 inset-x-0 h-72 bg-gradient-to-br from-[#041F45] via-[#072d61] to-[#056c4e] rounded-b-[2.5rem] shadow-lg overflow-hidden pointer-events-none z-0">
        {{-- Decorative Glows inside Mobile Hero --}}
        <div class="absolute -top-16 -right-12 w-52 h-52 rounded-full bg-emerald-400/20 blur-2xl"></div>
        <div class="absolute bottom-0 -left-12 w-48 h-48 rounded-full bg-sky-400/15 blur-2xl"></div>
        {{-- Subtle Dot Matrix --}}
        <div class="absolute inset-0 opacity-10"
             style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 18px 18px;"></div>
    </div>

    {{-- Desktop Ambient Background Glows --}}
    <div class="hidden lg:block absolute inset-0 pointer-events-none overflow-hidden z-0"
         style="background: radial-gradient(circle at 15% 20%, #ffffff 0%, #f1f5f9 65%, #e2e8f0 100%);">
        <div class="absolute -top-32 -left-32 w-[28rem] h-[28rem] rounded-full opacity-45 blur-3xl"
             style="background: radial-gradient(circle, rgba(16, 185, 129, 0.22) 0%, rgba(255, 255, 255, 0) 70%);"></div>
        <div class="absolute -bottom-32 -right-32 w-[32rem] h-[32rem] rounded-full opacity-40 blur-3xl"
             style="background: radial-gradient(circle, rgba(2, 132, 199, 0.18) 0%, rgba(4, 31, 69, 0.12) 45%, rgba(255, 255, 255, 0) 75%);"></div>
        <div class="absolute inset-0 opacity-[0.03]"
             style="background-image: radial-gradient(#041f45 1px, transparent 1px); background-size: 24px 24px;"></div>
    </div>

    {{-- ===================================================================== --}}
    {{-- MOBILE TOP BRANDING HEADER (Visible only on < lg)                     --}}
    {{-- ===================================================================== --}}
    <div class="lg:hidden relative z-10 px-5 pt-6 pb-4 flex flex-col items-center text-center">
        {{-- Top Status Bar: School Name & Live App/Connection Indicator --}}
        <div class="w-full max-w-md flex items-center justify-between gap-2 mb-4">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/10 border border-white/15 backdrop-blur-md">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[10px] font-extrabold tracking-wider uppercase text-white/95">
                    SMK Assuniyah Tumijajar
                </span>
            </div>

            {{-- Dynamic Kiosk / Online Status Pill --}}
            <div x-show="isAndroidApp" x-cloak class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 backdrop-blur-md text-[10px] font-bold text-emerald-200">
                <svg class="w-3 h-3 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Kiosk App</span>
                <template x-if="batteryLevel !== null">
                    <span class="opacity-90" x-text="'• ' + batteryLevel + '%'"></span>
                </template>
            </div>

            <div x-show="!isAndroidApp" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/10 border border-white/15 backdrop-blur-md text-[10px] font-semibold text-sky-100">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-300"></span>
                <span>CBT Online</span>
            </div>
        </div>

        {{-- Seamless White Logo for Mobile Hero (No white bg/border) --}}
        <div class="my-1 flex items-center justify-center">
            <img src="{{ asset('images/logo/examuna-logo-white.png') }}"
                 alt="Examuna - SMK Assuniyah Tumijajar"
                 class="h-20 sm:h-24 w-auto object-contain drop-shadow-[0_6px_16px_rgba(0,0,0,0.28)]"
                 loading="eager" />
        </div>

        <p class="text-[11px] sm:text-xs text-sky-100/90 font-medium mt-2.5 tracking-wide">
            Sistem Ujian Berbasis Komputer &amp; Evaluasi Terpadu
        </p>
    </div>

    {{-- ===================================================================== --}}
    {{-- MAIN CONTAINER (Mobile Card Sheet / Desktop Split Showcase)           --}}
    {{-- ===================================================================== --}}
    <div class="w-full max-w-md sm:max-w-lg lg:max-w-5xl mx-auto px-4 sm:px-6 lg:px-0 pb-6 lg:pb-0 flex-1 lg:flex-initial flex flex-col justify-between lg:block relative z-10">

        <div class="w-full bg-white rounded-3xl lg:rounded-[1.75rem] shadow-[0_14px_40px_-10px_rgba(4,31,69,0.12),0_2px_10px_-2px_rgba(4,31,69,0.04)] border border-slate-200/80 overflow-hidden grid grid-cols-1 lg:grid-cols-12">

            {{-- ============================================================= --}}
            {{-- LEFT COLUMN (Desktop Only): Deep Navy & Emerald Brand Panel   --}}
            {{-- ============================================================= --}}
            <div class="hidden lg:flex lg:col-span-5 flex-col justify-between p-8 xl:p-10 relative overflow-hidden bg-gradient-to-br from-[#041F45] via-[#072d61] to-[#065f46] text-white">

                {{-- Ambient Glows --}}
                <div class="absolute -top-20 -right-20 w-60 h-60 rounded-full bg-emerald-400/20 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-20 w-64 h-64 rounded-full bg-sky-400/15 blur-3xl pointer-events-none"></div>
                <div class="absolute inset-0 opacity-[0.07] pointer-events-none"
                     style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>

                {{-- Top Institution Badge --}}
                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/15 backdrop-blur-md">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-[11px] font-extrabold tracking-wider uppercase text-white">
                            SMK ASSUNIYAH TUMIJAJAR
                        </span>
                    </div>
                </div>

                {{-- Center Logo & Platform Identity (Seamless without white box/border) --}}
                <div class="relative z-10 my-6 flex flex-col items-center text-center">
                    <div class="w-full max-w-[240px] flex items-center justify-center transition-transform duration-300 hover:scale-[1.02]">
                        <img src="{{ asset('images/logo/examuna-logo-white.png') }}"
                             alt="Logo Examuna SMK Assuniyah Tumijajar"
                             class="w-full h-auto max-h-40 object-contain mx-auto drop-shadow-[0_10px_24px_rgba(0,0,0,0.28)]"
                             loading="eager" />
                    </div>

                    <div class="mt-5">
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-widest uppercase bg-emerald-400/15 text-emerald-300 border border-emerald-400/30">
                            Digital Examination System
</span>
                        <p class="text-xs text-slate-200/85 mt-1.5 leading-relaxed max-w-xs">
                            Platform asesmen digital terpadu dengan pengawasan integritas real-time dan sinkronisasi jawaban otomatis.
                        </p>
                    </div>
                </div>

                {{-- Feature Highlights --}}
                <div class="relative z-10 space-y-2.5 pt-4 border-t border-white/10">
                    <div class="flex items-center gap-3 p-2.5 rounded-xl bg-white/8 border border-white/10 backdrop-blur-xs">
                        <div class="w-8 h-8 rounded-lg bg-emerald-400/20 text-emerald-300 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <div class="text-[11px] font-bold text-white leading-tight">Keamanan Kiosk &amp; Token Sesi</div>
                            <div class="text-[10px] text-slate-300/80 leading-tight mt-0.5">Proteksi keluar aplikasi &amp; validasi proktor</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-2.5 rounded-xl bg-white/8 border border-white/10 backdrop-blur-xs">
                        <div class="w-8 h-8 rounded-lg bg-sky-400/20 text-sky-300 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <div class="text-[11px] font-bold text-white leading-tight">Auto-Save &amp; Responsif Mobile</div>
                            <div class="text-[10px] text-slate-300/80 leading-tight mt-0.5">Jawaban tersimpan instan di setiap nomor soal</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================= --}}
            {{-- RIGHT COLUMN: Login Form Area (Mobile-First Ergonomics)       --}}
            {{-- ============================================================= --}}
            <div class="lg:col-span-7 p-5 sm:p-8 lg:p-10 xl:p-12 flex flex-col justify-between bg-white text-slate-800">

                <div>
                    {{-- Header Title (Unified & Adaptive for Mobile + Desktop) --}}
                    <div class="flex items-start justify-between gap-3 mb-5 pb-3.5 border-b border-slate-100">
                        <div>
                            <div class="inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 mb-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Autentikasi Peserta &amp; Pengawas</span>
                            </div>
                            <h1 class="text-xl sm:text-2xl font-black text-[#041F45] tracking-tight font-['Outfit',sans-serif]">
                                Masuk Portal Ujian
                            </h1>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">
                                Gunakan <strong class="text-slate-700 font-semibold">NIS/NISN</strong> Siswa.
                            </p>
                        </div>

                        {{-- Shield Icon Accent --}}
                        <div class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-center text-[#041F45] shrink-0 shadow-2xs">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>

                    {{-- ===================================================== --}}
                    {{-- INTERACTIVE LOGIN FORM                                --}}
                    {{-- ===================================================== --}}
                    <form wire:submit.prevent="doLogin" class="space-y-4">

                        {{-- 1. Input NIS / NIP / Email --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="login_input" class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                                    Username / NIS / NISN
                                </label>
                                <span class="text-[11px] text-slate-400 font-medium">
                                    Contoh Username: <span class="font-mono font-semibold text-slate-600">10234</span>
                                </span>
                            </div>

                            <div class="relative group">
                                {{-- Left User Icon --}}
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#041F45] transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>

                                {{-- Note: text-base (16px) on mobile prevents iOS/Android auto-zoom on focus --}}
                                <input
                                    id="login_input"
                                    type="text"
                                    wire:model="login"
                                    placeholder="Ketik Username, NIS atau NISN Anda..."
                                    autocomplete="username"
                                    autocapitalize="none"
                                    autocorrect="off"
                                    spellcheck="false"
                                    enterkeyhint="next"
                                    required
                                    class="w-full h-12 sm:h-12 pl-11 pr-10 bg-slate-50/80 hover:bg-slate-50 focus:bg-white text-slate-900 placeholder:text-slate-400 text-base sm:text-sm font-semibold rounded-xl border @error('login') border-rose-400 bg-rose-50/30 @else border-slate-200/90 focus:border-[#041F45] @enderror focus:ring-4 focus:ring-emerald-500/15 outline-none transition-all duration-150"
                                />
                            </div>

                            @error('login')
                                <div class="mt-2 p-2.5 rounded-xl bg-rose-50 border border-rose-200/80 text-xs font-semibold text-rose-700 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        {{-- 2. Input Password with Large Touch Toggle --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password_input" class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                                    Kata Sandi (Password)
                                </label>
                                <span class="text-[11px] text-slate-400 font-medium">
                                    Default: <code class="px-1.5 py-0.5 rounded-md bg-slate-100 border border-slate-200/70 font-mono text-[10px] font-bold text-slate-700">NIS/NISN</code>
                                </span>
                            </div>

                            <div class="relative group">
                                {{-- Left Lock Icon --}}
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#041F45] transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>

                                <input
                                    id="password_input"
                                    :type="showPassword ? 'text' : 'password'"
                                    wire:model="password"
                                    placeholder="Masukkan kata sandi akun..."
                                    autocomplete="current-password"
                                    enterkeyhint="go"
                                    required
                                    class="w-full h-12 sm:h-12 pl-11 pr-12 bg-slate-50/80 hover:bg-slate-50 focus:bg-white text-slate-900 placeholder:text-slate-400 text-base sm:text-sm font-semibold rounded-xl border @error('password') border-rose-400 bg-rose-50/30 @else border-slate-200/90 focus:border-[#041F45] @enderror focus:ring-4 focus:ring-emerald-500/15 outline-none transition-all duration-150"
                                />

                                {{-- Touch-Friendly Eye Button (44x48px hit area) --}}
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 w-12 flex items-center justify-center text-slate-400 hover:text-[#041F45] active:scale-95 transition-all focus:outline-none"
                                    :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                    tabindex="-1">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>

                            @error('password')
                                <div class="mt-2 p-2.5 rounded-xl bg-rose-50 border border-rose-200/80 text-xs font-semibold text-rose-700 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror
                        </div>

                        {{-- 3. Remember Session & Quick Help Toggle --}}
                        <div class="flex items-center justify-between pt-0.5">
                            <label class="inline-flex items-center gap-2.5 cursor-pointer py-1">
                                <input
                                    type="checkbox"
                                    wire:model="remember"
                                    class="w-4 h-4 rounded border-slate-300 text-[#041F45] focus:ring-emerald-500 focus:ring-offset-0 transition cursor-pointer"
                                />
                                <span class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                                    Ingat sesi saya
                                </span>
                            </label>

                            <button
                                type="button"
                                @click="showHelp = !showHelp"
                                class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100/80 px-2.5 py-1 rounded-full border border-emerald-200/70 transition-colors">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-text="showHelp ? 'Tutup Info' : 'Bantuan Login'">Bantuan Login</span>
                            </button>
                        </div>

                        {{-- Expandable Quick Help Drawer (Great for Mobile without cluttering) --}}
                        <div x-show="showHelp" x-cloak class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 space-y-1.5">
                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Panduan Singkat Masuk Ujian:</span>
                            </div>
                            <ul class="list-disc list-inside space-y-1 text-[11px] text-slate-600 leading-relaxed">
                                <li><strong>Siswa:</strong> Masukkan nomor <strong>NIS/NISN</strong> Anda dan password default <code class="px-1 py-0.5 bg-white rounded border border-slate-200 font-mono text-slate-800">NIS/NISN</code>.</li>
                                <li><strong>Token Ujian:</strong> Kode token 6 karakter dimasukkan pada halaman berikutnya setelah berhasil login.</li>
                                <li><strong>Kendala Akun:</strong> Hubungi Proktor atau Pengawas ruangan jika NIS tidak ditemukan atau akun terkunci.</li>
                            </ul>
                        </div>

                        {{-- 4. Primary Submit Button (Thumb-Friendly 50px Height) --}}
                        <div class="pt-1.5">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="w-full h-12 sm:h-13 px-6 rounded-xl font-bold text-sm sm:text-[15px] text-white tracking-wide transition-all duration-200 flex items-center justify-center gap-2.5 shadow-[0_8px_20px_-4px_rgba(4,31,69,0.35)] hover:shadow-[0_12px_24px_-4px_rgba(4,31,69,0.45)] active:scale-[0.98] disabled:opacity-75 disabled:cursor-not-allowed group"
                                style="background: linear-gradient(135deg, #041F45 0%, #07336b 55%, #059669 100%);">
                                <span wire:loading.remove wire:target="doLogin" class="flex items-center gap-2">
                                    <span>Masuk ke Portal Ujian</span>
                                    <svg class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </span>
                                <span wire:loading wire:target="doLogin" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Memverifikasi Kredensial...</span>
                                </span>
                            </button>
                        </div>
                    </form>

                    {{-- Compact Integrity Notice Banner --}}
                    <div class="mt-4 p-3 rounded-xl bg-amber-50/60 border border-amber-200/70 flex items-start gap-2.5">
                        <div class="p-1 rounded-lg bg-amber-100 text-amber-700 shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-relaxed m-0">
                            <strong class="font-bold text-slate-800">Perhatian:</strong> Selama ujian berlangsung, aktivitas keluar aplikasi atau berpindah tab akan tercatat otomatis pada sistem pengawas.
                        </p>
                    </div>
                </div>

                {{-- ========================================================= --}}
                {{-- FOOTER & ATTRIBUTION                                      --}}
                {{-- ========================================================= --}}
                <div class="mt-6 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
                    <div>
                        <div class="text-xs font-extrabold text-[#041F45] font-['Outfit',sans-serif]">
                            Examuna <span class="text-slate-300 font-normal">&bull;</span> SMK Assuniyah Tumijajar
                        </div>
                        <div class="text-[10px] sm:text-[11px] text-slate-400 font-medium">
                            &copy; {{ date('Y') }} Sistem Ujian Terpadu &bull; All Rights Reserved
                        </div>
</div>
                </div>

            </div>
        </div>
    </div>
</div>
