<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Filament\Resources\Siswas\SiswaResource;
use App\Services\AbsenSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSiswas extends ListRecords
{
    protected static string $resource = SiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
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

