<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\AbsenSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_guru')
                ->label('Tarik Guru dari Web Absen')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('success')
                ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                ->requiresConfirmation()
                ->modalHeading('Tarik Akun Guru dari Web Absensi')
                ->modalDescription('Sistem akan menyinkronkan data guru dari Web Absensi dan membuatkan akun login Guru CBT (password default: NIP guru).')
                ->modalSubmitActionLabel('Tarik Sekarang')
                ->action(function (AbsenSyncService $syncService) {
                    try {
                        $result = $syncService->syncData([
                            'types' => ['guru'],
                        ]);

                        $created = $result['guru']['created'] ?? 0;
                        $updated = $result['guru']['updated'] ?? 0;

                        Notification::make()
                            ->title('Sinkronisasi Guru Berhasil')
                            ->body("Akun guru baru: {$created}, diperbarui: {$updated}.")
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
