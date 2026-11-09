<?php

namespace App\Filament\Resources\BankSoals\Schemas;

use App\Filament\Plugins\MathematicsPlugin;
use App\Services\MediaCompressionService;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Form editor satu butir soal (dipakai di modal Tambah / Edit Butir Soal).
 *
 * Layout 2 kolom:
 *  - Kiri (lebar)  : Pertanyaan, Jawaban, Pembahasan
 *  - Kanan (sempit): Pengaturan soal & Lampiran media
 */
class SoalForm
{
    public const TIPE_SOAL = [
        'pilihan_ganda' => 'Pilihan Ganda',
        'pg_kompleks' => 'PG Kompleks (jawaban > 1)',
        'benar_salah' => 'Benar / Salah',
        'menjodohkan' => 'Menjodohkan',
        'isian' => 'Isian Singkat',
        'essay' => 'Uraian / Essay',
    ];

    /** Tipe soal yang memakai daftar opsi berlabel A, B, C ... */
    public const TIPE_OPSI = ['pilihan_ganda', 'pg_kompleks', 'benar_salah'];

    /** Tipe soal yang hanya boleh punya satu kunci jawaban. */
    public const TIPE_SATU_KUNCI = ['pilihan_ganda', 'benar_salah'];

    /**
     * @param  Closure|null  $nextNomorUrut  Closure yang mengembalikan nomor urut berikutnya.
     */
    public static function configure(Schema $schema, ?Closure $nextNomorUrut = null): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                // ================= KOLOM KIRI (UTAMA) =================
                Group::make([
                    self::pertanyaanSection(),
                    self::jawabanSection(),
                    self::pembahasanSection(),
                ])->columnSpan(['default' => 1, 'lg' => 2]),

