<?php

namespace App\Filament\Resources\JadwalRuangs\Tables;

use App\Models\BeritaAcara;
use App\Models\JadwalRuang;
use App\Models\RuangUjian;
use App\Models\Ujian;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JadwalRuangTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ruangUjian.nama_ruang')
                    ->label('Ruang Ujian')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (JadwalRuang $record) => $record->ruangUjian?->kode_ruang ?: '-'),

                TextColumn::make('daftar_agenda')
                    ->label('Agenda Ujian')
                    ->state(function (JadwalRuang $record) {
                        $ujians = $record->getAllUjians();
                        if ($ujians->isEmpty()) {
                            return ['-'];
                        }

                        $multi = $ujians->count() > 1;

                        return $ujians->values()->map(function (Ujian $u, int $index) use ($multi) {
                            $mapel = $u->mapel?->nama_mapel ?: 'Umum';
                            $prefix = $multi ? ($index + 1) . '. ' : '';
                            return "{$prefix}{$u->nama_ujian} ({$mapel})";
                        })->all();
                    })
                    ->listWithLineBreaks()
                    ->badge()
                    ->color('primary')
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('ujian', fn ($q) => $q->where('nama_ujian', 'like', "%{$search}%"))
                              ->orWhereHas('ujians', fn ($q) => $q->where('nama_ujian', 'like', "%{$search}%"));
                    }),

                TextColumn::make('pengawas.name')
                    ->label('Guru Pengawas')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->description(fn (JadwalRuang $record) => $record->pengawasPendamping ? "Pendamping: {$record->pengawasPendamping->name}" : null),

                TextColumn::make('tanggal')
                    ->label('Tanggal & Sesi')
                    ->date('d M Y')
                    ->sortable()
                    ->description(fn (JadwalRuang $record) => $record->label_sesi_waktu),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ONGOING' => 'success',
                        'SCHEDULED' => 'warning',
                        'COMPLETED' => 'gray',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('token_ruang')
                    ->label('Token Ruang')
                    ->getStateUsing(function (JadwalRuang $record) {
                        if (empty($record->token_ruang)) {
                            return 'Belum Dirilis';
                        }
                        if ($record->isTokenExpired()) {
                            return "{$record->token_ruang} (Kedaluwarsa)";
                        }
                        return "{$record->token_ruang} ({$record->sisaWaktuTokenFormatted()})";
                    })
                    ->badge()
                    ->color(fn (JadwalRuang $record) => $record->isTokenActive() ? 'success' : ($record->token_ruang ? 'danger' : 'gray'))
                    ->copyable()
                    ->copyableState(fn (JadwalRuang $record) => $record->token_ruang),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('ruang_ujian_id')
                    ->label('Filter Ruang Ujian')
                    ->options(fn () => RuangUjian::pluck('nama_ruang', 'id')),

                SelectFilter::make('ujian_id')
                    ->label('Filter Agenda Ujian')
                    ->options(fn () => Ujian::pluck('nama_ujian', 'id'))
                    ->query(function ($query, array $data) {
                        $val = $data['value'] ?? null;
                        if (! $val) {
                            return $query;
                        }
                        return $query->where(function ($q) use ($val) {
                            $q->where('ujian_id', $val)
                              ->orWhereHas('ujians', fn ($uq) => $uq->where('ujians.id', $val));
                        });
                    }),

                SelectFilter::make('pengawas_id')
                    ->label('Filter Guru Pengawas')
                    ->options(fn () => User::whereIn('role', ['guru', 'admin'])->pluck('name', 'id')),

                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'SCHEDULED' => 'SCHEDULED (Dijadwalkan)',
                        'ONGOING' => 'ONGOING (Sedang Berlangsung)',
                        'COMPLETED' => 'COMPLETED (Selesai)',
                        'CANCELLED' => 'CANCELLED (Dibatalkan)',
                    ]),
            ])
            ->actions([
                Action::make('rilisToken')
                    ->label(fn (JadwalRuang $record) => $record->token_ruang ? 'Rilis Ulang Token' : 'Rilis Token')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('success')
                    ->disabled(fn (JadwalRuang $record) => $record->isBelumMasukJadwal() || $record->isJadwalBerakhir())
                    ->tooltip(function (JadwalRuang $record) {
                        if ($record->isBelumMasukJadwal()) {
                            return "Belum masuk jadwal pelaksanaan ({$record->label_sesi_waktu}).";
                        }
                        if ($record->isJadwalBerakhir()) {
                            return "Jadwal sesi ini telah berakhir.";
                        }
                        return null;
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (JadwalRuang $record) => "Rilis Token Ruang: {$record->ruangUjian?->nama_ruang} - {$record->label_sesi_waktu}")
                    ->modalDescription('Token 6 karakter baru akan di-generate dan berlaku 10 menit. Status jadwal otomatis diubah menjadi ONGOING.')
                    ->action(function (JadwalRuang $record) {
                        if ($record->isBelumMasukJadwal()) {
                            Notification::make()
                                ->title('Belum Masuk Jadwal')
                                ->body("Sesi ini belum memasuki jam pelaksanaan ({$record->label_sesi_waktu}).")
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($record->isJadwalBerakhir()) {
                            Notification::make()
                                ->title('Jadwal Telah Berakhir')
                                ->body("Waktu jadwal sesi ini telah berakhir.")
                                ->danger()
                                ->send();
                            return;
                        }

                        $token = $record->rilisToken();
                        Notification::make()
                            ->title("Token Ruangan Berhasil Dirilis: {$token}")
                            ->body("Token berlaku 10 menit untuk {$record->ruangUjian?->nama_ruang}.")
                            ->success()
                            ->send();
                    }),

                Action::make('portalPengawas')
                    ->label('Buka Portal')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->color('primary')
                    ->url(fn (JadwalRuang $record) => route('pengawas.portal', ['sesi_ruang' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('daftarHadir')
                    ->label('Daftar Hadir')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->color('warning')
                    ->tooltip('Cetak Daftar Hadir Peserta Sesi Ruangan')
                    ->url(fn (JadwalRuang $record) => route('daftar-hadir.print', [
                        'ruang_ujian_id' => $record->ruang_ujian_id,
                        'jadwal_ruang_id' => $record->id,
                        'ujian_id' => $record->ujian_id,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('isiBeritaAcara')
                    ->label('Isi BAP')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->tooltip('Isi / Perbarui Berita Acara Pelaksanaan (BAP) Sesi Ini')
                    ->modalHeading(fn (JadwalRuang $record) => 'Isi Berita Acara Sesi: ' . ($record->ruangUjian?->nama_ruang ?? 'Ruang') . " (Sesi {$record->sesi_ke})")
                    ->modalDescription('Data kehadiran dihitung otomatis dari sesi peserta pada jadwal ini, dan dapat disesuaikan sebelum disimpan.')
                    ->modalSubmitActionLabel('💾 Simpan & Cetak BAP')
                    ->fillForm(function (JadwalRuang $record): array {
                        $existing = BeritaAcara::where('jadwal_ruang_id', $record->id)->latest('id')->first();
                        $stats = $record->computeAttendanceStats();
                        $ujians = $record->getAllUjians();
                        $defaultNamaUjian = $ujians->count() > 1
                            ? $ujians->pluck('nama_ujian')->implode(' / ')
                            : ($ujians->first()?->nama_ujian ?: 'TES KEMAMPUAN AKADEMIK SMK');

                        return [
                            'nama_ujian' => $existing?->nama_ujian ?: $defaultNamaUjian,
                            'tahun_pelajaran' => $existing?->tahun_pelajaran ?: ($record->tanggal?->format('Y') ?? now()->format('Y')),
                            'sesi' => $existing?->sesi ?: (string) ($record->sesi_ke ?? 1),
                            'waktu_mulai' => $existing?->waktu_mulai ?: substr((string) $record->waktu_mulai, 0, 5),
                            'waktu_selesai' => $existing?->waktu_selesai ?: substr((string) $record->waktu_selesai, 0, 5),
                            'jumlah_peserta_seharusnya' => $existing?->jumlah_peserta_seharusnya ?? $stats['seharusnya'],
                            'usernames_tidak_hadir' => $existing?->usernames_tidak_hadir ?? $stats['usernames_tidak_hadir'],
                            'jumlah_hadir' => $existing?->jumlah_hadir ?? $stats['hadir'],
                            'jumlah_tidak_hadir' => $existing?->jumlah_tidak_hadir ?? $stats['tidak_hadir'],
                            'catatan' => $existing?->catatan ?: ($record->catatan ?: 'Pelaksanaan ujian berjalan tertib, aman, dan lancar tanpa kendala.'),
                            'nama_pengawas' => $existing?->nama_pengawas ?: ($record->pengawas?->name ?: ''),
                            'nip_pengawas' => $existing?->nip_pengawas ?: ($record->pengawas?->nip ?: ''),
                            'nama_ketua_ujian' => $existing?->nama_ketua_ujian ?: 'Ahmad Daqiqi Syahrulloh, S.H.',
                            'nip_ketua_ujian' => $existing?->nip_ketua_ujian ?: '198904152014021003',
                            'nama_kepala_sekolah' => $existing?->nama_kepala_sekolah ?: 'Mahsun, M.Pd.',
                            'nip_kepala_sekolah' => $existing?->nip_kepala_sekolah ?: '197508102005011008',
                        ];
                    })
                    ->form([
                        Grid::make(3)->schema([
                            TextInput::make('nama_ujian')
                                ->label('Nama Ujian / Asesmen')
                                ->required()
                                ->columnSpan(2),
                            TextInput::make('sesi')
                                ->label('Sesi Ke')
                                ->required(),
                            TextInput::make('waktu_mulai')
                                ->label('Waktu Mulai')
                                ->required(),
                            TextInput::make('waktu_selesai')
                                ->label('Waktu Selesai')
                                ->required(),
                            TextInput::make('tahun_pelajaran')
                                ->label('Tahun Pelajaran')
                                ->required(),
                        ]),
                        Section::make('Kehadiran Peserta Sesi')->schema([
                            Grid::make(3)->schema([
                                TextInput::make('jumlah_peserta_seharusnya')
                                    ->label('Jumlah Peserta Seharusnya')
                                    ->numeric()
                                    ->required()
                                    ->live(),
                                TextInput::make('jumlah_hadir')
                                    ->label('Jumlah Hadir')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('jumlah_tidak_hadir')
                                    ->label('Jumlah Tidak Hadir')
                                    ->numeric()
                                    ->required(),
                            ]),
                            Select::make('usernames_tidak_hadir')
                                ->label('Tag Siswa Tidak Hadir (Username / NIS)')
                                ->multiple()
                                ->searchable()
                                ->options(function (JadwalRuang $record) {
                                    return $record->getSiswasForSession()
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
                            ->rows(3),
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
                    ->action(function (JadwalRuang $record, array $data) {
                        $ruang = $record->ruangUjian;
                        $tanggal = $record->tanggal ?? now();
                        $bap = BeritaAcara::updateOrCreate(
                            ['jadwal_ruang_id' => $record->id],
                            [
                                'ruang_ujian_id' => $record->ruang_ujian_id,
                                'nama_ujian' => $data['nama_ujian'],
                                'tahun_pelajaran' => $data['tahun_pelajaran'],
                                'hari' => match ($tanggal->dayOfWeek) {
                                    0 => 'Minggu',
                                    1 => 'Senin',
                                    2 => 'Selasa',
                                    3 => 'Rabu',
                                    4 => 'Kamis',
                                    5 => 'Jumat',
                                    6 => 'Sabtu',
                                    default => 'Senin',
                                },
                                'tanggal' => $tanggal->toDateString(),
                                'waktu_mulai' => $data['waktu_mulai'],
                                'waktu_selesai' => $data['waktu_selesai'],
                                'nama_sekolah' => 'SMKS ASSUNIYAH',
                                'username_sekolah' => 'K12130106',
                                'id_server' => $ruang?->kode_ruang ? 'K12130106-' . strtoupper($ruang->kode_ruang) : 'K12130106-AJ9K',
                                'ruang' => $ruang?->nama_ruang ?: '-',
                                'sesi' => $data['sesi'],
                                'jumlah_peserta_seharusnya' => (int) $data['jumlah_peserta_seharusnya'],
                                'jumlah_hadir' => (int) $data['jumlah_hadir'],
                                'jumlah_tidak_hadir' => (int) $data['jumlah_tidak_hadir'],
                                'usernames_tidak_hadir' => $data['usernames_tidak_hadir'] ?? [],
                                'catatan' => $data['catatan'],
                                'nama_pengawas' => $data['nama_pengawas'],
                                'nip_pengawas' => $data['nip_pengawas'],
                                'nama_ketua_ujian' => $data['nama_ketua_ujian'],
                                'nip_ketua_ujian' => $data['nip_ketua_ujian'],
                                'nama_kepala_sekolah' => $data['nama_kepala_sekolah'],
                                'nip_kepala_sekolah' => $data['nip_kepala_sekolah'],
                                'created_by' => auth()->id(),
                            ]
                        );

                        Notification::make()
                            ->title('Berita Acara Sesi Tersimpan')
                            ->body("BAP Sesi {$record->sesi_ke} ({$ruang?->nama_ruang}) berhasil disimpan.")
                            ->success()
                            ->send();

                        return redirect()->route('berita-acara.print', ['id' => $bap->id]);
                    }),

                Action::make('cetakBeritaAcara')
                    ->label('Cetak BAP')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->color('success')
                    ->tooltip('Buka / Cetak Berita Acara Pelaksanaan Sesi Ini')
                    ->url(fn (JadwalRuang $record) => route('berita-acara.print', [
                        'ruang_ujian_id' => $record->ruang_ujian_id,
                        'jadwal_ruang_id' => $record->id,
                        'ujian_id' => $record->ujian_id,
                    ]))
                    ->openUrlInNewTab(),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

