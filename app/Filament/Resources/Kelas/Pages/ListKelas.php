<?php

namespace App\Filament\Resources\Kelas\Pages;

use App\Filament\Resources\Kelas\KelasResource;
use App\Services\AbsenSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListKelas extends ListRecords
{
    protected static string $resource = KelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_absen')
                ->label('Tarik dari Web Absen')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('success')
                ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                ->requiresConfirmation()
                ->modalHeading('Tarik Data Kelas')
                ->modalDescription('Sistem akan mengambil dan memperbarui daftar kelas dari Web Absensi ke database CBT.')
                ->modalSubmitActionLabel('Tarik Sekarang')
                ->action(function (AbsenSyncService $syncService) {
                    try {
                        $result = $syncService->syncData([
                            'types' => ['kelas'],
                        ]);

                        $created = $result['kelas']['created'] ?? 0;
                        $updated = $result['kelas']['updated'] ?? 0;

                        Notification::make()
                            ->title('Sinkronisasi Kelas Berhasil')
                            ->body("Data baru: {$created}, diperbarui: {$updated}.")
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
            CreateAction::make()
                ->visible(fn () => auth()->user()?->isAdmin() ?? false),
        ];
    }
}
