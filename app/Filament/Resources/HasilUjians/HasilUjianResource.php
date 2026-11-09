<?php

namespace App\Filament\Resources\HasilUjians;

use App\Filament\Resources\HasilUjians\Pages\ListHasilUjians;
use App\Filament\Resources\HasilUjians\Pages\ViewHasilUjian;
use App\Filament\Resources\HasilUjians\Tables\HasilUjianTable;
use App\Models\HasilUjian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class HasilUjianResource extends Resource
{
    protected static ?string $model = HasilUjian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Hasil & Nilai Ujian';

    protected static ?string $modelLabel = 'Hasil & Rekap Ujian';

    protected static ?string $pluralModelLabel = 'Hasil & Rekap Ujian';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Ujian';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin', 'guru']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forUser(Auth::user())
            ->with(['mapel', 'bankSoal', 'guru', 'kelas'])
            ->withCount('pesertas');
    }

    public static function table(Table $table): Table
    {
        return HasilUjianTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHasilUjians::route('/'),
            'detail' => ViewHasilUjian::route('/{record}'),
        ];
    }
}

