<?php

namespace App\Filament\Widgets;

use App\Models\Ujian;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TailadminLiveExamsWidget extends Widget
{
    protected static ?int $sort = 2; // Displayed right under the 4 metric cards

    protected string $view = 'filament.widgets.tailadmin-live-exams';

    protected int | string | array $columnSpan = 'full';

    public function getUserRole(): string
    {
        return Auth::user()?->isAdmin() ? 'admin' : 'guru';
    }

    public function getRecentUjians()
    {
        $user = Auth::user();
        return Ujian::with(['mapel', 'kelas', 'guru'])
            ->forUser($user)
            ->latest('id')
            ->take(5)
            ->get();
    }
}
