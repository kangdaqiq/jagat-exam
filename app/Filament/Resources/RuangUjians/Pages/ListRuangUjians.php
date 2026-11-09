<?php

namespace App\Filament\Resources\RuangUjians\Pages;

use App\Filament\Resources\RuangUjians\RuangUjianResource;
use App\Models\RuangUjian;
use App\Models\Siswa;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListRuangUjians extends ListRecords
{
    protected static string $resource = RuangUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('distribusiOtomatis')
                ->label('Bagi Siswa ke Ruang (Mix Kelas)')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('info')
                ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                ->modalHeading('Distribusi Otomatis Siswa ke Ruang Ujian (Mix Lintas Kelas)')
                ->modalDescription('Sistem dapat membagi seluruh siswa aktif secara merata dan selang-seling (zig-zag lintas kelas/tingkatan) ke dalam ruang-ruang ujian agar 1 ruangan berisi campuran beberapa kelas.')
                ->modalSubmitActionLabel('Proses Pembagian Ruang')
                ->form([
                    TextInput::make('jumlah_ruang')
                        ->label('Jumlah Ruang Ujian yang Disiapkan')
                        ->helperText('Jika ruang belum ada (misal Ruang 1 s/d Ruang N), sistem akan membuatnya otomatis.')
                        ->numeric()
                        ->default(fn () => max(2, RuangUjian::count()))
                        ->minValue(1)
                        ->maxValue(50)
                        ->required(),

                    TextInput::make('kapasitas_per_ruang')
                        ->label('Kapasitas Maksimal per Ruang')
                        ->numeric()
                        ->default(36)
                        ->minValue(5)
                        ->maxValue(200)
                        ->required(),

                    Radio::make('metode')
                        ->label('Metode Pembagian Siswa')
                        ->options([
                            'zigzag_kelas' => 'Mix Lintas Kelas Merata (Selang-seling antar kelas di setiap ruangan)',
                            'urut_kelas' => 'Berurutan per Kelas (Penuhi Ruang 1 lalu lanjut Ruang 2, dst.)',
                        ])
                        ->default('zigzag_kelas')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $jumlahRuang = max(1, (int) ($data['jumlah_ruang'] ?? 2));
                    $kapasitas = max(5, (int) ($data['kapasitas_per_ruang'] ?? 36));
                    $metode = $data['metode'] ?? 'zigzag_kelas';

                    // Pastikan ruang 1..N tersedia
                    $ruangs = collect();
                    for ($i = 1; $i <= $jumlahRuang; $i++) {
                        $ruang = RuangUjian::firstOrCreate(
                            ['nama_ruang' => "Ruang {$i}"],
                            [
                                'kode_ruang' => sprintf('R-%02d', $i),
                                'kapasitas' => $kapasitas,
                                'is_active' => true,
                            ]
                        );
                        $ruang->update(['kapasitas' => $kapasitas, 'is_active' => true]);
                        $ruangs->push($ruang);
                    }

                    $siswas = Siswa::where('is_active', true)
                        ->orderBy('kelas_id')
                        ->orderBy('nama')
                        ->get();

                    if ($siswas->isEmpty()) {
                        Notification::make()
                            ->title('Tidak Ada Siswa Aktif')
                            ->body('Belum ada data siswa aktif untuk didistribusikan ke ruang ujian.')
                            ->warning()
                            ->send();
                        return;
                    }

                    if ($metode === 'zigzag_kelas') {
                        // Distribusi selang-seling (round-robin) sehingga tiap ruang berisi campuran siswa dari berbagai kelas
                        foreach ($siswas->values() as $idx => $siswa) {
                            $targetRuang = $ruangs[$idx % $ruangs->count()];
                            $siswa->update(['ruang_ujian_id' => $targetRuang->id]);
                        }
                    } else {
                        // Berurutan sesuai kapasitas per ruang
                        foreach ($siswas->values() as $idx => $siswa) {
                            $ruangIndex = min((int) floor($idx / $kapasitas), $ruangs->count() - 1);
                            $targetRuang = $ruangs[$ruangIndex];
                            $siswa->update(['ruang_ujian_id' => $targetRuang->id]);
                        }
                    }

                    Notification::make()
                        ->title('Distribusi Ruang Ujian Selesai')
                        ->body("Sebanyak {$siswas->count()} siswa telah ditempatkan ke dalam {$ruangs->count()} ruang ujian.")
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->label('Tambah Ruang Ujian')
                ->visible(fn () => auth()->user()?->isAdmin() ?? false),
        ];
    }
}

