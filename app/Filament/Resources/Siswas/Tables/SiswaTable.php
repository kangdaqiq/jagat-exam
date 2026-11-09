<?php

namespace App\Filament\Resources\Siswas\Tables;

use App\Models\RuangUjian;
use App\Models\Siswa;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SiswaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('NIS disalin'),

                TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas / Rombel')
                    ->badge()
                    ->color('info')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('ruangUjian.nama_ruang')
                    ->label('Ruang Ujian')
                    ->badge()
                    ->color('primary')
                    ->placeholder('Belum Diatur')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.email')
                    ->label('Akun Login CBT')
                    ->placeholder('Belum dibuat')
                    ->badge()
                    ->color('success')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('password_cbt')
                    ->label('Password CBT')
                    ->getStateUsing(fn (Siswa $record) => $record->password_cbt ?: ($record->nis ?: '-'))
                    ->badge()
                    ->color('warning')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Password CBT disalin'),

                TextColumn::make('no_wa')
                    ->label('No. WhatsApp')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('school_name')
                    ->label('Sekolah')
                    ->badge()
                    ->color('gray')
                    ->placeholder('CBT Pusat')
                    ->searchable(),

                TextColumn::make('sync_id')
                    ->label('ID Absensi')
                    ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Disinkronkan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Filter Kelas')
                    ->relationship('kelas', 'nama_kelas'),

                SelectFilter::make('ruang_ujian_id')
                    ->label('Filter Ruang Ujian')
                    ->relationship('ruangUjian', 'nama_ruang'),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Hanya Siswa Aktif')
                    ->falseLabel('Hanya Siswa Nonaktif'),
            ])
            ->recordActions([
                Action::make('cetakKartu')
                    ->label('Kartu CBT')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->color('info')
                    ->url(fn (Siswa $record) => route('kartu-peserta.print', ['ids' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->modalHeading(fn (Siswa $record) => "Reset Password: {$record->nama}")
                    ->modalDescription(fn (Siswa $record) => "Pilih metode reset password untuk siswa ini (NIS: {$record->nis}):")
                    ->modalSubmitActionLabel('Simpan Password')
                    ->form([
                        Radio::make('mode')
                            ->label('Metode Reset Password')
                            ->options([
                                'generator' => 'Auto-Generator 6 Karakter Angka Acak',
                                'nis' => 'Reset ke Default NIS Siswa',
                                'custom' => 'Ketik Password Manual',
                            ])
                            ->default('generator')
                            ->live(),

                        TextInput::make('password')
                            ->label('Password Baru')
                            ->default(fn (Siswa $record) => Siswa::generateSixDigitPin())
                            ->visible(fn ($get) => $get('mode') === 'custom')
                            ->required(fn ($get) => $get('mode') === 'custom')
                            ->minLength(4),
                    ])
                    ->action(function (Siswa $record, array $data) {
                        $mode = $data['mode'] ?? 'generator';
                        if ($mode === 'generator') {
                            $newPassword = Siswa::generateSixDigitPin();
                        } elseif ($mode === 'nis') {
                            $newPassword = $record->nis ?: Siswa::generateSixDigitPin();
                        } else {
                            $newPassword = $data['password'] ?: Siswa::generateSixDigitPin();
                        }

                        $record->resetPassword($newPassword);

                        Notification::make()
                            ->title('Password Berhasil Direset')
                            ->body("Password untuk {$record->nama} (NIS: {$record->nis}) telah direset ke: {$newPassword}")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assignRuangUjian')
                        ->label('Tempatkan ke Ruang Ujian')
                        ->icon(Heroicon::OutlinedBuildingOffice2)
                        ->color('info')
                        ->modalHeading('Tempatkan Siswa Terpilih ke Ruang Ujian')
                        ->modalDescription('Siswa dari berbagai kelas/tingkatan yang dicentang akan ditempatkan ke ruang ujian yang dipilih:')
                        ->modalSubmitActionLabel('Simpan Ruang Ujian')
                        ->form([
                            Select::make('ruang_ujian_id')
                                ->label('Pilih Ruang Ujian')
                                ->options(fn () => RuangUjian::where('is_active', true)->orderBy('nama_ruang')->pluck('nama_ruang', 'id'))
                                ->placeholder('-- Kosongkan / Lepas dari Ruang --')
                                ->searchable(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $ruangId = $data['ruang_ujian_id'] ?? null;
                            $ruang = $ruangId ? RuangUjian::find($ruangId) : null;

                            foreach ($records as $siswa) {
                                $siswa->update(['ruang_ujian_id' => $ruang?->id]);
                            }

                            $labelRuang = $ruang ? $ruang->nama_ruang : 'Tanpa Ruang';
                            Notification::make()
                                ->title('Penempatan Ruang Ujian Berhasil')
                                ->body("Sebanyak {$records->count()} siswa telah ditempatkan ke: {$labelRuang}.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('resetPasswordBulk')
                        ->label('Reset Password Terpilih')
                        ->icon(Heroicon::OutlinedKey)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Password Siswa Terpilih')
                        ->modalDescription('Pilih metode reset password untuk seluruh siswa yang dicentang:')
                        ->modalSubmitActionLabel('Proses Reset')
                        ->form([
                            Radio::make('mode')
                                ->label('Metode Reset Password')
                                ->options([
                                    'generator' => 'Auto-Generator 6 Karakter Angka Acak (Unik tiap siswa)',
                                    'nis' => 'Reset ke Default NIS masing-masing siswa',
                                    'custom' => 'Gunakan satu password seragam untuk semua siswa terpilih',
                                ])
                                ->default('generator')
                                ->live(),

                            TextInput::make('custom_password')
                                ->label('Password Seragam Baru')
                                ->placeholder('Contoh: 123456')
                                ->visible(fn ($get) => $get('mode') === 'custom')
                                ->required(fn ($get) => $get('mode') === 'custom')
                                ->minLength(4),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $mode = $data['mode'] ?? 'generator';
                            $custom = $data['custom_password'] ?? '123456';
                            $count = 0;

                            foreach ($records as $siswa) {
                                if ($mode === 'generator') {
                                    $pass = Siswa::generateSixDigitPin();
                                } elseif ($mode === 'nis') {
                                    $pass = $siswa->nis ?: Siswa::generateSixDigitPin();
                                } else {
                                    $pass = $custom;
                                }
                                $siswa->resetPassword($pass);
                                $count++;
                            }

                            Notification::make()
                                ->title('Reset Password Massal Selesai')
                                ->body("Sebanyak {$count} siswa berhasil direset passwordnya.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
