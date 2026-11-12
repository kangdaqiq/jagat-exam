@php
    $soalIds = $this->soals->pluck('id')->values()->toArray();
    $soalTypes = $this->soals->pluck('tipe_soal', 'id')->toArray();
@endphp

<div
    wire:key="exam-runner-root-{{ $sesi ? $sesi->token_sesi : ($bankSoal->kode_bank ?? 'preview') }}"
    x-data="examRunner($wire, {
        initialIndex: {{ $currentIndex }},
        totalSoals: {{ $this->soals->count() }},
        soalIds: @js($soalIds),
        soalTypes: @js($soalTypes),
        initialAnswers: @js($userAnswers),
        tokenSesi: '{{ $sesi ? $sesi->token_sesi : ($bankSoal->kode_bank ?? "preview") }}',
        isPreview: {{ $is_preview ? 'true' : 'false' }},
        initialViolationCount: {{ (int) $violationCount }},
        initialShowViolationModal: {{ $showViolationModal ? 'true' : 'false' }}
    })"
    style="min-height: 100vh; display: flex; flex-direction: column; background: #f1f5f9; color: #1e293b; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;"
>
    {{-- CSS Khusus untuk Responsive, Mobile Optimization, & Timer --}}
    <style>
        [x-cloak] { display: none !important; }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

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
        .exam-nav-btn-finish:hover:not(.is-disabled) {
            background: #047857 !important;
        }
        .exam-nav-btn-finish.is-disabled {
            background: #94a3b8 !important;
            opacity: 0.6 !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
        }

        /* Tombol Selesai & Tutup Custom */
        .exam-btn-finish-custom {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 12px !important;
            border: none !important;
            font-size: 13px !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            background: #059669 !important;
            box-shadow: 0 2px 8px rgba(5,150,105,0.3) !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            box-sizing: border-box !important;
            min-height: 44px !important;
            padding: 11px 16px !important;
        }
        .exam-btn-finish-custom:hover:not(.is-disabled) {
            background: #047857 !important;
        }
        .exam-btn-finish-custom.is-disabled {
            background: #94a3b8 !important;
            opacity: 0.6 !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
        }

        .exam-btn-sheet-close {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 12px !important;
            border: 1.5px solid #cbd5e1 !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            color: #475569 !important;
            background: #ffffff !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
            box-sizing: border-box !important;
            min-height: 44px !important;
            padding: 11px 16px !important;
        }
        .exam-btn-sheet-close:hover {
            background: #f8fafc !important;
            border-color: #94a3b8 !important;
        }

        /* Zoom Control Buttons */
        .exam-zoom-btn {
            padding: 4px 8px !important;
            border-radius: 8px !important;
            border: none !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            background: transparent;
            color: #64748b;
            transition: all 0.15s;
        }
        .exam-zoom-btn.is-active {
            background: #ffffff !important;
            color: #2563eb !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
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
            border: 1.5px solid #e2e8f0;
            background: #fff;
            box-sizing: border-box;
        }
        .exam-option-card:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }
        .exam-option-card.is-selected {
            border: 2px solid #2563eb !important;
            background: #eff6ff !important;
            box-shadow: 0 2px 8px rgba(37,99,235,0.12) !important;
        }
        .exam-option-card.is-selected-multi {
            border: 2px solid #7c3aed !important;
            background: #f5f3ff !important;
            box-shadow: 0 2px 8px rgba(124,58,237,0.1) !important;
        }

        .exam-option-badge {
            width: 34px !important;
            height: 34px !important;
            border-radius: 10px !important;
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: 800 !important;
            font-size: 14px !important;
            transition: all 0.15s !important;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            box-sizing: border-box !important;
        }
        .exam-option-badge.is-selected {
            background: #2563eb !important;
            color: #fff !important;
            box-shadow: 0 2px 6px rgba(37,99,235,0.3) !important;
            border: none !important;
        }
        .exam-option-badge.is-selected-multi {
            background: #7c3aed !important;
            color: #fff !important;
            box-shadow: 0 2px 6px rgba(124,58,237,0.3) !important;
            border: none !important;
        }

        .exam-radio-indicator {
            width: 20px !important;
            height: 20px !important;
            border-radius: 50% !important;
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: 2px solid #cbd5e1;
            background: #fff;
            box-sizing: border-box !important;
            transition: all 0.15s;
        }
        .exam-radio-indicator.is-selected {
            background: #2563eb !important;
            border: 2px solid #2563eb !important;
        }

        .exam-checkbox-indicator {
            width: 20px !important;
            height: 20px !important;
            border-radius: 6px !important;
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: 2px solid #cbd5e1;
            background: #fff;
            box-sizing: border-box !important;
            transition: all 0.15s;
        }
        .exam-checkbox-indicator.is-selected {
            background: #7c3aed !important;
            border: 2px solid #7c3aed !important;
        }

        /* Question Grid Number Buttons */
        .exam-grid-btn {
            height: 42px !important;
            border-radius: 12px !important;
            font-weight: 800 !important;
            font-size: 13px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            transition: all 0.15s !important;
            user-select: none !important;
            box-sizing: border-box !important;
            padding: 0 !important;
        }
        .exam-grid-btn.is-active {
            box-shadow: 0 0 0 3px #2563eb, 0 2px 8px rgba(37,99,235,0.3) !important;
        }
        .exam-grid-btn.is-ragu {
            background: #f59e0b !important;
            color: #ffffff !important;
            border: none !important;
        }
        .exam-grid-btn.is-answered {
            background: #059669 !important;
            color: #ffffff !important;
            border: none !important;
        }
        .exam-grid-btn.is-unanswered {
            background: #f1f5f9 !important;
            color: #475569 !important;
            border: 1.5px solid #e2e8f0 !important;
        }
        .exam-grid-btn:hover {
            opacity: 0.9;
        }

        /* Mobile Drawer / Overlay */
        .exam-mobile-overlay {
            position: fixed !important;
            inset: 0 !important;
            z-index: 50 !important;
            background: rgba(15,23,42,0.65) !important;
            backdrop-filter: blur(4px) !important;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding: 0;
        }
        .exam-mobile-overlay[style*="display: none"],
        .exam-confirm-modal-overlay[style*="display: none"],
        [x-cloak] {
            display: none !important;
        }
        .exam-confirm-modal-overlay {
            position: fixed !important;
            inset: 0 !important;
            z-index: 50 !important;
            background: rgba(15,23,42,0.65) !important;
            backdrop-filter: blur(4px) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .exam-mobile-sheet {
            background: #fff !important;
            border-radius: 24px 24px 0 0 !important;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.2) !important;
            padding: 20px 18px 24px !important;
            max-height: 85vh !important;
            overflow-y: auto !important;
            max-width: 480px !important;
            width: 100% !important;
            box-sizing: border-box !important;
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
                max-width: 110px !important;
                font-size: 12px !important;
            }
            .exam-topbar-badge {
                width: 32px !important;
                height: 32px !important;
                font-size: 10px !important;
            }
            .exam-timer {
                padding: 6px 9px !important;
                font-size: 13px !important;
                gap: 4px !important;
            }
            .exam-zoom-controls {
                display: none !important;
            }
            .status-label {
                display: none !important;
            }
            .exam-btn-daftar-soal-mobile {
                padding: 6px 10px !important;
                font-size: 11px !important;
            }
            .exam-btn-daftar-soal-mobile span.btn-label {
                display: none !important;
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
    @php
        $nilaiAkhir = $sesi && $sesi->skor_total !== null ? round((float) $sesi->skor_total, 2) : 0;
        $hasEssay = $this->soals->contains(fn ($s) => in_array($s->tipe_soal, ['essay', 'isian']));
        $nextSubtest = $this->nextSubtestInfo;
    @endphp
    <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 1.25rem;">
        <div style="width: 100%; max-width: 520px; background: #fff; border-radius: 24px; box-shadow: 0 20px 60px rgba(4,31,69,0.12); border: 1px solid #e2e8f0; padding: 2rem 1.5rem; text-align: center;">
            <div style="width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 50%; background: #d1fae5; display: flex; align-items: center; justify-content: center;">
                <svg width="34" height="34" fill="none" viewBox="0 0 24 24" stroke="#059669">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <span style="display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; background: #d1fae5; color: #065f46; margin-bottom: 8px;">
                {{ $nextSubtest ? 'Subtest Disimpan & Dikoreksi' : 'Ujian Berhasil Diserahkan' }}
            </span>
            <h2 style="font-size: 20px; font-weight: 900; color: #041F45; margin: 0 0 4px;">Terima Kasih, {{ $sesi->nama_peserta }}!</h2>
            <p style="font-size: 12px; color: #64748b; margin: 0 0 16px;">Jawaban untuk <strong>{{ $bankSoal->mapel?->nama_mapel ?? $bankSoal->judul }}</strong> telah tersimpan dan dikoreksi otomatis.</p>

            {{-- KARTU NILAI AKHIR SISWA --}}
            <div style="padding: 18px 16px; border-radius: 20px; background: linear-gradient(135deg, #041F45 0%, #072d61 55%, #065f46 100%); color: #ffffff; margin-bottom: 16px; box-shadow: 0 10px 24px -6px rgba(4,31,69,0.28);">
                <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #a7f3d0; margin-bottom: 4px;">
                    Nilai Hasil Ujian Anda
                </div>
                <div style="font-size: 42px; font-weight: 900; line-height: 1.1; letter-spacing: -0.02em; color: #ffffff; font-family: 'Outfit', sans-serif;">
                    {{ $nilaiAkhir }}
                </div>
                <div style="font-size: 11px; color: rgba(226,232,240,0.85); margin-top: 4px; font-weight: 600;">
                    Skala 0 – 100
                    @if($hasEssay)
                        &bull; <span style="color: #fde68a;">Nilai sementara (menunggu koreksi soal esai oleh Guru)</span>
                    @endif
                </div>
            </div>

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

            @if($nextSubtest)
                @php $nextUjian = $nextSubtest['ujian']; @endphp
                {{-- LAMAN KONFIRMASI MULAI SUBTEST BERIKUTNYA (TANPA TOKEN ULANG) --}}
                <div style="padding: 16px; border-radius: 18px; background: #eff6ff; border: 2px solid #93c5fd; text-align: left; margin-bottom: 14px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                        <span style="font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; padding: 3px 9px; border-radius: 999px; background: #1d4ed8; color: #ffffff;">
                            Konfirmasi Subtest {{ $nextSubtest['urutan_ke'] }} / {{ $nextSubtest['total_subtest'] }}
                        </span>
                        <span style="font-size: 11px; font-weight: 700; color: #059669;">
                            Tanpa Token Ulang
                        </span>
                    </div>
                    <h3 style="font-size: 15px; font-weight: 900; color: #041F45; margin: 0 0 4px;">
                        {{ $nextUjian->nama_ujian }}
                    </h3>
                    <div style="font-size: 12px; color: #334155; font-weight: 600; margin-bottom: 10px;">
                        {{ $nextUjian->mapel?->nama_mapel ?: 'Mata Pelajaran' }} &bull; {{ $nextSubtest['soal_count'] }} Butir Soal &bull; {{ $nextUjian->durasi_menit ?: 60 }} Menit
                    </div>
                    <p style="font-size: 11px; color: #475569; margin: 0 0 12px; line-height: 1.5;">
                        Pastikan Anda sudah siap mengerjakan soal berikutnya. Hitung mundur waktu pengerjaan akan dimulai saat Anda menekan tombol di bawah.
                    </p>
                    <button
                        type="button"
                        wire:click="mulaiSubtestBerikutnya"
                        wire:loading.attr="disabled"
                        style="display: inline-flex; width: 100%; box-sizing: border-box; align-items: center; justify-content: center; padding: 13px 20px; border-radius: 14px; font-size: 13px; font-weight: 800; color: #ffffff; background: linear-gradient(135deg, #041F45 0%, #059669 100%); border: none; cursor: pointer; box-shadow: 0 6px 18px rgba(4,31,69,0.28);"
                    >
                        <span wire:loading.remove wire:target="mulaiSubtestBerikutnya">Konfirmasi &amp; Mulai Kerjakan Subtest {{ $nextSubtest['urutan_ke'] }} &raquo;</span>
                        <span wire:loading wire:target="mulaiSubtestBerikutnya">Menyiapkan Soal Berikutnya...</span>
                    </button>
                </div>

                <a href="{{ route('siswa.portal') }}" style="display: inline-block; font-size: 12px; font-weight: 700; color: #64748b; text-decoration: underline;">
                    Kembali ke Portal Ujian
                </a>
            @else
                <a href="{{ route('siswa.portal') }}" style="display: inline-flex; width: 100%; box-sizing: border-box; align-items: center; justify-content: center; padding: 12px 22px; border-radius: 14px; font-size: 13px; font-weight: 800; color: #ffffff; background: linear-gradient(135deg, #041F45 0%, #059669 100%); border: none; text-decoration: none; box-shadow: 0 4px 14px rgba(4,31,69,0.25);">
                    Kembali ke Portal Ujian &raquo;
                </a>
            @endif
        </div>
    </div>

{{-- ===================================================================== --}}
{{-- KASUS 3: RUNNER AKTIF (LEMBAR SOAL CBT BERBASIS CLIENT-SIDE)          --}}
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
                    <div class="exam-topbar-title" style="font-size: 13px; font-weight: 800; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 280px;">
                        {{ $bankSoal->judul }}
                    </div>
                    <div style="font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 5px;">
                        <span style="font-weight: 700; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px;">{{ $sesi->nama_peserta }}</span>
                        @if($is_preview)
                            <span>&bull;</span>
                            <span style="padding: 1px 5px; border-radius: 4px; background: #fef3c7; color: #92400e; font-weight: 700; font-size: 9px;">PREVIEW</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tengah: Status Autosave/Offline & Countdown Timer Modern --}}
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                {{-- Status Jaringan & Autosave --}}
                <div style="display: flex; align-items: center;">
                    {{-- Status Hijau (Online & Tersinkron) --}}
                    <div
                        x-show="isOnline && !isSyncing && pendingSync.length === 0"
                        style="padding: 5px 10px; border-radius: 999px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; font-size: 11px; font-weight: 700; white-space: nowrap;"
                        title="Semua jawaban tersinkron ke server"
                    >
                        <span style="display: inline-flex; flex-direction: row; align-items: center; gap: 5px; line-height: 1;">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block; flex-shrink: 0;"></span>
                            <span class="status-label">Tersambung</span>
                        </span>
                    </div>

                    {{-- Status Biru (Sedang Menyinkronkan ke Server) --}}
                    <div
                        x-show="isSyncing"
                        x-cloak
                        style="padding: 5px 10px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 700; white-space: nowrap;"
                    >
                        <span style="display: inline-flex; flex-direction: row; align-items: center; gap: 6px; line-height: 1;">
                            <svg style="animation: spin 1s linear infinite; width: 12px; height: 12px; flex-shrink: 0; display: inline-block;" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="9" stroke="#93c5fd" stroke-width="3"></circle>
                                <path d="M12 3a9 9 0 0 1 9 9" stroke="#2563eb" stroke-width="3" stroke-linecap="round"></path>
                            </svg>
                            <span class="status-label">Menyimpan...</span>
                        </span>
                    </div>

                    {{-- Status Kuning/Oranye (Offline / Antrean Tersimpan di Browser) --}}
                    <div
                        x-show="!isSyncing && (!isOnline || pendingSync.length > 0)"
                        x-cloak
                        style="padding: 5px 10px; border-radius: 999px; background: #fffbeb; border: 1.5px solid #fde68a; color: #b45309; font-size: 11px; font-weight: 700; white-space: nowrap;"
                        :title="'Ada ' + pendingSync.length + ' jawaban tersimpan aman di perangkat (offline)'"
                    >
                        <span style="display: inline-flex; flex-direction: row; align-items: center; gap: 5px; line-height: 1;">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #f59e0b; display: inline-block; flex-shrink: 0;"></span>
                            <span class="status-label">Offline (<span x-text="pendingSync.length"></span>)</span>
                        </span>
                    </div>
                </div>

                {{-- Countdown Timer Modern (Presisi Wall-clock & Imun dari Livewire Morph serta Timezone Skew) --}}
                <div
                    wire:ignore
                    x-data="{
                        serverRemaining: {{ $sesi ? $sesi->sisaDetik() : 3600 }},
                        clientStartMs: 0,
                        remainingSeconds: {{ $sesi ? $sesi->sisaDetik() : 3600 }},
                        submitted: false,
                        initTime: 0,

                        init() {
                            this.clientStartMs = Date.now();
                            this.initTime = Date.now();
                            this.remainingSeconds = this.serverRemaining;

                            if (window.__examTimerInterval) {
                                clearInterval(window.__examTimerInterval);
                            }
                            window.__examTimerInterval = setInterval(() => {
                                this.updateTimer();
                            }, 1000);
                            window.addEventListener('cbt-update-end-timestamp', (e) => {
                                if (e.detail && typeof e.detail === 'number') {
                                    // Hitung ulang serverRemaining dari timestamp server baru
                                    const nowUnix = Math.floor(Date.now() / 1000);
                                    this.serverRemaining = Math.max(0, e.detail - nowUnix);
                                    this.clientStartMs = Date.now();
                                    this.updateTimer();
                                }
                            });
                        },

                        updateTimer() {
                            // Hitung selisih waktu berjalan (elapsed) dari client clock
                            // Ini membuat timer 100% imun terhadap jam iPhone yang tidak akurat/berbeda zona waktu
                            const elapsedSec = Math.floor((Date.now() - this.clientStartMs) / 1000);
                            this.remainingSeconds = Math.max(0, this.serverRemaining - elapsedSec);
                            window.dispatchEvent(new CustomEvent('cbt-timer-state', { detail: this.remainingSeconds }));

                            // Guard: Jangan auto-submit di 3 detik pertama load halaman untuk mencegah race condition/glitch
                            const uptimeMs = Date.now() - this.initTime;
                            if (this.remainingSeconds <= 0 && !this.submitted && uptimeMs > 3000) {
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
            </div>

            {{-- Kanan: Font Zoom (Desktop/Tablet) & Tombol Buka Daftar Soal (Mobile) --}}
            <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                {{-- Zoom Font Switcher (Client-side, 0ms, Sembunyi otomatis di mobile kecil) --}}
                <div class="exam-zoom-controls" style="display: flex; align-items: center; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; padding: 2px; gap: 1px;">
                    <button type="button" @click="zoomLevel = 'sm'"
                        class="exam-zoom-btn" :class="zoomLevel === 'sm' ? 'is-active' : ''"
                        style="font-size: 11px;">A-</button>
                    <button type="button" @click="zoomLevel = 'md'"
                        class="exam-zoom-btn" :class="zoomLevel === 'md' ? 'is-active' : ''"
                        style="font-size: 12px;">A</button>
                    <button type="button" @click="zoomLevel = 'lg'"
                        class="exam-zoom-btn" :class="zoomLevel === 'lg' ? 'is-active' : ''"
                        style="font-size: 13px;">A+</button>
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
                    @click="showDaftarSoalMobile = !showDaftarSoalMobile"
                    class="exam-btn-daftar-soal-mobile"
                    style="align-items: center; gap: 5px; padding: 7px 11px; border-radius: 10px; border: 1.5px solid #cbd5e1; background: #fff; color: #1e293b; font-size: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.06);"
                >
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span class="btn-label">Soal</span>
                    <span style="display: inline-flex; align-items: center; padding: 1px 5px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 10px; font-weight: 800;">
                        <span x-text="answeredCount"></span>/{{ $this->soals->count() }}
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
        {{-- KOLOM KIRI: Lembar Soal (Semua Soal Pre-Rendered di DOM)      --}}
        {{-- ============================================================= --}}
        <section class="exam-question-box">
            @forelse($this->soals as $idx => $soal)
                <div
                    x-show="currentIndex === {{ $idx }}"
                    x-cloak
                    wire:key="soal-container-{{ $soal->id }}"
                    style="display: flex; flex-direction: column; width: 100%;"
                >
                    {{-- Header Soal --}}
                    <div class="exam-question-header" style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #f8fafc; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8;">NO.</span>
                            <span style="font-size: 24px; font-weight: 900; color: #1e293b; line-height: 1;">{{ $idx + 1 }}</span>
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
                            <span
                                x-show="isRagu({{ $soal->id }})"
                                x-cloak
                                style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-weight: 700; font-size: 10px;"
                            >
                                <span style="width: 5px; height: 5px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                                Ragu
                            </span>
                            <span style="font-size: 11px; font-weight: 700; color: #64748b; white-space: nowrap;">{{ (float) $soal->bobot_nilai }} Poin</span>
                        </div>
                    </div>

                    {{-- Isi Pokok Soal & Stimulus --}}
                    <div class="exam-question-body">
                        {{-- Media Stimulus --}}
                        @if($soal->media_type === 'image' && $soal->media_file)
                            <div style="padding: 6px; border-radius: 14px; border: 1px solid #e2e8f0; background: #f8fafc; max-width: 480px; margin: 0 auto; width: 100%; box-sizing: border-box;">
                                <img src="{{ Storage::url($soal->media_file) }}" alt="Gambar Stimulus" loading="lazy" style="border-radius: 10px; width: 100%; max-height: 260px; object-fit: contain;" />
                            </div>
                        @elseif($soal->media_type === 'audio' && $soal->media_file)
                            <div style="padding: 12px 14px; border-radius: 14px; border: 1px solid #ddd6fe; background: #f5f3ff; display: flex; align-items: center; gap: 10px; max-width: 400px; width: 100%;">
                                <audio controls preload="none" src="{{ Storage::url($soal->media_file) }}" style="width: 100%;"></audio>
                            </div>
                        @elseif($soal->media_type === 'video' && $soal->media_file)
                            <div style="padding: 6px; border-radius: 14px; border: 1px solid #e2e8f0; background: #000000; max-width: 520px; margin: 0 auto; width: 100%; box-sizing: border-box;">
                                <video controls preload="metadata" playsinline src="{{ Storage::url($soal->media_file) }}" style="border-radius: 10px; width: 100%; max-height: 320px; display: block; margin: 0 auto;"></video>
                            </div>
                        @endif

                        {{-- Teks Pertanyaan (KaTeX & Arabic) --}}
                        <div
                            class="katex-content"
                            :style="{ fontSize: zoomLevel === 'lg' ? '17px' : (zoomLevel === 'sm' ? '13px' : '15px') }"
                            style="color: #1e293b; line-height: 1.75; font-weight: 400; overflow-wrap: break-word; word-break: break-word;"
                        >
                            {!! $soal->pertanyaan !!}
                        </div>

                        {{-- Pemisah --}}
                        <div style="border-top: 1px solid #f1f5f9;"></div>

                        {{-- ------------------------------------------------- --}}
                        {{-- OPSI JAWABAN SESUAI TIPE SOAL (REAKTIF 0MS)       --}}
                        {{-- ------------------------------------------------- --}}

                        {{-- TIPE 1: Pilihan Ganda & Benar/Salah --}}
                        @if(in_array($soal->tipe_soal, ['pilihan_ganda', 'benar_salah']))
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                @foreach($soal->opsiJawabans as $opsi)
                                    @php $optLabel = $opsi->display_label ?? $opsi->label; @endphp
                                    <div
                                        @click="pilihOpsi({{ $soal->id }}, '{{ $optLabel }}')"
                                        class="exam-option-card"
                                        :class="answers[{{ $soal->id }}]?.pilihan === '{{ $optLabel }}' ? 'is-selected' : ''"
                                    >
                                        {{-- Huruf A, B, C, D --}}
                                        <div
                                            class="exam-option-badge"
                                            :class="answers[{{ $soal->id }}]?.pilihan === '{{ $optLabel }}' ? 'is-selected' : ''"
                                        >
                                            {{ $optLabel }}
                                        </div>

                                        {{-- Teks Opsi & Gambar --}}
                                        <div
                                            class="katex-content"
                                            :style="{ fontSize: zoomLevel === 'lg' ? '15px' : (zoomLevel === 'sm' ? '12px' : '14px') }"
                                            style="flex: 1; color: #1e293b; line-height: 1.6; overflow-wrap: break-word; word-break: break-word;"
                                        >
                                            @if($opsi->gambar_opsi)
                                                <div style="margin-bottom: 6px;">
                                                    <img src="{{ Storage::url($opsi->gambar_opsi) }}" loading="lazy" alt="Gambar Opsi {{ $optLabel }}" style="max-height: 120px; max-width: 100%; object-fit: contain; border-radius: 6px;" />
                                                </div>
                                            @endif
                                            {!! $opsi->teks_opsi !!}
                                        </div>

                                        {{-- Lingkaran Radio Bulat --}}
                                        <div
                                            class="exam-radio-indicator"
                                            :class="answers[{{ $soal->id }}]?.pilihan === '{{ $optLabel }}' ? 'is-selected' : ''"
                                        >
                                            <template x-if="answers[{{ $soal->id }}]?.pilihan === '{{ $optLabel }}'">
                                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </template>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        {{-- TIPE 2: PG Kompleks (Multi Choice) --}}
                        @elseif($soal->tipe_soal === 'pg_kompleks')
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <p style="font-size: 11px; font-weight: 700; color: #6d28d9; margin: 0;">Pilihlah semua jawaban yang benar (boleh lebih dari satu):</p>
                                @foreach($soal->opsiJawabans as $opsi)
                                    @php $optLabel = $opsi->display_label ?? $opsi->label; @endphp
                                    <div
                                        @click="pilihMulti({{ $soal->id }}, '{{ $optLabel }}')"
                                        class="exam-option-card"
                                        :class="(Array.isArray(answers[{{ $soal->id }}]?.pilihan) && answers[{{ $soal->id }}].pilihan.includes('{{ $optLabel }}')) ? 'is-selected-multi' : ''"
                                    >
                                        <div
                                            class="exam-option-badge"
                                            :class="(Array.isArray(answers[{{ $soal->id }}]?.pilihan) && answers[{{ $soal->id }}].pilihan.includes('{{ $optLabel }}')) ? 'is-selected-multi' : ''"
                                        >
                                            {{ $optLabel }}
                                        </div>
                                        <div
                                            class="katex-content"
                                            :style="{ fontSize: zoomLevel === 'lg' ? '15px' : (zoomLevel === 'sm' ? '12px' : '14px') }"
                                            style="flex: 1; color: #1e293b; line-height: 1.6; overflow-wrap: break-word; word-break: break-word;"
                                        >
                                            @if($opsi->gambar_opsi)
                                                <div style="margin-bottom: 6px;">
                                                    <img src="{{ Storage::url($opsi->gambar_opsi) }}" loading="lazy" alt="Gambar Opsi {{ $optLabel }}" style="max-height: 120px; max-width: 100%; object-fit: contain; border-radius: 6px;" />
                                                </div>
                                            @endif
                                            {!! $opsi->teks_opsi !!}
                                        </div>
                                        <div
                                            class="exam-checkbox-indicator"
                                            :class="(Array.isArray(answers[{{ $soal->id }}]?.pilihan) && answers[{{ $soal->id }}].pilihan.includes('{{ $optLabel }}')) ? 'is-selected-multi' : ''"
                                        >
                                            <template x-if="Array.isArray(answers[{{ $soal->id }}]?.pilihan) && answers[{{ $soal->id }}].pilihan.includes('{{ $optLabel }}')">
                                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="white">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </template>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        {{-- TIPE 3: Menjodohkan --}}
                        @elseif($soal->tipe_soal === 'menjodohkan')
                            @php
                                $pilihanKananList = $soal->opsiJawabans->pluck('pasangan_matching')->filter()->unique()->values();
                            @endphp
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <p style="font-size: 11px; font-weight: 700; color: #92400e; margin: 0;">Pasangkan pernyataan kiri dengan pilihan yang tepat di kanan:</p>
                                @foreach($soal->opsiJawabans as $mIdx => $opsi)
                                    <div style="padding: 12px 14px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: #f8fafc; display: flex; flex-direction: column; gap: 8px;">
                                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                                            <span style="width: 22px; text-align: center; font-weight: 700; color: #94a3b8; font-size: 12px; flex-shrink: 0; margin-top: 2px;">{{ $mIdx + 1 }}.</span>
                                            <span class="katex-content" :style="{ fontSize: zoomLevel === 'lg' ? '15px' : (zoomLevel === 'sm' ? '12px' : '14px') }" style="font-weight: 600; color: #1e293b; flex: 1;">{!! $opsi->teks_opsi !!}</span>
                                        </div>
                                        <select
                                            @change="simpanMatching({{ $soal->id }}, {{ $mIdx }}, $event.target.value)"
                                            :value="(answers[{{ $soal->id }}]?.pilihan || {})[{{ $mIdx }}] || ''"
                                            style="width: 100%; font-size: 13px; font-weight: 600; border-radius: 10px; border: 1.5px solid #cbd5e1; background: #fff; padding: 9px 12px; color: #1e293b; outline: none; min-height: 42px;"
                                        >
                                            <option value="">-- Pilih Pasangan --</option>
                                            @foreach($pilihanKananList as $pk)
                                                <option value="{{ $pk }}">{{ $pk }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>

                        {{-- TIPE 4: Isian Singkat --}}
                        @elseif($soal->tipe_soal === 'isian')
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #475569;">Ketik Jawaban Anda:</label>
                                <input
                                    :value="answers[{{ $soal->id }}]?.teks || ''"
                                    type="text"
                                    @input.debounce.400ms="simpanTeks({{ $soal->id }}, $event.target.value)"
                                    placeholder="Ketik jawaban singkat / angka di sini..."
                                    :style="{ fontSize: zoomLevel === 'lg' ? '17px' : (zoomLevel === 'sm' ? '13px' : '15px') }"
                                    style="width: 100%; box-sizing: border-box; font-weight: 700; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 12px 14px; outline: none; color: #1e293b; background: #fff; min-height: 46px;"
                                    onfocus="this.style.borderColor='#3b82f6'"
                                    onblur="this.style.borderColor='#cbd5e1'"
                                />
                                <p style="font-size: 11px; color: #94a3b8;">Jawaban tersimpan otomatis secara real-time saat Anda mengetik.</p>
                            </div>

                        {{-- TIPE 5: Essay / Uraian --}}
                        @elseif($soal->tipe_soal === 'essay')
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #475569;">Tuliskan Lembar Jawaban Uraian Anda:</label>
                                <textarea
                                    :value="answers[{{ $soal->id }}]?.teks || ''"
                                    rows="6"
                                    @input.debounce.400ms="simpanTeks({{ $soal->id }}, $event.target.value)"
                                    placeholder="Tuliskan argumen, rumus, atau penjelasan rinci Anda di sini..."
                                    :style="{ fontSize: zoomLevel === 'lg' ? '15px' : (zoomLevel === 'sm' ? '12px' : '14px') }"
                                    style="width: 100%; box-sizing: border-box; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 12px 14px; outline: none; color: #1e293b; background: #fff; line-height: 1.65; resize: vertical; font-family: inherit;"
                                    onfocus="this.style.borderColor='#3b82f6'"
                                    onblur="this.style.borderColor='#cbd5e1'"
                                ></textarea>
                                <p style="font-size: 11px; color: #94a3b8;">Jawaban tersimpan otomatis secara real-time saat Anda mengetik.</p>
                            </div>
                        @endif
                    </div>

                    {{-- ----------------------------------------------------------------- --}}
                    {{-- FOOTER NAVIGASI (RESPONSIVE & CLIENT-SIDE INSTANT)                --}}
                    {{-- ----------------------------------------------------------------- --}}
                    <div class="exam-nav-footer">
                        {{-- Tombol Sebelumnya --}}
                        <button
                            type="button"
                            @click="prevSoal()"
                            :disabled="currentIndex === 0"
                            class="exam-nav-btn exam-nav-btn-prev"
                        >
                            <span>&laquo; Sebelumnya</span>
                        </button>

                        {{-- Tombol Ragu-Ragu --}}
                        <button
                            type="button"
                            @click="toggleRagu({{ $soal->id }})"
                            :class="isRagu({{ $soal->id }}) ? 'is-active' : ''"
                            class="exam-nav-btn exam-nav-btn-ragu"
                        >
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" :stroke="isRagu({{ $soal->id }}) ? 'white' : '#d97706'" style="flex-shrink: 0;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Ragu-Ragu</span>
                        </button>

                        {{-- Tombol Berikutnya / Selesai --}}
                        @if($idx < $this->soals->count() - 1)
                            <button
                                type="button"
                                @click="nextSoal()"
                                class="exam-nav-btn exam-nav-btn-next"
                            >
                                <span>Berikutnya &raquo;</span>
                            </button>
                        @else
                            <button
                                type="button"
                                @click="if (canSubmit) showConfirmSubmitModal = true"
                                :disabled="!canSubmit"
                                class="exam-nav-btn exam-nav-btn-finish"
                                :class="!canSubmit ? 'is-disabled' : ''"
                                :title="submitHint"
                            >
                                <span>Selesai Ujian &#10003;</span>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem; color: #94a3b8; font-size: 14px;">
                    Tidak ada butir soal tersedia.
                </div>
            @endforelse
        </section>

        {{-- ============================================================= --}}
        {{-- KOLOM KANAN: Sidebar Grid Nomor Soal (Desktop Murni CSS/JS)   --}}
        {{-- ============================================================= --}}
        <aside class="exam-sidebar-desktop" style="background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; position: sticky; top: 72px;">
            {{-- Header sidebar --}}
            <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: #f8fafc;">
                <span style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #1e293b;">Daftar Nomor Soal</span>
                <span style="font-size: 12px; font-weight: 800; color: #2563eb; background: #dbeafe; padding: 2px 8px; border-radius: 999px;">
                    <span x-text="answeredCount"></span> / {{ $this->soals->count() }}
                </span>
            </div>

            {{-- Grid nomor --}}
            <div style="padding: 16px; max-height: calc(100vh - 300px); overflow-y: auto;">
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;">
                    @foreach($this->soals as $idx => $s)
                        <button
                            type="button"
                            @click="goToSoal({{ $idx }})"
                            class="exam-grid-btn"
                            :class="{
                                'is-active': currentIndex === {{ $idx }},
                                'is-ragu': isRagu({{ $s->id }}),
                                'is-answered': !isRagu({{ $s->id }}) && isAnswered({{ $s->id }}),
                                'is-unanswered': !isRagu({{ $s->id }}) && !isAnswered({{ $s->id }})
                            }"
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
                        <span style="font-weight: 800; color: #059669;" x-text="answeredCount"></span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 14px; height: 14px; border-radius: 4px; background: #f59e0b; display: inline-block;"></span>
                            <span>Ragu-Ragu</span>
                        </div>
                        <span style="font-weight: 800; color: #f59e0b;" x-text="raguCount"></span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 14px; height: 14px; border-radius: 4px; background: #f1f5f9; border: 1.5px solid #cbd5e1; display: inline-block;"></span>
                            <span>Belum Dijawab</span>
                        </div>
                        <span style="font-weight: 800; color: #64748b;" x-text="belumCount"></span>
                    </div>
                </div>
            </div>

            {{-- Tombol Selesai Sidebar --}}
            <div style="padding: 14px 18px; border-top: 1px solid #f1f5f9; background: #f8fafc;">
                <button
                    type="button"
                    @click="if (canSubmit) showConfirmSubmitModal = true"
                    :disabled="!canSubmit"
                    class="exam-btn-finish-custom"
                    :class="!canSubmit ? 'is-disabled' : ''"
                    :title="submitHint"
                    style="width: 100%;"
                >
                    Selesai &amp; Kumpulkan &#10003;
                </button>
                <template x-if="!canSubmit">
                    <p style="margin: 7px 0 0; font-size: 11px; font-weight: 700; color: #dc2626; text-align: center; line-height: 1.4;">
                        <span x-text="submitHint"></span>
                    </p>
                </template>
            </div>
        </aside>
    </main>

    {{-- ----------------------------------------------------------------- --}}
    {{-- DRAWER MODAL MOBILE: Grid Nomor Soal (Smooth Touch Sheet)          --}}
    {{-- ----------------------------------------------------------------- --}}
    <div
        x-show="showDaftarSoalMobile"
        x-cloak
        @click.self="showDaftarSoalMobile = false"
        class="exam-mobile-overlay"
    >
        <div class="exam-mobile-sheet">
            {{-- Mobile Pull Handle --}}
            <div style="width: 40px; height: 4px; background: #cbd5e1; border-radius: 99px; margin: 0 auto 14px;"></div>

            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h3 style="font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #1e293b; margin: 0;">Daftar Nomor Soal</h3>
                    <span style="font-size: 11px; font-weight: 800; color: #2563eb; background: #dbeafe; padding: 2px 7px; border-radius: 999px;">
                        <span x-text="answeredCount"></span> / {{ $this->soals->count() }}
                    </span>
                </div>
                <button
                    type="button"
                    @click.stop="showDaftarSoalMobile = false"
                    style="background: none; border: none; font-size: 28px; font-weight: 700; color: #94a3b8; cursor: pointer; line-height: 1; padding: 6px 12px; touch-action: manipulation;"
                    title="Tutup"
                >&times;</button>
            </div>

            {{-- Grid Nomor --}}
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 16px;">
                @foreach($this->soals as $idx => $s)
                    <button
                        type="button"
                        @click="goToSoal({{ $idx }}); showDaftarSoalMobile = false;"
                        class="exam-grid-btn"
                        :class="{
                            'is-active': currentIndex === {{ $idx }},
                            'is-ragu': isRagu({{ $s->id }}),
                            'is-answered': !isRagu({{ $s->id }}) && isAnswered({{ $s->id }}),
                            'is-unanswered': !isRagu({{ $s->id }}) && !isAnswered({{ $s->id }})
                        }"
                    >
                        {{ $idx + 1 }}
                    </button>
                @endforeach
            </div>

            {{-- Legend Warna --}}
            <div style="padding: 10px 12px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; gap: 4px; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 16px; overflow-x: auto;">
                <div style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #059669; display: inline-block;"></span>
                    <span>Dijawab (<span x-text="answeredCount"></span>)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #f59e0b; display: inline-block;"></span>
                    <span>Ragu (<span x-text="raguCount"></span>)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                    <span style="width: 10px; height: 10px; border-radius: 3px; background: #cbd5e1; display: inline-block;"></span>
                    <span>Kosong (<span x-text="belumCount"></span>)</span>
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button
                    type="button"
                    @click.stop="showDaftarSoalMobile = false"
                    class="exam-btn-sheet-close"
                    style="flex: 1;"
                >
                    Tutup
                </button>
                <button
                    type="button"
                    @click="if (canSubmit) { showDaftarSoalMobile = false; showConfirmSubmitModal = true; }"
                    :disabled="!canSubmit"
                    class="exam-btn-finish-custom"
                    :class="!canSubmit ? 'is-disabled' : ''"
                    :title="submitHint"
                    style="flex: 1.5;"
                >
                    Selesai &amp; Kumpulkan &#10003;
                </button>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------- --}}
    {{-- MODAL KONFIRMASI SELESAI UJIAN (REAKTIF & OTOMATIS FLUSH SYNC)     --}}
    {{-- ----------------------------------------------------------------- --}}
    <div
        x-show="showConfirmSubmitModal"
        x-cloak
        @click.self="showConfirmSubmitModal = false"
        class="exam-confirm-modal-overlay"
    >
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
                    <span style="font-size: 18px; font-weight: 900; color: #059669;" x-text="answeredCount"></span>
                </div>
                <div style="padding: 8px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0;">
                    <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Ragu</span>
                    <span style="font-size: 18px; font-weight: 900; color: #f59e0b;" x-text="raguCount"></span>
                </div>
                <div style="padding: 8px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0;">
                    <span style="display: block; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 2px;">Kosong</span>
                    <span style="font-size: 18px; font-weight: 900; color: #ef4444;" x-text="belumCount"></span>
                </div>
            </div>

            <div x-show="belumCount > 0" style="padding: 10px 12px; border-radius: 12px; background: #fee2e2; border: 1.5px solid #fca5a5; font-size: 11px; color: #991b1b; text-align: left; margin-bottom: 14px;">
                <strong>Perhatian:</strong> Ujian belum dapat dikumpulkan karena masih ada <strong x-text="belumCount + ' soal kosong yang belum dijawab'"></strong>.
            </div>
            <div x-show="belumCount === 0 && raguCount > 0" style="padding: 10px 12px; border-radius: 12px; background: #fffbeb; border: 1.5px solid #fcd34d; font-size: 11px; color: #78350f; text-align: left; margin-bottom: 14px;">
                <strong>Catatan:</strong> Semua soal sudah dijawab, namun masih ada <strong x-text="raguCount + ' butir ragu-ragu'"></strong>.
            </div>

            <div style="display: flex; gap: 10px;">
                <button
                    type="button"
                    @click="showConfirmSubmitModal = false"
                    class="exam-btn-sheet-close"
                    style="flex: 1;"
                >
                    Cek Kembali
                </button>
                <button
                    type="button"
                    @click="if (canSubmit) submitFinal()"
                    :disabled="!canSubmit"
                    class="exam-btn-finish-custom"
                    :class="!canSubmit ? 'is-disabled' : ''"
                    style="flex: 1.2;"
                >
                    Kirim Jawaban &#10003;
                </button>
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------------- --}}
    {{-- MODAL PERINGATAN & AUTO-LOCK KECURANGAN (CLIENT-SIDE 0MS)         --}}
    {{-- ----------------------------------------------------------------- --}}
    <div
        x-show="showViolationModal || isLockedByViolation"
        x-cloak
        style="position: fixed; inset: 0; z-index: 9999; background: rgba(127,29,29,0.88); backdrop-filter: blur(8px);"
    >
        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box;">
        <div
            @click.outside="if (!isLockedByViolation) { showViolationModal = false; if (getWire()) { getWire().tutupViolationModal(); } }"
            style="width: 100%; max-width: 440px; margin: auto; background: #fff; border-radius: 24px; border: 2.5px solid #ef4444; box-shadow: 0 24px 64px rgba(0,0,0,0.4); padding: 26px 22px; text-align: center;"
        >
            {{-- Icon --}}
            <div style="width: 60px; height: 60px; margin: 0 auto 14px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center;">
                <template x-if="!isLockedByViolation">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#dc2626">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
                <template x-if="isLockedByViolation">
                    <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="#991b1b">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </template>
            </div>

            {{-- State 1: Peringatan (Pelanggaran 1-2x) --}}
            <template x-if="!isLockedByViolation">
                <div>
                    <span style="display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; background: #fee2e2; color: #991b1b; margin-bottom: 8px;">
                        Peringatan Pelanggaran #<span x-text="violationCount">{{ $violationCount }}</span> dari <span x-text="maxViolationLock">3</span>
                    </span>
                    <h3 style="font-size: 17px; font-weight: 900; color: #1e293b; margin: 0 0 8px;">Aktivitas Mencurigakan Terdeteksi!</h3>
                    <p style="font-size: 12px; color: #dc2626; font-weight: 600; margin: 0 0 10px; line-height: 1.55;">
                        Anda terdeteksi meninggalkan halaman ujian atau membuka aplikasi lain. Insiden ini dicatat dalam log pengawas ujian.
                    </p>
                    <div style="padding: 9px 12px; border-radius: 12px; background: #fffbeb; border: 1px solid #fde68a; font-size: 11px; font-weight: 700; color: #92400e; margin-bottom: 16px;">
                        ⚠️ Perhatian: Jika mencapai <span x-text="maxViolationLock">3</span>x pelanggaran, lembar ujian Anda akan <strong>terkunci otomatis</strong>!
                    </div>

                    <button
                        type="button"
                        @click="showViolationModal = false; if (getWire()) { getWire().tutupViolationModal(); }"
                        style="width: 100%; padding: 12px; border-radius: 12px; border: none; font-size: 13px; font-weight: 800; color: #fff; background: #dc2626; cursor: pointer; box-shadow: 0 2px 10px rgba(220,38,38,0.3); min-height: 44px;"
                    >
                        Saya Mengerti &amp; Kembali ke Ujian
                    </button>
                </div>
            </template>

            {{-- State 2: Terkunci Otomatis (Pelanggaran >= 3x) --}}
            <template x-if="isLockedByViolation">
                <div>
                    <span style="display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; background: #991b1b; color: #ffffff; margin-bottom: 10px;">
                        🔒 Lembar Ujian Terkunci (<span x-text="violationCount">{{ $violationCount }}</span>x Pelanggaran)
                    </span>
                    <h3 style="font-size: 18px; font-weight: 900; color: #7f1d1d; margin: 0 0 8px;">Sesi Ujian Anda Dikunci Sementara!</h3>
                    <p style="font-size: 12.5px; color: #334155; font-weight: 600; margin: 0 0 12px; line-height: 1.55;">
                        Anda telah melampaui batas maksimal berpindah tab/aplikasi (<strong style="color: #dc2626;">3 kali</strong>). Seluruh jawaban yang sudah Anda pilih <strong>tetap tersimpan aman</strong>.
                    </p>
                    <div style="padding: 11px 14px; border-radius: 12px; background: #fef2f2; border: 1.5px dashed #f87171; font-size: 12px; font-weight: 700; color: #991b1b; margin-bottom: 16px; line-height: 1.45;">
                        Silakan angkat tangan dan lapor kepada <strong>Guru Pengawas</strong> di ruangan untuk menekan tombol <strong>"Reset Login / Buka Kunci"</strong> pada akun Anda.
                    </div>

                    <button
                        type="button"
                        @click="checkProctorUnlock()"
                        :disabled="isCheckingUnlock"
                        style="width: 100%; padding: 12px; border-radius: 12px; border: none; font-size: 13px; font-weight: 800; color: #fff; background: #041F45; cursor: pointer; box-shadow: 0 4px 14px rgba(4,31,69,0.3); min-height: 46px; display: flex; align-items: center; justify-content: center; gap: 8px;"
                    >
                        <span x-show="!isCheckingUnlock">Cek Status Buka Kunci Pengawas &rarr;</span>
                        <span x-show="isCheckingUnlock">Memeriksa Persetujuan Pengawas...</span>
                    </button>

                    <div x-show="unlockFeedback" x-text="unlockFeedback" style="margin-top: 10px; font-size: 11px; font-weight: 700; color: #dc2626;"></div>
                </div>
            </template>
        </div>
        </div>
    </div>
