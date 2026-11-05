<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    /**
     * Guru dan Admin dapat melihat daftar kelas
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Guru dan Admin dapat melihat detail kelas
     */
    public function view(User $user, Kelas $kelas): bool
    {
        return true;
    }

    /**
     * Hanya Admin yang dapat menambah kelas baru
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya Admin yang dapat mengedit kelas
     */
    public function update(User $user, Kelas $kelas): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya Admin yang dapat menghapus kelas
     */
    public function delete(User $user, Kelas $kelas): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}

