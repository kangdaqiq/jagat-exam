<?php

namespace App\Filament\Resources\Mapels\Pages;

use App\Filament\Resources\Mapels\MapelResource;
use App\Services\AbsenSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMapels extends ListRecords
{
    protected static string $resource = MapelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_absen')
                ->label('Tarik dari Web Absen')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('success')
                ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                ->requiresConfirmation()
                ->modalHeading('Tarik Data Mata Pelajaran')
                ->modalDescription('Sistem akan mengambil dan memperbarui daftar mata pelajaran dari Web Absensi ke database CBT.')
                ->modalSubmitActionLabel('Tarik Sekarang')
                ->action(function (AbsenSyncService $syncService) {
                    try {
                        $result = $syncService->syncData([
                            'types' => ['mapel'],
                        ]);

                        $created = $result['mapel']['created'] ?? 0;
                        $updated = $result['mapel']['updated'] ?? 0;

                        Notification::make()
                            ->title('Sinkronisasi Mapel Berhasil')
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
