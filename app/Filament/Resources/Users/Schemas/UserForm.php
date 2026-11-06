<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->placeholder('Contoh: Budi Santoso, S.Pd.')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Username / Alamat Email')
                    ->placeholder('Contoh: yayuknuriya atau guru@sekolah.sch.id')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Biarkan kosong jika tidak ingin mengubah password.' : null),

                Select::make('role')
                    ->label('Peran Akun')
                    ->options([
                        'admin' => 'Administrator (Akses Penuh)',
                        'guru' => 'Guru (Manajemen Soal & Ujian)',
                    ])
                    ->default('guru')
                    ->required(),

                TextInput::make('nip')
                    ->label('NIP / NUPTK / Kode Guru')
                    ->placeholder('19850101...')
                    ->maxLength(50),

                TextInput::make('phone')
                    ->label('Nomor WhatsApp / HP')
                    ->placeholder('08123456789')
                    ->tel()
                    ->maxLength(20),

                Toggle::make('is_active')
                    ->label('Akun Aktif')
                    ->helperText('Jika nonaktif, pengguna tidak dapat masuk ke sistem.')
                    ->default(true)
                    ->required(),
            ]);
    }
}