                // ================= KOLOM KANAN (SIDEBAR) =================
                Group::make([
                    self::pengaturanSection($nextNomorUrut),
                    self::mediaSection(),
                ])->columnSpan(['default' => 1, 'lg' => 1]),
            ]);
    }

    // ------------------------------------------------------------------
    // SECTIONS
    // ------------------------------------------------------------------

    protected static function pertanyaanSection(): Section
    {
        return Section::make('Pertanyaan')
            ->icon(Heroicon::OutlinedQuestionMarkCircle)
            ->compact()
            ->schema([
                RichEditor::make('pertanyaan')
                    ->hiddenLabel()
                    ->placeholder('Tulis pertanyaan di sini... Ketik $rumus$ untuk membuat rumus matematika langsung (contoh: $x^2 + y^2 = 25$)')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('soal-attachments')
                    ->plugins([
                        MathematicsPlugin::make(),
                    ])
                    ->formatStateUsing(function ($state) {
                        if (! $state) return $state;
                        return preg_replace_callback('/\$([^\$\n]+?)\$/', function ($m) {
                            $latex = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                            return '<span data-type="inline-math" data-latex="' . $latex . '">$' . $latex . '$</span>';
                        }, (string) $state);
                    })
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript'],
                        ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                        ['h2', 'h3', 'bulletList', 'orderedList', 'blockquote'],
                        ['table', 'attachFiles', 'link'],
                        ['undo', 'redo'],
                    ])
                    ->required()
                    ->columnSpanFull(),

                // Bantuan rumus + pratinjau (mencari editor di dalam section yang sama)
                ViewField::make('preview_pertanyaan')
                    ->hiddenLabel()
                    ->dehydrated(false)
                    ->view('filament.components.math-preview')
                    ->columnSpanFull(),
            ]);
    }

    protected static function jawabanSection(): Section
    {
        return Section::make(fn (Get $get) => match ($get('tipe_soal')) {
            'benar_salah' => 'Jawaban Benar / Salah',
            'menjodohkan' => 'Pasangan Menjodohkan',
            'isian' => 'Kunci Jawaban Isian',
            'essay' => 'Rubrik Penilaian Essay',
            'pg_kompleks' => 'Pilihan Jawaban (boleh lebih dari 1 kunci)',
            default => 'Pilihan Jawaban',
        })
            ->description(fn (Get $get) => match ($get('tipe_soal')) {
                'pilihan_ganda', 'benar_salah' => 'Centang pada satu jawaban yang benar sebagai kunci. Label A, B, C diisi otomatis.',
                'pg_kompleks' => 'Centang semua jawaban yang benar.',
                'menjodohkan' => 'Tulis pernyataan kiri dan pasangan jawabannya di kanan. Urutan diacak saat ujian.',
                default => null,
            })
            ->icon(Heroicon::OutlinedCheckCircle)
            ->compact()
            ->schema([
                self::opsiRepeater(),

                TextInput::make('kunci_isian')
                    ->label('Kunci Jawaban')
                    ->placeholder('Contoh: Jakarta; DKI Jakarta')
                    ->helperText('Pisahkan dengan titik koma (;) bila ada beberapa variasi jawaban benar.')
                    ->visible(fn (Get $get) => $get('tipe_soal') === 'isian')
                    ->required(fn (Get $get) => $get('tipe_soal') === 'isian')
                    ->columnSpanFull(),

                Textarea::make('rubrik_essay')
                    ->label('Rubrik / Pedoman Penilaian')
                    ->placeholder('Kriteria skor penilaian jawaban siswa...')
                    ->rows(4)
                    ->autosize()
                    ->visible(fn (Get $get) => $get('tipe_soal') === 'essay')
                    ->columnSpanFull(),
            ]);
    }

    protected static function pembahasanSection(): Section
    {
        return Section::make('Pembahasan')
            ->description('Opsional — ditampilkan ke siswa saat evaluasi hasil.')
            ->icon(Heroicon::OutlinedLightBulb)
            ->compact()
            ->collapsible()
            ->collapsed(fn (?Model $record) => blank(trim(strip_tags((string) $record?->pembahasan))))
            ->schema([
                RichEditor::make('pembahasan')
                    ->hiddenLabel()
                    ->placeholder('Penjelasan / langkah penyelesaian soal... Ketik $rumus$ untuk rumus matematika.')
                    ->plugins([
                        MathematicsPlugin::make(),
                    ])
                    ->formatStateUsing(function ($state) {
                        if (! $state) return $state;
                        return preg_replace_callback('/\$([^\$\n]+?)\$/', function ($m) {
                            $latex = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
                            return '<span data-type="inline-math" data-latex="' . $latex . '">$' . $latex . '$</span>';
                        }, (string) $state);
                    })
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'subscript', 'superscript'],
                        ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                        ['bulletList', 'orderedList', 'table'],
                        ['undo', 'redo'],
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function pengaturanSection(?Closure $nextNomorUrut): Section
    {
        return Section::make('Pengaturan')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->compact()
            ->schema([
                Select::make('tipe_soal')
                    ->label('Tipe Soal')
                    ->options(self::TIPE_SOAL)
                    ->default('pilihan_ganda')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (?string $state, ?string $old, Get $get, Set $set) => self::onTipeSoalChanged($state, $old, $get, $set)),

                Grid::make(2)->schema([
                    TextInput::make('nomor_urut')
                        ->label('No. Urut')
                        ->numeric()
                        ->minValue(1)
                        ->default($nextNomorUrut ?? 1)
                        ->required(),

                    TextInput::make('bobot_nilai')
                        ->label('Bobot')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.5)
                        ->default(2)
                        ->suffix('poin')
                        ->required(),
                ]),
            ]);
    }

    protected static function mediaSection(): Section
    {
        return Section::make('Lampiran Media')
            ->icon(Heroicon::OutlinedPaperClip)
            ->compact()
            ->collapsible()
            ->schema([
                Select::make('media_type')
                    ->label('Jenis Media')
                    ->options([
                        'none' => 'Tidak ada',
                        'image' => 'Gambar',
                        'audio' => 'Audio (Listening)',
                        'video' => 'Video',
                    ])
                    ->default('none')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->live(),

                FileUpload::make('media_file')
                    ->label('File Media')
                    ->disk('public')
                    ->directory('soal-media')
                    ->visibility('public')
                    ->visible(fn (Get $get) => in_array($get('media_type'), ['image', 'audio', 'video']))
                    ->required(fn (Get $get) => in_array($get('media_type'), ['image', 'audio', 'video']))
                    ->acceptedFileTypes(fn (Get $get) => match ($get('media_type')) {
                        'image' => ['image/png', 'image/jpeg', 'image/webp', 'image/gif'],
                        'audio' => ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a'],
                        'video' => ['video/mp4', 'video/webm'],
                        default => null,
                    })
                    ->helperText('Otomatis dikompresi agar siswa memuat soal secara cepat.')
                    ->maxSize(51200)
                    ->saveUploadedFileUsing(function ($file, Get $get) {
                        return app(MediaCompressionService::class)->compressAndStore(
                            $file,
                            'soal-media',
                            in_array($get('media_type'), ['image', 'video', 'audio']) ? $get('media_type') : null
                        );
                    }),

                TextInput::make('audio_max_play')
                    ->label('Batas Putar Audio')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->suffix('x')
                    ->helperText('0 = bebas diputar.')
                    ->visible(fn (Get $get) => $get('media_type') === 'audio'),
            ]);
    }

    // ------------------------------------------------------------------
    // REPEATER OPSI JAWABAN (satu repeater untuk semua tipe berbasis opsi)
    // ------------------------------------------------------------------

    protected static function opsiRepeater(): Repeater
    {
        $isTipeOpsi = fn (Get $get): bool => in_array($get('../../tipe_soal'), self::TIPE_OPSI);
        $isMenjodohkan = fn (Get $get): bool => $get('../../tipe_soal') === 'menjodohkan';

        return Repeater::make('opsiJawabans')
            ->hiddenLabel()
            ->relationship('opsiJawabans')
            ->orderColumn('nomor_urut')
            ->visible(fn (Get $get) => in_array($get('tipe_soal'), [...self::TIPE_OPSI, 'menjodohkan']))
            ->default(fn (Get $get) => self::defaultOpsi($get('tipe_soal') ?? 'pilihan_ganda'))
            ->minItems(2)
            ->maxItems(fn (Get $get) => $get('tipe_soal') === 'benar_salah' ? 2 : 10)
            ->addActionLabel(fn (Get $get) => $get('tipe_soal') === 'menjodohkan' ? 'Tambah Pasangan' : 'Tambah Opsi')
            ->reorderable()
            ->live()
            ->afterStateUpdated(fn (?array $state, Set $set) => $set('opsiJawabans', self::relabel($state ?? [])))
            ->table(fn (Get $get) => $get('tipe_soal') === 'menjodohkan'
                ? [
                    TableColumn::make('Pernyataan Kiri')->markAsRequired(),
                    TableColumn::make('Pasangan Jawaban Kanan')->markAsRequired(),
                ]
                : [
                    TableColumn::make('Opsi')->width('64px'),
                    TableColumn::make('Teks Jawaban')->markAsRequired(),
                    TableColumn::make('Gambar (opsional)')->width('170px'),
                    TableColumn::make('Kunci')->width('80px')->alignment(Alignment::Center),
                ])
            ->schema([
                TextInput::make('label')
                    ->hiddenLabel()
                    ->readOnly()
                    ->extraInputAttributes(['style' => 'text-align:center;font-weight:700;'])
                    ->visible($isTipeOpsi),

                Textarea::make('teks_opsi')
                    ->hiddenLabel()
                    ->placeholder(fn (Get $get) => $get('../../tipe_soal') === 'menjodohkan'
                        ? 'Pernyataan sisi kiri...'
                        : 'Teks jawaban, bisa $rumus$ / Arab...')
                    ->rows(1)
                    ->autosize()
                    ->required(fn (Get $get) => $isMenjodohkan($get) || blank($get('gambar_opsi'))),

                FileUpload::make('gambar_opsi')
                    ->hiddenLabel()
                    ->disk('public')
                    ->directory('soal-media/opsi')
                    ->image()
                    ->imagePreviewHeight('60')
                    ->panelLayout('compact')
                    ->saveUploadedFileUsing(function ($file) {
                        return app(MediaCompressionService::class)->compressAndStore(
                            $file,
                            'soal-media/opsi',
                            'image'
                        );
                    })
                    ->visible($isTipeOpsi),

                Checkbox::make('is_jawaban_benar')
                    ->hiddenLabel()
                    ->extraAttributes(['class' => 'flex justify-center'])
                    ->live()
                    ->afterStateUpdated(function (?bool $state, Get $get, Set $set, Checkbox $component) {
                        if (! $state) {
                            return;
                        }

                        $tipeSoal = $get('../../tipe_soal');

                        // Hanya 1 jawaban yang boleh dipilih untuk tipe selain PG Kompleks
                        if ($tipeSoal !== 'pg_kompleks') {
                            $currentKey = self::currentItemKey($component);
                            $items = $get('../../opsiJawabans') ?? [];

                            foreach ($items as $key => $item) {
                                if ((string) $key !== $currentKey) {
                                    $items[$key]['is_jawaban_benar'] = false;
                                    $set("../../opsiJawabans.{$key}.is_jawaban_benar", false);
                                }
                            }

                            $set('../../opsiJawabans', $items);
                        }
                    })
                    ->visible($isTipeOpsi),

                TextInput::make('pasangan_matching')
                    ->hiddenLabel()
                    ->placeholder('Pasangan yang benar...')
                    ->required($isMenjodohkan)
                    ->visible($isMenjodohkan),
            ])
            ->columnSpanFull();
    }

    // ------------------------------------------------------------------
    // HELPERS
    // ------------------------------------------------------------------

    protected static function onTipeSoalChanged(?string $state, ?string $old, Get $get, Set $set): void
    {
        $items = $get('opsiJawabans') ?? [];
        $hasContent = collect($items)->contains(fn ($item) => filled($item['teks_opsi'] ?? null) || filled($item['gambar_opsi'] ?? null));

        // Benar/Salah selalu pakai 2 opsi baku.
        if ($state === 'benar_salah') {
            $set('opsiJawabans', self::defaultOpsi('benar_salah'));

            return;
        }

        // Pindah dari Benar/Salah, atau opsi masih kosong -> isi template default.
        if ($old === 'benar_salah' || ! $hasContent) {
            $set('opsiJawabans', self::defaultOpsi($state ?? 'pilihan_ganda'));

            return;
        }

        // PG kompleks -> PG biasa: sisakan satu kunci saja.
        if ($state === 'pilihan_ganda') {
            $found = false;
            foreach ($items as $key => $item) {
                if (! empty($item['is_jawaban_benar'])) {
                    $items[$key]['is_jawaban_benar'] = ! $found;
                    $found = true;
                }
            }
            $set('opsiJawabans', $items);
        }
    }

    public static function defaultOpsi(string $tipe): array
    {
        $rows = match ($tipe) {
            'benar_salah' => [
                ['teks_opsi' => 'Benar'],
                ['teks_opsi' => 'Salah'],
            ],
            'menjodohkan' => array_fill(0, 3, ['teks_opsi' => '', 'pasangan_matching' => '']),
            'isian', 'essay' => [],
            default => array_fill(0, 4, ['teks_opsi' => '']),
        };

        $items = [];
        foreach ($rows as $row) {
            $items[(string) Str::uuid()] = array_merge([
                'label' => null,
                'teks_opsi' => '',
                'gambar_opsi' => null,
                'is_jawaban_benar' => false,
                'pasangan_matching' => null,
            ], $row);
        }

        return self::relabel($items);
    }

    /** Beri label A, B, C ... sesuai urutan item. */
    public static function relabel(array $items): array
    {
        $i = 0;
        foreach ($items as $key => $item) {
            $items[$key]['label'] = chr(65 + $i);
            $i++;
        }

        return $items;
    }

    /** Ambil key item repeater tempat komponen berada (dari state path). */
    protected static function currentItemKey(Checkbox $component): string
    {
        $segments = explode('.', $component->getStatePath());
        array_pop($segments); // 'is_jawaban_benar'

        return (string) array_pop($segments);
    }
}
