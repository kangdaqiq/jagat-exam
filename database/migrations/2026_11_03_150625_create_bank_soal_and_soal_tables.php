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
        // 1. Tabel Bank Soal
        Schema::create('bank_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
            $table->string('judul');
            $table->string('kode_bank', 50)->unique();
            $table->string('tingkat', 20)->nullable();
            $table->string('jurusan', 100)->nullable();
            $table->text('deskripsi')->nullable();
            $table->decimal('total_bobot', 8, 2)->default(100.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Pivot Bank Soal ke Kelas
        Schema::create('bank_soal_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_soal_id')->constrained('bank_soal')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bank_soal_id', 'kelas_id']);
        });

        // 3. Tabel Butir Soal
        Schema::create('soals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_soal_id')->constrained('bank_soal')->cascadeOnDelete();
            $table->integer('nomor_urut')->default(1);
            $table->enum('tipe_soal', [
                'pilihan_ganda',
                'pg_kompleks',
                'benar_salah',
                'menjodohkan',
                'isian',
                'essay'
            ])->default('pilihan_ganda');
            $table->longText('pertanyaan');
            $table->boolean('is_rtl')->default(false)->comment('Arah teks kanan ke kiri untuk Arab');
            $table->enum('media_type', ['none', 'image', 'audio', 'video'])->default('none');
            $table->string('media_file')->nullable();
            $table->integer('audio_max_play')->default(0)->comment('0 = tidak terbatas, > 0 = batasan putar');
            $table->decimal('bobot_nilai', 5, 2)->default(1.00);
            $table->text('kunci_isian')->nullable();
            $table->text('rubrik_essay')->nullable();
            $table->text('pembahasan')->nullable();
            $table->timestamps();
        });

        // 4. Tabel Opsi Jawaban
        Schema::create('opsi_jawabans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();
            $table->string('label', 10)->nullable(); // A, B, C, D, E atau Pernyataan 1, 2
            $table->text('teks_opsi')->nullable();
            $table->string('gambar_opsi')->nullable();
            $table->boolean('is_jawaban_benar')->default(false);
            $table->text('pasangan_matching')->nullable(); // Pasangan untuk soal tipe menjodohkan
            $table->integer('nomor_urut')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opsi_jawabans');
        Schema::dropIfExists('soals');
        Schema::dropIfExists('bank_soal_kelas');
        Schema::dropIfExists('bank_soal');
    }
};
