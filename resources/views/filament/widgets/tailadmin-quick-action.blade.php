@php
    $user = $this->getUser();
    $role = $this->getUserRole();
    $prefix = $role === 'admin' ? '/admin' : '/guru';
    $activeCount = $this->getActiveUjianCount();
    $initials = $this->getUserInitials();
    $schoolName = $user?->school_name ?: 'SMK Assuniyah Tumijajar';
@endphp

<x-filament-widgets::widget class="col-span-full" style="grid-column: 1 / -1;">
<div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-[#1a2231]">
    {{-- Decorative Background SVG from TailAdmin --}}
    <div class="absolute -right-10 -top-10 pointer-events-none opacity-5 dark:opacity-10">
        <svg width="260" height="260" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="120" cy="120" r="100" stroke="#465FFF" stroke-width="32" stroke-dasharray="16 16" />
        </svg>
    </div>

    <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-6 relative z-10">
        {{-- Profile & Info Block --}}
        <div class="flex items-start sm:items-center gap-4.5">
            {{-- User Avatar / Initials --}}
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#465fff] to-[#7592ff] text-white font-extrabold text-lg flex items-center justify-center shadow-md shrink-0 tracking-wider" style="font-family: 'Outfit', sans-serif;">
                {{ $initials }}
            </div>

            <div>
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        Selamat datang kembali,
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold {{
                        $role === 'admin'
                            ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400 border border-rose-200/50'
                            : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/50'
                    }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $role === 'admin' ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                        {{ $role === 'admin' ? 'Administrator CBT' : 'Guru Pengampu' }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        WIB (GMT+7)
                    </span>
                </div>

                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight" style="font-family: 'Outfit', sans-serif;">
                    {{ $user?->name ?: 'Pengguna CBT' }}
                </h1>

                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-1 flex-wrap">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $schoolName }}</span>
                    @if($user?->nip)
                        <span>&bull;</span>
                        <span>NIP: <strong class="font-semibold text-gray-800 dark:text-gray-200">{{ $user->nip }}</strong></span>
                    @endif
                    <span>&bull;</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Keamanan CBT Anti-Nyontek Aktif
                    </span>
                </div>
            </div>
        </div>

        {{-- Right Actions --}}
        <div class="flex flex-wrap items-center gap-2.5 w-full xl:w-auto">
            <a
                href="{{ $prefix }}/ujians/create"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl text-white bg-[#465fff] hover:bg-[#3641f5] active:bg-[#2a31d8] shadow-theme-xs transition-all duration-150"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Buat Jadwal Ujian</span>
            </a>

            <a
                href="{{ $prefix }}/bank-soals"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 dark:bg-white/[0.05] dark:text-gray-300 dark:border-gray-700 dark:hover:bg-white/[0.08] transition-all duration-150"
            >
                <svg class="w-4 h-4 text-[#465fff]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Bank Soal AI</span>
            </a>

            <a
                href="{{ $prefix }}/hasil-ujians"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 dark:bg-white/[0.05] dark:text-gray-300 dark:border-gray-700 dark:hover:bg-white/[0.08] transition-all duration-150"
            >
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Hasil & Nilai</span>
            </a>
        </div>
    </div>
</div>
</x-filament-widgets::widget>
