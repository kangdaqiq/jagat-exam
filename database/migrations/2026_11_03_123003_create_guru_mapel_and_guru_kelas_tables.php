<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pivot Guru - Mapel (Mata pelajaran yang diampu guru)
        Schema::create('guru_mapel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
            $table->unique(['user_id', 'mapel_id']);
        });

        // 2. Pivot Guru - Kelas (Kelas yang diajar oleh guru)
        Schema::create('guru_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->unique(['user_id', 'kelas_id']);
        });

        // 3. Tabel Detail Pengampu (Spesifik: Guru X mengajar Mapel Y di Kelas Z)
        Schema::create('guru_pengampu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'mapel_id', 'kelas_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guru_pengampu');
        Schema::dropIfExists('guru_kelas');
        Schema::dropIfExists('guru_mapel');
    }
};

