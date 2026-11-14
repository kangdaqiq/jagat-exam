<x-filament-panels::page>
    @vite(['resources/css/app.css'])

    <div class="w-full flex flex-col gap-6 -mt-2">
        {{-- ================================================================= --}}
        {{-- TOP HEADER & ACTION BUTTONS                                       --}}
        {{-- ================================================================= --}}
        <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-theme-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-xl shrink-0 shadow-sm">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">
                        Monitoring Progres Pembuatan Soal
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Pantau kelengkapan bank soal, distribusi bobot 100 poin, dan kesiapan butir soal menjelang ujian.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <button
                    wire:click="exportCsv"
                    type="button"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-sm"
                    title="Unduh seluruh rekap progres dalam file CSV"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Ekspor Rekap (CSV)</span>
                </button>

                <a
                    href="{{ \App\Filament\Resources\BankSoals\BankSoalResource::getUrl('create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-xl transition shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Buat Bank Soal Baru</span>
                </a>
            </div>
        </div>

        {{-- ================================================================= --}}
        {{-- KPI STATS CARDS                                                   --}}
        {{-- ================================================================= --}}
        @php
            $stats = $this->stats;
            $isAdmin = auth()->user()?->isAdmin() ?? false;
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 {{ $isAdmin ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4">
            {{-- 1. Total Paket --}}
            <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-theme-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Total Paket Soal</span>
                    <span class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2.5 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_paket'] }}</span>
                    <span class="text-xs text-gray-400">paket</span>
                </div>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                    {{ $stats['total_butir'] }} butir soal terinput
                </p>
            </div>

            {{-- 2. Siap Digunakan --}}
            <div
                wire:click="$set('filterStatus', 'siap')"
                class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-theme-xs cursor-pointer hover:border-emerald-500 transition"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Siap Digunakan</span>
                    <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2.5 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $stats['siap_uji'] }}</span>
                    <span class="text-xs text-emerald-600/70">paket</span>
                </div>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                    100 Poin & Kunci Lengkap
                </p>
            </div>

            {{-- 3. Perlu Review --}}
            <div
                wire:click="$set('filterStatus', 'review')"
                class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-theme-xs cursor-pointer hover:border-amber-500 transition"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">Perlu Review</span>
                    <span class="p-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2.5 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-amber-600 dark:text-amber-300">{{ $stats['perlu_review'] }}</span>
                    <span class="text-xs text-amber-600/70">paket</span>
                </div>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                    Bobot ≠ 100 / Kunci kosong
                </p>
            </div>

            {{-- 4. Kosong --}}
            <div
                wire:click="$set('filterStatus', 'kosong')"
                class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-theme-xs cursor-pointer hover:border-rose-500 transition"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-rose-600 dark:text-rose-400">Belum Ada Butir</span>
                    <span class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2.5 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-rose-600 dark:text-rose-300">{{ $stats['kosong'] }}</span>
                    <span class="text-xs text-rose-600/70">paket</span>
                </div>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                    0 butir pertanyaan
                </p>
            </div>

            {{-- 5. Guru Belum Buat (Admin Only) --}}
            @if($isAdmin)
                <div
                    wire:click="setTab('guru_belum_buat')"
                    class="bg-white dark:bg-[#1a2231] border {{ $activeTab === 'guru_belum_buat' ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-800' }} rounded-2xl p-4 shadow-theme-xs cursor-pointer hover:border-indigo-500 transition"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">Guru Belum Buat</span>
                        <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-2.5 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-300">{{ $stats['guru_belum_buat'] }}</span>
                        <span class="text-xs text-indigo-600/70">guru</span>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                        Klik untuk lihat daftar guru
                    </p>
                </div>
            @endif
        </div>

        {{-- ================================================================= --}}
        {{-- TABS NAVIGATION (JIKA ADMIN)                                      --}}
        {{-- ================================================================= --}}
        @if($isAdmin)
            <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-2">
                <button
                    wire:click="setTab('paket')"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ $activeTab === 'paket' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Matriks Paket Bank Soal</span>
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'paket' ? 'bg-white/20 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200' }}">
                        {{ $stats['total_paket'] }}
                    </span>
                </button>

                <button
                    wire:click="setTab('guru_belum_buat')"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ $activeTab === 'guru_belum_buat' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Guru Belum Membuat Soal</span>
                    @if($stats['guru_belum_buat'] > 0)
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'guru_belum_buat' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                            {{ $stats['guru_belum_buat'] }}
                        </span>
                    @endif
                </button>
            </div>
        @endif

        {{-- ================================================================= --}}
        {{-- TAB 1: MATRIKS PAKET BANK SOAL                                    --}}
        {{-- ================================================================= --}}
        @if($activeTab === 'paket')
            <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs overflow-hidden">
                {{-- FILTER TOOLBAR --}}
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    {{-- Search Input --}}
                    <div class="relative flex-1 max-w-md">
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Cari judul soal, kode bank, guru, atau mapel..."
                            class="w-full pl-9 pr-4 py-2 text-xs border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    {{-- Dropdown Filters --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Tingkat --}}
                        <select
                            wire:model.live="filterTingkat"
                            class="text-xs border border-gray-300 dark:border-gray-700 rounded-xl px-2.5 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="all">Semua Tingkat</option>
                            <option value="10">Kelas X (10)</option>
                            <option value="11">Kelas XI (11)</option>
                            <option value="12">Kelas XII (12)</option>
                        </select>

                        {{-- Mapel --}}
                        <select
                            wire:model.live="filterMapel"
                            class="text-xs border border-gray-300 dark:border-gray-700 rounded-xl px-2.5 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500 max-w-[160px] truncate"
                        >
                            <option value="all">Semua Mapel</option>
                            @foreach($this->mapelOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>

                        {{-- Guru (Admin Only) --}}
                        @if($isAdmin)
                            <select
                                wire:model.live="filterGuru"
                                class="text-xs border border-gray-300 dark:border-gray-700 rounded-xl px-2.5 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500 max-w-[160px] truncate"
                            >
                                <option value="all">Semua Guru</option>
                                @foreach($this->guruOptions as $id => $nama)
                                    <option value="{{ $id }}">{{ $nama }}</option>
                                @endforeach
                            </select>
                        @endif

                        {{-- Status --}}
                        <select
                            wire:model.live="filterStatus"
                            class="text-xs border border-gray-300 dark:border-gray-700 rounded-xl px-2.5 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="all">Semua Status</option>
                            <option value="siap">Siap Digunakan</option>
                            <option value="review">Perlu Review</option>
                            <option value="kosong">Belum Ada Butir</option>
                        </select>

                        {{-- Reset Filter Button --}}
                        <button
                            wire:click="resetFilters"
                            type="button"
                            class="p-2 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                            title="Reset Seluruh Filter"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- TABLE MATRIX PROGRESS --}}
                @php
                    $bankList = $this->evaluatedBankSoals;
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4 cursor-pointer hover:text-primary-600 select-none" wire:click="toggleSort('judul')">
                                    <div class="flex items-center gap-1.5">
                                        <span>Paket Bank Soal</span>
                                        @if($sortBy === 'judul')
                                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-3 px-4 cursor-pointer hover:text-primary-600 select-none" wire:click="toggleSort('guru')">
                                    <div class="flex items-center gap-1.5">
                                        <span>Guru Pembuat</span>
                                        @if($sortBy === 'guru')
                                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-3 px-4">Target Rombel</th>
                                <th class="py-3 px-4 text-center cursor-pointer hover:text-primary-600 select-none" wire:click="toggleSort('total_soal')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span>Butir & Tipe</span>
                                        @if($sortBy === 'total_soal')
                                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-3 px-4 cursor-pointer hover:text-primary-600 select-none" wire:click="toggleSort('total_bobot')">
                                    <div class="flex items-center gap-1.5">
                                        <span>Distribusi Bobot</span>
                                        @if($sortBy === 'total_bobot')
                                            <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-3 px-4 text-center">Kunci Jawaban</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($bankList as $idx => $item)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                    {{-- No --}}
                                    <td class="py-3.5 px-4 text-center font-medium text-gray-400">
                                        {{ $idx + 1 }}
                                    </td>

                                    {{-- Paket Soal --}}
                                    <td class="py-3.5 px-4">
                                        <div class="flex flex-col gap-1">
                                            <a
                                                href="{{ \App\Filament\Resources\BankSoals\BankSoalResource::getUrl('edit', ['record' => $item->id]) }}"
                                                class="font-bold text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 leading-snug line-clamp-1"
                                                title="{{ $item->judul }}"
                                            >
                                                {{ $item->judul }}
                                            </a>
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                    {{ $item->kode_bank }}
                                                </span>
                                                @if($item->mapel)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                                        {{ $item->mapel->nama_mapel }}
                                                    </span>
                                                @endif
                                                @if($item->tingkat)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                        Kelas {{ $item->tingkat }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Guru Pembuat --}}
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300 font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($item->guru?->name ?? 'G', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 dark:text-white truncate max-w-[140px]">
                                                    {{ $item->guru?->name ?? 'Belum Ditentukan' }}
                                                </p>
                                                <p class="text-[10px] text-gray-400 truncate">
                                                    {{ $item->guru?->nip ?: 'NIP: -' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Target Rombel --}}
                                    <td class="py-3.5 px-4">
                                        @if($item->kelases->isNotEmpty())
                                            <div class="flex items-center gap-1 flex-wrap max-w-[180px]">
                                                @foreach($item->kelases as $k)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/50">
                                                        {{ $k->nama_kelas }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-[11px] text-gray-400 italic">Belum diatur</span>
                                        @endif
                                    </td>

                                    {{-- Butir & Komposisi --}}
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="font-bold text-sm text-gray-900 dark:text-white">
                                                {{ $item->total_soal }}
                                                <span class="text-[10px] font-normal text-gray-400">soal</span>
                                            </span>
                                            @if($item->total_soal > 0)
                                                <div class="flex items-center gap-1 text-[9px] text-gray-500 dark:text-gray-400 font-mono">
                                                    @if($item->komposisi['pilihan_ganda'] > 0)
                                                        <span title="Pilihan Ganda" class="px-1 py-0.2 rounded bg-gray-100 dark:bg-gray-800">PG:{{ $item->komposisi['pilihan_ganda'] }}</span>
                                                    @endif
                                                    @if($item->komposisi['pg_kompleks'] > 0)
                                                        <span title="Pilihan Ganda Kompleks" class="px-1 py-0.2 rounded bg-gray-100 dark:bg-gray-800">PGK:{{ $item->komposisi['pg_kompleks'] }}</span>
                                                    @endif
                                                    @if($item->komposisi['benar_salah'] > 0)
                                                        <span title="Benar / Salah" class="px-1 py-0.2 rounded bg-gray-100 dark:bg-gray-800">BS:{{ $item->komposisi['benar_salah'] }}</span>
                                                    @endif
                                                    @if($item->komposisi['menjodohkan'] > 0)
                                                        <span title="Menjodohkan" class="px-1 py-0.2 rounded bg-gray-100 dark:bg-gray-800">JD:{{ $item->komposisi['menjodohkan'] }}</span>
                                                    @endif
                                                    @if($item->komposisi['isian'] > 0)
                                                        <span title="Isian Singkat" class="px-1 py-0.2 rounded bg-gray-100 dark:bg-gray-800">IS:{{ $item->komposisi['isian'] }}</span>
                                                    @endif
                                                    @if($item->komposisi['essay'] > 0)
                                                        <span title="Uraian / Essay" class="px-1 py-0.2 rounded bg-gray-100 dark:bg-gray-800">ES:{{ $item->komposisi['essay'] }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Bobot & Progress Bar --}}
                                    <td class="py-3.5 px-4">
                                        <div class="flex flex-col gap-1.5 w-32">
                                            <div class="flex items-center justify-between text-[11px]">
                                                <span class="font-bold {{ $item->is_bobot_valid ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                    {{ (float) $item->total_bobot }} Poin
                                                </span>
                                                <span class="text-gray-400">/ 100</span>
                                            </div>
                                            {{-- Progress bar --}}
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                                <div
                                                    class="h-1.5 rounded-full {{ $item->is_bobot_valid ? 'bg-emerald-500' : ($item->total_bobot > 100 ? 'bg-rose-500' : 'bg-amber-500') }}"
                                                    style="width: {{ $item->bobot_percent }}%"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Kunci Jawaban Valid --}}
                                    <td class="py-3.5 px-4 text-center">
                                        @if($item->total_soal === 0)
                                            <span class="text-gray-400">-</span>
                                        @elseif($item->soal_tanpa_kunci === 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200/50">
                                                <svg class="w-3 h-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Lengkap
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200/50" title="{{ $item->soal_tanpa_kunci }} butir belum memiliki kunci jawaban">
                                                <svg class="w-3 h-3 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                {{ $item->soal_tanpa_kunci }} Kosong
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Status Kesiapan --}}
                                    <td class="py-3.5 px-4 text-center">
                                        @if($item->status_kesiapan === 'siap')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                Siap Digunakan
                                            </span>
                                        @elseif($item->status_kesiapan === 'review')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                                Perlu Review
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                                Belum Ada Butir
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Aksi Cepat --}}
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            {{-- Tombol Bagi Rata Bobot jika belum 100 poin --}}
                                            @if($item->total_soal > 0 && !$item->is_bobot_valid)
                                                <button
                                                    wire:click="autoBagiRataBobot({{ $item->id }})"
                                                    type="button"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 rounded-lg hover:bg-amber-100 transition shadow-xs"
                                                    title="Bagi rata seluruh butir soal ke total 100 poin"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                                    </svg>
                                                    <span>Bagi 100 Poin</span>
                                                </button>
                                            @endif

                                            {{-- Preview Siswa --}}
                                            @if($item->total_soal > 0)
                                                <a
                                                    href="{{ route('exam.run', ['bankSoal' => $item->id, 'preview' => 1]) }}"
                                                    target="_blank"
                                                    class="p-1.5 text-gray-500 hover:text-emerald-600 dark:text-gray-400 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 rounded-lg transition"
                                                    title="Pratinjau Lembar Soal Siswa"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                            @endif

                                            {{-- Studio Editor Soal --}}
                                            <a
                                                href="{{ \App\Filament\Resources\BankSoals\BankSoalResource::getUrl('edit', ['record' => $item->id]) }}"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-950/50 border border-primary-200 dark:border-primary-800 rounded-lg hover:bg-primary-100 transition shadow-xs"
                                                title="Buka Studio Editor Soal"
                                            >
                                                <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <span>Studio</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="font-medium text-sm text-gray-600 dark:text-gray-300">Tidak ada paket bank soal yang cocok dengan filter.</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau reset filter.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ================================================================= --}}
        {{-- TAB 2: DAFTAR GURU BELUM BUAT SOAL (ADMIN ONLY)                   --}}
        {{-- ================================================================= --}}
        @if($activeTab === 'guru_belum_buat' && $isAdmin)
            <div class="bg-white dark:bg-[#1a2231] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-theme-xs overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        Daftar Guru yang Belum Memiliki Paket Bank Soal
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Daftar guru aktif yang belum menyusun atau menginput bank soal sama sekali pada sistem CBT.
                    </p>
                </div>

                @php
                    $guruBelumBuatList = $this->guruBelumBuatList;
                @endphp

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nama Guru</th>
                                <th class="py-3 px-4">NIP / Identitas</th>
                                <th class="py-3 px-4">Mata Pelajaran Diampu</th>
                                <th class="py-3 px-4">Kelas Diampu</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($guruBelumBuatList as $gIdx => $guru)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                    <td class="py-3.5 px-4 text-center text-gray-400 font-medium">
                                        {{ $gIdx + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($guru->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p>{{ $guru->name }}</p>
                                                <p class="text-[10px] text-gray-400 font-normal">{{ $guru->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-gray-600 dark:text-gray-300">
                                        {{ $guru->nip ?: '-' }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($guru->mapels && $guru->mapels->isNotEmpty())
                                            <div class="flex items-center gap-1 flex-wrap">
                                                @foreach($guru->mapels as $mp)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                                        {{ $mp->nama_mapel }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">Belum diatur</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($guru->kelases && $guru->kelases->isNotEmpty())
                                            <div class="flex items-center gap-1 flex-wrap">
                                                @foreach($guru->kelases as $kl)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                                        {{ $kl->nama_kelas }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">Belum diatur</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                            0 Paket Soal
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <a
                                            href="{{ \App\Filament\Resources\BankSoals\BankSoalResource::getUrl('create') }}"
                                            class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition shadow-xs"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span>Buat Paket</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="font-bold text-sm text-gray-800 dark:text-white">Luar biasa! Seluruh guru telah membuat bank soal.</p>
                                            <p class="text-xs text-gray-500">Tidak ada guru pengampu yang tertinggal.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
