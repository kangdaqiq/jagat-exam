<?php

namespace App\Livewire;

use App\Models\BankSoal;
use App\Models\Soal;
use App\Models\UjianJawaban;
use App\Models\UjianSesi;
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

            $this->bankSoal = $this->sesi->bankSoal;
            $this->is_preview = false;

            if ($this->sesi->status === 'selesai' || $this->sesi->isSelesai()) {
                $this->is_submitted = true;
            } else {
                $this->loadSessionData();
            }
            return;
        }

        // 2. Jika diakses via bank soal langsung
        if ($bankSoal) {
            $this->bankSoal = $bankSoal->loadMissing(['mapel', 'soals.opsiJawabans']);
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

        // Inisialisasi baris jawaban di database untuk setiap butir soal
        $soals = $this->bankSoal->soals;
        foreach ($soals as $order => $soal) {
            UjianJawaban::create([
                'ujian_sesi_id' => $this->sesi->id,
                'soal_id' => $soal->id,
                'nomor_urut' => $order + 1,
                'jawaban_pilihan' => null,
                'jawaban_teks' => null,
                'is_ragu' => false,
            ]);
        }

        $this->loadSessionData();
    }

    protected function loadSessionData(): void
    {
        if (!$this->sesi) return;

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
        return $this->bankSoal->soals;
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
    public function syncBatchJawaban(array $batch): void
    {
        if (!$this->sesi || $this->is_submitted || empty($batch)) return;

        foreach ($batch as $item) {
            if (!is_array($item)) continue;
            $soalId = (int) ($item['soal_id'] ?? 0);
            if (!$soalId) continue;

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

        // Otomatis koreksi butir soal pilihan ganda, benar-salah, pg-kompleks, dan isian
        $totalSkor = 0.0;
        $jawabans = $this->sesi->jawabans()->with('soal.opsiJawabans')->get();

        foreach ($jawabans as $jawaban) {
            $soal = $jawaban->soal;
            if (!$soal) continue;

            $bobot = (float) $soal->bobot_nilai;
            $isBenar = false;
            $skor = 0.0;

            if ($soal->tipe_soal === 'pilihan_ganda' || $soal->tipe_soal === 'benar_salah') {
                $kunci = $soal->opsiJawabans->firstWhere('is_jawaban_benar', true);
                if ($kunci && $jawaban->jawaban_pilihan === $kunci->label) {
                    $isBenar = true;
                    $skor = $bobot;
                }
            } elseif ($soal->tipe_soal === 'pg_kompleks') {
                $kuncis = $soal->opsiJawabans->where('is_jawaban_benar', true)->pluck('label')->sort()->values()->toArray();
                $siswaAns = is_array($jawaban->jawaban_pilihan) ? collect($jawaban->jawaban_pilihan)->sort()->values()->toArray() : [];
                if ($kuncis === $siswaAns && !empty($kuncis)) {
                    $isBenar = true;
                    $skor = $bobot;
                }
            } elseif ($soal->tipe_soal === 'isian') {
                $kunciIsian = strtolower(trim($soal->kunci_isian ?? ''));
                $siswaIsian = strtolower(trim($jawaban->jawaban_teks ?? ''));
                if (filled($kunciIsian) && $kunciIsian === $siswaIsian) {
                    $isBenar = true;
                    $skor = $bobot;
                }
            }

            $jawaban->update([
                'is_benar' => $isBenar,
                'skor_didapat' => $skor,
            ]);

            $totalSkor += $skor;
        }

        $this->sesi->update([
            'status' => 'selesai',
            'submitted_at' => now(),
            'skor_total' => $totalSkor,
        ]);

        if ($this->sesi->ujian_id) {
            \App\Models\UjianPeserta::where('ujian_id', $this->sesi->ujian_id)
                ->where('user_id', $this->sesi->user_id)
                ->update([
                    'status' => 'SELESAI',
                    'nilai' => $totalSkor,
                    'waktu_selesai' => now(),
                ]);
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

    public function triggerViolation(): void
    {
        $this->handleTabViolation();
    }

    public function handleTabViolation(): void
    {
        if ($this->is_preview) {
            return;
        }

        $this->violationCount++;
        $this->showViolationModal = true;
    }

    public function tutupViolationModal(): void
    {
        $this->showViolationModal = false;
    }

    public function render()
    {
        return view('livewire.exam-runner');
    }
}

