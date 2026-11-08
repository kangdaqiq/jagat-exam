<?php

namespace App\Livewire;

use App\Models\BankSoal;
use App\Models\Soal;
use App\Models\UjianJawaban;
use App\Models\UjianSesi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Renderless;
use Livewire\Component;

#[Layout('layouts.exam')]
class ExamRunner extends Component
{
    public BankSoal $bankSoal;

    // Sesi Ujian & Peserta
    public ?UjianSesi $sesi = null;
    public string $token_sesi = '';
    public string $nama_peserta = '';
    public string $nomor_peserta = '';
    public bool $is_preview = false;

    // State Navigasi
    public int $currentIndex = 0;
    public string $zoomLevel = 'md'; // 'sm', 'md', 'lg', 'xl'
    public bool $showDaftarSoalMobile = false;
    public bool $showConfirmSubmitModal = false;
    public bool $is_submitted = false;

    // Audio tracking
    public array $audioPlayCounts = [];

    // Jawaban Siswa in-memory cache: [soal_id => ['jawaban' => ..., 'is_ragu' => bool]]
    public array $userAnswers = [];

    // Siswa Peringatan Kecurangan
    public int $violationCount = 0;
    public bool $showViolationModal = false;

    // Request-level cache untuk koleksi soal agar tidak berulang kali query DB
    protected ?Collection $cachedSoals = null;

    protected $listeners = [
        'siswa-curang-tab' => 'handleTabViolation',
        'timer-expired' => 'autoSubmitOnTimeout',
    ];

