<?php

namespace App\Policies;

use App\Models\Mapel;
use App\Models\User;

class MapelPolicy
{
    /**
     * Guru dan Admin dapat melihat daftar mapel
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Guru dan Admin dapat melihat detail mapel
     */
    public function view(User $user, Mapel $mapel): bool
    {
        return true;
    }

    /**
     * Hanya Admin yang dapat menambah mapel baru
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya Admin yang dapat mengedit mapel
     */
    public function update(User $user, Mapel $mapel): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya Admin yang dapat menghapus mapel
     */
    public function delete(User $user, Mapel $mapel): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}

