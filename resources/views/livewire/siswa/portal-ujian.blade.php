<div style="min-height: 100vh; display: flex; flex-direction: column; background: #f8fafc; color: #1e293b; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;">

    {{-- TOPBAR SISWA --}}
    <header style="background: #ffffff; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 40; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="max-width: 1200px; margin: 0 auto; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            {{-- Logo & Identitas Aplikasi --}}
            <div style="display: flex; align-items: center;">
                <img src="{{ asset('images/logo/examuna.png') }}" alt="Examuna - SMK Assuniyah Tumijajar" style="height: 38px; width: auto; max-width: 220px; object-fit: contain;">
            </div>

            {{-- Info Siswa & Tombol Keluar --}}
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px; padding: 6px 14px; background: #f1f5f9; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #2563eb; color: #fff; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center;">
                        {{ strtoupper(substr($siswa?->nama ?: $user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-size: 12px; font-weight: 800; color: #1e293b; line-height: 1.2;">
                            {{ $siswa?->nama ?: $user->name }}
                        </div>
                        <div style="font-size: 10px; font-weight: 600; color: #64748b; line-height: 1.2;">
                            NIS: {{ $siswa?->nis ?: ($user->nip ?: '-') }} &bull; {{ $siswa?->kelas?->nama_kelas ?: 'Siswa' }}
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="logout"
                    title="Keluar dari akun"
                    style="padding: 8px 12px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; color: #dc2626; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.15s;"
                    onmouseover="this.style.background='#fef2f2'"
                    onmouseout="this.style.background='#ffffff'"
                >
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Keluar</span>
                </button>
            </div>
        </div>
    </header>

    {{-- KONTEN UTAMA --}}
    <main style="flex: 1; max-width: 1100px; width: 100%; margin: 0 auto; padding: 24px 20px; box-sizing: border-box; display: flex; flex-direction: column; gap: 24px;">

        {{-- 1. HERO CARD: INPUT KODE TOKEN UJIAN --}}
        <div style="background: #ffffff; border-radius: 20px; border: 1.5px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 24px 24px 20px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #ffffff; text-align: center;">
                <div style="width: 48px; height: 48px; margin: 0 auto 10px; border-radius: 14px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h1 style="font-size: 22px; font-weight: 900; margin: 0; letter-spacing: -0.01em;">Masuk Sesi Ujian dengan Token</h1>
                <p style="font-size: 13px; color: rgba(219,234,254,0.95); margin-top: 6px; max-width: 540px; margin-left: auto; margin-right: auto; line-height: 1.5;">
                    Minta <strong>6 karakter Kode Token Akses</strong> kepada Guru Pengawas / Proktor di ruangan ujian Anda untuk memulai pengerjaan.
                </p>
            </div>

            <div style="padding: 28px 24px; max-width: 520px; margin: 0 auto; width: 100%; box-sizing: border-box;">
                <form wire:submit.prevent="submitToken" style="display: flex; flex-direction: column; gap: 18px;">
                    <div style="display: flex; flex-direction: column; gap: 8px; text-align: center;">
                        <label style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #475569;">
                            Ketik Kode Token Ujian (6 Karakter)
                        </label>
                        <input
                            type="text"
                            wire:model="token_input"
                            maxlength="10"
                            placeholder="Contoh: A7K9P2"
                            autocomplete="off"
                            autofocus
                            style="width: 100%; box-sizing: border-box; text-align: center; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 26px; font-weight: 900; letter-spacing: 0.25em; text-transform: uppercase; padding: 14px 16px; border-radius: 14px; border: 2px solid #cbd5e1; color: #1e293b; background: #f8fafc; outline: none; transition: all 0.15s;"
                            onfocus="this.style.borderColor='#2563eb'; this.style.background='#ffffff'; this.style.boxShadow='0 0 0 4px rgba(37,99,235,0.15)'"
                            onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc'; this.style.boxShadow='none'"
                            oninput="this.value = this.value.toUpperCase()"
                        />
                    </div>

                    @error('token_input')
                        <div style="padding: 12px 16px; border-radius: 12px; background: #fef2f2; border: 1.5px solid #fecaca; color: #dc2626; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        style="width: 100%; padding: 14px 20px; border-radius: 14px; border: none; background: #2563eb; color: #ffffff; font-size: 16px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 14px rgba(37,99,235,0.3); transition: all 0.15s;"
                        onmouseover="this.style.background='#1d4ed8'"
                        onmouseout="this.style.background='#2563eb'"
                    >
                        <span wire:loading.remove>Mulai Kerjakan Ujian &raquo;</span>
                        <span wire:loading>Memverifikasi Token & Membuka Soal...</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- 2. DAFTAR UJIAN KELAS HARI INI / TERJADWAL --}}
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Agenda Ujian Rombel {{ $siswa?->kelas?->nama_kelas ?: 'Anda' }}
                </h3>
                <span style="font-size: 12px; color: #64748b; font-weight: 600;">
                    {{ count($daftarUjian) }} Ujian Terjadwal
                </span>
            </div>

            @if($daftarUjian->isEmpty())
                <div style="padding: 32px 20px; text-align: center; background: #ffffff; border-radius: 16px; border: 1px dashed #cbd5e1; color: #94a3b8;">
                    <p style="font-size: 14px; font-weight: 600; margin: 0;">Belum ada jadwal ujian aktif untuk rombel kelas Anda hari ini.</p>
                    <p style="font-size: 12px; margin-top: 4px;">Guru atau Admin akan menjadwalkan ujian dan memberikan kode token di kelas.</p>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px;">
                    @foreach($daftarUjian as $uj)
                        <div style="background: #ffffff; border-radius: 16px; border: 1.5px solid {{ $uj->status === 'ONGOING' ? '#3b82f6' : '#e2e8f0' }}; padding: 18px; display: flex; flex-direction: column; justify-content: space-between; gap: 14px; box-shadow: {{ $uj->status === 'ONGOING' ? '0 4px 16px rgba(59,130,246,0.12)' : '0 1px 3px rgba(0,0,0,0.03)' }};">
                            <div>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: #eff6ff; color: #1d4ed8;">
                                            {{ $uj->mapel?->nama_mapel ?: 'Mata Pelajaran' }}
                                        </span>
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; background: #f1f5f9; color: #475569;">
                                            {{ $uj->getTipeLabel() }}
                                        </span>
                                    </div>

                                    @if($uj->status === 'ONGOING')
                                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 800;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #22c55e;"></span>
                                            SESI DIBUKA
                                        </span>
                                    @elseif($uj->status === 'SCHEDULED')
                                        <span style="display: inline-block; padding: 3px 9px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700;">
                                            Dijadwalkan
                                        </span>
                                    @else
                                        <span style="display: inline-block; padding: 3px 9px; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 700;">
                                            Selesai
                                        </span>
                                    @endif
                                </div>

                                <h4 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.3;">
                                    {{ $uj->nama_ujian }}
                                </h4>

                                <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #64748b;">
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Guru: <strong>{{ $uj->guru?->name ?: 'Guru Pengawas' }}</strong></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Durasi: <strong>{{ $uj->durasi_menit ?: 60 }} Menit</strong> &bull; {{ $uj->bankSoal?->soals()->count() ?: 0 }} Butir Soal</span>
                                    </div>
                                </div>
                            </div>

                            <div style="padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 11px;">
                                @if($uj->status === 'ONGOING')
                                    <span style="color: #2563eb; font-weight: 700;">
                                        &bull; Minta kode token ke Guru Pengawas untuk memulai
                                    </span>
                                @elseif($uj->status === 'SCHEDULED')
                                    <span style="color: #64748b;">
                                        Menunggu Guru Pengawas mengklik "Mulai Ujian" di ruang kelas
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">
                                        Sesi ujian telah ditutup
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 3. RIWAYAT UJIAN YANG SUDAH SELESAI --}}
        @if($riwayatUjian->isNotEmpty())
            <div style="margin-top: 10px;">
                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 12px 0;">
                    Riwayat Lembar Ujian yang Sudah Selesai
                </h3>
                <div style="background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 800;">
                                <th style="padding: 12px 16px;">Nama Ujian</th>
                                <th style="padding: 12px 16px;">Mata Pelajaran</th>
                                <th style="padding: 12px 16px;">Waktu Selesai</th>
                                <th style="padding: 12px 16px; text-align: center;">Nilai</th>
                                <th style="padding: 12px 16px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($riwayatUjian as $rw)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 12px 16px; font-weight: 700; color: #1e293b;">
                                        {{ $rw->ujian?->nama_ujian ?: 'Ujian CBT' }}
                                    </td>
                                    <td style="padding: 12px 16px; color: #475569;">
                                        {{ $rw->ujian?->mapel?->nama_mapel ?: '-' }}
                                    </td>
                                    <td style="padding: 12px 16px; color: #64748b;">
                                        {{ $rw->waktu_selesai ? $rw->waktu_selesai->format('d M Y, H:i') : '-' }}
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center; font-weight: 900; color: #2563eb;">
                                        {{ $rw->nilai !== null ? (float) $rw->nilai : '-' }}
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 999px; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 800;">
                                            Terkirim &#10003;
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </main>

    {{-- FOOTER --}}
    <footer style="padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; background: #ffffff;">
        Examuna CBT &bull; Sistem Ujian Online Terintegrasi Token Sesi &bull; {{ date('Y') }}
    </footer>
</div>

