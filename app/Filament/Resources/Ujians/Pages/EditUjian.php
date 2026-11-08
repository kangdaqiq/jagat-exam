<?php

namespace App\Filament\Resources\Ujians\Pages;

use App\Filament\Resources\Ujians\UjianResource;
use App\Models\BankSoal;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditUjian extends EditRecord
{
    protected static string $resource = UjianResource::class;

    protected static ?string $title = 'Kelola Jadwal & Sesi Ujian';

    protected Width | string | null $maxContentWidth = Width::SevenExtraLarge;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if ($user && $user->isGuru()) {
            $ownsBankSoal = BankSoal::where('id', $data['bank_soal_id'] ?? null)
                ->where('user_id', $user->id)
                ->exists();

            if (!$ownsBankSoal) {
                Notification::make()
                    ->title('Akses Bank Soal Ditolak')
                    ->body('Anda hanya dapat menggunakan Bank Soal milik Anda sendiri.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $data['guru_id'] = $user->id;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Perbarui peserta jika ada penambahan/perubahan kelas
        $this->getRecord()->syncPesertaFromKelas();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

