<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); padding: 1.5rem; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;">
    <div style="width: 100%; max-width: 440px; background: #ffffff; border-radius: 24px; box-shadow: 0 25px 70px rgba(0,0,0,0.35); overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">

        {{-- Header Card --}}
        <div style="padding: 2.25rem 2rem 1.75rem; text-align: center; background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); color: #ffffff; position: relative;">
            <div style="width: 60px; height: 60px; margin: 0 auto 12px; border-radius: 18px; background: rgba(255,255,255,0.2); border: 1.5px solid rgba(255,255,255,0.35); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
                <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <span style="display: inline-block; padding: 4px 14px; border-radius: 999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; background: rgba(255,255,255,0.22); color: #ffffff; margin-bottom: 8px;">
                Portal Ujian Siswa
            </span>
            <h1 style="font-size: 22px; font-weight: 900; line-height: 1.2; margin: 0; letter-spacing: -0.02em;">ExamGuard CBT</h1>
            <p style="font-size: 12px; color: rgba(219,234,254,0.95); margin-top: 6px; font-weight: 500;">
                Silakan masuk menggunakan NIS / Akun Siswa Anda
            </p>
        </div>

        {{-- Form Login --}}
        <div style="padding: 2rem 2rem 2.25rem;">
            <form wire:submit.prevent="doLogin" style="display: flex; flex-direction: column; gap: 16px;">

                {{-- Input NIS / Email --}}
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #475569;">
                        NIS / Email Siswa
                    </label>
                    <div style="position: relative;">
                        <input
                            type="text"
                            wire:model="login"
                            placeholder="Contoh: 10234 atau email siswa"
                            autocomplete="username"
                            required
                            style="width: 100%; box-sizing: border-box; padding: 12px 14px 12px 42px; border-radius: 12px; border: 1.5px solid #cbd5e1; font-size: 14px; font-weight: 600; color: #1e293b; outline: none; transition: all 0.15s;"
                            onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.15)'"
                            onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'"
                        />
                        <div style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; display: flex; align-items: center;">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                    @error('login')
                        <span style="font-size: 12px; font-weight: 600; color: #dc2626;">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Input Password --}}
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #475569;">
                        Password
                    </label>
                    <div style="position: relative;">
                        <input
                            type="password"
                            wire:model="password"
                            placeholder="Masukkan password akun Anda"
                            autocomplete="current-password"
                            required
                            style="width: 100%; box-sizing: border-box; padding: 12px 14px 12px 42px; border-radius: 12px; border: 1.5px solid #cbd5e1; font-size: 14px; font-weight: 600; color: #1e293b; outline: none; transition: all 0.15s;"
                            onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.15)'"
                            onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'"
                        />
                        <div style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; display: flex; align-items: center;">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>
                    @error('password')
                        <span style="font-size: 12px; font-weight: 600; color: #dc2626;">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Remember Me --}}
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 2px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; color: #64748b; font-weight: 600;">
                        <input type="checkbox" wire:model="remember" style="accent-color: #2563eb; width: 16px; height: 16px; border-radius: 4px;" />
                        Ingat Saya
                    </label>
                    <span style="font-size: 12px; color: #94a3b8;">Default: password</span>
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    style="width: 100%; margin-top: 8px; padding: 14px; border-radius: 14px; border: none; background: #2563eb; color: #ffffff; font-size: 15px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(37,99,235,0.35); transition: all 0.15s;"
                    onmouseover="this.style.background='#1d4ed8'"
                    onmouseout="this.style.background='#2563eb'"
                >
                    <span wire:loading.remove>Masuk ke Portal Ujian &raquo;</span>
                    <span wire:loading>Memverifikasi Akun...</span>
                </button>
            </form>

            {{-- Footer Note --}}
            <div style="margin-top: 1.75rem; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 1.25rem;">
                <p style="font-size: 11px; color: #94a3b8; margin: 0; line-height: 1.5;">
                    Lupa password atau belum memiliki akun? Hubungi Guru Pengawas / Proktor CBT di sekolah Anda.
                </p>
                <div style="margin-top: 10px;">
                    <a href="/admin/login" style="font-size: 11px; font-weight: 700; color: #2563eb; text-decoration: none;">
                        &larr; Login Guru / Administrator
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