@endif

{{-- ===================================================================== --}}
{{-- SCRIPT: ALPINE EXAM RUNNER LOGIC (CLIENT-SIDE NAVIGATION & OFFLINE)  --}}
{{-- ===================================================================== --}}
<script>
function examRunner($wire, config) {
    if (!config && $wire && typeof $wire === 'object' && !('$set' in $wire)) {
        config = $wire;
        $wire = null;
    }
    config = config || {};

    return {
        _wire: $wire,
        currentIndex: config.initialIndex || 0,
        totalSoals: config.totalSoals || 0,
        soalIds: config.soalIds || [],
        soalTypes: config.soalTypes || {},
        answers: config.initialAnswers || {},
        tokenSesi: config.tokenSesi || 'token',
        isPreview: Boolean(config.isPreview),
        violationCount: config.initialViolationCount || 0,
        showViolationModal: Boolean(config.initialShowViolationModal),
        maxViolationLock: 3,
        isCheckingUnlock: false,
        unlockFeedback: '',

        zoomLevel: 'md',
        showDaftarSoalMobile: false,
        showConfirmSubmitModal: false,

        isOnline: navigator.onLine,
        isSyncing: false,
        pendingSync: [],
        storageKey: 'cbt_answers_' + (config.tokenSesi || 'default'),
        queueKey: 'cbt_queue_' + (config.tokenSesi || 'default'),

        getWire() {
            return this.$wire || this._wire || (typeof window !== 'undefined' && window.Livewire && this.$el ? window.Livewire.find(this.$el.closest('[wire\\:id]')?.getAttribute('wire:id')) : null);
        },

        get isLockedByViolation() {
            return !this.isPreview && this.violationCount >= this.maxViolationLock;
        },

        async checkProctorUnlock(silent = false) {
            const wire = this.getWire();
            if (!wire || typeof wire.cekStatusSesiServer !== 'function') return;
            if (!silent) {
                this.isCheckingUnlock = true;
                this.unlockFeedback = '';
            }
            try {
                const res = await wire.cekStatusSesiServer();
                if (res && res.finished === true) {
                    try {
                        localStorage.removeItem(this.storageKey);
                        localStorage.removeItem(this.queueKey);
                    } catch (e) {}
                    if (typeof wire.refreshToFinishedState === 'function') {
                        wire.refreshToFinishedState();
                    }
                    return;
                }
                if (res && typeof res.end_timestamp === 'number') {
                    window.dispatchEvent(new CustomEvent('cbt-update-end-timestamp', { detail: res.end_timestamp }));
                }
                if (res && typeof res.count === 'number') {
                    this.violationCount = res.count;
                }
                if (res && res.locked === false) {
                    this.showViolationModal = false;
                    this.unlockFeedback = '';
                } else if (!silent) {
                    this.unlockFeedback = 'Status masih terkunci. Minta Guru Pengawas klik "Reset Login / Buka Kunci".';
                }
            } catch (e) {
                if (!silent) {
                    this.unlockFeedback = 'Gagal memeriksa status ke server. Pastikan koneksi internet aktif.';
                }
            } finally {
                if (!silent) {
                    this.isCheckingUnlock = false;
                }
            }
        },

        get currentSoalId() {
            return this.soalIds[this.currentIndex] || null;
        },

        init() {
            window.addEventListener('cbt-timer-state', (e) => {
                const s = Number(e.detail);
                this.sisaWaktuDetik = s;
                this.waktuHabis = s <= 0;
            });

            // Restore dari localStorage jika ada data lokal tersimpan
            try {
                const local = localStorage.getItem(this.storageKey);
                if (local) {
                    const parsed = JSON.parse(local);
                    for (const id in parsed) {
                        this.answers[id] = Object.assign({}, this.answers[id] || {}, parsed[id]);
                    }
                }
                const queue = localStorage.getItem(this.queueKey);
                if (queue) {
                    this.pendingSync = JSON.parse(queue);
                }
            } catch (e) {
                console.warn('Gagal memuat cache localStorage:', e);
            }

            // Sync antrean yang belum terkirim saat startup
            if (navigator.onLine && this.pendingSync.length > 0) {
                this.flushQueue();
            }

            // Event listener Online / Offline browser
            window.addEventListener('online', () => {
                this.isOnline = true;
                this.flushQueue();
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
            });

            // Sinkronisasi otomatis berkala setiap 15 detik jika ada antrean
            setInterval(() => {
                if (this.isOnline && this.pendingSync.length > 0 && !this.isSyncing) {
                    this.flushQueue();
                }
            }, 15000);

            // Sinkronisasi status sesi dari server setiap 5 detik (deteksi paksa selesai oleh pengawas, +10m waktu, & buka kunci)
            setInterval(() => {
                if (this.isOnline && !this.isPreview) {
                    this.checkProctorUnlock(true);
                }
            }, 5000);

            // Render rumus matematika KaTeX saat halaman selesai dimuat
            this.$nextTick(() => {
                if (typeof renderMathInElement === 'function') {
                    renderMathInElement(document.body, {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '$', right: '$', display: false},
                            {left: '\\(', right: '\\)', display: false},
                            {left: '\\[', right: '\\]', display: true}
                        ],
                        throwOnError: false
                    });
                }
            });

            // Keyboard navigation (0ms instantaneous)
            window.addEventListener('keydown', (e) => {
                if (this.showViolationModal || this.isLockedByViolation) return;
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
                const key = e.key.toUpperCase();
                if (['A', 'B', 'C', 'D', 'E'].includes(key)) {
                    if (this.currentSoalId) {
                        this.pilihOpsi(this.currentSoalId, key);
                    }
                } else if (e.key === 'ArrowRight') {
                    this.nextSoal();
                } else if (e.key === 'ArrowLeft') {
                    this.prevSoal();
                } else if (key === 'R') {
                    if (this.currentSoalId) {
                        this.toggleRagu(this.currentSoalId);
                    }
                }
            });

            // Kunci browser history
            history.pushState(null, document.title, location.href);
            window.addEventListener('popstate', function () {
                history.pushState(null, document.title, location.href);
            });

            // Tab/focus violation detection (jika bukan mode preview)
            @if(!$is_preview)
            window.isLegitNavigation = false;
            window.addEventListener('beforeunload', () => {
                window.isLegitNavigation = true;
            });
            const examPageMountedAt = Date.now();
            document.addEventListener('visibilitychange', () => {
                // Beri grace period 3 detik setelah halaman dibuka agar transisi redirect PWA di iOS tidak terhitung pelanggaran
                if (Date.now() - examPageMountedAt < 3000) {
                    return;
                }
                if (document.hidden && !window.isLegitNavigation) {
                    this.violationCount++;
                    this.showViolationModal = true;
                    if (window.AndroidExam && typeof window.AndroidExam.triggerViolation === 'function') {
                        window.AndroidExam.triggerViolation('Siswa beralih aplikasi atau membuka notifikasi');
                    }
                    const wire = this.getWire();
                    if (wire && typeof wire.triggerViolation === 'function') {
                        wire.triggerViolation();
                    }
                }
            });
            @endif
        },

        goToSoal(index) {
            if (index >= 0 && index < this.totalSoals) {
                this.currentIndex = index;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        nextSoal() {
            if (this.currentIndex < this.totalSoals - 1) {
                this.goToSoal(this.currentIndex + 1);
            }
        },

        prevSoal() {
            if (this.currentIndex > 0) {
                this.goToSoal(this.currentIndex - 1);
            }
        },

        ensureAnswer(soalId) {
            if (!this.answers[soalId]) {
                this.answers[soalId] = { pilihan: null, teks: null, is_ragu: false };
            }
        },

        saveLocal() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.answers));
                localStorage.setItem(this.queueKey, JSON.stringify(this.pendingSync));
            } catch (e) {
                console.warn('Gagal menyimpan cache localStorage:', e);
            }
        },

        queueSync(item) {
            this.pendingSync = this.pendingSync.filter(q => q.soal_id !== item.soal_id);
            this.pendingSync.push(item);
            this.saveLocal();
        },

        async flushQueue() {
            if (this.pendingSync.length === 0 || this.isSyncing) return;
            this.isSyncing = true;
            const batch = [...this.pendingSync];
            const wire = this.getWire();
            if (!wire) {
                console.warn('Livewire $wire instance belum siap, antrean tetap tersimpan.');
                this.isSyncing = false;
                return;
            }
            try {
                const syncRes = await wire.syncBatchJawaban(batch);
                if (syncRes && syncRes.status === 'finished') {
                    try {
                        localStorage.removeItem(this.storageKey);
                        localStorage.removeItem(this.queueKey);
                    } catch (e) {}
                    if (typeof wire.refreshToFinishedState === 'function') {
                        wire.refreshToFinishedState();
                    }
                    return;
                }
                const syncedIds = new Set(batch.map(b => b.soal_id));
                this.pendingSync = this.pendingSync.filter(q => !syncedIds.has(q.soal_id));
                this.isOnline = true;
                this.saveLocal();
            } catch (err) {
                console.warn('Sinkronisasi latar gagal, disimpan di antrean lokal:', err);
                this.isOnline = false;
            } finally {
                this.isSyncing = false;
                if (this.pendingSync.length > 0 && this.isOnline) {
                    setTimeout(() => this.flushQueue(), 100);
                }
            }
        },

        pilihOpsi(soalId, label) {
            this.ensureAnswer(soalId);
            this.answers[soalId].pilihan = label;
            this.saveLocal();

            this.queueSync({
                soal_id: soalId,
                pilihan: label,
                teks: this.answers[soalId].teks || null,
                is_ragu: Boolean(this.answers[soalId].is_ragu)
            });
            this.flushQueue();
        },

        pilihMulti(soalId, label) {
            this.ensureAnswer(soalId);
            let current = this.answers[soalId].pilihan;
            if (!Array.isArray(current)) current = [];
            if (current.includes(label)) {
                current = current.filter(x => x !== label);
            } else {
                current.push(label);
                current.sort();
            }
            this.answers[soalId].pilihan = current;
            this.saveLocal();

            this.queueSync({
                soal_id: soalId,
                pilihan: current,
                teks: this.answers[soalId].teks || null,
                is_ragu: Boolean(this.answers[soalId].is_ragu)
            });
            this.flushQueue();
        },

        simpanTeks(soalId, teks) {
            this.ensureAnswer(soalId);
            this.answers[soalId].teks = teks;
            this.saveLocal();

            this.queueSync({
                soal_id: soalId,
                pilihan: this.answers[soalId].pilihan || null,
                teks: teks,
                is_ragu: Boolean(this.answers[soalId].is_ragu)
            });
            this.flushQueue();
        },

        simpanMatching(soalId, index, value) {
            this.ensureAnswer(soalId);
            let current = this.answers[soalId].pilihan;
            if (typeof current !== 'object' || current === null || Array.isArray(current)) {
                current = {};
            }
            current[index] = value;
            this.answers[soalId].pilihan = current;
            this.saveLocal();

            this.queueSync({
                soal_id: soalId,
                pilihan: current,
                teks: this.answers[soalId].teks || null,
                is_ragu: Boolean(this.answers[soalId].is_ragu)
            });
            this.flushQueue();
        },

        toggleRagu(soalId) {
            this.ensureAnswer(soalId);
            this.answers[soalId].is_ragu = !this.answers[soalId].is_ragu;
            this.saveLocal();

            this.queueSync({
                soal_id: soalId,
                pilihan: this.answers[soalId].pilihan || null,
                teks: this.answers[soalId].teks || null,
                is_ragu: Boolean(this.answers[soalId].is_ragu)
            });
            this.flushQueue();
        },

        isAnswered(soalId) {
            const a = this.answers[soalId];
            if (!a) return false;
            const tipe = this.soalTypes[soalId];
            if (tipe === 'pilihan_ganda' || tipe === 'benar_salah') {
                return Boolean(a.pilihan);
            }
            if (tipe === 'pg_kompleks') {
                return Array.isArray(a.pilihan) && a.pilihan.length > 0;
            }
            if (tipe === 'menjodohkan') {
                if (Array.isArray(a.pilihan)) return a.pilihan.length > 0;
                return Boolean(a.pilihan && Object.keys(a.pilihan).length > 0);
            }
            return Boolean(a.teks && a.teks.trim().length > 0);
        },

        isRagu(soalId) {
            return Boolean(this.answers[soalId] && this.answers[soalId].is_ragu);
        },

        get answeredCount() {
            let count = 0;
            for (const id of this.soalIds) {
                if (this.isAnswered(id)) count++;
            }
            return count;
        },

        get raguCount() {
            let count = 0;
            for (const id of this.soalIds) {
                if (this.isRagu(id)) count++;
            }
            return count;
        },

        get belumCount() {
            return Math.max(0, this.totalSoals - this.answeredCount);
        },

        // Tombol kumpulkan hanya aktif jika semua soal terjawab DAN waktu pengerjaan sudah habis
        waktuHabis: false,
        sisaWaktuDetik: null,

        get canSubmit() {
            return this.answeredCount >= this.totalSoals && this.waktuHabis;
        },

        get submitHint() {
            if (this.belumCount > 0) {
                return 'Jawab ' + this.belumCount + ' soal lagi untuk mengumpulkan.';
            }
            if (!this.waktuHabis) {
                const s = this.sisaWaktuDetik ?? 0;
                const m = Math.floor(s / 60);
                const d = String(s % 60).padStart(2, '0');
                return 'Semua soal terjawab. Lembar jawaban baru bisa dikumpulkan setelah waktu habis (sisa ' + m + ':' + d + ').';
            }
            return 'Selesai dan kumpulkan lembar jawaban';
        },

        async submitFinal() {
            if (!this.canSubmit) return;
            if (this.pendingSync.length > 0) {
                await this.flushQueue();
            }
            try {
                localStorage.removeItem(this.storageKey);
                localStorage.removeItem(this.queueKey);
            } catch (e) {}
            const wire = this.getWire();
            if (wire && typeof wire.submitUjianFinal === 'function') {
                wire.submitUjianManual();
            }
        }
    };
}
</script>
</div>
