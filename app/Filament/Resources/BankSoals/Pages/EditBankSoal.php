<?php

namespace App\Filament\Resources\BankSoals\Pages;

use App\Filament\Resources\BankSoals\BankSoalResource;
use App\Models\BankSoal;
use App\Models\Mapel;
use App\Models\Soal;
use App\Services\AiQuestionService;
use Filament\Actions\Action;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class EditBankSoal extends Page
{
    use InteractsWithRecord;
    use WithFileUploads;

    protected static string $resource = BankSoalResource::class;

    protected string $view = 'filament.pages.kelola-bank-soal';

    protected static ?string $title = 'Studio Editor Soal';

    protected Width | string | null $maxContentWidth = Width::Full;

    // Active state
    public ?int $selectedSoalId = null;
    public string $search = '';

    // Soal Editor Properties
    public int $nomor_urut = 1;
    public string $tipe_soal = 'pilihan_ganda';
    public float $bobot_nilai = 2.0;
    public ?string $pertanyaan = '';
    public string $media_type = 'none';
    public $media_file_upload = null;
    public ?string $existing_media_file = null;
    public int $audio_max_play = 0;
    public array $opsi_jawabans = [];
    public ?string $kunci_isian = '';
    public ?string $rubrik_essay = '';
    public ?string $pembahasan = '';

    // AI Generator Modal Properties
    public bool $showAiModal = false;
    public string $ai_mode = 'current'; // 'current' | 'batch'
    public string $ai_topik = '';
    public string $ai_tipe_soal = 'pilihan_ganda';
    public string $ai_kesulitan = 'MOTS'; // 'LOTS' | 'MOTS' | 'HOTS'
    public int $ai_jumlah = 5; // 1-50
    public string $ai_instruksi = '';
    public string $ai_api_key = '';
    public string $ai_model = 'gemini-3.6-flash';
    public bool $ai_simpan_api_key = true;
    public bool $is_generating_ai = false;

    // Import dari Teks / Copas AI Chatbot Properties
    public bool $showImportTextModal = false;
    public string $raw_text_input = '';
    public string $import_mode = 'batch'; // 'batch' | 'current'
    public float $import_default_bobot = 2.0;
    public array $parsed_soals_preview = [];
    public bool $is_parsing_text = false;
    public bool $has_parsed = false;


    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        abort_unless(BankSoalResource::canEdit($this->record), 403, 'Anda tidak memiliki akses untuk mengedit Bank Soal ini.');
        $this->record->loadMissing(['mapel', 'kelases']);

        // Jika bank soal belum memiliki soal sama sekali, buatkan Soal No. 1 otomatis
        if ($this->record->soals()->count() === 0) {
            $firstSoal = $this->record->soals()->create([
                'nomor_urut' => 1,
                'tipe_soal' => 'pilihan_ganda',
                'bobot_nilai' => 100.0,
                'pertanyaan' => '<p>Tuliskan butir soal di sini...</p>',
                'media_type' => 'none',
            ]);

            foreach (['A', 'B', 'C', 'D'] as $index => $label) {
                $firstSoal->opsiJawabans()->create([
                    'label' => $label,
                    'teks_opsi' => '',
                    'is_jawaban_benar' => false,
                    'nomor_urut' => $index + 1,
                ]);
            }
        } else {
            // Otomatis seimbangkan ke 100 poin jika belum pas 100
            $totalCurrent = (float) $this->record->soals()->sum('bobot_nilai');
            if (abs($totalCurrent - 100.0) > 0.5 || (float) $this->record->total_bobot !== 100.0) {
                $this->record->autoDistributeBobot();
                $this->record->refresh();
            }
        }

        $first = $this->record->soals()->orderBy('nomor_urut', 'asc')->first();
        if ($first) {
            $this->selectSoal($first->id, false);
        }
    }

    public function getSoalsProperty()
    {
        $query = $this->record->soals()->with('opsiJawabans')->orderBy('nomor_urut', 'asc');

        if (filled($this->search)) {
            $query->where('pertanyaan', 'like', '%' . $this->search . '%');
        }

        return $query->get();
    }

    public function selectSoal(int $id, bool $autoSaveCurrent = true): void
    {
        if ($autoSaveCurrent && $this->selectedSoalId && $this->selectedSoalId !== $id) {
            $this->simpanSoal(false, true);
        }

        $soal = $this->record->soals()->with('opsiJawabans')->find($id);
        if (! $soal) return;

        $this->selectedSoalId = $soal->id;
        $this->nomor_urut = $soal->nomor_urut;
        $this->tipe_soal = $soal->tipe_soal;
        $this->bobot_nilai = (float) $soal->bobot_nilai;
        $this->pertanyaan = $soal->pertanyaan ?? '';
        $this->media_type = $soal->media_type ?? 'none';
        $this->existing_media_file = $soal->media_file;
        $this->media_file_upload = null;
        $this->audio_max_play = $soal->audio_max_play ?? 0;
        $this->kunci_isian = $soal->kunci_isian ?? '';
        $this->rubrik_essay = $soal->rubrik_essay ?? '';
        $this->pembahasan = $soal->pembahasan ?? '';

        $this->opsi_jawabans = $soal->opsiJawabans->map(fn ($o) => [
            'id' => $o->id,
            'label' => $o->label,
            'teks_opsi' => $o->teks_opsi ?? '',
            'gambar_opsi' => $o->gambar_opsi,
            'is_jawaban_benar' => (bool) $o->is_jawaban_benar,
            'pasangan_matching' => $o->pasangan_matching ?? '',
        ])->toArray();

        // Default opsi jika kosong
        if (empty($this->opsi_jawabans) && in_array($this->tipe_soal, ['pilihan_ganda', 'pg_kompleks', 'benar_salah'])) {
            $this->resetDefaultOptions($this->tipe_soal);
        }

        $this->dispatch('soal-loaded',
            pertanyaan: $this->pertanyaan,
            pembahasan: $this->pembahasan,
        );
    }

    public function updatedTipeSoal(string $newType): void
    {
        if ($newType === 'benar_salah') {
            $this->opsi_jawabans = [
                ['label' => 'A', 'teks_opsi' => 'Benar', 'gambar_opsi' => null, 'is_jawaban_benar' => false, 'pasangan_matching' => ''],
                ['label' => 'B', 'teks_opsi' => 'Salah', 'gambar_opsi' => null, 'is_jawaban_benar' => false, 'pasangan_matching' => ''],
            ];
        } elseif ($newType === 'menjodohkan') {
            $this->opsi_jawabans = [
                ['label' => '1', 'teks_opsi' => '', 'gambar_opsi' => null, 'is_jawaban_benar' => false, 'pasangan_matching' => ''],
                ['label' => '2', 'teks_opsi' => '', 'gambar_opsi' => null, 'is_jawaban_benar' => false, 'pasangan_matching' => ''],
                ['label' => '3', 'teks_opsi' => '', 'gambar_opsi' => null, 'is_jawaban_benar' => false, 'pasangan_matching' => ''],
            ];
        } elseif (in_array($newType, ['pilihan_ganda', 'pg_kompleks']) && count($this->opsi_jawabans) < 2) {
            $this->resetDefaultOptions($newType);
        } elseif ($newType === 'pilihan_ganda') {
            // Jika pindah ke pilihan ganda, pastikan hanya 1 kunci yang aktif
            $found = false;
            foreach ($this->opsi_jawabans as $i => $opt) {
                if (!empty($opt['is_jawaban_benar'])) {
                    $this->opsi_jawabans[$i]['is_jawaban_benar'] = ! $found;
                    $found = true;
                }
            }
        }
    }

    protected function resetDefaultOptions(string $type): void
    {
        $this->opsi_jawabans = [];
        foreach (['A', 'B', 'C', 'D'] as $label) {
            $this->opsi_jawabans[] = [
                'label' => $label,
                'teks_opsi' => '',
                'gambar_opsi' => null,
                'is_jawaban_benar' => false,
                'pasangan_matching' => '',
            ];
        }
    }

    public function tambahOpsi(): void
    {
        $count = count($this->opsi_jawabans);
        $this->opsi_jawabans[] = [
            'label' => chr(65 + $count),
            'teks_opsi' => '',
            'gambar_opsi' => null,
            'is_jawaban_benar' => false,
            'pasangan_matching' => '',
        ];
    }

    public function hapusOpsi(int $index): void
    {
        unset($this->opsi_jawabans[$index]);
        $this->opsi_jawabans = array_values($this->opsi_jawabans);

        // Relabel otomatis A, B, C, D...
        if ($this->tipe_soal !== 'menjodohkan') {
            foreach ($this->opsi_jawabans as $i => $opt) {
                $this->opsi_jawabans[$i]['label'] = chr(65 + $i);
            }
        }
    }

    public function pilihKunciJawaban(int $index): void
    {
        if ($this->tipe_soal === 'pg_kompleks') {
            // Multi-choice: toggle checkbox ini saja
            $this->opsi_jawabans[$index]['is_jawaban_benar'] = !($this->opsi_jawabans[$index]['is_jawaban_benar'] ?? false);
        } else {
            // Single-choice (Pilihan Ganda & Benar/Salah):
            // Klik checkbox: centang yang ini, dan uncheck semua yang lain!
            foreach ($this->opsi_jawabans as $i => $opt) {
                $this->opsi_jawabans[$i]['is_jawaban_benar'] = ($i === $index);
            }
        }
    }

    public function simpanSoal(bool $advanceToNext = false, bool $silent = false): void
    {
        if (! $this->selectedSoalId) return;

        $soal = $this->record->soals()->find($this->selectedSoalId);
        if (! $soal) return;

        // Upload media file jika ada
        $mediaPath = $this->existing_media_file;
        if ($this->media_file_upload) {
            $mediaPath = $this->media_file_upload->store('soal-media', 'public');
            $this->existing_media_file = $mediaPath;
            $this->media_file_upload = null;
        }

        $soal->update([
            'nomor_urut' => $this->nomor_urut,
            'tipe_soal' => $this->tipe_soal,
            'bobot_nilai' => $this->bobot_nilai,
            'pertanyaan' => $this->pertanyaan,
            'media_type' => $this->media_type,
            'media_file' => $mediaPath,
            'audio_max_play' => $this->audio_max_play,
            'kunci_isian' => $this->kunci_isian,
            'rubrik_essay' => $this->rubrik_essay,
            'pembahasan' => $this->pembahasan,
        ]);

        // Simpan Opsi Jawaban
        if (in_array($this->tipe_soal, ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'])) {
            $soal->opsiJawabans()->delete();
            $order = 1;
            foreach ($this->opsi_jawabans as $opt) {
                $soal->opsiJawabans()->create([
                    'label' => $opt['label'] ?? chr(64 + $order),
                    'teks_opsi' => $opt['teks_opsi'] ?? '',
                    'gambar_opsi' => $opt['gambar_opsi'] ?? null,
                    'is_jawaban_benar' => !empty($opt['is_jawaban_benar']),
                    'pasangan_matching' => $opt['pasangan_matching'] ?? null,
                    'nomor_urut' => $order++,
                ]);
            }
        }

        if (! $silent) {
            Notification::make()
                ->title("Soal No. {$soal->nomor_urut} berhasil disimpan")
                ->success()
                ->send();
        }

        if ($advanceToNext) {
            $next = $this->record->soals()
                ->where('nomor_urut', '>', $soal->nomor_urut)
                ->orderBy('nomor_urut', 'asc')
                ->first();

            if ($next) {
                $this->selectSoal($next->id, false);
            } else {
                $this->tambahSoal();
            }
        }
    }

    public function simpanSemua(): void
    {
        if ($this->selectedSoalId) {
            $this->simpanSoal(false, true);
        }

        $this->record->refresh();
        $totalSoal = $this->record->soals()->count();

        Notification::make()
            ->title("Semua perubahan soal ({$totalSoal} butir) berhasil disimpan")
            ->success()
            ->send();
    }

    public function tambahSoal(): void
    {
        if ($this->selectedSoalId) {
            $this->simpanSoal(false, true);
        }

        $nextNomor = ((int) $this->record->soals()->max('nomor_urut')) + 1;
        $newSoal = $this->record->soals()->create([
            'nomor_urut' => $nextNomor,
            'tipe_soal' => 'pilihan_ganda',
            'bobot_nilai' => 2.0,
            'pertanyaan' => '<p></p>',
            'media_type' => 'none',
        ]);

        foreach (['A', 'B', 'C', 'D'] as $index => $label) {
            $newSoal->opsiJawabans()->create([
                'label' => $label,
                'teks_opsi' => '',
                'is_jawaban_benar' => false,
                'nomor_urut' => $index + 1,
            ]);
        }

        $this->record->autoDistributeBobot();
        $this->record->refresh();
        $this->selectSoal($newSoal->id, false);

        Notification::make()
            ->title("Soal No. {$nextNomor} ditambahkan (Bobot otomatis disesuaikan ke 100 poin)")
            ->success()
            ->send();
    }

    public function duplikatSoal(?int $id = null): void
    {
        if ($this->selectedSoalId) {
            $this->simpanSoal(false, true);
        }

        $targetId = $id ?? $this->selectedSoalId;
        $soal = $this->record->soals()->with('opsiJawabans')->find($targetId);
        if (! $soal) return;

        $nextNomor = ((int) $this->record->soals()->max('nomor_urut')) + 1;
        $replica = $soal->replicate(['id', 'created_at', 'updated_at']);
        $replica->nomor_urut = $nextNomor;
        $replica->save();

        foreach ($soal->opsiJawabans as $opt) {
            $replica->opsiJawabans()->create($opt->only([
                'label', 'teks_opsi', 'gambar_opsi', 'is_jawaban_benar', 'pasangan_matching', 'nomor_urut',
            ]));
        }

        $this->record->autoDistributeBobot();
        $this->record->refresh();
        $this->selectSoal($replica->id, false);

        Notification::make()
            ->title("Soal diduplikasi menjadi No. {$nextNomor} (Bobot otomatis 100 poin)")
            ->success()
            ->send();
    }

    public function hapusSoal(?int $id = null): void
    {
        $targetId = $id ?? $this->selectedSoalId;
        $soal = $this->record->soals()->find($targetId);
        if (! $soal) return;

        $nomorHapus = $soal->nomor_urut;
        $soal->opsiJawabans()->delete();
        $soal->delete();

        // Rapikan kembali nomor urut 1, 2, 3...
        $remaining = $this->record->soals()->orderBy('nomor_urut', 'asc')->get();
        $num = 1;
        foreach ($remaining as $r) {
            $r->update(['nomor_urut' => $num++]);
        }

        $this->record->autoDistributeBobot();
        $this->record->refresh();

        $next = $this->record->soals()->where('nomor_urut', '>=', $nomorHapus)->orderBy('nomor_urut', 'asc')->first()
            ?? $this->record->soals()->orderBy('nomor_urut', 'desc')->first();

        if ($next) {
            $this->selectSoal($next->id, false);
        } else {
            $this->tambahSoal();
        }

        Notification::make()
            ->title("Soal No. {$nomorHapus} telah dihapus")
            ->warning()
            ->send();
    }

    public function hapusMediaFile(): void
    {
        $this->existing_media_file = null;
        $this->media_file_upload = null;
    }

    public function openAiModal(string $mode = 'current'): void
    {
        $this->ai_mode = $mode;
        $this->ai_tipe_soal = $this->tipe_soal;
        if (empty($this->ai_model)) {
            $this->ai_model = config('ai.gemini.model', 'gemini-3.6-flash');
        }
        if (empty($this->ai_api_key)) {
            $this->ai_api_key = config('ai.gemini.api_key', '');
        }
        if (empty($this->ai_topik)) {
            $this->ai_topik = $this->record->judul ?? '';
        }
        $this->showAiModal = true;
    }

    public function closeAiModal(): void
    {
        $this->showAiModal = false;
    }

    /**
     * Hapus butir soal dummy bawaan (No. 1 kosong) jika ada,
     * sebelum batch AI generate atau batch text parser menambahkan soal baru.
     */
    protected function cleanupInitialDummySoal(): void
    {
        if ($this->record->soals()->count() === 1) {
            $first = $this->record->soals()->first();
            if ($first) {
                $cleanText = trim(strip_tags((string) $first->pertanyaan));
                $isPlaceholder = in_array($cleanText, [
                    '',
                    'Tuliskan butir soal di sini...',
                    'Tuliskan butir soal di sini',
                    '&nbsp;',
                ]);
                $hasNoCorrectKey = ! $first->opsiJawabans()->where('is_jawaban_benar', true)->exists();

                if ($isPlaceholder && $hasNoCorrectKey) {
                    $first->opsiJawabans()->delete();
                    $first->delete();
                    $this->selectedSoalId = null;
                }
            }
        }
    }

    public function generateWithAi(?AiQuestionService $aiService = null): void
    {
        $aiService = $aiService ?? app(AiQuestionService::class);

        $this->validate([

            'ai_topik' => 'required|min:3',
            'ai_jumlah' => 'required|integer|min:1|max:50',
        ], [
            'ai_topik.required' => 'Topik atau materi soal wajib diisi.',
            'ai_topik.min' => 'Topik soal minimal 3 karakter.',
            'ai_jumlah.max' => 'Batas maksimal generate soal masal adalah 50 butir.',
        ]);

        $this->is_generating_ai = true;

        try {
            $apiKey = trim($this->ai_api_key);
            if (filled($apiKey) && $this->ai_simpan_api_key) {
                $aiService->saveApiKeyToEnv($apiKey, 'gemini');
                config(['ai.gemini.api_key' => $apiKey]);
            }

            $jumlahToGenerate = $this->ai_mode === 'current' ? 1 : $this->ai_jumlah;

            $generatedItems = $aiService->generateSoals([
                'topik' => $this->ai_topik,
                'mapel' => $this->record->mapel?->nama_mapel ?? 'Mata Pelajaran',
                'tingkat' => $this->record->tingkat ? "Kelas {$this->record->tingkat}" : 'Semua Tingkat',
                'tipe_soal' => $this->ai_tipe_soal,
                'kesulitan' => $this->ai_kesulitan,
                'jumlah' => $jumlahToGenerate,
                'instruksi_tambahan' => $this->ai_instruksi,
                'api_key' => $apiKey ?: null,
                'provider' => 'gemini',
                'model' => $this->ai_model ?: 'gemini-3.6-flash',
            ]);

            if (empty($generatedItems)) {
                throw new \Exception('AI tidak menghasilkan butir soal.');
            }

            if ($this->ai_mode === 'current') {
                // Terapkan ke butir soal yang sedang aktif di editor
                $first = $generatedItems[0];
                $this->tipe_soal = $first['tipe_soal'];
                $this->bobot_nilai = $first['bobot_nilai'];
                $this->pertanyaan = $first['pertanyaan'];
                $this->opsi_jawabans = $first['opsi_jawabans'];
                $this->kunci_isian = $first['kunci_isian'];
                $this->rubrik_essay = $first['rubrik_essay'];
                $this->pembahasan = $first['pembahasan'];

                // Simpan langsung perubahan ke database untuk soal aktif
                $this->simpanSoal(false);

                // Sinkronkan editor tampilan
                $this->dispatch('soal-loaded',
                    pertanyaan: $this->pertanyaan,
                    pembahasan: $this->pembahasan,
                );

                Notification::make()
                    ->title("Soal No. {$this->nomor_urut} berhasil digenerate oleh AI!")
                    ->success()
                    ->send();
            } else {
                // Batch mode: Bersihkan soal dummy bawaan No. 1 jika masih kosong
                $this->cleanupInitialDummySoal();

                // Tambahkan butir-butir soal baru ke database
                $startNomor = ((int) $this->record->soals()->max('nomor_urut')) + 1;
                $firstNewId = null;

                foreach ($generatedItems as $item) {
                    $newSoal = $this->record->soals()->create([
                        'nomor_urut' => $startNomor++,
                        'tipe_soal' => $item['tipe_soal'],
                        'bobot_nilai' => $item['bobot_nilai'],
                        'pertanyaan' => $item['pertanyaan'],
                        'media_type' => 'none',
                        'kunci_isian' => $item['kunci_isian'] ?? '',
                        'rubrik_essay' => $item['rubrik_essay'] ?? '',
                        'pembahasan' => $item['pembahasan'] ?? '',
                    ]);

                    if (!$firstNewId) $firstNewId = $newSoal->id;

                    if (!empty($item['opsi_jawabans'])) {
                        foreach ($item['opsi_jawabans'] as $order => $opt) {
                            $newSoal->opsiJawabans()->create([
                                'label' => $opt['label'] ?? chr(65 + $order),
                                'teks_opsi' => $opt['teks_opsi'] ?? '',
                                'gambar_opsi' => null,
                                'is_jawaban_benar' => !empty($opt['is_jawaban_benar']),
                                'pasangan_matching' => $opt['pasangan_matching'] ?? null,
                                'nomor_urut' => $order + 1,
                            ]);
                        }
                    }
                }

                if ($firstNewId) {
                    $this->record->autoDistributeBobot();
                    $this->record->refresh();
                    $this->selectSoal($firstNewId, false);
                }

                Notification::make()
                    ->title(count($generatedItems) . " butir soal baru berhasil digenerate (Bobot otomatis 100 poin)!")
                    ->success()
                    ->send();
            }

            $this->showAiModal = false;
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Generate Soal dengan AI')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        } finally {
            $this->is_generating_ai = false;
        }
    }

    public function generatePembahasanWithAi(?AiQuestionService $aiService = null): void
    {
        $aiService = $aiService ?? app(AiQuestionService::class);

        if (empty(trim(strip_tags($this->pertanyaan)))) {

            Notification::make()->title('Teks pokok soal masih kosong.')->warning()->send();
            return;
        }

        try {
            $apiKey = trim($this->ai_api_key);
            $pembahasan = $aiService->generatePembahasan(
                $this->pertanyaan,
                $this->opsi_jawabans,
                filled($apiKey) ? $apiKey : null,
                'gemini',
                $this->ai_model ?: 'gemini-3.5-flash'
            );

            $this->pembahasan = $pembahasan;

            Notification::make()
                ->title('Pembahasan berhasil dibuat oleh AI!')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Membuat Pembahasan AI')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function openImportTextModal(string $mode = 'batch'): void
    {
        $this->import_mode = $mode;
        $this->showImportTextModal = true;
        $this->has_parsed = false;
        $this->parsed_soals_preview = [];
        if (empty($this->ai_model)) {
            $this->ai_model = config('ai.gemini.model', 'gemini-3.6-flash');
        }
        if (empty($this->ai_api_key)) {
            $this->ai_api_key = config('ai.gemini.api_key', '');
        }
    }

    public function closeImportTextModal(): void
    {
        $this->showImportTextModal = false;
    }

    public function resetImportTextPreview(): void
    {
        $this->has_parsed = false;
        $this->parsed_soals_preview = [];
    }

    public function loadSampleText(): void
    {
        $this->raw_text_input = "1. Jika diketahui f(x) = 2x^2 + 5x - 3, berapakah nilai f(2)?\n" .
            "A. 11\n" .
            "B. 15\n" .
            "C. 18\n" .
            "D. 21\n" .
            "Kunci: B\n" .
            "Pembahasan: Substitusikan x = 2 ke fungsi: f(2) = 2(2)^2 + 5(2) - 3 = 8 + 10 - 3 = 15.\n\n" .
            "2. Organel sel yang berfungsi sebagai tempat berlangsungnya respirasi aerob dan penghasil utama energi (ATP) adalah...\n" .
            "A. Ribosom\n" .
            "B. Lisosom\n" .
            "C. Mitokondria\n" .
            "D. Badan Golgi\n" .
            "Kunci: C\n" .
            "Pembahasan: Mitokondria adalah penghasil energi seluler (ATP) melalui siklus Krebs dan rantai transpor elektron.";
    }

    public function hapusParsedItem(int $index): void
    {
        unset($this->parsed_soals_preview[$index]);
        $this->parsed_soals_preview = array_values($this->parsed_soals_preview);
        if (empty($this->parsed_soals_preview)) {
            $this->has_parsed = false;
        }
    }

    public function parseTextWithAi(?AiQuestionService $aiService = null): void
    {
        $aiService = $aiService ?? app(AiQuestionService::class);

        $this->validate([
            'raw_text_input' => 'required|string|min:10',
            'import_default_bobot' => 'required|numeric|min:0.1',
        ], [
            'raw_text_input.required' => 'Silakan tempelkan (paste) teks soal dari chatbot terlebih dahulu.',
            'raw_text_input.min' => 'Teks soal terlalu pendek (minimal 10 karakter).',
            'import_default_bobot.required' => 'Bobot default wajib diisi.',
            'import_default_bobot.min' => 'Bobot minimal adalah 0.1 poin.',
        ]);

        $this->is_parsing_text = true;

        try {
            $apiKey = trim($this->ai_api_key);
            if (filled($apiKey) && $this->ai_simpan_api_key) {
                $aiService->saveApiKeyToEnv($apiKey, 'gemini');
                config(['ai.gemini.api_key' => $apiKey]);
            }

            $results = $aiService->parseRawTextToSoals($this->raw_text_input, [
                'bobot_nilai' => $this->import_default_bobot,
                'api_key' => $apiKey ?: null,
                'provider' => 'gemini',
                'model' => $this->ai_model ?: 'gemini-3.6-flash',
            ]);

            if (empty($results)) {
                throw new \Exception('Tidak dapat mendeteksi atau membedah butir soal dari teks yang diberikan.');
            }

            $this->parsed_soals_preview = $results;
            $this->has_parsed = true;

            Notification::make()
                ->title(count($results) . ' butir soal berhasil dibedah oleh AI!')
                ->body('Silakan tinjau butir soal sebelum disimpan ke Bank Soal.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Membedah Teks Soal')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        } finally {
            $this->is_parsing_text = false;
        }
    }

    public function simpanParsedSoals(): void
    {
        if (empty($this->parsed_soals_preview)) {
            Notification::make()
                ->title('Tidak ada butir soal untuk disimpan.')
                ->warning()
                ->send();
            return;
        }

        if ($this->import_mode === 'current') {
            // Terapkan butir pertama ke soal yang sedang aktif
            $item = $this->parsed_soals_preview[0];
            $this->tipe_soal = $item['tipe_soal'];
            $this->bobot_nilai = (float) ($item['bobot_nilai'] ?? $this->import_default_bobot);
            $this->pertanyaan = $item['pertanyaan'];
            $this->opsi_jawabans = $item['opsi_jawabans'] ?? [];
            $this->kunci_isian = $item['kunci_isian'] ?? '';
            $this->rubrik_essay = $item['rubrik_essay'] ?? '';
            $this->pembahasan = $item['pembahasan'] ?? '';

            $this->simpanSoal(false);

            $this->dispatch('soal-loaded',
                pertanyaan: $this->pertanyaan,
                pembahasan: $this->pembahasan,
            );

            Notification::make()
                ->title("Soal No. {$this->nomor_urut} berhasil diperbarui dari teks!")
                ->success()
                ->send();
        } else {
            // Batch mode: Bersihkan soal dummy bawaan No. 1 jika masih kosong
            $this->cleanupInitialDummySoal();

            // Tambahkan butir-butir baru ke bank soal
            $startNomor = ((int) $this->record->soals()->max('nomor_urut')) + 1;
            $firstNewId = null;

            foreach ($this->parsed_soals_preview as $item) {
                $newSoal = $this->record->soals()->create([
                    'nomor_urut' => $startNomor++,
                    'tipe_soal' => $item['tipe_soal'],
                    'bobot_nilai' => (float) ($item['bobot_nilai'] ?? $this->import_default_bobot),
                    'pertanyaan' => $item['pertanyaan'],
                    'media_type' => 'none',
                    'kunci_isian' => $item['kunci_isian'] ?? '',
                    'rubrik_essay' => $item['rubrik_essay'] ?? '',
                    'pembahasan' => $item['pembahasan'] ?? '',
                ]);

                if (!$firstNewId) {
                    $firstNewId = $newSoal->id;
                }

                if (!empty($item['opsi_jawabans'])) {
                    foreach ($item['opsi_jawabans'] as $order => $opt) {
                        $newSoal->opsiJawabans()->create([
                            'label' => $opt['label'] ?? chr(65 + $order),
                            'teks_opsi' => $opt['teks_opsi'] ?? '',
                            'gambar_opsi' => null,
                            'is_jawaban_benar' => !empty($opt['is_jawaban_benar']),
                            'pasangan_matching' => $opt['pasangan_matching'] ?? null,
                            'nomor_urut' => $order + 1,
                        ]);
                    }
                }
            }

            if ($this->import_default_bobot == 2.0) {
                // Otomatis sesuaikan bobot seluruh bank soal ke skala 100 poin (rasio 75:25)
                $this->record->autoDistributeBobot();
                $this->record->refresh();
            }

            if ($firstNewId) {
                $this->selectSoal($firstNewId, false);
            }

            Notification::make()
                ->title(count($this->parsed_soals_preview) . " butir soal baru berhasil ditambahkan!")
                ->success()
                ->send();
        }

        $this->showImportTextModal = false;
        $this->has_parsed = false;
        $this->parsed_soals_preview = [];
        $this->raw_text_input = '';
    }


    protected function getHeaderActions(): array
    {
        return [
            Action::make('sesuaikanBobot')
                ->label('Bobot Otomatis (100 Poin)')
                ->icon(Heroicon::OutlinedCalculator)
                ->color('success')
                ->tooltip('Bobot soal sudah otomatis terdistribusi 100 poin secara default (Objektif 75% : Subjektif 25%). Klik untuk sinkronisasi ulang kapan saja.')
                ->requiresConfirmation()
                ->modalHeading('Sinkronisasi Bobot Soal Menjadi 100 Poin?')
                ->modalDescription('Sistem secara default telah membagi bobot seluruh butir soal menjadi total 100 poin dengan rasio Objektif : Essay/Isian = 75 : 25 (atau 100% jika hanya ada satu jenis soal). Klik konfirmasi untuk sinkronisasi ulang sekarang.')
                ->action(function () {
                    $this->record->autoDistributeBobot();
                    $this->record->refresh();
                    if ($this->selectedSoalId) {
                        $this->selectSoal($this->selectedSoalId);
                    }
                    Notification::make()->title('Bobot seluruh butir soal berhasil disesuaikan ke total 100 poin (75:25)')->success()->send();
                }),

            Action::make('pengaturanPaket')
                ->label('Pengaturan Paket')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->color('gray')
                ->modalHeading('Pengaturan Paket Bank Soal')
                ->modalWidth(Width::FourExtraLarge)
                ->form([
                    Grid::make(3)->schema([
                        TextInput::make('judul')
                            ->label('Judul Bank Soal')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('kode_bank')
                            ->label('Kode Soal (Bank Soal)')
                            ->disabled()
                            ->dehydrated(),

                        Select::make('mapel_id')
                            ->label('Mata Pelajaran')
                            ->options(Mapel::pluck('nama_mapel', 'id'))
                            ->required(),

                        Select::make('tingkat')
                            ->label('Tingkat')
                            ->options([
                                'Semua' => 'Semua Tingkat',
                                '10' => 'Kelas X (10)',
                                '11' => 'Kelas XI (11)',
                                '12' => 'Kelas XII (12)',
                            ]),

                        TextInput::make('total_bobot')
                            ->label('Target Bobot')
                            ->numeric()
                            ->suffix('poin'),
                    ]),

                    Grid::make(2)->schema([
                        Toggle::make('acak_soal')
                            ->label('Acak Urutan Soal')
                            ->helperText('Urutan nomor butir soal diacak untuk tiap siswa.')
                            ->default(true),

                        Toggle::make('acak_opsi')
                            ->label('Acak Opsi Pilihan Ganda')
                            ->helperText('Posisi pilihan jawaban (A, B, C, D, E) diacak untuk tiap siswa.')
                            ->default(true),
                    ]),

                    Textarea::make('deskripsi')
                        ->label('Petunjuk Pengerjaan Ujian')
                        ->rows(3),
                ])
                ->fillForm(fn () => [
                    'judul' => $this->record->judul,
                    'kode_bank' => $this->record->kode_bank,
                    'mapel_id' => $this->record->mapel_id,
                    'tingkat' => $this->record->tingkat,
                    'total_bobot' => $this->record->total_bobot,
                    'deskripsi' => $this->record->deskripsi,
                    'acak_soal' => $this->record->acak_soal ?? true,
                    'acak_opsi' => $this->record->acak_opsi ?? true,
                ])
                ->action(function (array $data): void {
                    $this->record->update($data);
                    Notification::make()->title('Informasi paket berhasil diperbarui')->success()->send();
                }),

            Action::make('kembali')
                ->label('Daftar Bank Soal')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(BankSoalResource::getUrl('index')),
        ];
    }
}
