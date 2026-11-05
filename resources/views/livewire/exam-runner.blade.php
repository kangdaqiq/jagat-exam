<div
    x-data="{
        init() {
            // Keyboard shortcuts
            window.addEventListener('keydown', (e) => {
                if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
                const key = e.key.toUpperCase();
                if (['A', 'B', 'C', 'D', 'E'].includes(key)) {
                    $wire.pilihJawabanOpsi(key);
                } else if (e.key === 'ArrowRight') {
                    $wire.nextSoal();
                } else if (e.key === 'ArrowLeft') {
                    $wire.prevSoal();
                } else if (key === 'R') {
                    $wire.toggleRagu();
                }
            });

            // Kunci browser history agar siswa tidak bisa kembali ke halaman login/portal
            history.pushState(null, document.title, location.href);
            window.addEventListener('popstate', function () {
                history.pushState(null, document.title, location.href);
            });

            // Tab/focus violation detection (hanya pemicu jika bukan preview mode)
            @if(!$is_preview)
            window.isLegitNavigation = false;
            window.addEventListener('beforeunload', () => {
                window.isLegitNavigation = true;
            });
            document.addEventListener('visibilitychange', () => {
                if (document.hidden && !window.isLegitNavigation && !@this.isSubmitted) {
                    if (window.AndroidExam && typeof window.AndroidExam.triggerViolation === 'function') {
                        window.AndroidExam.triggerViolation('Siswa beralih aplikasi atau membuka notifikasi');
                    }
                    $wire.triggerViolation();
                }
            });
            @endif
        }
    }"
    style="min-height: 100vh; display: flex; flex-direction: column; background: #f1f5f9; color: #1e293b; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;"
>
    {{-- CSS Khusus untuk Responsive, Mobile Optimization, & Timer --}}
    <style>
        /* Timer Layout */
        .exam-timer {
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            padding: 7px 14px !important;
            border-radius: 12px !important;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
            font-weight: 800 !important;
            font-size: 15px !important;
            letter-spacing: 0.05em !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            box-sizing: border-box !important;
            line-height: 1 !important;
        }
        .timer-normal {
            background: #f8fafc !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #1e293b !important;
        }
        .timer-warning {
            background: #fef2f2 !important;
            border: 1.5px solid #ef4444 !important;
            color: #dc2626 !important;
        }

        /* Topbar & Header */
        .exam-topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 30;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            padding: 10px 20px;
        }

        /* Main Container */
        .exam-main-container {
            flex: 1;
            max-width: 1320px;
            width: 100%;
            margin: 0 auto;
            padding: 16px 20px;
            display: flex;
            gap: 20px;
            align-items: flex-start;
            box-sizing: border-box;
        }

        /* Question Box & Body */
        .exam-question-box {
            flex: 1;
            min-width: 0;
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .exam-question-body {
            padding: 24px 24px 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Bottom Navigation Footer */
        .exam-nav-footer {
            padding: 16px 24px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-sizing: border-box;
        }

        .exam-nav-btn {
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            min-width: 130px !important;
            height: 46px !important;
            box-sizing: border-box !important;
            white-space: nowrap !important;
            cursor: pointer !important;
            text-decoration: none !important;
            line-height: 1 !important;
            transition: all 0.15s ease !important;
        }

        .exam-nav-btn-prev {
            border: 1.5px solid #cbd5e1 !important;
            background: #ffffff !important;
            color: #334155 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
        }
        .exam-nav-btn-prev:disabled {
            border-color: #e2e8f0 !important;
            background: #f8fafc !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
            opacity: 0.6 !important;
        }

        .exam-nav-btn-ragu {
            border: 1.5px solid #fcd34d !important;
            background: #fffbeb !important;
            color: #b45309 !important;
            box-shadow: 0 1px 3px rgba(245,158,11,0.15) !important;
            min-width: 140px !important;
        }
        .exam-nav-btn-ragu.is-active {
            background: #f59e0b !important;
            border-color: #d97706 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(245,158,11,0.35) !important;
        }

        .exam-nav-btn-next {
            border: none !important;
            background: #2563eb !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(37,99,235,0.3) !important;
        }
        .exam-nav-btn-next:hover {
            background: #1d4ed8 !important;
        }

        .exam-nav-btn-finish {
            border: none !important;
            background: #059669 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(5,150,105,0.3) !important;
        }
        .exam-nav-btn-finish:hover {
            background: #047857 !important;
        }

        /* Option Card Touch Optimization */
        .exam-option-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.15s;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        /* Media Queries untuk Desktop (>= 1024px) */
        @media (min-width: 1024px) {
            .exam-sidebar-desktop {
                display: flex !important;
                flex-direction: column !important;
                width: 300px !important;
                flex-shrink: 0 !important;
            }
            .exam-btn-daftar-soal-mobile {
                display: none !important;
            }
        }

        /* Media Queries untuk Layar Tablet / Ponsel (< 1024px) */
        @media (max-width: 1023px) {
            .exam-sidebar-desktop {
                display: none !important;
            }
            .exam-btn-daftar-soal-mobile {
                display: inline-flex !important;
            }
        }

        /* Media Queries Khusus Ponsel (< 640px / Smartphone) */
        @media (max-width: 639px) {
            .exam-topbar {
                padding: 8px 12px !important;
            }
            .exam-topbar-title {
                max-width: 130px !important;
                font-size: 12px !important;
            }
            .exam-topbar-badge {
                width: 32px !important;
                height: 32px !important;
                font-size: 10px !important;
            }
            .exam-timer {
                padding: 6px 10px !important;
                font-size: 13px !important;
                gap: 4px !important;
            }
            .exam-zoom-controls {
                display: none !important; /* sembunyikan zoom di mobile kecil agar header tidak sesak */
            }
            .exam-btn-daftar-soal-mobile {
                padding: 6px 10px !important;
                font-size: 11px !important;
            }
            .exam-btn-daftar-soal-mobile span.btn-label {
                display: none !important; /* tampilkan hanya ikon + badge angka di layar sangat sempit */
            }
            .exam-main-container {
                padding: 10px 10px !important;
            }
            .exam-question-box {
                border-radius: 16px !important;
            }
            .exam-question-header {
                padding: 12px 14px !important;
            }
            .exam-question-body {
                padding: 16px 14px 14px !important;
                gap: 14px !important;
            }
            .exam-option-card {
                padding: 12px 12px !important;
                gap: 10px !important;
                border-radius: 12px !important;
            }
            .exam-option-badge {
                width: 32px !important;
                height: 32px !important;
                font-size: 13px !important;
            }
            .exam-nav-footer {
                padding: 12px 12px !important;
                gap: 8px !important;
            }
            .exam-nav-btn {
                padding: 10px 8px !important;
                font-size: 12px !important;
                min-width: 0 !important;
                flex: 1 !important;
                height: 42px !important;
                gap: 5px !important;
            }
            .exam-mobile-sheet {
                border-radius: 24px 24px 0 0 !important;
                max-height: 85vh !important;
                margin-top: auto !important;
                width: 100% !important;
            }
            .exam-mobile-overlay {
                align-items: flex-end !important;
                padding: 0 !important;
            }
        }
                align-items: flex-end !important;
                padding: 0 !important;
            }
        }
    </style>

