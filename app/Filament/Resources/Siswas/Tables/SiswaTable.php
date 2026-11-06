<?php

namespace App\Filament\Resources\Siswas\Tables;

use App\Models\Siswa;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
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

                TextColumn::make('user.email')
                    ->label('Akun Login CBT')
                    ->placeholder('Belum dibuat')
                    ->badge()
                    ->color('success')
                    ->copyable()
                    ->toggleable(),

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

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Hanya Siswa Aktif')
                    ->falseLabel('Hanya Siswa Nonaktif'),
            ])
            ->recordActions([
                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->modalHeading(fn (Siswa $record) => "Reset Password: {$record->nama}")
                    ->modalDescription(fn (Siswa $record) => "Masukkan password baru untuk siswa ini. Secara default terisi NIS siswa ({$record->nis}).")
                    ->modalSubmitActionLabel('Simpan Password')
                    ->form([
                        TextInput::make('password')
                            ->label('Password Baru')
                            ->default(fn (Siswa $record) => $record->nis ?: 'siswa12345')
                            ->helperText('Siswa dapat login menggunakan NIS dan password ini di portal CBT.')
                            ->required()
                            ->minLength(4),
                    ])
                    ->action(function (Siswa $record, array $data) {
                        $newPassword = $data['password'];
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
                                    'nis' => 'Reset ke NIS masing-masing siswa (Rekomendasi CBT)',
                                    'custom' => 'Gunakan satu password seragam untuk semua siswa terpilih',
                                ])
                                ->default('nis')
                                ->live(),

                            TextInput::make('custom_password')
                                ->label('Password Seragam Baru')
                                ->placeholder('Contoh: siswa12345')
                                ->visible(fn ($get) => $get('mode') === 'custom')
                                ->required(fn ($get) => $get('mode') === 'custom')
                                ->minLength(4),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $mode = $data['mode'] ?? 'nis';
                            $custom = $data['custom_password'] ?? 'siswa12345';
                            $count = 0;

                            foreach ($records as $siswa) {
                                $pass = ($mode === 'nis') ? ($siswa->nis ?: 'siswa12345') : $custom;
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
