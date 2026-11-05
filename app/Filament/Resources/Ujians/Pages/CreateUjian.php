<?php

namespace App\Filament\Resources\Ujians\Pages;

use App\Filament\Resources\Ujians\UjianResource;
use App\Models\Ujian;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateUjian extends CreateRecord
{
    protected static string $resource = UjianResource::class;

    protected static ?string $title = 'Jadwalkan Pelaksanaan Ujian Baru';

    protected Width | string | null $maxContentWidth = Width::SevenExtraLarge;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Keamanan: Jika guru membuat ujian, tipe ujian dikunci ke Asesmen Harian
        if ($user && $user->isGuru()) {
            $data['tipe_ujian'] = Ujian::TIPE_ASESMEN_HARIAN;
            $data['guru_id'] = $user->id;
        } else {
            $data['tipe_ujian'] = $data['tipe_ujian'] ?? Ujian::TIPE_ASESMEN_HARIAN;
        }

        // Fitur Asesmen Harian: Guru langsung bisa generate token instan tanpa jadwal
        $isLangsungToken = !empty($this->data['langsung_generate_token']) && $data['tipe_ujian'] === Ujian::TIPE_ASESMEN_HARIAN;

        if ($isLangsungToken) {
            $data['status'] = 'ONGOING';
            $data['token_ujian'] = Ujian::generateToken();
            $data['token_generated_at'] = now();
            // Jika tanggal kosong, set ke hari ini
            if (empty($data['tanggal'])) {
                $data['tanggal'] = now()->toDateString();
            }
        } else {
            $data['status'] = 'SCHEDULED';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Otomatis daftarkan seluruh siswa di rombel kelas yang dipilih
        $record = $this->getRecord();
        $record->syncPesertaFromKelas();

        // Jika langsung generate token, kirim notifikasi berisi token
        if ($record->status === 'ONGOING' && $record->token_ujian) {
            Notification::make()
                ->title('Token Ujian Berhasil Dibuat!')
                ->body("KODE TOKEN AKSES UJIAN:\n\n# **{$record->token_ujian}**\n\nToken aktif selama **10 menit**. Siswa dapat langsung memasukkan token ini di portal ujian.")
                ->success()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        $record = $this->getRecord();
        if ($record->status === 'ONGOING' && $record->token_ujian) {
            return "Asesmen Harian Langsung Aktif (Token: {$record->token_ujian})";
        }
        return 'Ujian berhasil dijadwalkan (Status: SCHEDULED).';
    }
}