{{-- ===================================================================== --}}
{{-- KASUS 1: HALAMAN ENTRANCE / REGISTRASI SISWA                           --}}
{{-- ===================================================================== --}}
@if(!$sesi)
    <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 1.25rem;">
        <div style="width: 100%; max-width: 480px; background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.12); border: 1px solid #e2e8f0; overflow: hidden;">

            {{-- Banner Header --}}
            <div style="padding: 1.75rem 1.5rem; text-align: center; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: #fff; position: relative;">
                <div style="width: 52px; height: 52px; margin: 0 auto 10px; border-radius: 16px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center;">
                    <svg width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="white">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span style="display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; background: rgba(255,255,255,0.2); color: #fff; margin-bottom: 8px;">
                    Portal Ujian CBT Siswa
                </span>
                <h1 style="font-size: 19px; font-weight: 900; line-height: 1.3; margin: 0;">{{ $bankSoal->judul }}</h1>
                <p style="font-size: 12px; color: rgba(219,234,254,0.9); margin-top: 6px;">
                    {{ $bankSoal->mapel?->nama_mapel ?? 'Mata Pelajaran' }} &bull; Kelas {{ $bankSoal->tingkat ?? 'Semua' }}
                </p>
            </div>

            {{-- Info Ujian --}}
            <div style="padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 12px; border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; text-align: center;">
                    <div>
                        <span style="display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 2px;">Jumlah Soal</span>
                        <span style="font-size: 18px; font-weight: 900; color: #1e293b;">{{ $this->soals->count() }} Butir</span>
                    </div>
                    <div style="border-left: 1px solid #e2e8f0;">
                        <span style="display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 2px;">Alokasi Waktu</span>
                        <span style="font-size: 18px; font-weight: 900; color: #2563eb;">{{ $bankSoal->durasi_menit ?? 60 }} Menit</span>
                    </div>
                </div>

                {{-- Form Identitas --}}
                <form wire:submit.prevent="startExam(false)" style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #475569; margin-bottom: 6px;">
                            Nama Lengkap Siswa <span style="color: #ef4444;">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="nama_peserta"
                            placeholder="Masukkan nama lengkap sesuai absen..."
                            autofocus
                            style="width: 100%; box-sizing: border-box; font-size: 14px; font-weight: 600; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 11px 14px; outline: none; color: #1e293b; background: #fff; transition: border-color 0.15s;"
                            onfocus="this.style.borderColor='#3b82f6'"
                            onblur="this.style.borderColor='#cbd5e1'"
                        />
                        @error('nama_peserta')
                            <span style="font-size: 11px; color: #dc2626; margin-top: 4px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #475569; margin-bottom: 6px;">
                            Nomor Peserta / NISN (Opsional)
                        </label>
                        <input
                            type="text"
                            wire:model="nomor_peserta"
                            placeholder="Contoh: 2026-X-01 atau NISN..."
                            style="width: 100%; box-sizing: border-box; font-size: 14px; font-weight: 600; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 11px 14px; outline: none; color: #1e293b; background: #fff;"
                            onfocus="this.style.borderColor='#3b82f6'"
                            onblur="this.style.borderColor='#cbd5e1'"
                        />
                    </div>

                    {{-- Peringatan --}}
                    <div style="padding: 12px 14px; border-radius: 12px; background: #fffbeb; border: 1px solid #fcd34d; display: flex; align-items: flex-start; gap: 10px;">
                        <svg width="18" height="18" style="flex-shrink: 0; margin-top: 1px; color: #d97706;" fill="none" viewBox="0 0 24 24" stroke="#d97706">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div style="font-size: 12px; color: #78350f; line-height: 1.55;">
                            <strong>Integritas Ujian:</strong>
                            Kerjakan mandiri dan jujur. Jangan berpindah tab atau aplikasi agar tidak memicu peringatan sistem.
                        </div>
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        style="width: 100%; padding: 13px; border-radius: 14px; font-size: 14px; font-weight: 700; color: #fff; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none; cursor: pointer; box-shadow: 0 4px 14px rgba(37,99,235,0.35); display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity 0.15s; min-height: 48px;"
                    >
                        <span wire:loading.remove>Mulai Mengerjakan Ujian &rarr;</span>
                        <span wire:loading>Menyiapkan Lembar Soal...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

