<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_ruangs', function (Blueprint $table) {
            $table->unsignedBigInteger('ujian_id')->nullable()->change();
        });

        Schema::create('jadwal_ruang_ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_ruang_id')->constrained('jadwal_ruangs')->cascadeOnDelete();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['jadwal_ruang_id', 'ujian_id']);
        });

        // Migrasi data jadwal_ruangs yang sudah ada ke tabel pivot
        $existing = DB::table('jadwal_ruangs')
            ->whereNotNull('ujian_id')
            ->select('id', 'ujian_id')
            ->get();

        $now = now();
        foreach ($existing as $row) {
            DB::table('jadwal_ruang_ujian')->insertOrIgnore([
                'jadwal_ruang_id' => $row->id,
                'ujian_id' => $row->ujian_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_ruang_ujian');

        Schema::table('jadwal_ruangs', function (Blueprint $table) {
            $table->unsignedBigInteger('ujian_id')->nullable(false)->change();
        });
    }
};

