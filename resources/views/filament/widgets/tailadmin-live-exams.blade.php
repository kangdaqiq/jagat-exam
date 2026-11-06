@php
    $role = $this->getUserRole();
    $prefix = $role === 'admin' ? '/admin' : '/guru';
    $ujians = $this->getRecentUjians();
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-theme-xs dark:border-gray-800 dark:bg-[#1a2231]">
    {{-- Card Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-gray-100 dark:border-gray-800">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Live Monitor
                </span>
                <span class="text-xs text-gray-400">&bull;</span>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Sistem Ujian CBT Realtime
                </span>
            </div>
            <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white" style="font-family: 'Outfit', sans-serif;">
                Monitoring Sesi Pelaksanaan Ujian
            </h3>
        </div>

        <a
            href="{{ $prefix }}/ujians"
            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#465fff] hover:text-[#3641f5] dark:text-[#7592ff] transition self-start sm:self-center"
        >
            Lihat Seluruh Jadwal Ujian
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>

    {{-- Table Content --}}
    <div class="overflow-x-auto mt-4">
        <table class="w-full text-left text-xs">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    <th class="pb-3 pr-4">Exam ID</th>
                    <th class="pb-3 px-4">Nama Agenda Ujian</th>
                    <th class="pb-3 px-4">Mapel & Kelas</th>
                    <th class="pb-3 px-4">Waktu Pelaksanaan</th>
                    <th class="pb-3 px-4 text-center">Token Akses Siswa</th>
                    <th class="pb-3 px-4 text-center">Status</th>
                    <th class="pb-3 pl-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 font-medium">
                @forelse($ujians as $ujian)
                    @php
                        $isOngoing = $ujian->status === 'ONGOING';
                        $isCompleted = $ujian->status === 'COMPLETED';
                        $tokenActive = $ujian->isTokenActive();
                    @endphp
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-[#1f2a3d] transition">
                        {{-- Exam ID --}}
                        <td class="py-3.5 pr-4">
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                {{ $ujian->exam_id }}
                            </span>
                        </td>

                        {{-- Nama Ujian --}}
                        <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-white max-w-[220px] truncate">
                            {{ $ujian->nama_ujian }}
                            <div class="text-[11px] font-normal text-gray-400">
                                {{ $ujian->getTipeLabel() }}
                            </div>
                        </td>

                        {{-- Mapel & Kelas --}}
                        <td class="py-3.5 px-4">
                            <div class="font-semibold text-gray-800 dark:text-gray-200">
                                {{ $ujian->mapel?->nama_mapel ?: '-' }}
                            </div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                Kelas: {{ $ujian->kelas->pluck('nama_kelas')->join(', ') ?: '-' }}
                            </div>
                        </td>

                        {{-- Jadwal & Jam --}}
                        <td class="py-3.5 px-4 text-gray-600 dark:text-gray-400">
                            <div>{{ $ujian->tanggal ? $ujian->tanggal->format('d M Y') : 'Fleksibel' }}</div>
                            <div class="text-[11px] text-gray-400 font-mono">
                                {{ $ujian->waktu_mulai ? substr($ujian->waktu_mulai, 0, 5) : '-' }}
                                @if($ujian->durasi_menit)
                                    ({{ $ujian->durasi_menit }}m)
                                @endif
                            </div>
                        </td>

                        {{-- Token Ujian (10 Menit Expire) --}}
                        <td class="py-3.5 px-4 text-center">
                            @if($isOngoing && $ujian->token_ujian)
                                <div class="inline-flex flex-col items-center">
                                    <span class="font-mono font-black text-sm px-3 py-1 rounded-lg tracking-widest {{
                                        $tokenActive
                                            ? 'bg-[#ecf3ff] text-[#465fff] dark:bg-[#465fff]/20 dark:text-[#7592ff] border border-[#465fff]/30'
                                            : 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200'
                                    }}">
                                        {{ $ujian->token_ujian }}
                                    </span>
                                    <span class="text-[10px] mt-0.5 font-bold {{ $tokenActive ? 'text-emerald-600' : 'text-rose-500' }}">
                                        {{ $tokenActive ? 'Aktif (10 Menit)' : 'Kedaluwarsa' }}
                                    </span>
                                </div>
                            @elseif($isCompleted)
                                <span class="text-gray-400 text-xs italic">Sesi Ditutup</span>
                            @else
                                <span class="text-gray-400 text-xs italic">Belum Dibuka</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="py-3.5 px-4 text-center">
                            @if($isOngoing)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/50">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Sedang Berjalan
                                </span>
                            @elseif($isCompleted)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200/50">
                                    Selesai
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    Dijadwalkan
                                </span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="py-3.5 pl-4 text-right">
                            @if($isCompleted)
                                <a
                                    href="{{ $prefix }}/hasil-ujians/{{ $ujian->id }}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 transition"
                                >
                                    Rekap Nilai
                                </a>
                            @else
                                <a
                                    href="{{ $prefix }}/ujians/{{ $ujian->id }}/edit"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-[#ecf3ff] text-[#465fff] hover:bg-[#465fff] hover:text-white dark:bg-[#465fff]/15 dark:text-[#7592ff] dark:hover:bg-[#465fff] dark:hover:text-white transition duration-150"
                                >
                                    {{ $isOngoing ? 'Kelola Sesi' : 'Detail' }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-gray-400 italic">
                            Belum ada jadwal atau sesi ujian. Klik tombol <strong>"Buat Jadwal Ujian"</strong> di atas untuk membuat ujian baru.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