    public function mount(?BankSoal $bankSoal = null, ?string $token_sesi = null): void
    {
        // 1. Jika diakses via token sesi ujian resmi siswa
        if ($token_sesi) {
            $this->sesi = UjianSesi::where('token_sesi', $token_sesi)
                ->with(['bankSoal.mapel', 'bankSoal.soals.opsiJawabans'])
                ->firstOrFail();

            // Keamanan: Pastikan sesi milik siswa yang sedang login (kecuali Admin/Guru)
            if ($this->sesi->user_id && Auth::check()) {
                $currentUser = Auth::user();
                if ((int) $this->sesi->user_id !== (int) $currentUser->id && !$currentUser->isAdmin() && !$currentUser->isGuru()) {
                    abort(403, 'Akses ditolak: Sesi ujian ini milik peserta lain.');
                }

                // Keamanan Single-Device Lock
                if ($currentUser->isSiswa()) {
                    $browserToken = session('cbt_device_token');
                    if (filled($currentUser->active_session_token) && filled($browserToken) && $currentUser->active_session_token !== $browserToken) {
                        Auth::logout();
                        session()->invalidate();
                        session()->regenerateToken();
                        redirect()->route('login');
                        return;
                    }
                    if (filled($this->sesi->device_token) && filled($browserToken) && $this->sesi->device_token !== $browserToken) {
                        abort(403, 'Sesi ujian ini sedang terkunci pada perangkat lain. Hubungi Pengawas untuk Reset Login.');
                    }
                }
            }

            $this->bankSoal = $this->sesi->bankSoal;
            $this->is_preview = false;
            $this->violationCount = (int) ($this->sesi->jumlah_pelanggaran ?? 0);
            if ($this->sesi->isTerblokirPelanggaran()) {
                $this->showViolationModal = true;
            }

            if ($this->sesi->status === 'selesai') {
                $this->loadSessionData();
                $this->is_submitted = true;
            } elseif ($this->sesi->isSelesai() || ($this->sesi->ujian && $this->sesi->ujian->status === 'COMPLETED')) {
                // Waktu sesi atau jadwal ujian telah berakhir
                $this->sesi->koreksiDanSelesaikan();
                $this->sesi->refresh();
                $this->loadSessionData();
                $this->is_submitted = true;
            } else {
                $this->loadSessionData();
            }
            return;
        }

        // 2. Jika diakses via bank soal langsung
        if ($bankSoal) {
            $this->bankSoal = $bankSoal->loadMissing(['mapel', 'soals.opsiJawabans']);

            // Keamanan: Guru hanya boleh mengakses / mempratinjau bank soal miliknya sendiri
            if (Auth::check() && Auth::user()->isGuru() && (int) $this->bankSoal->user_id !== (int) Auth::id()) {
                abort(403, 'Akses ditolak: Anda hanya dapat mengakses dan mempratinjau Bank Soal milik Anda sendiri.');
            }

            $this->is_preview = request()->boolean('preview') || (Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isGuru()));

            // Cari sesi aktif dari session/cookie
            $existingToken = session("ujian_token_{$this->bankSoal->id}");
            if ($existingToken) {
                $existingSesi = UjianSesi::where('token_sesi', $existingToken)
                    ->where('bank_soal_id', $this->bankSoal->id)
                    ->first();

                if ($existingSesi) {
                    if ($existingSesi->status === 'selesai') {
                        $this->sesi = $existingSesi;
                        $this->is_submitted = true;
                        return;
                    } elseif (!$existingSesi->isSelesai()) {
                        $this->sesi = $existingSesi;
                        $this->loadSessionData();
                        return;
                    }
                }
            }

            // Jika mode preview pengawas/guru, otomatis mulai sesi pratinjau
            if ($this->is_preview) {
                $user = Auth::user();
                $this->nama_peserta = $user ? "{$user->name} (Guru)" : "Pratinjau Guru";
                $this->nomor_peserta = "PREVIEW-GURU";
                $this->startExam(true);
                return;
            }

            // Jika siswa dan sudah login, arahkan ke portal ujian untuk memasukkan token
            if (Auth::check() && Auth::user()->isSiswa()) {
                redirect()->route('siswa.portal');
                return;
            }
        }
    }

    public function startExam(bool $isPreview = false): void
    {
        if (!$isPreview) {
            $this->validate([
                'nama_peserta' => 'required|min:2|max:100',
                'nomor_peserta' => 'nullable|max:50',
            ], [
                'nama_peserta.required' => 'Nama lengkap peserta wajib diisi.',
                'nama_peserta.min' => 'Nama peserta minimal 2 karakter.',
            ]);
        }

        $token = Str::random(36);
        $durasi = 60; // 60 menit default

        $this->sesi = UjianSesi::create([
            'bank_soal_id' => $this->bankSoal->id,
            'user_id' => Auth::id(),
            'token_sesi' => $token,
            'nama_peserta' => $this->nama_peserta,
            'nomor_peserta' => $this->nomor_peserta ?: 'PESERTA-' . rand(100, 999),
            'waktu_mulai' => now(),
            'waktu_selesai' => now()->addMinutes($durasi),
            'durasi_menit' => $durasi,
            'status' => 'mengerjakan',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        session(["ujian_token_{$this->bankSoal->id}" => $token]);

        // Inisialisasi baris jawaban di database untuk setiap butir soal (Bulk Insert 1 Query)
        $soals = $this->bankSoal->soals()->with('opsiJawabans')->orderBy('nomor_urut', 'asc')->get();
        $acakSoal = $this->bankSoal->acak_soal ?? true;
        $acakOpsi = $this->bankSoal->acak_opsi ?? true;

        if ($acakSoal) {
            $soals = $soals->shuffle();
        }

        $now = now();
        $jawabanRows = [];
        foreach ($soals->values() as $order => $soal) {
            $urutanOpsi = null;
            if ($acakOpsi && in_array($soal->tipe_soal, ['pilihan_ganda', 'pg_kompleks']) && $soal->opsiJawabans->isNotEmpty()) {
                $urutanOpsi = $soal->opsiJawabans->pluck('id')->shuffle()->values()->toArray();
            }

            $jawabanRows[] = [
                'ujian_sesi_id' => $this->sesi->id,
                'soal_id' => $soal->id,
                'nomor_urut' => $order + 1,
                'urutan_opsi' => $urutanOpsi !== null ? json_encode($urutanOpsi) : null,
                'jawaban_pilihan' => null,
                'jawaban_teks' => null,
                'is_ragu' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($jawabanRows)) {
            UjianJawaban::insert($jawabanRows);
        }

        $this->cachedSoals = null;
        $this->loadSessionData();
    }

    protected function loadSessionData(): void
    {
        if (!$this->sesi) return;

        $this->violationCount = (int) ($this->sesi->jumlah_pelanggaran ?? 0);

        // Ambil semua jawaban yang sudah ada
        $jawabans = $this->sesi->jawabans()->get();
        foreach ($jawabans as $j) {
            $this->userAnswers[$j->soal_id] = [
                'pilihan' => $j->jawaban_pilihan,
                'teks' => $j->jawaban_teks,
                'is_ragu' => (bool) $j->is_ragu,
            ];
        }

        $this->currentIndex = 0;
        $this->dispatch('soal-rendered');
    }

    public function getSoalsProperty()
    {
        if ($this->cachedSoals !== null) {
            return $this->cachedSoals;
        }

        if ($this->sesi) {
            $jawabans = $this->sesi->jawabans()->orderBy('nomor_urut', 'asc')->get();
            if ($jawabans->isNotEmpty()) {
                $soalIds = $jawabans->pluck('soal_id')->toArray();
                $loadedSoals = $this->bankSoal->soals()->with('opsiJawabans')->whereIn('id', $soalIds)->get()->keyBy('id');
                $orderedSoals = collect();

                foreach ($jawabans as $j) {
                    $originalSoal = $loadedSoals->get($j->soal_id);
                    if (!$originalSoal) continue;

                    $soal = clone $originalSoal;

                    if (!empty($j->urutan_opsi) && is_array($j->urutan_opsi)) {
                        $urutan = $j->urutan_opsi;
                        $shuffledOpsi = $soal->opsiJawabans->sortBy(function ($item) use ($urutan) {
                            $pos = array_search($item->id, $urutan);
                            return $pos !== false ? $pos : 999;
                        })->values();

                        foreach ($shuffledOpsi as $i => $opt) {
                            $opt->display_label = chr(65 + $i);
                        }
                        $soal->setRelation('opsiJawabans', $shuffledOpsi);
                    } else {
                        foreach ($soal->opsiJawabans as $opt) {
                            $opt->display_label = $opt->label;
                        }
                    }

                    $orderedSoals->push($soal);
                }

                return $this->cachedSoals = $orderedSoals;
            }
        }

        $rawSoals = $this->bankSoal->soals()->with('opsiJawabans')->orderBy('nomor_urut', 'asc')->get();
        foreach ($rawSoals as $s) {
            foreach ($s->opsiJawabans as $opt) {
                $opt->display_label = $opt->label;
            }
        }
        return $this->cachedSoals = $rawSoals;
    }

    public function getCurrentSoalProperty()
    {
        return $this->soals[$this->currentIndex] ?? null;
    }

    public function selectSoal(int $index): void
    {
        if ($index >= 0 && $index < $this->soals->count()) {
            $this->currentIndex = $index;
            $this->showDaftarSoalMobile = false;
            $this->dispatch('soal-rendered');
        }
    }

    public function nextSoal(): void
    {
        if ($this->currentIndex < $this->soals->count() - 1) {
            $this->currentIndex++;
            $this->dispatch('soal-rendered');
        }
    }

    public function prevSoal(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
            $this->dispatch('soal-rendered');
        }
    }

    public function toggleRagu(?int $soalId = null): void
    {
        $targetId = $soalId ?? $this->currentSoal?->id;
        if (!$targetId || !$this->sesi) return;

        $currentRagu = $this->userAnswers[$targetId]['is_ragu'] ?? false;
        $newRagu = !$currentRagu;

        $this->userAnswers[$targetId]['is_ragu'] = $newRagu;

        $updated = UjianJawaban::where('ujian_sesi_id', $this->sesi->id)
            ->where('soal_id', $targetId)
            ->update(['is_ragu' => $newRagu]);

        if (!$updated) {
            UjianJawaban::create([
                'ujian_sesi_id' => $this->sesi->id,
                'soal_id' => $targetId,
                'is_ragu' => $newRagu,
            ]);
        }
    }

    public function pilihJawabanOpsi(string $label, ?int $soalId = null): void
    {
        $targetId = $soalId ?? $this->currentSoal?->id;
        if (!$targetId || !$this->sesi || $this->is_submitted) return;

        // Single Choice (Pilihan Ganda & Benar / Salah)
        $this->userAnswers[$targetId]['pilihan'] = $label;

        $updated = UjianJawaban::where('ujian_sesi_id', $this->sesi->id)
            ->where('soal_id', $targetId)
            ->update(['jawaban_pilihan' => json_encode($label)]);

        if (!$updated) {
            UjianJawaban::create([
                'ujian_sesi_id' => $this->sesi->id,
                'soal_id' => $targetId,
                'jawaban_pilihan' => $label,
            ]);
        }
    }

    public function pilihJawabanMulti(string $label, ?int $soalId = null): void
    {
        $targetId = $soalId ?? $this->currentSoal?->id;
        if (!$targetId || !$this->sesi || $this->is_submitted) return;

        $current = $this->userAnswers[$targetId]['pilihan'] ?? [];
        if (!is_array($current)) $current = [];

        if (in_array($label, $current)) {
            $current = array_values(array_diff($current, [$label]));
        } else {
            $current[] = $label;
            sort($current);
        }

        $this->userAnswers[$targetId]['pilihan'] = $current;

        $updated = UjianJawaban::where('ujian_sesi_id', $this->sesi->id)
            ->where('soal_id', $targetId)
            ->update(['jawaban_pilihan' => json_encode($current)]);

        if (!$updated) {
            UjianJawaban::create([
                'ujian_sesi_id' => $this->sesi->id,
                'soal_id' => $targetId,
                'jawaban_pilihan' => $current,
            ]);
        }
    }

    public function simpanJawabanTeks(int|string $soalIdOrTeks, ?string $teks = null): void
    {
        if ($teks === null && is_string($soalIdOrTeks)) {
            $targetId = $this->currentSoal?->id;
            if (!$targetId) return;
            $teks = $soalIdOrTeks;
        } else {
            $targetId = (int) $soalIdOrTeks;
            $teks = $teks ?? '';
        }

        if (!$targetId || !$this->sesi || $this->is_submitted) return;

        $this->userAnswers[$targetId]['teks'] = $teks;

        $updated = UjianJawaban::where('ujian_sesi_id', $this->sesi->id)
            ->where('soal_id', $targetId)
            ->update(['jawaban_teks' => $teks]);

        if (!$updated) {
            UjianJawaban::create([
                'ujian_sesi_id' => $this->sesi->id,
                'soal_id' => $targetId,
                'jawaban_teks' => $teks,
            ]);
        }
    }

    public function simpanMatching(int $index, string $pilihanKanan, ?int $soalId = null): void
    {
        $targetId = $soalId ?? $this->currentSoal?->id;
        if (!$targetId || !$this->sesi || $this->is_submitted) return;

        $current = $this->userAnswers[$targetId]['pilihan'] ?? [];
        if (!is_array($current)) $current = [];

        $current[$index] = $pilihanKanan;
        $this->userAnswers[$targetId]['pilihan'] = $current;

        $updated = UjianJawaban::where('ujian_sesi_id', $this->sesi->id)
            ->where('soal_id', $targetId)
            ->update(['jawaban_pilihan' => json_encode($current)]);

        if (!$updated) {
            UjianJawaban::create([
                'ujian_sesi_id' => $this->sesi->id,
                'soal_id' => $targetId,
                'jawaban_pilihan' => $current,
            ]);
        }
    }

    #[Renderless]
    public function syncBatchJawaban(array $batch): array
    {
        if (!$this->sesi || $this->is_submitted) {
            return ['status' => 'finished'];
        }

        $this->sesi->refresh();
        if ($this->sesi->status === 'selesai' || ($this->sesi->ujian && $this->sesi->ujian->status === 'COMPLETED')) {
            $this->is_submitted = true;
            return ['status' => 'finished'];
        }

        if (empty($batch)) {
            return ['status' => 'ok'];
        }

        $validSoalIds = $this->bankSoal->soals()->pluck('id')->flip();

        foreach ($batch as $item) {
            if (!is_array($item)) continue;
            $soalId = (int) ($item['soal_id'] ?? 0);
            if (!$soalId || !$validSoalIds->has($soalId)) continue;

            $updateData = [];
            if (array_key_exists('pilihan', $item)) {
                $this->userAnswers[$soalId]['pilihan'] = $item['pilihan'];
                $updateData['jawaban_pilihan'] = $item['pilihan'] !== null ? json_encode($item['pilihan']) : null;
            }
            if (array_key_exists('teks', $item)) {
                $this->userAnswers[$soalId]['teks'] = $item['teks'];
                $updateData['jawaban_teks'] = $item['teks'];
            }
            if (array_key_exists('is_ragu', $item)) {
                $this->userAnswers[$soalId]['is_ragu'] = (bool) $item['is_ragu'];
                $updateData['is_ragu'] = (bool) $item['is_ragu'];
            }

            if (!empty($updateData)) {
                $updated = UjianJawaban::where('ujian_sesi_id', $this->sesi->id)
                    ->where('soal_id', $soalId)
                    ->update($updateData);

                if (!$updated) {
                    UjianJawaban::create(array_merge([
                        'ujian_sesi_id' => $this->sesi->id,
                        'soal_id' => $soalId,
                    ], $updateData));
                }
            }
        }

        return ['status' => 'ok'];
    }

    public function setZoom(string $level): void
    {
        if (in_array($level, ['sm', 'md', 'lg', 'xl'])) {
            $this->zoomLevel = $level;
            $this->dispatch('soal-rendered');
        }
    }

    public function getRecapProperty(): array
    {
        $total = $this->soals->count();
        $dijawab = 0;
        $ragu = 0;
        $belum = 0;

        foreach ($this->soals as $s) {
            $ans = $this->userAnswers[$s->id] ?? null;
            $hasAnswer = false;

            if ($ans) {
                if (!empty($ans['is_ragu'])) {
                    $ragu++;
                }

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

            if ($hasAnswer) {
                $dijawab++;
            } else {
                $belum++;
            }
        }

        return [
            'total' => $total,
            'dijawab' => $dijawab,
            'ragu' => $ragu,
            'belum' => $belum,
        ];
    }

    public function bukaKonfirmasiSelesai(): void
    {
        $this->showConfirmSubmitModal = true;
    }

    public function tutupKonfirmasiSelesai(): void
    {
        $this->showConfirmSubmitModal = false;
    }

    public function submitUjianFinal(): void
    {
        if (!$this->sesi || $this->is_submitted) return;

        $this->sesi->koreksiDanSelesaikan();
        $this->sesi->update(['device_token' => null]);
        $this->sesi->refresh();

        if (Auth::check() && Auth::user()->isSiswa()) {
            Auth::user()->update(['active_session_token' => null]);
        }

        $this->is_submitted = true;
        $this->showConfirmSubmitModal = false;

        // Beri tahu browser & aplikasi Android ExamGuard untuk melepas kunci Kiosk
        $this->dispatch('ujian-selesai-resmi');
    }

    public function autoSubmitOnTimeout(): void
    {
        $this->submitUjianFinal();
    }

    #[Renderless]
    public function triggerViolation(): void
    {
        $this->handleTabViolation();
    }

    #[Renderless]
    public function handleTabViolation(): void
    {
        if ($this->is_preview || $this->is_submitted) {
            return;
        }

        if ($this->sesi) {
            $this->violationCount = $this->sesi->catatPelanggaran();
        } else {
            $this->violationCount++;
        }

        $this->showViolationModal = true;
    }

    #[Renderless]
    public function tutupViolationModal(): void
    {
        if ($this->sesi && $this->sesi->fresh()?->isTerblokirPelanggaran()) {
            $this->violationCount = (int) $this->sesi->jumlah_pelanggaran;
            $this->showViolationModal = true;
            return;
        }

        $this->showViolationModal = false;
    }

    #[Renderless]
    public function cekStatusBukaKunci(): array
    {
        return $this->cekStatusSesiServer();
    }

    /**
     * Sinkronisasi ringan status sesi siswa dari server (deteksi paksa selesai oleh pengawas,
     * tambahan waktu +10 menit, maupun buka kunci pelanggaran tab).
     */
    #[Renderless]
    public function cekStatusSesiServer(): array
    {
        if (!$this->sesi || $this->is_preview) {
            return ['finished' => false, 'locked' => false, 'count' => 0];
        }

        $this->sesi->refresh();
        $ujianCompleted = $this->sesi->ujian && $this->sesi->ujian->fresh()?->status === 'COMPLETED';

        if ($this->sesi->status === 'selesai' || $ujianCompleted) {
            if ($this->sesi->status !== 'selesai') {
                $this->sesi->koreksiDanSelesaikan();
            }
            $this->is_submitted = true;

            return [
                'finished' => true,
                'locked' => false,
                'count' => (int) ($this->sesi->jumlah_pelanggaran ?? 0),
                'end_timestamp' => $this->sesi->waktu_selesai?->timestamp,
            ];
        }

        $this->violationCount = (int) ($this->sesi->jumlah_pelanggaran ?? 0);
        $locked = $this->sesi->isTerblokirPelanggaran();
        $this->showViolationModal = $locked;

        return [
            'finished' => false,
            'locked' => $locked,
            'count' => $this->violationCount,
            'end_timestamp' => $this->sesi->waktu_selesai?->timestamp,
        ];
    }

    public function refreshToFinishedState(): void
    {
        if ($this->sesi) {
            $this->sesi->refresh();
        }
        $this->is_submitted = true;
        $this->showConfirmSubmitModal = false;
        $this->showViolationModal = false;
        $this->dispatch('ujian-selesai-resmi');
    }

    public function render()
    {
        return view('livewire.exam-runner');
    }
}

