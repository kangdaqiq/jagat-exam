<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Filament\Resources\Siswas\SiswaResource;
use App\Models\Siswa;
use App\Services\AbsenSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSiswas extends ListRecords
{
    protected static string $resource = SiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetAllPassword')
                ->label('Reset Password Seluruh Siswa')
                ->icon(Heroicon::OutlinedKey)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Password SELURUH Siswa')
                ->modalDescription('PERHATIAN: Tindakan ini akan mereset password akun login seluruh siswa aktif. Silakan pilih metode password baru:')
                ->modalSubmitActionLabel('Proses Reset Seluruh Siswa')
                ->form([
                    Radio::make('mode')
                        ->label('Metode Reset Password')
                        ->options([
                            'nis' => 'Reset ke NIS masing-masing siswa (Rekomendasi CBT)',
                            'custom' => 'Reset ke satu password serentak untuk seluruh siswa',
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
                ->action(function (array $data) {
                    $mode = $data['mode'] ?? 'nis';
                    $custom = $data['custom_password'] ?? 'siswa12345';

                    $query = Siswa::query()->with('user')->forUser(auth()->user());
                    $count = 0;

                    foreach ($query->lazy() as $siswa) {
                        $pass = ($mode === 'nis') ? ($siswa->nis ?: 'siswa12345') : $custom;
                        $siswa->resetPassword($pass);
                        $count++;
                    }

                    Notification::make()
                        ->title('Reset Seluruh Password Selesai')
                        ->body("Sebanyak {$count} siswa berhasil direset passwordnya ke " . ($mode === 'nis' ? 'NIS masing-masing siswa.' : "password seragam: {$custom}"))
                        ->success()
                        ->send();
                }),

            Action::make('sync_absen')
                ->label('Tarik Siswa dari Web Absensi')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('success')
                ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                ->requiresConfirmation()
                ->modalHeading('Tarik Data Siswa & Akun CBT')
                ->modalDescription('Sistem akan menarik data siswa dan membuat/memperbarui akun peserta ujian (login via NIS) dari Web Absensi ke MySQL CBT.')
                ->modalSubmitActionLabel('Tarik Sekarang')
                ->action(function (AbsenSyncService $syncService) {
                    try {
                        $result = $syncService->syncData([
                            'types' => ['kelas', 'siswa'],
                        ]);

                        $created = $result['siswa']['created'] ?? 0;
                        $updated = $result['siswa']['updated'] ?? 0;
                        $total = $result['siswa']['total'] ?? 0;

                        Notification::make()
                            ->title('Sinkronisasi Siswa Berhasil')
                            ->body("Total: {$total} siswa (Baru: {$created}, Diperbarui: {$updated}) tersimpan.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sinkronisasi Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            CreateAction::make(),
        ];
    }
}
