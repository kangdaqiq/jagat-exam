<?php

namespace App\Filament\Resources\JadwalRuangs;

use App\Filament\Resources\JadwalRuangs\Pages\CreateJadwalRuang;
use App\Filament\Resources\JadwalRuangs\Pages\EditJadwalRuang;
use App\Filament\Resources\JadwalRuangs\Pages\ListJadwalRuangs;
use App\Filament\Resources\JadwalRuangs\Schemas\JadwalRuangForm;
use App\Filament\Resources\JadwalRuangs\Tables\JadwalRuangTable;
use App\Models\JadwalRuang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class JadwalRuangResource extends Resource
{
    protected static ?string $model = JadwalRuang::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Jadwal Ruang & Pengawas';

    protected static ?string $modelLabel = 'Jadwal Ruang & Pengawas';

    protected static ?string $pluralModelLabel = 'Jadwal Ruang & Pengawas';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Ujian';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'sesi_ke';

    public static function form(Schema $schema): Schema
    {
        return JadwalRuangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JadwalRuangTable::configure($table);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['ruangUjian', 'ujian.mapel', 'ujian.bankSoal', 'pengawas', 'pengawasPendamping']);

        $user = auth()->user();
        if ($user && $user->isGuru()) {
            return $query->where(function ($q) use ($user) {
                $q->where('pengawas_id', $user->id)
                  ->orWhere('pengawas_pendamping_id', $user->id);
            });
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJadwalRuangs::route('/'),
            'create' => CreateJadwalRuang::route('/create'),
            'edit' => EditJadwalRuang::route('/{record}/edit'),
        ];
    }
}

