@php
    $role = $this->getUserRole();
    $prefix = $role === 'admin' ? '/admin' : '/guru';
    $activeCount = $this->getActiveUjianCount();
@endphp

<div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-[#1a2231]">
    {{-- Background Decorative Shape from TailAdmin --}}
    <div class="absolute -right-8 -top-8 pointer-events-none opacity-5 dark:opacity-10">
        <svg width="240" height="240" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="120" cy="120" r="100" stroke="#465FFF" stroke-width="30" stroke-dasharray="15 15" />
        </svg>
    </div>

    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 relative z-10">
        {{-- Left Info Block --}}
        <div class="max-w-2xl">
            <div class="flex items-center gap-2.5 mb-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#ecf3ff] text-[#465fff] dark:bg-[#465fff]/15 dark:text-[#7592ff]">
                    <span class="w-2 h-2 rounded-full bg-[#465fff] animate-pulse"></span>
                    TailAdmin Theme Active
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Token 10 Menit Exp
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                    Bobot 75:25 (100 Poin)
                </span>
            </div>

            <h2 class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-white tracking-tight" style="font-family: 'Outfit', sans-serif;">
                ExamGuard CBT Dashboard
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                Platform Ujian Berbasis Komputer Modern & Anti-Nyontek. Kelola Bank Soal AI, Pelaksanaan Sesi Ujian dengan Refresh Token Otomatis, dan Penilaian Terpadu.
            </p>
        </div>

        {{-- Right Quick Actions --}}
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
            <a
                href="{{ $prefix }}/ujians/create"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl text-white bg-[#465fff] hover:bg-[#3641f5] active:bg-[#2a31d8] shadow-sm transition-all duration-150"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Buat Jadwal Ujian</span>
            </a>

            <a
                href="{{ $prefix }}/bank-soals"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 dark:bg-white/[0.05] dark:text-gray-300 dark:border-gray-700 dark:hover:bg-white/[0.08] transition-all duration-150"
            >
                <svg class="w-4 h-4 text-[#465fff]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Bank Soal AI</span>
            </a>

            <a
                href="{{ $prefix }}/hasil-ujians"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 dark:bg-white/[0.05] dark:text-gray-300 dark:border-gray-700 dark:hover:bg-white/[0.08] transition-all duration-150"
            >
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Hasil & Nilai</span>
            </a>
        </div>
    </div>
</div>

