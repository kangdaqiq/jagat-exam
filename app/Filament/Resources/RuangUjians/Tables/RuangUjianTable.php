<?php

namespace App\Filament\Resources\RuangUjians\Tables;

use App\Filament\Resources\JadwalRuangs\JadwalRuangResource;
use App\Models\BeritaAcara;
use App\Models\RuangUjian;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RuangUjianTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                TextColumn::make('kode_ruang')
                    ->label('Kode')
                    ->badge()
                    ->color('gray')
                    ->fontFamily('mono')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_ruang')
                    ->label('Nama Ruang Ujian')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('pengawas.name')
                    ->label('Pengawas Ruangan')
                    ->placeholder('Semua Pengawas')
                    ->searchable(),

                TextColumn::make('siswas_count')
                    ->counts('siswas')
                    ->label('Jumlah Peserta')
                    ->formatStateUsing(fn ($state, RuangUjian $record) => "{$state} / {$record->kapasitas} Siswa")
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('komposisi_kelas')
                    ->label('Mix Kelas di Ruangan')
                    ->getStateUsing(function (RuangUjian $record) {
                        $kelasNames = $record->siswas()
                            ->with('kelas')
                            ->get()
                            ->pluck('kelas.nama_kelas')
                            ->filter()
                            ->unique()
                            ->values();

                        return $kelasNames->isNotEmpty() ? $kelasNames->join(', ') : 'Belum ada siswa';
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('token_ruang')
                    ->label('Token Ruangan (10m)')
                    ->getStateUsing(function (RuangUjian $record) {
                        if (!$record->token_ruang) {
                            return 'BELUM DIRILIS';
                        }
                        $status = $record->sisaWaktuTokenFormatted();
                        return "{$record->token_ruang} ({$status})";
                    })
                    ->badge()
                    ->color(fn (RuangUjian $record) => $record->isTokenActive() ? 'success' : ($record->token_ruang ? 'danger' : 'gray'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyableState(fn (RuangUjian $record) => $record->token_ruang)
                    ->copyMessage('Token ruangan disalin'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('rilisTokenRuang')
                    ->label(fn (RuangUjian $record) => $record->token_ruang ? 'Rilis Token Baru' : 'Rilis Token Ruang')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (RuangUjian $record) => "Rilis Token Akses: {$record->nama_ruang}")
                    ->modalDescription('Token 6 karakter ini berlaku selama 10 menit untuk seluruh siswa di ruangan ini. Soal ujian yang terbuka akan otomatis menyesuaikan kelas/tingkat masing-masing siswa.')
                    ->modalSubmitActionLabel('Rilis Token Sekarang')
                    ->action(function (RuangUjian $record) {
                        if ($record->siswas()->count() === 0) {
                            Notification::make()
                                ->title('Ruang Belum Memiliki Peserta')
                                ->body("Silakan tempatkan siswa terlebih dahulu ke {$record->nama_ruang} sebelum merilis token.")
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($record->isSemuaJadwalBerakhir()) {
                            Notification::make()
                                ->title('Jadwal Ujian Telah Berakhir')
                                ->body('Seluruh jadwal ujian untuk kelas yang ada di ruangan ini telah melewati jam selesai.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $token = $record->rilisToken();

                        Notification::make()
                            ->title("Token {$record->nama_ruang} Dirilis: {$token}")
                            ->body("Token {$token} aktif selama 10 menit untuk seluruh peserta di {$record->nama_ruang}.")
                            ->success()
                            ->send();
                    }),

                Action::make('cetakKartuRuang')
                    ->label('Kartu Peserta')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('info')
                    ->url(fn (RuangUjian $record) => route('kartu-peserta.print', ['ruang_ujian_id' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('cetakKartuMeja')
                    ->label('Kartu Meja')
                    ->icon(Heroicon::OutlinedTag)
                    ->color('success')
                    ->url(fn (RuangUjian $record) => route('kartu-peserta.print', ['ruang_ujian_id' => $record->id, 'tipe' => 'meja']))
                    ->openUrlInNewTab(),

                Action::make('daftarHadir')
                    ->label('Daftar Hadir')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->color('warning')
                    ->tooltip('Cetak Daftar Hadir Peserta Ruangan (Sebelum Ujian Dimulai)')
                    ->url(fn (RuangUjian $record) => route('daftar-hadir.print', ['ruang_ujian_id' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('isiBeritaAcara')
                    ->label('Berita Acara (BAP)')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->color('gray')
                    ->tooltip('Isi & Cetak Berita Acara Pelaksanaan (Setelah Ujian Selesai)')
                    ->modalHeading(fn (RuangUjian $record) => "Isi Berita Acara Pelaksanaan: {$record->nama_ruang}")
                    ->modalDescription('Proktor mengisi data jumlah kehadiran siswa, tag username siswa tidak hadir, catatan ujian, dan pengesahan TTD sebelum dicetak.')
                    ->modalSubmitActionLabel('💾 Simpan & Buka Cetak BAP')
                    ->fillForm(function (RuangUjian $record): array {
                        $totalSiswa = $record->siswas()->count();
                        $lastBap = $record->beritaAcaras()->latest()->first();

                        return [
                            'nama_ujian' => $lastBap?->nama_ujian ?: 'TES KEMAMPUAN AKADEMIK SMK',
                            'tahun_pelajaran' => $lastBap?->tahun_pelajaran ?: '2026',
                            'sesi' => $lastBap?->sesi ?: '1',
                            'waktu_mulai' => $lastBap?->waktu_mulai ?: '07:30',
                            'waktu_selesai' => $lastBap?->waktu_selesai ?: '09:30',
                            'jumlah_peserta_seharusnya' => $lastBap?->jumlah_peserta_seharusnya ?: $totalSiswa,
                            'usernames_tidak_hadir' => $lastBap?->usernames_tidak_hadir ?: [],
                            'jumlah_hadir' => $lastBap?->jumlah_hadir ?: $totalSiswa,
                            'jumlah_tidak_hadir' => $lastBap?->jumlah_tidak_hadir ?: 0,
                            'catatan' => $lastBap?->catatan ?: 'Pelaksanaan ujian berjalan tertib, aman, dan lancar tanpa kendala.',
                            'nama_pengawas' => $lastBap?->nama_pengawas ?: ($record->pengawas?->name ?: 'ATHIQ NUR AZIZAH'),
                            'nip_pengawas' => $lastBap?->nip_pengawas ?: ($record->pengawas?->nip ?: '198705122010012004'),
                            'nama_ketua_ujian' => $lastBap?->nama_ketua_ujian ?: 'Ahmad Daqiqi Syahrulloh, S.H.',
                            'nip_ketua_ujian' => $lastBap?->nip_ketua_ujian ?: '198904152014021003',
                            'nama_kepala_sekolah' => $lastBap?->nama_kepala_sekolah ?: 'Mahsun, M.Pd.',
                            'nip_kepala_sekolah' => $lastBap?->nip_kepala_sekolah ?: '197508102005011008',
                        ];
                    })
                    ->form([
                        Select::make('jadwal_ruang_id')
                            ->label('Pilih Sesi / Jadwal Ujian Ruangan Ini')
                            ->helperText('Pilih jadwal untuk otomatis memuat Mapel, Jam, Sesi, dan Pengawas.')
                            ->placeholder('-- Pilih Jadwal Sesi / Mapel (Atau Isi Manual di Bawah) --')
                            ->options(function (RuangUjian $record) {
                                return $record->jadwalRuangs()
                                    ->with(['ujian'])
                                    ->orderBy('tanggal')
                                    ->orderBy('waktu_mulai')
                                    ->get()
                                    ->mapWithKeys(function ($j) {
                                        $mapel = $j->ujian?->nama_ujian ?: 'Ujian';
                                        $waktu = substr($j->waktu_mulai, 0, 5) . ' - ' . substr($j->waktu_selesai, 0, 5);
                                        return [$j->id => "Sesi {$j->sesi_ke} ({$waktu}): {$mapel}"];
                                    });
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if (!$state) return;
                                $j = \App\Models\JadwalRuang::with(['ujian', 'pengawas'])->find($state);
                                if ($j) {
                                    $set('nama_ujian', $j->ujian?->nama_ujian ?: 'TES KEMAMPUAN AKADEMIK SMK');
                                    $set('sesi', (string) $j->sesi_ke);
                                    $set('waktu_mulai', substr($j->waktu_mulai, 0, 5));
                                    $set('waktu_selesai', substr($j->waktu_selesai, 0, 5));
                                    if ($j->pengawas) {
                                        $set('nama_pengawas', $j->pengawas->name);
                                        $set('nip_pengawas', $j->pengawas->nip ?: '');
                                    }
                                }
                            }),

                        Grid::make(3)->schema([
                            TextInput::make('nama_ujian')
                                ->label('Nama Ujian / Asesmen')
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('sesi')
                                ->label('Sesi Ke')
                                ->default('1')
                                ->required(),
                            TextInput::make('waktu_mulai')
                                ->label('Waktu Mulai')
                                ->default('07:30')
                                ->required(),
                            TextInput::make('waktu_selesai')
                                ->label('Waktu Selesai')
                                ->default('09:30')
                                ->required(),
                            TextInput::make('tahun_pelajaran')
                                ->label('Tahun Pelajaran')
                                ->default('2026')
                                ->required(),
                        ]),
                        Section::make('Kehadiran Peserta Ujian')->schema([
                            Grid::make(3)->schema([
                                TextInput::make('jumlah_peserta_seharusnya')
                                    ->label('Jumlah Peserta Seharusnya')
                                    ->numeric()
                                    ->required()
                                    ->live(),
                                TextInput::make('jumlah_hadir')
                                    ->label('Jumlah Hadir (Ikut Ujian)')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('jumlah_tidak_hadir')
                                    ->label('Jumlah Tidak Mengerjakan')
                                    ->numeric()
                                    ->required(),
                            ]),
                            Select::make('usernames_tidak_hadir')
                                ->label('Tag Siswa Tidak Hadir (Username / NIS)')
                                ->helperText('Pilih siswa yang tidak hadir. Jumlah hadir & tidak hadir akan terhitung otomatis.')
                                ->multiple()
                                ->searchable()
                                ->options(function (RuangUjian $record) {
                                    return $record->siswas()
                                        ->orderBy('nama')
                                        ->get()
                                        ->mapWithKeys(function ($s) {
                                            $tag = $s->nis ?: ($s->user?->nip ?: "SISWA-{$s->id}");
                                            return [$tag => "{$tag} - {$s->nama}"];
                                        });
                                })
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $arr = is_array($state) ? $state : [];
                                    $tidak = count($arr);
                                    $seharusnya = (int) ($get('jumlah_peserta_seharusnya') ?: 0);
                                    $set('jumlah_tidak_hadir', $tidak);
                                    $set('jumlah_hadir', max(0, $seharusnya - $tidak));
                                }),
                        ]),
                        Textarea::make('catatan')
                            ->label('Catatan Selama Tes')
                            ->rows(3)
                            ->placeholder('Tuliskan kendala teknis atau catatan khusus bila ada...'),
                        Section::make('Penandatangan Berita Acara (TTD)')->schema([
                            Grid::make(3)->schema([
                                Fieldset::make('1. Pengawas')->schema([
                                    TextInput::make('nama_pengawas')->label('Nama Pengawas')->required(),
                                    TextInput::make('nip_pengawas')->label('NIP Pengawas'),
                                ])->columnSpan(1),
                                Fieldset::make('2. Ketua Ujian')->schema([
                                    TextInput::make('nama_ketua_ujian')->label('Nama Ketua Ujian')->required(),
                                    TextInput::make('nip_ketua_ujian')->label('NIP Ketua Ujian'),
                                ])->columnSpan(1),
                                Fieldset::make('3. Kepala Sekolah')->schema([
                                    TextInput::make('nama_kepala_sekolah')->label('Nama Kepala Sekolah')->required(),
                                    TextInput::make('nip_kepala_sekolah')->label('NIP Kepala Sekolah'),
                                ])->columnSpan(1),
                            ]),
                        ]),
                    ])
                    ->action(function (RuangUjian $record, array $data) {
                        $bap = BeritaAcara::create([
                            'ruang_ujian_id' => $record->id,
                            'nama_ujian' => $data['nama_ujian'],
                            'tahun_pelajaran' => $data['tahun_pelajaran'],
                            'hari' => match(now()->dayOfWeek) {
                                0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', default => 'Senin'
                            },
                            'tanggal' => now()->toDateString(),
                            'waktu_mulai' => $data['waktu_mulai'],
                            'waktu_selesai' => $data['waktu_selesai'],
                            'nama_sekolah' => 'SMKS ASSUNIYAH',
                            'username_sekolah' => 'K12130106',
                            'id_server' => $record->kode_ruang ? 'K12130106-' . strtoupper($record->kode_ruang) : 'K12130106-AJ9K',
                            'ruang' => $record->nama_ruang,
                            'sesi' => $data['sesi'],
                            'jumlah_peserta_seharusnya' => $data['jumlah_peserta_seharusnya'],
                            'jumlah_hadir' => $data['jumlah_hadir'],
                            'jumlah_tidak_hadir' => $data['jumlah_tidak_hadir'],
                            'usernames_tidak_hadir' => $data['usernames_tidak_hadir'],
                            'catatan' => $data['catatan'],
                            'nama_pengawas' => $data['nama_pengawas'],
                            'nip_pengawas' => $data['nip_pengawas'],
                            'nama_ketua_ujian' => $data['nama_ketua_ujian'],
                            'nip_ketua_ujian' => $data['nip_ketua_ujian'],
                            'nama_kepala_sekolah' => $data['nama_kepala_sekolah'],
                            'nip_kepala_sekolah' => $data['nip_kepala_sekolah'],
                            'created_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Berita Acara Berhasil Disimpan')
                            ->body("BAP untuk {$record->nama_ruang} telah siap dicetak.")
                            ->success()
                            ->send();

                        return redirect()->route('berita-acara.print', ['id' => $bap->id]);
                    }),

                Action::make('jadwalRuang')
                    ->label('Jadwal & Pengawas')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->color('warning')
                    ->url(fn (RuangUjian $record): string => JadwalRuangResource::getUrl('index', [
                        'tableFilters' => [
                            'ruang_ujian_id' => [
                                'value' => $record->id,
                            ],
                        ],
                    ])),

                Action::make('bukaPengawasRuang')
                    ->label('Monitor Ruang')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->color('primary')
                    ->url(fn (RuangUjian $record) => route('pengawas.portal', ['ruang' => $record->id])),

                EditAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])->visible(fn () => auth()->user()?->isAdmin() ?? false),
            ]);
    }
}

