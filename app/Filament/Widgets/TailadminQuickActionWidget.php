<?php

namespace App\Filament\Widgets;

use App\Models\Ujian;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TailadminQuickActionWidget extends Widget
{
    protected static ?int $sort = 0; // Displayed first above stats

    protected string $view = 'filament.widgets.tailadmin-quick-action';

    protected int | string | array $columnSpan = 'full';

    public function getUserRole(): string
    {
        return Auth::user()?->isAdmin() ? 'admin' : 'guru';
    }

    public function getActiveUjianCount(): int
    {
        return Ujian::where('status', 'aktif')->count();
    }
}
