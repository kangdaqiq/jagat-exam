<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Admin Utama
        User::firstOrCreate(
            ['email' => 'admin@cbt.id'],
            [
                'name' => 'Administrator CBT',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'nip' => '198001012005011001',
                'phone' => '081234567890',
                'is_active' => true,
            ]
        );

        // 2. Akun Guru Contoh 1
        User::firstOrCreate(
            ['email' => 'guru1@cbt.id'],
            [
                'name' => 'Budi Santoso, S.Pd.',
                'password' => Hash::make('guru123'),
                'role' => 'guru',
                'nip' => '198502152010011002',
                'phone' => '081298765432',
                'is_active' => true,
            ]
        );

        // 3. Akun Guru Contoh 2
        User::firstOrCreate(
            ['email' => 'guru2@cbt.id'],
            [
                'name' => 'Siti Rahmawati, M.Pd.',
                'password' => Hash::make('guru123'),
                'role' => 'guru',
                'nip' => '199003202015022003',
                'phone' => '081311223344',
                'is_active' => true,
            ]
        );
    }
}
