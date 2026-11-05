<x-filament-panels::page>
    @vite(['resources/css/app.css'])

    <div
        x-data="studioSoalManager()"
        @keydown.window.ctrl.enter.prevent="$wire.simpanSoal(true)"
        class="w-full flex flex-col gap-4 -mt-2"
    >
        {{-- ================================================================= --}}
        {{-- TOP SUBHEADER: Info Paket Bank Soal & Status Total                 --}}
        {{-- ================================================================= --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-primary-100 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-lg shrink-0" style="width: 2.5rem; height: 2.5rem; min-width: 2.5rem;">
                    <svg width="20" height="20" style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white leading-tight">
                            {{ $record->judul }}
                        </h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300">
                            {{ $record->kode_bank }}
                        </span>
                        @if($record->mapel)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                {{ $record->mapel->nama_mapel }}
                            </span>
                        @endif
                        @if($record->tingkat)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                Kelas {{ $record->tingkat }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Total {{ $this->soals->count() }} butir soal &bull; Akumulasi Bobot: <span class="font-bold text-primary-600 dark:text-primary-400">{{ (float) $this->soals->sum('bobot_nilai') }} Poin</span>
                        @if($record->total_bobot > 0)
                            <span class="text-gray-400">/ Target {{ (float) $record->total_bobot }} Poin</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a
                    href="{{ route('exam.run', ['bankSoal' => $record->id, 'preview' => 1]) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 rounded-lg hover:bg-emerald-100 transition shadow-sm"
                    title="Buka pratinjau lembar ujian siswa di tab baru"
                >
                    <svg width="15" height="15" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Pratinjau Siswa ↗</span>
                </a>

                <button
                    type="button"
                    wire:click="openAiModal('current')"

                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-lg text-white shadow-sm transition hover:opacity-95"
                    style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;"
                    title="Buat soal otomatis dengan AI Agent (Google Gemini)"
                >
                    <svg width="15" height="15" class="w-4 h-4 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>Generate Soal AI</span>
                </button>

                <button
                    type="button"
                    wire:click="mountAction('pengaturanPaket')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/60 shadow-sm transition"
                >
                    <svg width="16" height="16" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Pengaturan Paket
                </button>
            </div>

        </div>

        {{-- ================================================================= --}}
        {{-- MAIN 2-PANE STUDIO LAYOUT                                         --}}
        {{-- ================================================================= --}}
        <div class="flex flex-col lg:flex-row gap-5 items-start w-full">

            {{-- ------------------------------------------------------------- --}}
            {{-- PANE KIRI: Sidebar Daftar Butir Soal                          --}}
            {{-- ------------------------------------------------------------- --}}
            <aside class="w-full lg:w-80 xl:w-96 shrink-0 flex flex-col bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden sticky top-4">
                {{-- Header Sidebar: Tombol Tambah Soal & Search --}}
                <div class="p-3 border-b border-gray-200 dark:border-gray-800 space-y-2.5 bg-gray-50/70 dark:bg-gray-900/80">
                    <div class="grid grid-cols-5 gap-1.5">
                        <button
                            type="button"
                            wire:click="tambahSoal"
                            wire:loading.attr="disabled"
                            class="col-span-3 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white shadow-sm transition hover:opacity-95"
                            style="background-color: #2563eb; color: #ffffff;"
                            title="Tambah 1 butir soal manual"
                        >
                            <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>+ Tambah</span>
                        </button>

                        <button
                            type="button"
                            wire:click="openAiModal('batch')"
                            class="col-span-2 inline-flex items-center justify-center gap-1 px-2 py-2 rounded-lg text-xs font-semibold text-white shadow-sm transition hover:opacity-95"
                            style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;"
                            title="Generate beberapa butir soal sekaligus dengan AI Agent"
                        >
                            <svg width="14" height="14" class="w-3.5 h-3.5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>AI Batch</span>
                        </button>
                    </div>


                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg width="16" height="16" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="search"
                            placeholder="Cari teks butir soal..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>
                </div>

                {{-- List Butir Soal Scrollable --}}
                <div class="overflow-y-auto max-h-[calc(100vh-320px)] divide-y divide-gray-100 dark:divide-gray-800/80 p-2 space-y-1.5">
                    @forelse($this->soals as $item)
                        @php
                            $isActive = ($item->id === $selectedSoalId);
                            $cleanText = trim(strip_tags($item->pertanyaan ?? ''));
                            if (empty($cleanText)) {
                                $cleanText = '(Belum ada teks pertanyaan)';
                            }

                            // Kunci jawaban badge logic
                            $kunciInfo = null;
                            if (in_array($item->tipe_soal, ['pilihan_ganda', 'benar_salah'])) {
                                $kunciOpsi = $item->opsiJawabans->firstWhere('is_jawaban_benar', true);
                                if ($kunciOpsi) {
                                    $kunciInfo = ['valid' => true, 'text' => 'Kunci: ' . $kunciOpsi->label];
                                } else {
                                    $kunciInfo = ['valid' => false, 'text' => 'Belum ada kunci'];
                                }
                            } elseif ($item->tipe_soal === 'pg_kompleks') {
                                $kunciCount = $item->opsiJawabans->where('is_jawaban_benar', true)->count();
                                if ($kunciCount > 0) {
                                    $kunciInfo = ['valid' => true, 'text' => $kunciCount . ' Kunci dipilih'];
                                } else {
                                    $kunciInfo = ['valid' => false, 'text' => 'Belum ada kunci'];
                                }
                            } elseif ($item->tipe_soal === 'menjodohkan') {
                                $pasangCount = $item->opsiJawabans->count();
                                $kunciInfo = ['valid' => $pasangCount > 0, 'text' => $pasangCount . ' Pasangan'];
                            } elseif ($item->tipe_soal === 'isian') {
                                if (filled($item->kunci_isian)) {
                                    $kunciInfo = ['valid' => true, 'text' => 'Kunci isian diisi'];
                                } else {
                                    $kunciInfo = ['valid' => false, 'text' => 'Kunci kosong'];
                                }
                            } elseif ($item->tipe_soal === 'essay') {
                                if (filled($item->rubrik_essay)) {
                                    $kunciInfo = ['valid' => true, 'text' => 'Rubrik tersedia'];
                                } else {
                                    $kunciInfo = ['valid' => false, 'text' => 'Draft rubrik'];
                                }
                            }

                            // Tipe Soal Label
                            $tipeLabel = match($item->tipe_soal) {
                                'pilihan_ganda' => 'Pilihan Ganda',
                                'pg_kompleks' => 'PG Kompleks',
                                'benar_salah' => 'Benar / Salah',
                                'menjodohkan' => 'Menjodohkan',
                                'isian' => 'Isian Singkat',
                                'essay' => 'Essay / Uraian',
                                default => 'Pilihan Ganda',
                            };
                        @endphp

                        <div
                            wire:key="soal-nav-{{ $item->id }}"
                            wire:click="selectSoal({{ $item->id }})"
                            @if($isActive) style="border-color: #2563eb; background-color: rgba(239, 246, 255, 0.9);" @endif
                            class="cursor-pointer rounded-lg p-2.5 transition relative text-left {{
                                $isActive
                                    ? 'bg-primary-50/80 dark:bg-primary-950/40 border-2 border-primary-500 shadow-sm'
                                    : 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/60'
                            }}"
                        >
                            {{-- Baris 1: Nomor, Tipe, Bobot --}}
                            <div class="flex items-center justify-between gap-1 mb-1.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-extrabold text-sm {{ $isActive ? 'text-primary-700 dark:text-primary-400' : 'text-gray-800 dark:text-gray-200' }}">
                                        {{ sprintf('%02d.', $item->nomor_urut) }}
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{
                                        $isActive
                                            ? 'bg-primary-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'
                                    }}">
                                        {{ $tipeLabel }}
                                    </span>
                                </div>
                                <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                    {{ (float) $item->bobot_nilai }} Poin
                                </span>
                            </div>

                            {{-- Baris 2: Cuplikan Teks Soal --}}
                            <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2 leading-relaxed mb-2 font-normal">
                                {{ Str::limit($cleanText, 70) }}
                            </p>

                            {{-- Baris 3: Status Kunci & Media --}}
                            <div class="flex items-center justify-between text-[10px]">
                                @if($kunciInfo)
                                    @if($kunciInfo['valid'])
                                        <span class="inline-flex items-center gap-1 font-medium text-emerald-700 dark:text-emerald-400">
                                            <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            {{ $kunciInfo['text'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 font-medium text-amber-600 dark:text-amber-400">
                                            <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            {{ $kunciInfo['text'] }}
                                        </span>
                                    @endif
                                @endif

                                @if($item->media_type && $item->media_type !== 'none')
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 font-semibold uppercase text-[9px]">
                                        {{ $item->media_type }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-gray-500 dark:text-gray-400">
                            Tidak ada butir soal ditemukan.
                        </div>
                    @endforelse
                </div>

                {{-- Footer Sidebar: Quick Actions & Counter --}}
                <div class="p-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/90 flex flex-col gap-2">
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            wire:click="duplikatSoal"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            title="Duplikat butir soal yang sedang aktif"
                        >
                            <svg width="14" height="14" class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Duplikasi</span>
                        </button>

                        <button
                            type="button"
                            wire:click="hapusSoal"
                            wire:confirm="Yakin ingin menghapus Soal No. {{ $nomor_urut }} ini?"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 text-xs font-medium text-danger-600 dark:text-danger-400 bg-white dark:bg-gray-800 border border-danger-200 dark:border-danger-900/60 rounded-lg hover:bg-danger-50 dark:hover:bg-danger-950/40 transition"
                            title="Hapus butir soal ini"
                        >
                            <svg width="14" height="14" class="w-3.5 h-3.5 text-danger-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 px-1 pt-1 border-t border-gray-200/60 dark:border-gray-800">
                        <span>Soal Aktif: No. {{ $nomor_urut }}</span>
                        <span>Total: {{ $this->soals->count() }} Butir</span>
                    </div>
                </div>
            </aside>

            {{-- ------------------------------------------------------------- --}}
            {{-- PANE TENGAH / KANAN: Editor Butir Soal Utama                  --}}
            {{-- ------------------------------------------------------------- --}}
            <main class="flex-1 min-w-0 flex flex-col gap-4 w-full">

                {{-- Header Soal Aktif: Nomor, Tipe Soal, Bobot --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex items-baseline gap-1">
                            <span class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Soal</span>
                            <span class="text-2xl font-black text-gray-900 dark:text-white leading-none">
                                No. {{ $nomor_urut }}
                            </span>
                        </div>

                        {{-- Select Tipe Soal --}}
                        <div class="relative">
                            <select
                                wire:model.live="tipe_soal"
                                class="text-xs font-semibold rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 py-1.5 pl-3 pr-8 shadow-sm focus:outline-none focus:ring-1 focus:ring-primary-500"
                            >
                                <option value="pilihan_ganda">Pilihan Ganda (Single Choice)</option>
                                <option value="pg_kompleks">Pilihan Ganda Kompleks (Multi Choice)</option>
                                <option value="benar_salah">Benar / Salah</option>
                                <option value="menjodohkan">Menjodohkan (Matching)</option>
                                <option value="isian">Isian Singkat</option>
                                <option value="essay">Uraian / Essay</option>
                            </select>
                        </div>
                    </div>

                    {{-- Bobot Nilai --}}
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Bobot Nilai:</label>
                        <div class="flex items-center">
                            <input
                                type="number"
                                step="0.5"
                                min="0.5"
                                wire:model.live.debounce.300ms="bobot_nilai"
                                class="w-20 text-xs font-bold text-center rounded-l-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-1.5 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            />
                            <span class="inline-flex items-center px-2.5 py-1.5 rounded-r-lg border border-l-0 border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-300 font-medium">
                                Poin
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ========================================================= --}}
                {{-- CARD 1: TEKS STIMULUS & POKOK SOAL (STEM)                 --}}
                {{-- ========================================================= --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/60 dark:bg-gray-800/40">
                        <div class="flex items-center gap-2">
                            <svg width="16" height="16" class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                Teks Stimulus & Pokok Soal (Stem) <span class="text-danger-500">*</span>
                            </span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Ketik <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[11px]">$rumus$</code> untuk rumus matematika langsung
                        </span>
                    </div>

                    {{-- WYSIWYG Editor dengan Render Rumus KaTeX Langsung & Teks Arab --}}
                    <div
                        x-data="soalWysiwygEditor({
                            initialContent: @js($pertanyaan),
                            onUpdate: (html) => { $wire.set('pertanyaan', html, false); }
                        })"
                        class="p-4 space-y-3"
                    >
                        {{-- Toolbar --}}
                        <div class="flex flex-wrap items-center gap-1 p-2 rounded-lg bg-gray-50 dark:bg-gray-800/70 border border-gray-200 dark:border-gray-700/80">
                            {{-- Basic Text Formatting --}}
                            <div class="flex items-center gap-0.5 pr-2 border-r border-gray-300 dark:border-gray-700">
                                <button type="button" @click="format('bold')" class="editor-btn" title="Bold (Ctrl+B)">
                                    <span class="font-extrabold text-xs">B</span>
                                </button>
                                <button type="button" @click="format('italic')" class="editor-btn" title="Italic (Ctrl+I)">
                                    <span class="italic text-xs font-serif">I</span>
                                </button>
                                <button type="button" @click="format('underline')" class="editor-btn" title="Underline (Ctrl+U)">
                                    <span class="underline text-xs">U</span>
                                </button>
                                <button type="button" @click="format('strikeThrough')" class="editor-btn" title="Strikethrough">
                                    <span class="line-through text-xs">S</span>
                                </button>
                                <button type="button" @click="format('subscript')" class="editor-btn" title="Subscript (x₂)">
                                    <span class="text-xs">x₂</span>
                                </button>
                                <button type="button" @click="format('superscript')" class="editor-btn" title="Superscript (x²)">
                                    <span class="text-xs">x²</span>
                                </button>
                            </div>

                            {{-- Text Alignment & Arab RTL --}}
                            <div class="flex items-center gap-0.5 px-2 border-r border-gray-300 dark:border-gray-700">
                                <button type="button" @click="format('justifyLeft')" class="editor-btn" title="Rata Kiri">
                                    <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16" />
                                    </svg>
                                </button>
                                <button type="button" @click="format('justifyCenter')" class="editor-btn" title="Rata Tengah">
                                    <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M4 18h16" />
                                    </svg>
                                </button>
                                <button type="button" @click="format('justifyRight')" class="editor-btn" title="Rata Kanan (Cocok untuk Teks Arab)">
                                    <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M4 18h16" />
                                    </svg>
                                </button>
                                <button type="button" @click="format('justifyFull')" class="editor-btn" title="Rata Kiri-Kanan (Justify)">
                                    <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Headings & Lists --}}
                            <div class="flex items-center gap-0.5 px-2 border-r border-gray-300 dark:border-gray-700">
                                <button type="button" @click="formatBlock('h2')" class="editor-btn" title="Heading 2">
                                    <span class="text-xs font-bold">H2</span>
                                </button>
                                <button type="button" @click="formatBlock('h3')" class="editor-btn" title="Heading 3">
                                    <span class="text-xs font-bold">H3</span>
                                </button>
                                <button type="button" @click="format('insertUnorderedList')" class="editor-btn" title="Bullet List">
                                    <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16M2 6h.01M2 12h.01M2 18h.01" />
                                    </svg>
                                </button>
                                <button type="button" @click="format('insertOrderedList')" class="editor-btn" title="Numbered List">
                                    <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h13M7 12h13M7 18h13M3 6h.01M3 12h.01M3 18h.01" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Formula Helper Button --}}
                            <div class="flex items-center gap-1 pl-2">
                                <button
                                    type="button"
                                    @click="toggleMathModal()"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-bold bg-primary-100 hover:bg-primary-200 text-primary-800 dark:bg-primary-950/60 dark:text-primary-300 transition"
                                    title="Sisipkan Rumus Matematika (KaTeX/LaTeX)"
                                >
                                    <span class="text-sm font-serif">∑</span>
                                    <span>Rumus LaTeX</span>
                                </button>

                                <button
                                    type="button"
                                    @click="format('removeFormat')"
                                    class="editor-btn text-gray-500"
                                    title="Hapus Format Teks"
                                >
                                    <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Editable Content Area --}}
                        <div
                            wire:ignore
                            x-ref="editor"
                            contenteditable="true"
                            @input="handleInput()"
                            @blur="syncContent()"
                            class="min-h-[180px] max-h-[500px] overflow-y-auto p-4 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500/50 leading-relaxed font-sans text-sm"
                            placeholder="Tuliskan butir soal di sini..."
                        >{!! $pertanyaan !!}</div>

                        {{-- Modal Dialog Sisipkan Rumus --}}
                        <div
                            x-show="showMathModal"
                            x-cloak
                            @click.away="showMathModal = false"
                            class="p-4 rounded-xl border border-primary-200 dark:border-primary-800 bg-primary-50/50 dark:bg-primary-950/30 space-y-3"
                        >
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                    Sisipkan Rumus Matematika (LaTeX)
                                </h4>
                                <button type="button" @click="showMathModal = false" class="text-gray-400 hover:text-gray-600">
                                    &times;
                                </button>
                            </div>

                            {{-- Tombol Preset Cepat --}}
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="insertPreset('x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100">
                                    Rumus ABC
                                </button>
                                <button type="button" @click="insertPreset('\\frac{a}{b}')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">
                                    Pecahan (a/b)
                                </button>
                                <button type="button" @click="insertPreset('\\sqrt{x}')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">
                                    Akar (√x)
                                </button>
                                <button type="button" @click="insertPreset('x^{2}')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">
                                    Pangkat (x²)
                                </button>
                                <button type="button" @click="insertPreset('x_{1}')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">
                                    Indeks (x₁)
                                </button>
                                <button type="button" @click="appendSymbol('\\pm')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">±</button>
                                <button type="button" @click="appendSymbol('\\times')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">×</button>
                                <button type="button" @click="appendSymbol('\\div')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">÷</button>
                                <button type="button" @click="appendSymbol('\\pi')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">π</button>
                                <button type="button" @click="appendSymbol('\\le')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">≤</button>
                                <button type="button" @click="appendSymbol('\\ge')" class="px-2 py-0.5 text-xs rounded bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 font-mono">≥</button>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">Kode LaTeX:</label>
                                <input
                                    type="text"
                                    x-model="latexInput"
                                    @input="updateMathPreview()"
                                    placeholder="Contoh: \frac{1}{2} atau x^2 + 4 = 0"
                                    class="w-full text-xs font-mono px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                />
                            </div>

                            {{-- Preview Rumus --}}
                            <div class="p-2.5 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-center min-h-[45px] flex items-center justify-center">
                                <div x-ref="mathPreview" class="text-sm"></div>
                            </div>

                            <div class="flex justify-end gap-2">
                                <button type="button" @click="showMathModal = false" class="px-3 py-1 text-xs rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Batal
                                </button>
                                <button type="button" @click="insertFormulaToEditor()" class="px-3 py-1 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-700 shadow-sm">
                                    Sisipkan Rumus ke Teks
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Lampiran Media (Gambar / Audio) --}}
                    <div class="p-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-800/20 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                                <svg width="16" height="16" class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                Lampiran Media Stimulus Soal
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
                            <div>
                                <label class="text-[11px] font-medium text-gray-600 dark:text-gray-400 block mb-1">Jenis Media</label>
                                <select
                                    wire:model.live="media_type"
                                    class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-1.5 px-2.5"
                                >
                                    <option value="none">Tidak Ada</option>
                                    <option value="image">Gambar / Diagram</option>
                                    <option value="audio">Audio (Listening)</option>
                                    <option value="video">Video</option>
                                </select>
                            </div>

                            @if($media_type !== 'none')
                                <div class="sm:col-span-2 space-y-2">
                                    <label class="text-[11px] font-medium text-gray-600 dark:text-gray-400 block mb-1">
                                        Upload File Media ({{ ucfirst($media_type) }})
                                    </label>
                                    <input
                                        type="file"
                                        wire:model="media_file_upload"
                                        class="w-full text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-950 dark:file:text-primary-300"
                                    />
                                    <div wire:loading wire:target="media_file_upload" class="text-xs text-primary-600">
                                        Mengunggah file media...
                                    </div>

                                    @if($existing_media_file)
                                        <div class="flex items-center gap-3 p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                            @if($media_type === 'image')
                                                <img src="{{ Storage::url($existing_media_file) }}" alt="Media Soal" class="max-h-24 max-w-xs rounded object-contain border border-gray-200 dark:border-gray-700" />
                                            @elseif($media_type === 'audio')
                                                <audio controls src="{{ Storage::url($existing_media_file) }}" class="h-8 max-w-xs"></audio>
                                            @endif
                                            <button
                                                type="button"
                                                wire:click="hapusMediaFile"
                                                class="text-xs text-danger-600 hover:text-danger-700 font-medium inline-flex items-center gap-1"
                                            >
                                                <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus Media
                                            </button>
                                        </div>
                                    @endif

                                    @if($media_type === 'audio')
                                        <div class="flex items-center gap-2 pt-1">
                                            <label class="text-[11px] text-gray-500">Maks. Diputar Siswa (0 = tanpa batas):</label>
                                            <input
                                                type="number"
                                                min="0"
                                                max="10"
                                                wire:model.live="audio_max_play"
                                                class="w-16 text-xs text-center rounded border border-gray-300 dark:border-gray-700 py-1"
                                            />
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ========================================================= --}}
                {{-- CARD 2: PILIHAN JAWABAN & KUNCI JAWABAN (DYNAMIC)         --}}
                {{-- ========================================================= --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/60 dark:bg-gray-800/40">
                        <div class="flex items-center gap-2">
                            <svg width="16" height="16" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                @if(in_array($tipe_soal, ['pilihan_ganda', 'benar_salah']))
                                    Pilihan Jawaban (Kunci Tunggal / Single Choice)
                                @elseif($tipe_soal === 'pg_kompleks')
                                    Pilihan Jawaban (Kunci Jamak / Multi Choice)
                                @elseif($tipe_soal === 'menjodohkan')
                                    Pasangan Menjodohkan
                                @elseif($tipe_soal === 'isian')
                                    Kunci Jawaban Isian Singkat
                                @elseif($tipe_soal === 'essay')
                                    Rubrik Penilaian & Pedoman Skor Essay
                                @endif
                            </span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            @if(in_array($tipe_soal, ['pilihan_ganda', 'benar_salah']))
                                Centang checkbox untuk memilih 1 jawaban benar
                            @elseif($tipe_soal === 'pg_kompleks')
                                Centang semua opsi yang merupakan jawaban benar
                            @endif
                        </span>
                    </div>

                    <div class="p-4 space-y-3">
                        {{-- KASUS 1: PILIHAN GANDA & BENAR SALAH & PG KOMPLEKS --}}
                        @if(in_array($tipe_soal, ['pilihan_ganda', 'pg_kompleks', 'benar_salah']))
                            <div class="space-y-2.5">
                                @foreach($opsi_jawabans as $index => $opsi)
                                    @php
                                        $isBenar = !empty($opsi['is_jawaban_benar']);
                                    @endphp
                                    <div
                                        wire:key="opt-row-{{ $selectedSoalId }}-{{ $index }}-{{ md5($opsi['teks_opsi'] ?? '') }}"
                                        @if($isBenar) style="border-color: #10b981; background-color: rgba(236, 253, 245, 0.7);" @endif
                                        class="p-3 rounded-xl border transition flex flex-col gap-2 {{
                                            $isBenar
                                                ? 'border-2 border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/20 shadow-sm'
                                                : 'border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-gray-700'
                                        }}"
                                    >
                                        <div class="flex items-center gap-3">
                                            {{-- Checkbox Kunci Jawaban (Single / Multi Choice) --}}
                                            <div class="shrink-0 flex items-center">
                                                <label class="cursor-pointer flex items-center gap-1.5" title="Klik untuk menjadikan kunci jawaban">
                                                    <input
                                                        type="checkbox"
                                                        wire:click="pilihKunciJawaban({{ $index }})"
                                                        @checked($isBenar)
                                                        style="accent-color: #059669; width: 1.25rem; height: 1.25rem;"
                                                        class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 transition cursor-pointer"
                                                    />
                                                </label>
                                            </div>

                                            {{-- Label Opsi (A, B, C...) --}}
                                            <div
                                                @if($isBenar) style="background-color: #059669; color: #ffffff;" @endif
                                                class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm shrink-0 {{
                                                    $isBenar
                                                        ? 'bg-emerald-600 text-white'
                                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'
                                                }}"
                                            >
                                                {{ $opsi['label'] }}
                                            </div>

                                            {{-- Input Teks Opsi & Render Rumus KaTeX Otomatis --}}
                                            <div
                                                x-data="opsiMathField(@js($opsi['teks_opsi'] ?? ''), {{ $index }})"
                                                class="flex-1 min-w-0"
                                            >
                                                {{-- 1. Mode Render KaTeX: Tampil otomatis jika teks memuat rumus LaTeX ($ atau \) --}}
                                                <div
                                                    x-show="!editing && hasMath()"
                                                    @click="editing = true; $nextTick(() => { $refs.inputEl.focus(); })"
                                                    class="w-full min-h-[38px] rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-1.5 px-3 cursor-text hover:border-primary-500 dark:hover:border-primary-400 transition flex items-center justify-between group shadow-2xs"
                                                    title="Rumus ter-render. Klik untuk mengedit kode LaTeX / teks opsi."
                                                >
                                                    <div x-ref="mathDisplay" class="text-sm font-medium text-gray-900 dark:text-gray-100 overflow-x-auto py-0.5"></div>
                                                    <span class="text-[10px] font-semibold text-primary-600 dark:text-primary-400 opacity-60 group-hover:opacity-100 transition flex items-center gap-1 shrink-0 ml-2 bg-primary-50 dark:bg-primary-950/60 px-1.5 py-0.5 rounded border border-primary-200 dark:border-primary-800">
                                                        <svg width="11" height="11" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                        </svg>
                                                        Edit
                                                    </span>
                                                </div>

                                                {{-- 2. Mode Input Teks: Tampil saat sedang mengedit atau jika opsi berupa teks biasa --}}
                                                <div x-show="editing || !hasMath()">
                                                    <div class="relative flex items-center">
                                                        <input
                                                            x-ref="inputEl"
                                                            type="text"
                                                            x-model="text"
                                                            wire:model.live.debounce.300ms="opsi_jawabans.{{ $index }}.teks_opsi"
                                                            @blur="editing = false; $nextTick(() => renderMath($refs.mathDisplay))"
                                                            @keydown.enter.prevent="editing = false; $nextTick(() => renderMath($refs.mathDisplay))"
                                                            placeholder="Tuliskan teks opsi jawaban (ketik $rumus$ untuk matematika)..."
                                                            class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 py-2 px-3 pr-8 focus:outline-none focus:ring-1 focus:ring-primary-500 font-sans"
                                                        />
                                                        <template x-if="hasMath()">
                                                            <button
                                                                type="button"
                                                                @click="editing = false; $nextTick(() => renderMath($refs.mathDisplay))"
                                                                class="absolute right-2 text-gray-400 hover:text-primary-600 p-1"
                                                                title="Tampilkan Hasil Render Rumus"
                                                            >
                                                                <span class="text-xs font-serif font-bold text-primary-600">∑</span>
                                                            </button>
                                                        </template>
                                                    </div>

                                                    {{-- Live Preview saat sedang mengetik / mengedit rumus --}}
                                                    <div
                                                        x-show="hasMath()"
                                                        class="mt-1.5 px-2.5 py-1 rounded bg-primary-50/70 dark:bg-primary-950/40 border border-primary-200 dark:border-primary-800/60 flex items-center gap-2"
                                                    >
                                                        <span class="text-[10px] font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider shrink-0 flex items-center gap-1">
                                                            <span class="font-serif font-black text-xs">∑</span> Pratinjau Rumus:
                                                        </span>
                                                        <div x-ref="livePreview" class="text-sm font-medium text-gray-900 dark:text-white" x-html="formatMath(text)"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Tombol Hapus Opsi (hanya jika opsi > 2 dan bukan Benar/Salah) --}}
                                            @if($tipe_soal !== 'benar_salah' && count($opsi_jawabans) > 2)
                                                <button
                                                    type="button"
                                                    wire:click="hapusOpsi({{ $index }})"
                                                    class="text-gray-400 hover:text-danger-600 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition shrink-0"
                                                    title="Hapus opsi ini"
                                                >
                                                    <svg width="16" height="16" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>

                                        {{-- Badge Kunci Jawaban Benar --}}
                                        @if($isBenar)
                                            <div class="flex items-center justify-between pl-11 pt-1 text-xs text-emerald-700 dark:text-emerald-400 font-semibold border-t border-emerald-200/60 dark:border-emerald-900/40">
                                                <span class="inline-flex items-center gap-1">
                                                    <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    KUNCI JAWABAN BENAR
                                                </span>
                                                <span class="text-[11px] font-normal text-emerald-800 dark:text-emerald-300">
                                                    Poin Penuh: {{ (float) $bobot_nilai }} Poin
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @if($tipe_soal !== 'benar_salah')
                                <div class="pt-2">
                                    <button
                                        type="button"
                                        wire:click="tambahOpsi"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-950/60 border border-primary-200 dark:border-primary-800 hover:bg-primary-100 transition"
                                    >
                                        <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>+ Tambah Opsi Jawaban ({{ chr(65 + count($opsi_jawabans)) }})</span>
                                    </button>
                                </div>
                            @endif

                        {{-- KASUS 2: MENJODOHKAN --}}
                        @elseif($tipe_soal === 'menjodohkan')
                            <div class="space-y-2">
                                <p class="text-xs text-gray-500 mb-2">Tuliskan premis di kolom kiri dan pasangan jawaban benarnya di kolom kanan:</p>
                                @foreach($opsi_jawabans as $index => $opsi)
                                    <div wire:key="match-{{ $index }}" class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-800">
                                        <span class="w-6 text-center font-bold text-xs text-gray-500">{{ $index + 1 }}.</span>
                                        <input
                                            type="text"
                                            wire:model.live.debounce.300ms="opsi_jawabans.{{ $index }}.teks_opsi"
                                            placeholder="Premis / Pernyataan Kiri..."
                                            class="flex-1 text-xs rounded-lg border border-gray-300 dark:border-gray-700 py-1.5 px-2.5"
                                        />
                                        <span class="text-gray-400 font-bold">&harr;</span>
                                        <input
                                            type="text"
                                            wire:model.live.debounce.300ms="opsi_jawabans.{{ $index }}.pasangan_matching"
                                            placeholder="Pasangan Jawaban Kanan..."
                                            class="flex-1 text-xs rounded-lg border border-gray-300 dark:border-gray-700 py-1.5 px-2.5"
                                        />
                                        <button type="button" wire:click="hapusOpsi({{ $index }})" class="text-gray-400 hover:text-danger-600 p-1">
                                            &times;
                                        </button>
                                    </div>
                                @endforeach
                                <button type="button" wire:click="tambahOpsi" class="text-xs font-semibold text-primary-600 hover:underline pt-1">
                                    + Tambah Pasangan Menjodohkan
                                </button>
                            </div>

                        {{-- KASUS 3: ISIAN SINGKAT --}}
                        @elseif($tipe_soal === 'isian')
                            <div class="space-y-2">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Kunci Jawaban Teks / Angka (Case-Insensitive):
                                </label>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="kunci_isian"
                                    placeholder="Contoh: Fotosintesis atau 25 atau H2O"
                                    class="w-full text-xs font-bold rounded-lg border border-gray-300 dark:border-gray-700 py-2 px-3 focus:ring-1 focus:ring-primary-500"
                                />
                                <p class="text-[11px] text-gray-500">
                                    Sistem akan mencocokkan jawaban siswa secara otomatis mengabaikan huruf besar/kecil.
                                </p>
                            </div>

                        {{-- KASUS 4: ESSAY / URAIAN --}}
                        @elseif($tipe_soal === 'essay')
                            <div class="space-y-2">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Rubrik Penilaian & Pedoman Penskoran Guru:
                                </label>
                                <textarea
                                    wire:model.live.debounce.300ms="rubrik_essay"
                                    rows="4"
                                    placeholder="Tuliskan kata kunci wajib, kriteria penilaian, atau batas toleransi jawaban untuk mempermudah guru saat koreksi manual..."
                                    class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 py-2 px-3 focus:ring-1 focus:ring-primary-500"
                                ></textarea>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ========================================================= --}}
                {{-- CARD 3: PEMBAHASAN SOAL & KUNCI JAWABAN (COLLAPSIBLE)     --}}
                {{-- ========================================================= --}}
                <div
                    x-data="{ open: false }"
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden"
                >
                    <button
                        type="button"
                        @click="open = !open"
                        class="w-full p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/60 dark:bg-gray-800/40 text-left"
                    >
                        <div class="flex items-center gap-2">
                            <svg width="16" height="16" class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <span class="text-sm font-bold text-gray-900 dark:text-white block">
                                    Pembahasan Soal & Kunci Jawaban
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Ditampilkan kepada siswa pada sesi pembahasan pasca-ujian dan untuk arsip kurikulum
                                </span>
                            </div>
                        </div>
                        <svg width="16" height="16" class="w-4 h-4 text-gray-400 transition transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" x-cloak class="p-4 space-y-3">
                        <div class="flex items-center justify-between pb-1 border-b border-gray-100 dark:border-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Tuliskan manual atau generate langkah penyelesaian otomatis:
                            </span>
                            <button
                                type="button"
                                wire:click="generatePembahasanWithAi"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/60 border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition"
                                title="Buatkan teks pembahasan otomatis berdasarkan pokok soal & opsi jawaban saat ini"
                            >
                                <svg width="14" height="14" class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span wire:loading.remove wire:target="generatePembahasanWithAi">🤖 Buat Pembahasan AI</span>
                                <span wire:loading wire:target="generatePembahasanWithAi">Sedang Menghasilkan...</span>
                            </button>
                        </div>

                        <textarea
                            wire:model.live.debounce.300ms="pembahasan"

                            rows="4"
                            placeholder="Tuliskan ulasan materi atau langkah penyelesaian soal ini (ketik $rumus$ jika ada formula matematika)..."
                            class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 py-2 px-3 focus:outline-none focus:ring-1 focus:ring-primary-500"
                        ></textarea>
                    </div>
                </div>

                {{-- ========================================================= --}}
                {{-- STICKY BOTTOM BAR: ACTIONS & SAVE                         --}}
                {{-- ========================================================= --}}
                <div class="sticky bottom-0 z-20 bg-white/95 dark:bg-gray-900/95 backdrop-blur border border-gray-200 dark:border-gray-800 p-3.5 rounded-xl shadow-lg flex flex-wrap items-center justify-between gap-3 mt-2">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="selectSoal({{ $selectedSoalId }})"
                            class="px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                        >
                            Reset Form
                        </button>
                        <button
                            type="button"
                            wire:click="duplikatSoal"
                            class="px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition flex items-center gap-1.5"
                        >
                            <svg width="14" height="14" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Duplikat Soal</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="simpanSoal(false)"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-xs font-semibold text-gray-800 dark:text-gray-100 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-700 rounded-lg transition"
                        >
                            <span wire:loading.remove wire:target="simpanSoal(false)">Simpan Perubahan</span>
                            <span wire:loading wire:target="simpanSoal(false)">Menyimpan...</span>
                        </button>

                        <button
                            type="button"
                            wire:click="simpanSoal(true)"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white shadow-md transition hover:opacity-95 rounded-lg"
                            style="background-color: #2563eb; color: #ffffff;"
                            title="Simpan perubahan dan langsung lanjut ke butir soal berikutnya (Ctrl + Enter)"
                        >
                            <svg width="16" height="16" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span wire:loading.remove wire:target="simpanSoal(true)">
                                Simpan & Lanjut ke Soal Berikutnya
                            </span>
                            <span wire:loading wire:target="simpanSoal(true)">Menyimpan...</span>
                            <kbd class="hidden sm:inline-block ml-1 px-1.5 py-0.5 text-[10px] font-mono bg-primary-700 text-primary-100 rounded border border-primary-500">
                                Ctrl+Enter
                            </kbd>
                        </button>
                    </div>
                </div>

            </main>
        </div>
    </div>

    {{-- ===================================================================== --}}
    {{-- JAVASCRIPT: WYSIWYG EDITOR DENGAN RENDER KATEX LIVE & SINKRONISASI     --}}
    {{-- ===================================================================== --}}
    <script>
        function studioSoalManager() {
            return {
                init() {}
            };
        }

        function opsiMathField(initialText, index) {
            return {
                text: initialText || '',
                editing: false,

                init() {
                    const render = () => {
                        this.renderMath(this.$refs.mathDisplay);
                        if (this.$refs.livePreview) {
                            this.$refs.livePreview.innerHTML = this.formatMath(this.text);
                        }
                    };

                    this.$nextTick(() => {
                        if (window.katex) {
                            render();
                        } else {
                            let count = 0;
                            const timer = setInterval(() => {
                                count++;
                                if (window.katex) {
                                    clearInterval(timer);
                                    render();
                                } else if (count > 30) {
                                    clearInterval(timer);
                                }
                            }, 100);
                        }
                    });

                    this.$watch('text', (val) => {
                        this.renderMath(this.$refs.mathDisplay);
                        if (this.$refs.livePreview) {
                            this.$refs.livePreview.innerHTML = this.formatMath(val);
                        }
                    });

                    // Sync if Livewire updates opsi_jawabans array
                    if (this.$wire) {
                        this.$watch(() => this.$wire.get('opsi_jawabans.' + index + '.teks_opsi'), (val) => {
                            if (val !== undefined && val !== this.text) {
                                this.text = val || '';
                                render();
                            }
                        });
                    }

                    window.addEventListener('soal-loaded', () => {
                        this.$nextTick(() => {
                            if (this.$wire) {
                                const livewireVal = this.$wire.get('opsi_jawabans.' + index + '.teks_opsi');
                                if (livewireVal !== undefined) {
                                    this.text = livewireVal || '';
                                }
                            }
                            render();
                        });
                    });
                },

                hasMath() {
                    if (!this.text) return false;
                    return this.text.includes('$') || this.text.includes('\\');
                },

                renderMath(targetEl) {
                    if (!targetEl) return;
                    const content = this.text ? this.text.trim() : '';
                    if (!content) {
                        targetEl.innerHTML = '<span class="text-gray-400 italic text-xs">Kosong (klik untuk mengisi)...</span>';
                        return;
                    }
                    if (this.hasMath()) {
                        targetEl.innerHTML = this.formatMath(content);
                    } else {
                        targetEl.textContent = content;
                    }
                },

                formatMath(str) {
                    if (!str) return '';
                    if (!window.katex) return str;

                    let textToParse = str;
                    if (textToParse.trim().startsWith('\\') && !textToParse.includes('$')) {
                        textToParse = '$' + textToParse.trim() + '$';
                    }

                    return textToParse.replace(/\$\$([^\$]+?)\$\$/g, (m, latex) => {
                        try {
                            return window.katex.renderToString(latex.trim(), { displayMode: true, throwOnError: false });
                        } catch(e) { return m; }
                    }).replace(/\$([^\$\n]+?)\$/g, (m, latex) => {
                        try {
                            return window.katex.renderToString(latex.trim(), { displayMode: false, throwOnError: false });
                        } catch(e) { return m; }
                    });
                }
            };
        }

        function soalWysiwygEditor(config) {
            return {
                content: config.initialContent || '',
                onUpdate: config.onUpdate,
                showMathModal: false,
                latexInput: '',

                init() {
                    const el = this.$refs.editor;
                    if (!el) return;

                    // Konten awal: utamakan dari config, lalu dari innerHTML yang sudah dirender server
                    const text = this.content || el.innerHTML || '';
                    if (text && text.trim().length > 0) {
                        this.loadContent(text);
                    }

                    // Dengarkan event Livewire saat berganti soal
                    window.addEventListener('soal-loaded', (e) => {
                        let text = null;
                        if (Array.isArray(e.detail) && e.detail.length > 0) {
                            text = e.detail[0]?.pertanyaan ?? (typeof e.detail[0] === 'string' ? e.detail[0] : null);
                        } else if (e.detail && typeof e.detail === 'object') {
                            text = e.detail.pertanyaan ?? null;
                        } else if (typeof e.detail === 'string') {
                            text = e.detail;
                        }

                        if (text !== null && text !== undefined) {
                            this.loadContent(text);
                        }
                    });

                    // Event delegasi klik pada rumus matematika untuk mengedit
                    el.addEventListener('click', (e) => {
                        const mathNode = e.target.closest('.tiptap-inline-math');
                        if (mathNode) {
                            e.preventDefault();
                            e.stopPropagation();
                            const currentLatex = mathNode.getAttribute('data-latex') || '';
                            const newLatex = prompt('Edit Rumus Matematika (LaTeX):', currentLatex);
                            if (newLatex !== null) {
                                mathNode.setAttribute('data-latex', newLatex.trim());
                                if (window.katex) {
                                    try {
                                        window.katex.render(newLatex.trim(), mathNode, { displayMode: false, throwOnError: false });
                                    } catch(err) {
                                        mathNode.textContent = '$' + newLatex + '$';
                                    }
                                }
                                this.syncContent();
                            }
                        }
                    });
                },

                loadContent(text) {
                    const el = this.$refs.editor;
                    if (!el) return;
                    this.content = text || '';
                    el.innerHTML = this.parseFormulasToHtml(this.content);
                    this.renderMathInElement(el);
                },

                handleInput() {
                    const el = this.$refs.editor;
                    if (!el) return;

                    // Deteksi jika user baru saja mengetik tanda $ penutup untuk $rumus$
                    this.autoConvertTypedMath(el);
                    this.syncContent();
                },

                autoConvertTypedMath(el) {
                    const selection = window.getSelection();
                    if (!selection || selection.rangeCount === 0) return;
                    const range = selection.getRangeAt(0);
                    const node = range.startContainer;

                    if (node && node.nodeType === Node.TEXT_NODE) {
                        const text = node.textContent;
                        const match = /\$([^\$\n]+)\$$/.exec(text.substring(0, range.startOffset));
                        if (match) {
                            const latex = match[1].trim();
                            const fullMatch = match[0];
                            const matchStart = range.startOffset - fullMatch.length;

                            // Pecah text node dan gantikan dengan node matematika ter-render
                            const beforeText = text.substring(0, matchStart);
                            const afterText = text.substring(range.startOffset);

                            node.textContent = beforeText;

                            const mathSpan = document.createElement('span');
                            mathSpan.className = 'tiptap-inline-math';
                            mathSpan.setAttribute('contenteditable', 'false');
                            mathSpan.setAttribute('data-latex', latex);
                            mathSpan.title = 'Klik untuk mengedit rumus (LaTeX)';

                            if (window.katex) {
                                try {
                                    window.katex.render(latex, mathSpan, { displayMode: false, throwOnError: false });
                                } catch(e) {
                                    mathSpan.textContent = '$' + latex + '$';
                                }
                            } else {
                                mathSpan.textContent = '$' + latex + '$';
                            }

                            const spaceNode = document.createTextNode('\u00A0' + afterText);

                            const parent = node.parentNode;
                            parent.insertBefore(mathSpan, node.nextSibling);
                            parent.insertBefore(spaceNode, mathSpan.nextSibling);

                            // Pindahkan cursor setelah rumus
                            const newRange = document.createRange();
                            newRange.setStart(spaceNode, 1);
                            newRange.collapse(true);
                            selection.removeAllRanges();
                            selection.addRange(newRange);
                        }
                    }
                },

                parseFormulasToHtml(text) {
                    if (!text) return '<p><br></p>';
                    return text.replace(/\$([^\$\n]+?)\$/g, (match, latex) => {
                        const cleanLatex = latex.trim();
                        let rendered = '$' + cleanLatex + '$';
                        if (window.katex) {
                            try {
                                rendered = window.katex.renderToString(cleanLatex, { displayMode: false, throwOnError: false });
                            } catch (e) {
                                rendered = '$' + cleanLatex + '$';
                            }
                        }
                        return `<span class="tiptap-inline-math" contenteditable="false" data-latex="${cleanLatex}" title="Klik untuk mengedit rumus">${rendered}</span>`;
                    });
                },

                serializeHtmlToFormulas(html) {
                    if (!html) return '';
                    const temp = document.createElement('div');
                    temp.innerHTML = html;

                    // Kembalikan semua span.tiptap-inline-math menjadi $latex$ untuk disimpan ke database
                    temp.querySelectorAll('.tiptap-inline-math').forEach(node => {
                        const latex = node.getAttribute('data-latex') || '';
                        node.replaceWith(document.createTextNode(`$${latex}$`));
                    });

                    return temp.innerHTML;
                },

                renderMathInElement(container) {
                    if (!container) return;
                    const doRender = () => {
                        if (!window.katex) return false;
                        container.querySelectorAll('.tiptap-inline-math').forEach(node => {
                            const latex = node.getAttribute('data-latex');
                            if (latex) {
                                try {
                                    window.katex.render(latex, node, { displayMode: false, throwOnError: false });
                                } catch(e) {}
                            }
                        });
                        return true;
                    };

                    if (!doRender()) {
                        let count = 0;
                        const timer = setInterval(() => {
                            count++;
                            if (doRender() || count > 30) clearInterval(timer);
                        }, 100);
                    }
                },

                syncContent() {
                    const el = this.$refs.editor;
                    if (!el) return;
                    const cleanHtml = this.serializeHtmlToFormulas(el.innerHTML);
                    this.content = cleanHtml;
                    if (typeof this.onUpdate === 'function') {
                        this.onUpdate(cleanHtml);
                    }
                },

                format(cmd, val = null) {
                    document.execCommand(cmd, false, val);
                    this.$refs.editor.focus();
                    this.syncContent();
                },

                formatBlock(tag) {
                    document.execCommand('formatBlock', false, `<${tag}>`);
                    this.$refs.editor.focus();
                    this.syncContent();
                },

                toggleMathModal() {
                    this.showMathModal = !this.showMathModal;
                    this.latexInput = '';
                    if (this.showMathModal) {
                        this.$nextTick(() => {
                            this.updateMathPreview();
                        });
                    }
                },

                insertPreset(latex) {
                    this.latexInput = latex;
                    this.updateMathPreview();
                },

                appendSymbol(sym) {
                    this.latexInput = (this.latexInput + ' ' + sym).trim();
                    this.updateMathPreview();
                },

                updateMathPreview() {
                    const preview = this.$refs.mathPreview;
                    if (!preview) return;
                    const f = (this.latexInput || '').trim();
                    if (!f) {
                        preview.innerHTML = '<span class="text-xs text-gray-400">Pratinjau rumus akan tampil di sini...</span>';
                        return;
                    }
                    if (window.katex) {
                        try {
                            preview.innerHTML = window.katex.renderToString(f, { displayMode: true, throwOnError: false });
                        } catch(e) {
                            preview.textContent = e.message;
                        }
                    } else {
                        preview.textContent = '$' + f + '$';
                    }
                },

                insertFormulaToEditor() {
                    const latex = (this.latexInput || '').trim();
                    if (!latex) return;

                    const el = this.$refs.editor;
                    el.focus();

                    const mathSpan = document.createElement('span');
                    mathSpan.className = 'tiptap-inline-math';
                    mathSpan.setAttribute('contenteditable', 'false');
                    mathSpan.setAttribute('data-latex', latex);
                    mathSpan.title = 'Klik untuk mengedit rumus (LaTeX)';

                    if (window.katex) {
                        try {
                            window.katex.render(latex, mathSpan, { displayMode: false, throwOnError: false });
                        } catch(e) {
                            mathSpan.textContent = '$' + latex + '$';
                        }
                    } else {
                        mathSpan.textContent = '$' + latex + '$';
                    }

                    const spaceNode = document.createTextNode('\u00A0');

                    const selection = window.getSelection();
                    if (selection && selection.rangeCount > 0) {
                        const range = selection.getRangeAt(0);
                        range.deleteContents();
                        range.insertNode(spaceNode);
                        range.insertNode(mathSpan);
                        range.setStartAfter(spaceNode);
                        range.collapse(true);
                        selection.removeAllRanges();
                        selection.addRange(range);
                    } else {
                        el.appendChild(mathSpan);
                        el.appendChild(spaceNode);
                    }

                    this.syncContent();
                    this.showMathModal = false;
                }
            };
        }
    </script>

    <style>
        /* Force SVG dimensions across all browsers */
        svg {
            display: inline-block;
            vertical-align: middle;
            flex-shrink: 0;
            max-width: 100%;
        }
        svg.w-5, svg.h-5, svg[class*="w-5"] {
            width: 20px !important;
            height: 20px !important;
            min-width: 20px !important;
            max-width: 20px !important;
        }
        svg.w-4, svg.h-4, svg[class*="w-4"] {
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            max-width: 16px !important;
        }
        svg.w-3\.5, svg.h-3\.5, svg[class*="w-3.5"] {
            width: 14px !important;
            height: 14px !important;
            min-width: 14px !important;
            max-width: 14px !important;
        }
        .editor-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            color: #4b5563;
            transition: all 0.15s;
        }
        .editor-btn:hover {
            background-color: #e5e7eb;
            color: #111827;
        }
        .dark .editor-btn {
            color: #9ca3af;
        }
        .dark .editor-btn:hover {
            background-color: #374151;
            color: #f9fafb;
        }
        [contenteditable]:empty:before {
            content: attr(placeholder);
            color: #9ca3af;
            cursor: text;
        }
    </style>
    {{-- ===================================================================== --}}
    {{-- MODAL DIALOG: AI AGENT GENERATOR SOAL OTOMATIS                       --}}
    {{-- ===================================================================== --}}
    @if($showAiModal)
        <div
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4"
            x-transition.opacity
            @keydown.escape.window="$wire.closeAiModal()"
        >
            <div
                class="relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-2xl overflow-hidden"
            >
                {{-- Modal Header --}}
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between"
                     style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(147, 51, 234, 0.08) 100%);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
                             style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;">
                            <svg width="22" height="22" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                AI Agent Generator Soal
                                <span class="px-2 py-0.5 text-[10px] uppercase font-extrabold tracking-wider rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">
                                    Google Gemini
                                </span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Buat butir soal ujian berkualitas tinggi, rumus LaTeX & teks Arab secara instan
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeAiModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg"
                    >
                        <svg width="20" height="20" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 space-y-4 max-h-[calc(85vh-140px)] overflow-y-auto">
                    {{-- 1. Mode Generate Toggle --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 uppercase tracking-wider">
                            Pilih Mode Pembuatan:
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer p-3 rounded-xl border transition flex items-start gap-2.5 {{ $ai_mode === 'current' ? 'border-primary-500 bg-primary-50/60 dark:bg-primary-950/30 ring-1 ring-primary-500' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800/40 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="ai_mode" value="current" class="mt-0.5 text-primary-600 focus:ring-primary-500" />
                                <div>
                                    <span class="block text-xs font-bold text-gray-900 dark:text-white">
                                        Isi Soal Ini (No. {{ $nomor_urut }})
                                    </span>
                                    <span class="block text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        Generate 1 butir soal dan langsung masukkan ke form editor yang sedang terbuka.
                                    </span>
                                </div>
                            </label>

                            <label class="cursor-pointer p-3 rounded-xl border transition flex items-start gap-2.5 {{ $ai_mode === 'batch' ? 'border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/30 ring-1 ring-indigo-500' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800/40 hover:bg-gray-50' }}">
                                <input type="radio" wire:model.live="ai_mode" value="batch" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <span class="block text-xs font-bold text-gray-900 dark:text-white">
                                        Batch / Sekaligus Banyak Soal
                                    </span>
                                    <span class="block text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                        Generate beberapa soal sekaligus (1 - 50 butir) dan langsung tambahkan ke list bank soal.
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Pilihan Model AI Gemini --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                Model AI Google Gemini
                            </label>
                            <span class="text-[11px] text-gray-400">
                                Ganti model jika server AI Google sedang sibuk
                            </span>
                        </div>
                        <select
                            wire:model.live="ai_model"
                            class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 px-3 font-medium text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="gemini-2.5-flash">⚡ Gemini 2.5 Flash (Rekomendasi - Cepat, Cerdas &amp; Akurat)</option>
                            <option value="gemini-2.0-flash">🚀 Gemini 2.0 Flash (Alternatif Cepat &amp; Stabil)</option>
                            <option value="gemini-2.5-pro">🧠 Gemini 2.5 Pro (Penalaran Mendalam / Soal HOTS &amp; Rumit)</option>
                            <option value="gemini-2.0-flash-lite">🌱 Gemini 2.0 Flash-Lite (Hemat Kuota &amp; Ringan)</option>
                            <option value="gemini-1.5-flash">🛡️ Gemini 1.5 Flash (Paling Tahan Beban Saat Model Baru Sibuk)</option>
                            <option value="gemini-1.5-pro">💎 Gemini 1.5 Pro (Generasi 1.5 Pro - Akurasi Klasik)</option>
                            <option value="custom">✏️ Model Kustom / Versi Lainnya (Input Manual)...</option>
                        </select>

                        @if($ai_model === 'custom')
                            <div class="mt-2 p-2.5 rounded-lg border border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/20">
                                <label class="block text-[11px] font-bold text-indigo-900 dark:text-indigo-300 mb-1">
                                    Ketik Nama Model Gemini:
                                </label>
                                <input
                                    type="text"
                                    wire:model="ai_custom_model"
                                    placeholder="Contoh: gemini-2.0-flash-exp, gemini-3.1, gemini-3.5, gemini-3.8..."
                                    class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 py-1.5 px-2.5 font-mono text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 block">
                                    💡 Masukkan kode model yang terdaftar di Google AI Studio akun Anda.
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- 2. Topik / Materi Soal --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Topik / Materi / Kompetensi Dasar (KD) <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="ai_topik"
                            placeholder="Contoh: Persamaan Kuadrat & Rumus ABC, Struktur Sel Membran, Tajwid Nun Mati, Past Tense..."
                            class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                        @error('ai_topik')
                            <span class="text-[11px] text-danger-600 dark:text-danger-400 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- 3. Tipe Soal & Tingkat Kesulitan --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Tipe Butir Soal
                            </label>
                            <select
                                wire:model="ai_tipe_soal"
                                class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 px-3 focus:ring-1 focus:ring-primary-500"
                            >
                                <option value="pilihan_ganda">Pilihan Ganda (Single Choice)</option>
                                <option value="pg_kompleks">Pilihan Ganda Kompleks (Multi Choice)</option>
                                <option value="benar_salah">Benar / Salah</option>
                                <option value="menjodohkan">Menjodohkan (Matching)</option>
                                <option value="isian">Isian Singkat</option>
                                <option value="essay">Essay / Uraian</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Tingkat Kognitif / Kesulitan
                            </label>
                            <div class="grid grid-cols-3 gap-1">
                                @foreach(['LOTS' => 'Mudah', 'MOTS' => 'Sedang', 'HOTS' => 'Sulit'] as $k => $label)
                                    <label class="cursor-pointer text-center py-1.5 px-2 rounded-lg border text-xs font-semibold transition {{ $ai_kesulitan === $k ? 'border-primary-500 bg-primary-600 text-white shadow-sm' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50' }}">
                                        <input type="radio" wire:model="ai_kesulitan" value="{{ $k }}" class="sr-only" />
                                        <span>{{ $k }}</span>
                                        <span class="block text-[10px] font-normal opacity-80">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- 4. Jumlah Soal (Hanya jika mode batch) --}}
                    @if($ai_mode === 'batch')
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    Jumlah Soal yang Digenerate (Maksimal 50 Butir):
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <input
                                        type="number"
                                        min="1"
                                        max="50"
                                        wire:model.live.debounce.300ms="ai_jumlah"
                                        class="w-16 text-center text-xs font-bold text-primary-600 dark:text-primary-400 border border-gray-300 dark:border-gray-700 rounded-lg py-1 px-1 bg-white dark:bg-gray-800 focus:ring-1 focus:ring-primary-500"
                                    />
                                    <span class="text-xs font-medium text-gray-500">Butir</span>
                                </div>
                            </div>
                            <input
                                type="range"
                                min="1"
                                max="50"
                                wire:model.live="ai_jumlah"
                                class="w-full accent-primary-600 cursor-pointer"
                            />
                            {{-- Preset Tombol Cepat --}}
                            <div class="flex items-center flex-wrap gap-1.5 mt-2">
                                <span class="text-[11px] text-gray-400 mr-1">Pilihan cepat:</span>
                                @foreach([5, 10, 15, 20, 30, 40, 50] as $preset)
                                    <button
                                        type="button"
                                        wire:click="$set('ai_jumlah', {{ $preset }})"
                                        class="px-2 py-0.5 text-[11px] font-semibold rounded border transition {{ (int)$ai_jumlah === $preset ? 'bg-primary-600 text-white border-primary-600 shadow-xs' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50' }}"
                                    >
                                        {{ $preset }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- 5. Instruksi Khusus (Opsional) --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            Instruksi Tambahan (Opsional)
                        </label>
                        <input
                            type="text"
                            wire:model="ai_instruksi"
                            placeholder="Misal: Sertakan rumus akar & pecahan, gunakan studi kasus kehidupan sehari-hari..."
                            class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 px-3 focus:ring-1 focus:ring-primary-500"
                        />
                    </div>

                    {{-- 6. Konfigurasi API Key Google Gemini --}}
                    <div x-data="{ showKeyConfig: {{ empty(config('ai.gemini.api_key')) ? 'true' : 'false' }} }" class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ filled(config('ai.gemini.api_key')) || filled($ai_api_key) ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse' }}"></span>
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Status API Key Gemini:
                                    @if(filled(config('ai.gemini.api_key')) || filled($ai_api_key))
                                        <span class="text-emerald-600 font-bold">Siap Digunakan</span>
                                    @else
                                        <span class="text-amber-600 font-bold">Belum Diisi</span>
                                    @endif
                                </span>
                            </div>
                            <button type="button" @click="showKeyConfig = !showKeyConfig" class="text-[11px] text-primary-600 dark:text-primary-400 hover:underline">
                                <span x-text="showKeyConfig ? 'Sembunyikan Pengaturan' : 'Ubah / Masukkan Key'"></span>
                            </button>
                        </div>

                        <div x-show="showKeyConfig" x-cloak class="mt-3 space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">
                                Google Gemini API Key:
                            </label>
                            <input
                                type="password"
                                wire:model="ai_api_key"
                                placeholder="Masukkan API Key (AIzaSy...)"
                                class="w-full text-xs font-mono rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 py-1.5 px-3 focus:ring-1 focus:ring-primary-500"
                            />
                            <div class="flex items-center justify-between text-[11px]">
                                <label class="flex items-center gap-1.5 cursor-pointer text-gray-600 dark:text-gray-400">
                                    <input type="checkbox" wire:model="ai_simpan_api_key" class="rounded text-primary-600" />
                                    <span>Simpan ke file .env secara otomatis</span>
                                </label>
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline font-medium">
                                    Dapatkan API Key Gratis &nearr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <button
                        type="button"
                        wire:click="closeAiModal"
                        class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        wire:click="generateWithAi"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-bold text-white rounded-lg shadow-md transition hover:opacity-95"
                        style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff;"
                    >
                        <svg wire:loading.remove wire:target="generateWithAi" width="16" height="16" class="w-4 h-4 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <svg wire:loading wire:target="generateWithAi" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="generateWithAi">
                            {{ $ai_mode === 'current' ? 'Generate & Isi Soal Ini' : 'Generate ' . $ai_jumlah . ' Butir Soal' }}
                        </span>
                        <span wire:loading wire:target="generateWithAi">
                            Sedang Memproses AI Agent...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

