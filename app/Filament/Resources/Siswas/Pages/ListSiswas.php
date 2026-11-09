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
            Action::make('cetakKartuPeserta')
                ->label('Cetak Kartu Peserta')
                ->icon(Heroicon::OutlinedIdentification)
                ->color('info')
                ->url(fn () => route('kartu-peserta.print'))
                ->openUrlInNewTab(),

            Action::make('resetAllPassword')
                ->label('Reset Password Seluruh Siswa')
                ->icon(Heroicon::OutlinedKey)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Password SELURUH Siswa')
                ->modalDescription('PERHATIAN: Tindakan ini akan mereset password akun login seluruh siswa aktif dan memperbarui Kartu Peserta:')
                ->modalSubmitActionLabel('Proses Reset Seluruh Siswa')
                ->form([
                    Radio::make('mode')
                        ->label('Metode Reset Password')
                        ->options([
                            'generator' => 'Auto-Generator 6 Karakter Angka Acak (Unik tiap siswa)',
                            'nis' => 'Reset ke Default NIS masing-masing siswa',
                            'custom' => 'Reset ke satu password serentak untuk seluruh siswa',
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
                ->action(function (array $data) {
                    $mode = $data['mode'] ?? 'generator';
                    $custom = $data['custom_password'] ?? '123456';

                    $query = Siswa::query()->with('user')->forUser(auth()->user());
                    $count = 0;

                    foreach ($query->lazy() as $siswa) {
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

                    $ket = match ($mode) {
                        'generator' => '6 karakter angka acak unik untuk masing-masing siswa.',
                        'nis' => 'default NIS masing-masing siswa.',
                        default => "password seragam: {$custom}",
                    };

                    Notification::make()
                        ->title('Reset Seluruh Password Selesai')
                        ->body("Sebanyak {$count} siswa berhasil direset passwordnya ke {$ket}")
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
