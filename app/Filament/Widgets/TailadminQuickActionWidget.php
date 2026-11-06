<?php

namespace App\Filament\Widgets;

use App\Models\Ujian;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TailadminQuickActionWidget extends Widget
{
    protected static ?int $sort = 0;

    protected string $view = 'filament.widgets.tailadmin-quick-action';

    protected int | string | array $columnSpan = 'full';

    public function getUser(): ?User
    {
        return Auth::user();
    }

    public function getUserRole(): string
    {
        return $this->getUser()?->isAdmin() ? 'admin' : 'guru';
    }

    public function getUserInitials(): string
    {
        $name = trim($this->getUser()?->name ?? 'User');
        $words = preg_split('/\s+/', $name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper(mb_substr($w, 0, 1));
        }
        return $initials ?: 'CBT';
    }

    public function getActiveUjianCount(): int
    {
        $user = $this->getUser();
        return Ujian::forUser($user)->where('status', 'ONGOING')->count();
    }
}