{{-- ===================================================================== --}}
{{-- KASUS 2: UJIAN SELESAI / SUBMIT                                        --}}
{{-- ===================================================================== --}}
@elseif($is_submitted)
    <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 1.25rem;">
        <div style="width: 100%; max-width: 480px; background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.12); border: 1px solid #e2e8f0; padding: 2rem 1.5rem; text-align: center;">
            <div style="width: 68px; height: 68px; margin: 0 auto 16px; border-radius: 50%; background: #d1fae5; display: flex; align-items: center; justify-content: center;">
                <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="#059669">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <span style="display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; background: #d1fae5; color: #065f46; margin-bottom: 10px;">
                Ujian Berhasil Diserahkan
            </span>
            <h2 style="font-size: 20px; font-weight: 900; color: #1e293b; margin: 0 0 6px;">Terima Kasih, {{ $sesi->nama_peserta }}!</h2>
            <p style="font-size: 12px; color: #64748b; margin: 0 0 18px;">Seluruh jawaban Anda telah tersimpan dengan aman di server CBT.</p>

            <div style="padding: 14px; border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; text-align: left; font-size: 12px; margin-bottom: 18px;">
                <div style="display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #e2e8f0;">
                    <span style="color: #64748b;">Mata Pelajaran:</span>
                    <span style="font-weight: 700; color: #1e293b;">{{ $bankSoal->mapel?->nama_mapel ?? $bankSoal->judul }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #e2e8f0;">
                    <span style="color: #64748b;">Nomor Peserta:</span>
                    <span style="font-weight: 700; color: #1e293b;">{{ $sesi->nomor_peserta ?: '-' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #e2e8f0;">
                    <span style="color: #64748b;">Waktu Selesai:</span>
                    <span style="font-weight: 700; color: #1e293b;">{{ $sesi->submitted_at ? $sesi->submitted_at->format('d/m/Y H:i:s') : '-' }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 7px 0;">
                    <span style="color: #64748b;">Total Terjawab:</span>
                    <span style="font-weight: 700; color: #059669;">{{ $this->recap['dijawab'] }} dari {{ $this->recap['total'] }} Butir</span>
                </div>
            </div>

            <a href="{{ route('siswa.portal') }}" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 22px; border-radius: 12px; font-size: 13px; font-weight: 800; color: #ffffff; background: #2563eb; border: none; text-decoration: none; box-shadow: 0 4px 12px rgba(37,99,235,0.3);">
                Kembali ke Portal Ujian &raquo;
            </a>
        </div>
    </div>

{{-- ===================================================================== --}}
{{-- KASUS 3: RUNNER AKTIF (LEMBAR SOAL CBT)                                --}}
{{-- ===================================================================== --}}
@else
    {{-- ----------------------------------------------------------------- --}}
    {{-- TOPBAR UJIAN (RESPONSIVE)                                          --}}
    {{-- ----------------------------------------------------------------- --}}
    <header class="exam-topbar">
        <div style="max-width: 1320px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 8px;">

            {{-- Kiri: Identitas Ujian & Siswa --}}
            <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
                <div class="exam-topbar-badge" style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); color: #fff; font-weight: 900; font-size: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 6px rgba(37,99,235,0.3);">
                    CBT
                </div>
                <div style="min-width: 0;">
                    <div class="exam-topbar-title" style="font-size: 13px; font-weight: 800; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px;">
                        {{ $bankSoal->judul }}
                    </div>
                    <div style="font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 5px;">
                        <span style="font-weight: 700; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">{{ $sesi->nama_peserta }}</span>
                        @if($is_preview)
                            <span>&bull;</span>
                            <span style="padding: 1px 5px; border-radius: 4px; background: #fef3c7; color: #92400e; font-weight: 700; font-size: 9px;">PREVIEW</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tengah: Countdown Timer Modern (Presisi Wall-clock & Imun dari Livewire Morph) --}}
            <div
                wire:ignore
                x-data="{
                    endTimestamp: {{ $sesi && $sesi->waktu_selesai ? $sesi->waktu_selesai->timestamp : (now()->addHour()->timestamp) }},
                    remainingSeconds: {{ $sesi ? $sesi->sisaDetik() : 3600 }},
                    submitted: false,

                    init() {
                        this.updateTimer();
                        if (window.__examTimerInterval) {
                            clearInterval(window.__examTimerInterval);
                        }
                        window.__examTimerInterval = setInterval(() => {
                            this.updateTimer();
                        }, 1000);
                    },

                    updateTimer() {
                        const nowSec = Math.floor(Date.now() / 1000);
                        this.remainingSeconds = Math.max(0, this.endTimestamp - nowSec);

                        if (this.remainingSeconds <= 0 && !this.submitted) {
                            this.submitted = true;
                            if (window.__examTimerInterval) {
                                clearInterval(window.__examTimerInterval);
                            }
                            $wire.autoSubmitOnTimeout();
                        }
                    },

                    formatTimer() {
                        const h = Math.floor(this.remainingSeconds / 3600);
                        const m = Math.floor((this.remainingSeconds % 3600) / 60);
                        const s = this.remainingSeconds % 60;
                        if (h > 0) {
                            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                        }
                        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    }
                }"
                class="exam-timer"
                :class="remainingSeconds < 300 ? 'timer-warning animate-pulse' : 'timer-normal'"
                title="Sisa Waktu Ujian"
            >
                <svg width="16" height="16" style="flex-shrink: 0;" fill="none" viewBox="0 0 24 24" :stroke="remainingSeconds < 300 ? '#dc2626' : '#475569'">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span x-text="formatTimer()" style="display: inline-block; min-width: 46px; text-align: center; font-variant-numeric: tabular-nums;">00:00</span>
            </div>

            {{-- Kanan: Font Zoom (Desktop/Tablet) & Tombol Buka Daftar Soal (Mobile) --}}
            <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                {{-- Zoom Font Switcher (Sembunyi otomatis di mobile kecil) --}}
                <div class="exam-zoom-controls" style="display: flex; align-items: center; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; padding: 2px; gap: 1px;">
                    <button type="button" wire:click="setZoom('sm')"
                        style="padding: 4px 8px; border-radius: 8px; border: none; font-size: 11px; font-weight: 700; cursor: pointer; background: {{ $zoomLevel === 'sm' ? '#fff' : 'transparent' }}; color: {{ $zoomLevel === 'sm' ? '#2563eb' : '#64748b' }}; box-shadow: {{ $zoomLevel === 'sm' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none' }};">A-</button>
                    <button type="button" wire:click="setZoom('md')"
                        style="padding: 4px 8px; border-radius: 8px; border: none; font-size: 12px; font-weight: 700; cursor: pointer; background: {{ $zoomLevel === 'md' ? '#fff' : 'transparent' }}; color: {{ $zoomLevel === 'md' ? '#2563eb' : '#64748b' }}; box-shadow: {{ $zoomLevel === 'md' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none' }};">A</button>
                    <button type="button" wire:click="setZoom('lg')"
                        style="padding: 4px 8px; border-radius: 8px; border: none; font-size: 13px; font-weight: 700; cursor: pointer; background: {{ $zoomLevel === 'lg' ? '#fff' : 'transparent' }}; color: {{ $zoomLevel === 'lg' ? '#2563eb' : '#64748b' }}; box-shadow: {{ $zoomLevel === 'lg' ? '0 1px 3px rgba(0,0,0,0.1)' : 'none' }};">A+</button>
                </div>

                {{-- Tombol Reload / Segarkan Halaman jika soal macet --}}
                <button
                    type="button"
                    onclick="if (typeof window.isLegitNavigation !== 'undefined') { window.isLegitNavigation = true; } window.location.reload();"
                    style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 10px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s;"
                    onmouseover="this.style.color='#2563eb'; this.style.borderColor='#93c5fd';"
                    onmouseout="this.style.color='#64748b'; this.style.borderColor='#e2e8f0';"
                    title="Segarkan / Muat Ulang Halaman jika soal macet"
                >
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>

                {{-- Tombol Daftar Soal (Khusus Mobile/Tablet) --}}
                <button
                    type="button"
                    wire:click="$toggle('showDaftarSoalMobile')"
                    class="exam-btn-daftar-soal-mobile"
                    style="align-items: center; gap: 5px; padding: 7px 11px; border-radius: 10px; border: 1.5px solid #cbd5e1; background: #fff; color: #1e293b; font-size: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.06);"
                >
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span class="btn-label">Soal</span>
                    <span style="display: inline-flex; align-items: center; padding: 1px 5px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 10px; font-weight: 800;">
                        {{ $this->recap['dijawab'] }}/{{ $this->recap['total'] }}
                    </span>
                </button>
            </div>
        </div>
    </header>

    {{-- ----------------------------------------------------------------- --}}
    {{-- KONTEN UTAMA: Dua Kolom (Area Soal + Sidebar Nomor Soal)           --}}
    {{-- ----------------------------------------------------------------- --}}
    <main class="exam-main-container">

        {{-- ============================================================= --}}
        {{-- KOLOM KIRI: Lembar Soal Aktif                                 --}}
        {{-- ============================================================= --}}
        <section class="exam-question-box" wire:key="soal-box-{{ $soal?->id ?? $currentIndex }}">
            @php
                $soal = $this->currentSoal;
                $ansData = $soal ? ($this->userAnswers[$soal->id] ?? null) : null;
                $selectedOpsi = $ansData['pilihan'] ?? null;
                $isRagu = (bool) ($ansData['is_ragu'] ?? false);
                $fontSizeSoal = $zoomLevel === 'lg' ? '17px' : ($zoomLevel === 'sm' ? '13px' : '15px');
                $fontSizeOpsi = $zoomLevel === 'lg' ? '15px' : ($zoomLevel === 'sm' ? '12px' : '14px');
            @endphp

            @if($soal)
                {{-- Header Soal --}}
                <div class="exam-question-header" style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #f8fafc; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8;">NO.</span>
                        <span style="font-size: 24px; font-weight: 900; color: #1e293b; line-height: 1;">{{ $currentIndex + 1 }}</span>
                        <span style="font-size: 12px; color: #94a3b8;">/ {{ $this->soals->count() }}</span>
                        <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
                            {{ $soal->tipe_soal === 'pilihan_ganda' ? 'background: #dbeafe; color: #1d4ed8;' : '' }}
                            {{ $soal->tipe_soal === 'pg_kompleks' ? 'background: #ede9fe; color: #6d28d9;' : '' }}
                            {{ $soal->tipe_soal === 'benar_salah' ? 'background: #d1fae5; color: #065f46;' : '' }}
                            {{ $soal->tipe_soal === 'menjodohkan' ? 'background: #fef3c7; color: #92400e;' : '' }}
                            {{ in_array($soal->tipe_soal, ['isian','essay']) ? 'background: #f1f5f9; color: #475569;' : '' }}
                        ">
                            {{ str_replace('_', ' ', $soal->tipe_soal) }}
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        @if($isRagu)
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-weight: 700; font-size: 10px;">
                                <span style="width: 5px; height: 5px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                                Ragu
                            </span>
                        @endif
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; white-space: nowrap;">{{ (float) $soal->bobot_nilai }} Poin</span>
                    </div>
                </div>

                {{-- Isi Pokok Soal & Stimulus --}}
                <div class="exam-question-body">
                    {{-- Media Stimulus --}}
                    @if($soal->media_type === 'image' && $soal->media_file)
                        <div wire:ignore wire:key="media-img-{{ $soal->id }}" style="padding: 6px; border-radius: 14px; border: 1px solid #e2e8f0; background: #f8fafc; max-width: 480px; margin: 0 auto; width: 100%; box-sizing: border-box;">
                            <img src="{{ Storage::url($soal->media_file) }}" alt="Gambar Stimulus" style="border-radius: 10px; width: 100%; max-height: 260px; object-fit: contain;" />
                        </div>
                    @elseif($soal->media_type === 'audio' && $soal->media_file)
                        <div wire:ignore wire:key="media-audio-{{ $soal->id }}" style="padding: 12px 14px; border-radius: 14px; border: 1px solid #ddd6fe; background: #f5f3ff; display: flex; align-items: center; gap: 10px; max-width: 400px; width: 100%;">
                            <audio controls src="{{ Storage::url($soal->media_file) }}" style="width: 100%;"></audio>
                        </div>
                    @endif

                    {{-- Teks Soal (KaTeX & Arabic - Diimunkan dari Livewire Morph saat menjawab) --}}
                    <div class="katex-content" wire:ignore wire:key="soal-stem-{{ $soal->id }}" style="font-size: {{ $fontSizeSoal }}; color: #1e293b; line-height: 1.75; font-weight: 400; overflow-wrap: break-word; word-break: break-word;">
                        {!! $soal->pertanyaan !!}
                    </div>

                    {{-- Pemisah --}}
                    <div style="border-top: 1px solid #f1f5f9;"></div>

                    {{-- ------------------------------------------------- --}}
                    {{-- OPSI JAWABAN SESUAI TIPE SOAL                     --}}
                    {{-- ------------------------------------------------- --}}

                    {{-- TIPE 1: Pilihan Ganda & Benar/Salah --}}
                    @if(in_array($soal->tipe_soal, ['pilihan_ganda', 'benar_salah']))
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            @foreach($soal->opsiJawabans as $opsi)
                                @php $isSelected = ($selectedOpsi === $opsi->label); @endphp
                                <div
                                    wire:key="opt-{{ $soal->id }}-{{ $opsi->label }}"
                                    wire:click="pilihJawabanOpsi('{{ $opsi->label }}')"
                                    class="exam-option-card"
                                    style="{{ $isSelected
                                        ? 'border: 2px solid #2563eb; background: #eff6ff; box-shadow: 0 2px 8px rgba(37,99,235,0.12);'
                                        : 'border: 1.5px solid #e2e8f0; background: #fff;' }}"
                                >
                                    {{-- Huruf A, B, C, D --}}
                                    <div class="exam-option-badge" style="
                                        width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
                                        display: flex; align-items: center; justify-content: center;
                                        font-weight: 800; font-size: 14px; transition: all 0.15s;
                                        {{ $isSelected
                                            ? 'background: #2563eb; color: #fff; box-shadow: 0 2px 6px rgba(37,99,235,0.3);'
                                            : 'background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;' }}
                                    ">
                                        {{ $opsi->label }}
                                    </div>

                                    {{-- Teks Opsi (wire:ignore menjaga rumus KaTeX tetap utuh saat opsi diklik) --}}
                                    <div class="katex-content" wire:ignore wire:key="opt-txt-{{ $soal->id }}-{{ $opsi->label }}" style="flex: 1; font-size: {{ $fontSizeOpsi }}; color: #1e293b; line-height: 1.6; overflow-wrap: break-word; word-break: break-word;">
                                        {!! $opsi->teks_opsi !!}
                                    </div>

                                    {{-- Lingkaran Radio / Centang --}}
                                    <div style="
                                        width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
                                        display: flex; align-items: center; justify-content: center;
                                        {{ $isSelected
                                            ? 'background: #2563eb; border: 2px solid #2563eb;'
                                            : 'border: 2px solid #cbd5e1; background: #fff;' }}
                                    ">
                                        @if($isSelected)
                                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="white">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    {{-- TIPE 2: PG Kompleks (Multi Choice) --}}
                    @elseif($soal->tipe_soal === 'pg_kompleks')
                        @php $multiArr = is_array($selectedOpsi) ? $selectedOpsi : []; @endphp
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <p style="font-size: 11px; font-weight: 700; color: #6d28d9; margin: 0;">Pilihlah semua jawaban yang benar (boleh lebih dari satu):</p>
                            @foreach($soal->opsiJawabans as $opsi)
                                @php $isSelected = in_array($opsi->label, $multiArr); @endphp
                                <div
                                    wire:key="opt-multi-{{ $soal->id }}-{{ $opsi->label }}"
                                    wire:click="pilihJawabanMulti('{{ $opsi->label }}')"
                                    class="exam-option-card"
                                    style="{{ $isSelected
                                        ? 'border: 2px solid #7c3aed; background: #f5f3ff; box-shadow: 0 2px 8px rgba(124,58,237,0.1);'
                                        : 'border: 1.5px solid #e2e8f0; background: #fff;' }}"
                                >
                                    <div class="exam-option-badge" style="
                                        width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
                                        display: flex; align-items: center; justify-content: center;
                                        font-weight: 800; font-size: 14px;
                                        {{ $isSelected
                                            ? 'background: #7c3aed; color: #fff;'
                                            : 'background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;' }}
                                    ">
                                        {{ $opsi->label }}
                                    </div>
                                    <div class="katex-content" wire:ignore wire:key="opt-multi-txt-{{ $soal->id }}-{{ $opsi->label }}" style="flex: 1; font-size: {{ $fontSizeOpsi }}; color: #1e293b; line-height: 1.6; overflow-wrap: break-word; word-break: break-word;">
                                        {!! $opsi->teks_opsi !!}
                                    </div>
                                    <div style="
                                        width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0;
                                        display: flex; align-items: center; justify-content: center;
                                        {{ $isSelected
                                            ? 'background: #7c3aed; border: 2px solid #7c3aed;'
                                            : 'border: 2px solid #cbd5e1; background: #fff;' }}
                                    ">
                                        @if($isSelected)
                                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="white">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    {{-- TIPE 3: Menjodohkan --}}
                    @elseif($soal->tipe_soal === 'menjodohkan')
                        @php
                            $matchMap = is_array($selectedOpsi) ? $selectedOpsi : [];
                            $pilihanKananList = $soal->opsiJawabans->pluck('pasangan_matching')->filter()->unique()->values();
                        @endphp
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <p style="font-size: 11px; font-weight: 700; color: #92400e; margin: 0;">Pasangkan pernyataan kiri dengan pilihan yang tepat di kanan:</p>
                            @foreach($soal->opsiJawabans as $index => $opsi)
                                <div wire:key="match-{{ $index }}" style="padding: 12px 14px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; display: flex; flex-direction: column; gap: 8px;">
                                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                                        <span style="width: 22px; text-align: center; font-weight: 700; color: #94a3b8; font-size: 12px; flex-shrink: 0; margin-top: 2px;">{{ $index + 1 }}.</span>
                                        <span class="katex-content" wire:ignore wire:key="match-txt-{{ $soal->id }}-{{ $index }}" style="font-size: {{ $fontSizeOpsi }}; font-weight: 600; color: #1e293b; flex: 1;">{!! $opsi->teks_opsi !!}</span>
                                    </div>
                                    <select
                                        wire:change="simpanMatching({{ $index }}, $event.target.value)"
                                        style="width: 100%; font-size: 13px; font-weight: 600; border-radius: 10px; border: 1.5px solid #cbd5e1; background: #fff; padding: 9px 12px; color: #1e293b; outline: none; min-height: 42px;"
                                    >
                                        <option value="">-- Pilih Pasangan --</option>
                                        @foreach($pilihanKananList as $pk)
                                            <option value="{{ $pk }}" @selected(($matchMap[$index] ?? '') === $pk)>{{ $pk }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>

                    {{-- TIPE 4: Isian Singkat --}}
                    @elseif($soal->tipe_soal === 'isian')
                        <div wire:key="isian-box-{{ $soal->id }}" style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #475569;">Ketik Jawaban Anda:</label>
                            <input
                                wire:key="isian-input-{{ $soal->id }}"
                                x-data="{ val: @js($ansData['teks'] ?? '') }"
                                x-model="val"
                                type="text"
                                @input.debounce.300ms="$wire.simpanJawabanTeks({{ $soal->id }}, $event.target.value)"
                                placeholder="Ketik jawaban singkat / angka di sini..."
                                style="width: 100%; box-sizing: border-box; font-size: {{ $fontSizeSoal }}; font-weight: 700; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 12px 14px; outline: none; color: #1e293b; background: #fff; min-height: 46px;"
                                onfocus="this.style.borderColor='#3b82f6'"
                                onblur="this.style.borderColor='#cbd5e1'"
                            />
                            <p style="font-size: 11px; color: #94a3b8;">Jawaban tersimpan otomatis saat Anda mengetik.</p>
                        </div>

                    {{-- TIPE 5: Essay / Uraian --}}
                    @elseif($soal->tipe_soal === 'essay')
                        <div wire:key="essay-box-{{ $soal->id }}" style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #475569;">Tuliskan Lembar Jawaban Uraian Anda:</label>
                            <textarea
                                wire:key="essay-textarea-{{ $soal->id }}"
                                x-data="{ val: @js($ansData['teks'] ?? '') }"
                                x-model="val"
                                rows="6"
                                @input.debounce.300ms="$wire.simpanJawabanTeks({{ $soal->id }}, $event.target.value)"
                                placeholder="Tuliskan argumen, rumus, atau penjelasan rinci Anda di sini..."
                                style="width: 100%; box-sizing: border-box; font-size: {{ $fontSizeOpsi }}; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 12px 14px; outline: none; color: #1e293b; background: #fff; line-height: 1.65; resize: vertical; font-family: inherit;"
                                onfocus="this.style.borderColor='#3b82f6'"
                                onblur="this.style.borderColor='#cbd5e1'"
                            ></textarea>
                            <p style="font-size: 11px; color: #94a3b8;">Jawaban tersimpan otomatis saat Anda mengetik.</p>
                        </div>
                    @endif
                </div>

                {{-- ----------------------------------------------------------------- --}}
                {{-- FOOTER NAVIGASI (RESPONSIVE & MOBILE-OPTIMIZED)                    --}}
                {{-- ----------------------------------------------------------------- --}}
                <div class="exam-nav-footer">
                    {{-- Tombol Sebelumnya --}}
                    <button
                        type="button"
                        wire:click="prevSoal"
                        @disabled($currentIndex === 0)
                        class="exam-nav-btn exam-nav-btn-prev"
                    >
                        <span>&laquo; Sebelumnya</span>
                    </button>

                    {{-- Tombol Ragu-Ragu --}}
                    <button
                        type="button"
                        wire:click="toggleRagu"
                        class="exam-nav-btn exam-nav-btn-ragu {{ $isRagu ? 'is-active' : '' }}"
                    >
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="{{ $isRagu ? 'white' : '#d97706' }}" style="flex-shrink: 0;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Ragu-Ragu</span>
                    </button>

                    {{-- Tombol Berikutnya / Selesai --}}
                    @if($currentIndex < $this->soals->count() - 1)
                        <button
                            type="button"
                            wire:click="nextSoal"
                            class="exam-nav-btn exam-nav-btn-next"
                        >
                            <span>Berikutnya &raquo;</span>
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="bukaKonfirmasiSelesai"
                            class="exam-nav-btn exam-nav-btn-finish"
                        >
                            <span>Selesai Ujian &#10003;</span>
                        </button>
                    @endif
                </div>
            @else
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem; color: #94a3b8; font-size: 14px;">
                    Tidak ada soal tersedia.
                </div>
            @endif
        </section>

        {{-- ============================================================= --}}
        {{-- KOLOM KANAN: Sidebar Grid Nomor Soal (Desktop Murni CSS)       --}}
        {{-- ============================================================= --}}
        <aside class="exam-sidebar-desktop" style="background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; position: sticky; top: 72px;">
            {{-- Header sidebar --}}
            <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
                <span style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #1e293b;">Daftar Nomor Soal</span>
                <span style="font-size: 12px; font-weight: 800; color: #2563eb; background: #dbeafe; padding: 2px 8px; border-radius: 999px;">
                    {{ $this->recap['dijawab'] }} / {{ $this->recap['total'] }}
                </span>
            </div>

            {{-- Grid nomor --}}
            <div style="padding: 16px; max-height: calc(100vh - 300px); overflow-y: auto;">
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;">
                    @foreach($this->soals as $idx => $s)
                        @php
                            $ans = $this->userAnswers[$s->id] ?? null;
                            $isCurrent = ($idx === $currentIndex);
                            $isRaguItem = (bool) ($ans['is_ragu'] ?? false);
                            $hasAnswer = false;
                            if ($ans) {
                                if (in_array($s->tipe_soal, ['pilihan_ganda', 'benar_salah'])) {
                                    $hasAnswer = !empty($ans['pilihan']);
                                } elseif ($s->tipe_soal === 'pg_kompleks') {
                                    $hasAnswer = is_array($ans['pilihan']) && count($ans['pilihan']) > 0;
                                } elseif ($s->tipe_soal === 'menjodohkan') {
                                    $hasAnswer = is_array($ans['pilihan']) && count($ans['pilihan']) > 0;
                                } else {
                                    $hasAnswer = filled(trim($ans['teks'] ?? ''));
                                }
                            }
                        @endphp
                        <button
                            type="button"
                            wire:key="grid-no-{{ $idx }}"
                            wire:click="selectSoal({{ $idx }})"
                            style="
                                height: 42px; border-radius: 12px; border: none; font-weight: 800; font-size: 13px;
                                display: flex; align-items: center; justify-content: center;
                                cursor: pointer; transition: all 0.15s;
                                {{ $isCurrent ? 'box-shadow: 0 0 0 3px #2563eb, 0 2px 8px rgba(37,99,235,0.3);' : '' }}
                                {{ $isRaguItem
                                    ? 'background: #f59e0b; color: #fff;'
                                    : ($hasAnswer
                                        ? 'background: #059669; color: #fff;'
                                        : 'background: #f1f5f9; color: #475569; border: 1.5px solid #e2e8f0;') }}
                            "
                        >
                            {{ $idx + 1 }}
                        </button>
                    @endforeach
                </div>

                {{-- Legend / Keterangan Status Warna --}}
                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 8px; font-size: 11px; font-weight: 600; color: #475569;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 14px; height: 14px; border-radius: 4px; background: #059669; display: inline-block;"></span>
                            <span>Sudah Dijawab</span>
                        </div>
                        <span style="font-weight: 800; color: #059669;">{{ $this->recap['dijawab'] }}</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 14px; height: 14px; border-radius: 4px; background: #f59e0b; display: inline-block;"></span>
                            <span>Ragu-Ragu</span>
                        </div>
                        <span style="font-weight: 800; color: #f59e0b;">{{ $this->recap['ragu'] }}</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 14px; height: 14px; border-radius: 4px; background: #f1f5f9; border: 1.5px solid #cbd5e1; display: inline-block;"></span>
                            <span>Belum Dijawab</span>
                        </div>
                        <span style="font-weight: 800; color: #64748b;">{{ $this->recap['belum'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Tombol Selesai Sidebar --}}
            <div style="padding: 14px 18px; border-top: 1px solid #f1f5f9; background: #f8fafc;">
                <button
                    type="button"
                    wire:click="bukaKonfirmasiSelesai"
                    style="width: 100%; padding: 12px; border-radius: 12px; border: none; font-size: 13px; font-weight: 800; color: #fff; background: #059669; cursor: pointer; box-shadow: 0 2px 8px rgba(5,150,105,0.3); transition: opacity 0.15s; min-height: 44px;"
                    onmouseover="this.style.opacity='0.9'"
                    onmouseout="this.style.opacity='1'"
                >
                    Selesai &amp; Kumpulkan &#10003;
                </button>
            </div>
        </aside>
    </main>

    {{-- ----------------------------------------------------------------- --}}
    {{-- DRAWER MODAL MOBILE: Grid Nomor Soal (Smooth Touch Sheet)          --}}
    {{-- ----------------------------------------------------------------- --}}
    @if($showDaftarSoalMobile)
        <div class="exam-mobile-overlay" style="position: fixed; inset: 0; z-index: 50; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 16px;">
            <div class="exam-mobile-sheet" style="background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); padding: 20px 18px; max-height: 90vh; overflow-y: auto; max-width: 440px;">
                {{-- Mobile Pull Handle --}}
                <div style="width: 40px; height: 4px; background: #e2e8f0; border-radius: 99px; margin: 0 auto 12px;"></div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 14px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <h3 style="font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #1e293b; margin: 0;">Daftar Nomor Soal</h3>
                        <span style="font-size: 11px; font-weight: 800; color: #2563eb; background: #dbeafe; padding: 2px 7px; border-radius: 999px;">
                            {{ $this->recap['dijawab'] }} / {{ $this->recap['total'] }}
                        </span>
                    </div>
                    <button type="button" wire:click="$set('showDaftarSoalMobile', false)" style="background: none; border: none; font-size: 24px; font-weight: 700; color: #94a3b8; cursor: pointer; line-height: 1; padding: 4px;">&times;</button>
                </div>

                {{-- Grid Nomor --}}
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 16px;">
                    @foreach($this->soals as $idx => $s)
                        @php
                            $ans = $this->userAnswers[$s->id] ?? null;
                            $isCurrent = ($idx === $currentIndex);
                            $isRaguItem = (bool) ($ans['is_ragu'] ?? false);
                            $hasAnswer = false;
                            if ($ans) {
                                if (in_array($s->tipe_soal, ['pilihan_ganda', 'benar_salah'])) {
                                    $hasAnswer = !empty($ans['pilihan']);
                                } elseif (in_array($s->tipe_soal, ['pg_kompleks', 'menjodohkan'])) {
                                    $hasAnswer = is_array($ans['pilihan']) && count($ans['pilihan']) > 0;
                                } else {
                                    $hasAnswer = filled(trim($ans['teks'] ?? ''));
                                }
                            }
                        @endphp
                        <button
                            type="button"
                            wire:key="grid-m-{{ $idx }}"
                            wire:click="selectSoal({{ $idx }}); $set('showDaftarSoalMobile', false);"
                            style="
                                height: 44px; border-radius: 12px; border: none; font-weight: 800; font-size: 13px;
                                display: flex; align-items: center; justify-content: center; cursor: pointer;
                                {{ $isCurrent ? 'box-shadow: 0 0 0 3px #2563eb, 0 2px 8px rgba(37,99,235,0.3);' : '' }}
                                {{ $isRaguItem
                                    ? 'background: #f59e0b; color: #fff;'
                                    : ($hasAnswer
                                        ? 'background: #059669; color: #fff;'
                                        : 'background: #f1f5f9; color: #475569; border: 1.5px solid #e2e8f0;') }}
                            "
                        >
                            {{ $idx + 1 }}
                        </button>
                    @endforeach
                </div>

                {{-- Legend Warna --}}
                <div style="padding: 10px 12px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; justify-content: space-around; gap: 6px; font-size: 10px; font-weight: 700; color: #475569; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="width: 10px; height: 10px; border-radius: 3px; background: #059669; display: inline-block;"></span>
                        <span>Dijawab ({{ $this->recap['dijawab'] }})</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="width: 10px; height: 10px; border-radius: 3px; background: #f59e0b; display: inline-block;"></span>
                        <span>Ragu ({{ $this->recap['ragu'] }})</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="width: 10px; height: 10px; border-radius: 3px; background: #f1f5f9; border: 1px solid #cbd5e1; display: inline-block;"></span>
                        <span>Kosong ({{ $this->recap['belum'] }})</span>
                    </div>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button
                        type="button"
                        wire:click="$set('showDaftarSoalMobile', false)"
                        style="flex: 1; padding: 11px; border-radius: 12px; border: 1.5px solid #cbd5e1; font-size: 12px; font-weight: 700; color: #475569; background: #fff; cursor: pointer; min-height: 44px;"
                    >
                        Tutup
                    </button>
                    <button
                        type="button"
                        wire:click="bukaKonfirmasiSelesai"
                        style="flex: 1.5; padding: 11px; border-radius: 12px; border: none; font-size: 12px; font-weight: 800; color: #fff; background: #059669; cursor: pointer; box-shadow: 0 2px 10px rgba(5,150,105,0.3); min-height: 44px;"
                    >
                        Selesai &amp; Kumpulkan &#10003;
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ----------------------------------------------------------------- --}}
    {{-- MODAL KONFIRMASI SELESAI UJIAN                                     --}}
    {{-- ----------------------------------------------------------------- --}}
    @if($showConfirmSubmitModal)
        <div style="position: fixed; inset: 0; z-index: 50; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 16px;">
            <div style="width: 100%; max-width: 420px; background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); padding: 24px 20px; text-align: center;">
                <div style="width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 50%; background: #d1fae5; display: flex; align-items: center; justify-content: center;">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#059669">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h3 style="font-size: 17px; font-weight: 900; color: #1e293b; margin: 0 0 6px;">Konfirmasi Selesai Ujian</h3>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 16px;">Periksa kembali ringkasan jawaban Anda sebelum menyerahkan lembar ujian.</p>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; padding: 12px; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 14px;">
                    <div style="padding: 8px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0;">
                        <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Dijawab</span>
                        <span style="font-size: 18px; font-weight: 900; color: #059669;">{{ $this->recap['dijawab'] }}</span>
                    </div>
                    <div style="padding: 8px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0;">
                        <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Ragu</span>
                        <span style="font-size: 18px; font-weight: 900; color: #f59e0b;">{{ $this->recap['ragu'] }}</span>
                    </div>
                    <div style="padding: 8px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0;">
                        <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Kosong</span>
                        <span style="font-size: 18px; font-weight: 900; color: #ef4444;">{{ $this->recap['belum'] }}</span>
                    </div>
                </div>

                @if($this->recap['belum'] > 0 || $this->recap['ragu'] > 0)
                    <div style="padding: 10px 12px; border-radius: 12px; background: #fffbeb; border: 1px solid #fcd34d; font-size: 11px; color: #78350f; text-align: left; margin-bottom: 14px;">
                        <strong>Perhatian:</strong> Masih ada <strong>{{ $this->recap['belum'] }} soal kosong</strong> dan <strong>{{ $this->recap['ragu'] }} soal ragu</strong>.
                    </div>
                @endif

                <div style="display: flex; gap: 8px;">
                    <button
                        type="button"
                        wire:click="tutupKonfirmasiSelesai"
                        style="flex: 1; padding: 11px; border-radius: 12px; border: 1.5px solid #cbd5e1; font-size: 12px; font-weight: 700; color: #475569; background: #fff; cursor: pointer; min-height: 44px;"
                    >
                        Cek Kembali
                    </button>
                    <button
                        type="button"
                        wire:click="submitUjianFinal"
                        style="flex: 1.2; padding: 11px; border-radius: 12px; border: none; font-size: 12px; font-weight: 800; color: #fff; background: #059669; cursor: pointer; box-shadow: 0 2px 8px rgba(5,150,105,0.3); min-height: 44px;"
                    >
                        Kirim Jawaban &#10003;
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ----------------------------------------------------------------- --}}
    {{-- MODAL PERINGATAN KECURANGAN                                        --}}
    {{-- ----------------------------------------------------------------- --}}
    @if($showViolationModal)
        <div style="position: fixed; inset: 0; z-index: 50; background: rgba(127,29,29,0.85); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; padding: 16px;">
            <div style="width: 100%; max-width: 420px; background: #fff; border-radius: 24px; border: 2px solid #ef4444; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 24px 20px; text-align: center;">
                <div style="width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; animation: bounce 1s infinite;">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#dc2626">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <span style="display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; background: #fee2e2; color: #991b1b; margin-bottom: 8px;">
                    Peringatan Pelanggaran #{{ $violationCount }}
                </span>
                <h3 style="font-size: 17px; font-weight: 900; color: #1e293b; margin: 0 0 8px;">Aktivitas Mencurigakan Terdeteksi!</h3>
                <p style="font-size: 12px; color: #dc2626; font-weight: 600; margin: 0 0 16px; line-height: 1.55;">
                    Anda terdeteksi meninggalkan halaman ujian atau membuka aplikasi lain. Insiden ini dicatat dalam log pengawas ujian.
                </p>

                <button
                    type="button"
                    wire:click="tutupViolationModal"
                    style="width: 100%; padding: 12px; border-radius: 12px; border: none; font-size: 13px; font-weight: 700; color: #fff; background: #dc2626; cursor: pointer; box-shadow: 0 2px 10px rgba(220,38,38,0.3); min-height: 44px;"
                >
                    Saya Mengerti &amp; Kembali ke Ujian
                </button>
            </div>
        </div>
    @endif
@endif
</div>
