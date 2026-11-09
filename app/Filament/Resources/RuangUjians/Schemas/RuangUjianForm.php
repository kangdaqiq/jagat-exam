<?php

namespace App\Filament\Resources\RuangUjians\Schemas;

use App\Models\RuangUjian;
use App\Models\Siswa;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RuangUjianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_ruang')
                    ->label('Kode Ruang')
                    ->placeholder('Contoh: R-01, LAB-KOMP-1')
                    ->maxLength(50),

                TextInput::make('nama_ruang')
                    ->label('Nama Ruang Ujian')
                    ->placeholder('Contoh: Ruang 1, Lab Komputer A')
                    ->required()
                    ->maxLength(255),

                TextInput::make('kapasitas')
                    ->label('Kapasitas Kursi Siswa')
                    ->numeric()
                    ->default(36)
                    ->minValue(1)
                    ->maxValue(200)
                    ->required(),

                Select::make('pengawas_id')
                    ->label('Guru Pengawas / Proktor Ruangan')
                    ->options(fn () => User::query()
                        ->whereIn('role', ['guru', 'admin'])
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih Guru Pengawas Ruangan'),

                Select::make('siswa_ids')
                    ->label('Peserta di Ruangan Ini (Bisa Campuran / Mix Lintas Kelas)')
                    ->helperText('Pilih siswa dari berbagai kelas/tingkatan untuk ditempatkan di ruang ujian ini. Saat ujian, soal tetap menyesuaikan kelas/tingkat masing-masing siswa.')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->dehydrated(false)
                    ->columnSpanFull()
                    ->options(function () {
                        return Siswa::query()
                            ->with(['kelas', 'ruangUjian'])
                            ->where('is_active', true)
                            ->orderBy('kelas_id')
                            ->orderBy('nama')
                            ->get()
                            ->mapWithKeys(function (Siswa $s) {
                                $kelasLabel = $s->kelas?->nama_kelas ?: 'Tanpa Kelas';
                                $ruangInfo = $s->ruangUjian ? " [Saat ini: {$s->ruangUjian->nama_ruang}]" : '';
                                return [
                                    $s->id => "[{$kelasLabel}] {$s->nama} (NIS: {$s->nis}){$ruangInfo}",
                                ];
                            });
                    })
                    ->afterStateHydrated(function (Select $component, ?RuangUjian $record) {
                        if ($record && $record->exists) {
                            $component->state($record->siswas()->pluck('id')->all());
                        }
                    })
                    ->saveRelationshipsUsing(function (RuangUjian $record, ?array $state) {
                        $selectedIds = array_map('intval', $state ?? []);

                        // Lepaskan siswa lama yang tidak lagi dipilih di ruang ini
                        Siswa::where('ruang_ujian_id', $record->id)
                            ->whereNotIn('id', $selectedIds)
                            ->update(['ruang_ujian_id' => null]);

                        // Masukkan siswa terpilih ke ruang ini
                        if (!empty($selectedIds)) {
                            Siswa::whereIn('id', $selectedIds)
                                ->update(['ruang_ujian_id' => $record->id]);
                        }
                    }),

                Toggle::make('is_active')
                    ->label('Ruang Aktif Digunakan')
                    ->default(true),
            ]);
    }
}

