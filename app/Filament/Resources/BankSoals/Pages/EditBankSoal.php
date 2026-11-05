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
    public string $ai_model = 'gemini-2.5-flash';
    public bool $ai_simpan_api_key = true;
    public bool $is_generating_ai = false;


    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->loadMissing(['mapel', 'kelases']);

        // Jika bank soal belum memiliki soal sama sekali, buatkan Soal No. 1 otomatis
        if ($this->record->soals()->count() === 0) {
            $firstSoal = $this->record->soals()->create([
                'nomor_urut' => 1,
                'tipe_soal' => 'pilihan_ganda',
                'bobot_nilai' => 2.0,
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
        }

        $first = $this->record->soals()->orderBy('nomor_urut', 'asc')->first();
        if ($first) {
            $this->selectSoal($first->id);
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

    public function selectSoal(int $id): void
    {
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

    public function simpanSoal(bool $advanceToNext = false): void
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

        Notification::make()
            ->title("Soal No. {$soal->nomor_urut} berhasil disimpan")
            ->success()
            ->send();

        if ($advanceToNext) {
            $next = $this->record->soals()
                ->where('nomor_urut', '>', $soal->nomor_urut)
                ->orderBy('nomor_urut', 'asc')
                ->first();

            if ($next) {
                $this->selectSoal($next->id);
            } else {
                $this->tambahSoal();
            }
        }
    }

    public function tambahSoal(): void
    {
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

        $this->selectSoal($newSoal->id);

        Notification::make()
            ->title("Soal No. {$nextNomor} ditambahkan")
            ->success()
            ->send();
    }

    public function duplikatSoal(?int $id = null): void
    {
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

        $this->selectSoal($replica->id);

        Notification::make()
            ->title("Soal diduplikasi menjadi No. {$nextNomor}")
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

        $next = $this->record->soals()->where('nomor_urut', '>=', $nomorHapus)->orderBy('nomor_urut', 'asc')->first()
            ?? $this->record->soals()->orderBy('nomor_urut', 'desc')->first();

        if ($next) {
            $this->selectSoal($next->id);
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
            $this->ai_model = config('ai.gemini.model', 'gemini-2.5-flash');
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
                'model' => $this->ai_model ?: 'gemini-2.5-flash',
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
                // Batch mode: Tambahkan butir-butir soal baru ke database
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
                    $this->selectSoal($firstNewId);
                }

                Notification::make()
                    ->title(count($generatedItems) . " butir soal baru berhasil digenerate dan ditambahkan!")
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
                $this->ai_model ?: 'gemini-2.5-flash'
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


    protected function getHeaderActions(): array
    {
        return [
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
