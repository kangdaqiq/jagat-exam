<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AbsenSyncService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class SinkronisasiAbsen extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Integrasi Web Absensi';

    protected static ?string $title = 'Sinkronisasi Web Absensi (REST API)';

    protected static string|UnitEnum|null $navigationGroup = 'Integrasi & Sinkronisasi';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public ?string $verifiedSchoolName = null;
    public ?string $verifiedSchoolCode = null;
    public ?array $verifiedCounts = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $default = AbsenSyncService::getDefaultConfig();

        $this->form->fill([
            'api_url' => $default['api_url'],
            'api_key' => $default['api_key'],
            'school_id' => '6', // Default contoh ID sekolah (SMK ASSUNIYAH TUMIJAJAR)
            'school_name_display' => 'Belum diperiksa',
            'entities' => ['guru', 'siswa', 'mapel', 'kelas'],
        ]);

        // Cek sekolah default secara otomatis saat halaman dibuka
        $this->checkSchool(app(AbsenSyncService::class), false);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1. Ringkasan Database CBT Saat Ini (MySQL)
                Section::make('Informasi Bank Data CBT Saat Ini (Database MySQL: exam_web)')
                    ->description('Jumlah data tersimpan di server CBT MySQL saat ini')
                    ->icon(Heroicon::OutlinedCircleStack)
                    ->schema([
                        Grid::make(4)->schema([
                            Placeholder::make('stat_guru')
                                ->label('Total Guru di CBT')
                                ->content(fn () => new HtmlString('<div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">' . User::where('role', 'guru')->count() . ' <span class="text-xs font-normal text-gray-500">Guru</span></div>')),

                            Placeholder::make('stat_siswa')
                                ->label('Total Siswa di CBT')
                                ->content(fn () => new HtmlString('<div class="text-2xl font-bold text-violet-600 dark:text-violet-400">' . Siswa::count() . ' <span class="text-xs font-normal text-gray-500">Siswa</span></div>')),

                            Placeholder::make('stat_mapel')
                                ->label('Total Mata Pelajaran')
                                ->content(fn () => new HtmlString('<div class="text-2xl font-bold text-blue-600 dark:text-blue-400">' . Mapel::count() . ' <span class="text-xs font-normal text-gray-500">Mapel</span></div>')),

                            Placeholder::make('stat_kelas')
                                ->label('Total Rombel Kelas')
                                ->content(fn () => new HtmlString('<div class="text-2xl font-bold text-amber-600 dark:text-amber-400">' . Kelas::count() . ' <span class="text-xs font-normal text-gray-500">Kelas</span></div>')),
                        ]),
                    ]),

                // 2. Konfigurasi REST API Web Absensi
                Section::make('Konfigurasi REST API Web Absensi')
                    ->description('Koneksi murni via HTTP REST API (aman, tanpa akses langsung database)')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('api_url')
                                ->label('URL Web Absensi')
                                ->required()
                                ->placeholder('http://127.0.0.1:8080')
                                ->helperText('Alamat server Web Absensi yang aktif'),

                            TextInput::make('api_key')
                                ->label('API Secret Key (X-Exam-Key)')
                                ->password()
                                ->revealable()
                                ->required()
                                ->placeholder('cbt_secret_sync_2026')
                                ->helperText('Kunci otentikasi API integrasi CBT'),
                        ]),
                    ]),

                // 3. Custom School ID & Pengecekan Nama Sekolah
                Section::make('Kustomisasi ID Sekolah Target & Pengecekan Nama Sekolah')
                    ->description('Masukkan School ID yang ingin ditarik datanya dan verifikasi nama sekolahnya secara live')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('school_id')
                                ->label('Custom School ID (ID Sekolah)')
                                ->numeric()
                                ->placeholder('Contoh: 3, 4, 6')
                                ->helperText('Ketik ID sekolah target. Kosongkan jika ingin menarik SEMUA sekolah.')
                                ->suffixAction(
                                    Action::make('checkSchoolBtn')
                                        ->label('Cek Nama Sekolah')
                                        ->icon(Heroicon::OutlinedMagnifyingGlass)
                                        ->color('primary')
                                        ->action(fn (AbsenSyncService $syncService) => $this->checkSchool($syncService, true))
                                ),

                            Placeholder::make('school_status')
                                ->label('Status & Nama Sekolah Terverifikasi')
                                ->content(function () {
                                    if ($this->verifiedSchoolName) {
                                        $badge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">✓ Terverifikasi</span>';
                                        $name = '<div class="text-base font-bold text-gray-900 dark:text-white mt-1">' . e($this->verifiedSchoolName) . ' (' . e($this->verifiedSchoolCode) . ')</div>';
                                        
                                        $counts = '';
                                        if ($this->verifiedCounts) {
                                            $siswaText = isset($this->verifiedCounts['siswas']) ? '<strong>' . $this->verifiedCounts['siswas'] . ' Siswa</strong>, ' : '';
                                            $counts = '<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Data tersedia di Absensi: <strong>' . ($this->verifiedCounts['gurus'] ?? 0) . ' Guru</strong>, ' . $siswaText . '<strong>' . ($this->verifiedCounts['mapels'] ?? 0) . ' Mapel</strong>, <strong>' . ($this->verifiedCounts['kelases'] ?? 0) . ' Kelas</strong></div>';
                                        }

                                        return new HtmlString($badge . $name . $counts);
                                    }

                                    return new HtmlString('<span class="text-xs text-gray-400 italic">Belum diperiksa. Klik tombol kaca pembesar atau tombol "Cek Nama Sekolah".</span>');
                                }),

                            Placeholder::make('help_check')
                                ->label('Petunjuk')
                                ->content(new HtmlString('<div class="text-xs text-gray-500">Anda dapat mengubah nomor School ID kapan saja (misal: 3 untuk SMPN 2 Sampit, 4 untuk SMKN 4 Pandeglang, 6 untuk SMK Assuniyah, dll). Sistem akan memverifikasi nama sekolah via API sebelum data ditarik.</div>')),
                        ]),
                    ]),

                // 4. Pilihan Data yang Disinkronkan
                Section::make('Pilihan Data yang Ingin Diambil')
                    ->description('Centang data yang ingin disalin dari sekolah target ke database CBT MySQL')
                    ->icon(Heroicon::OutlinedCloudArrowDown)
                    ->schema([
                        CheckboxList::make('entities')
                            ->label('Jenis Data')
                            ->options([
                                'guru' => 'Data Guru & Buat Akun Login CBT Guru (Password default: NIP guru)',
                                'siswa' => 'Data Siswa & Buat Akun Login CBT Siswa (Login via NIS, Password default: NIS)',
                                'mapel' => 'Data Mata Pelajaran (Mapel)',
                                'kelas' => 'Data Kelas & Rombongan Belajar (Rombel)',
                            ])
                            ->default(['guru', 'siswa', 'mapel', 'kelas'])
                            ->required(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('sync-form')
            ->livewireSubmitHandler('syncNow')
            ->footer([
                Actions::make($this->getFormActions())
                    ->key('form-actions'),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('syncNow')
                ->label('Mulai Tarik Data dari Web Absensi')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading(fn () => 'Konfirmasi Penarikan Data ' . ($this->verifiedSchoolName ? '(' . $this->verifiedSchoolName . ')' : '(Semua Sekolah)'))
                ->modalDescription('Data dari Web Absensi akan ditarik via REST API dan disimpan ke database MySQL CBT (exam_web).')
                ->modalSubmitActionLabel('Tarik Sekarang')
                ->action('syncNow'),

            Action::make('checkSchoolAction')
                ->label('Cek Nama Sekolah (School ID)')
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->color('primary')
                ->size('lg')
                ->action(fn (AbsenSyncService $syncService) => $this->checkSchool($syncService, true)),

            Action::make('testConnection')
                ->label('Uji Ping API')
                ->icon(Heroicon::OutlinedSignal)
                ->color('gray')
                ->size('lg')
                ->action('testConnection'),
        ];
    }

    /**
     * Cek & verifikasi nama sekolah via REST API
     */
    public function checkSchool(AbsenSyncService $syncService, bool $showNotification = true): void
    {
        $formData = $this->form->getState();
        $schoolId = $formData['school_id'] ?? null;
        $apiUrl = $formData['api_url'] ?? null;
        $apiKey = $formData['api_key'] ?? null;

        if (empty($schoolId)) {
            $this->verifiedSchoolName = null;
            $this->verifiedSchoolCode = null;
            $this->verifiedCounts = null;

            if ($showNotification) {
                Notification::make()
                    ->title('Mode Semua Sekolah')
                    ->body('School ID kosong. Sistem akan menarik data dari SEMUA sekolah yang ada di Web Absensi.')
                    ->info()
                    ->send();
            }
            return;
        }

        $result = $syncService->checkSchool($schoolId, $apiUrl, $apiKey);

        if ($result['success'] && !empty($result['found'])) {
            $this->verifiedSchoolName = $result['name'];
            $this->verifiedSchoolCode = $result['code'] ?? 'SCH';
            $this->verifiedCounts = $result['counts'] ?? [];

            if ($showNotification) {
                $siswaText = isset($result['counts']['siswas']) ? "{$result['counts']['siswas']} Siswa, " : '';
                Notification::make()
                    ->title('Nama Sekolah Ditemukan!')
                    ->body("Sekolah: {$result['name']} | Tersedia: {$result['counts']['gurus']} Guru, {$siswaText}{$result['counts']['mapels']} Mapel, {$result['counts']['kelases']} Kelas di Absensi.")
                    ->success()
                    ->send();
            }
        } else {
            $this->verifiedSchoolName = null;
            $this->verifiedSchoolCode = null;
            $this->verifiedCounts = null;

            if ($showNotification) {
                Notification::make()
                    ->title('Sekolah Tidak Ditemukan')
                    ->body($result['message'] ?? "Sekolah dengan ID {$schoolId} tidak ditemukan di server Web Absensi.")
                    ->danger()
                    ->send();
            }
        }
    }

    /**
     * Uji koneksi ping API
     */
    public function testConnection(AbsenSyncService $syncService): void
    {
        $formData = $this->form->getState();
        $apiUrl = $formData['api_url'] ?? null;
        $apiKey = $formData['api_key'] ?? null;

        $result = $syncService->testConnection($apiUrl, $apiKey);

        if ($result['success']) {
            $siswaText = isset($result['counts']['siswas']) ? "{$result['counts']['siswas']} Siswa, " : '';
            Notification::make()
                ->title('Koneksi REST API Berhasil!')
                ->body($result['message'] . " (Total Server: {$result['counts']['gurus']} Guru, {$siswaText}{$result['counts']['mapels']} Mapel, {$result['counts']['kelases']} Kelas)")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Koneksi API Gagal')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    /**
     * Eksekusi penarikan data via REST API
     */
    public function syncNow(AbsenSyncService $syncService): void
    {
        $formData = $this->form->getState();
        $entities = $formData['entities'] ?? [];

        if (empty($entities)) {
            Notification::make()
                ->title('Pilih Minimal Satu Jenis Data')
                ->body('Centang jenis data yang ingin diambil (Guru, Siswa, Mapel, atau Kelas).')
                ->warning()
                ->send();
            return;
        }

        try {
            $options = [
                'api_url' => $formData['api_url'],
                'api_key' => $formData['api_key'],
                'school_id' => !empty($formData['school_id']) ? (int) $formData['school_id'] : null,
                'types' => $entities,
            ];

            $result = $syncService->syncData($options);

            $schoolNameText = !empty($result['school_name']) ? " dari {$result['school_name']}" : '';

            $msg = [];
            if (in_array('guru', $entities)) {
                $msg[] = "Guru: {$result['guru']['total']} (Baru: {$result['guru']['created']})";
            }
            if (in_array('siswa', $entities)) {
                $msg[] = "Siswa: {$result['siswa']['total']} (Baru: {$result['siswa']['created']})";
            }
            if (in_array('mapel', $entities)) {
                $msg[] = "Mapel: {$result['mapel']['total']} (Baru: {$result['mapel']['created']})";
            }
            if (in_array('kelas', $entities)) {
                $msg[] = "Kelas: {$result['kelas']['total']} (Baru: {$result['kelas']['created']})";
            }
            if (!empty($result['assignments']['linked'])) {
                $msg[] = "Pemetaan Pengampu: {$result['assignments']['linked']} jadwal mengajar berhasil dipetakan ke guru";
            }

            Notification::make()
                ->title("Sukses Menarik Data{$schoolNameText}!")
                ->body(implode(' | ', $msg) . ' tersimpan ke MySQL exam_web.')
                ->success()
                ->duration(8000)
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Sinkronisasi Terhenti')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
