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
        Schema::dropIfExists('ujian_jawabans');
        Schema::dropIfExists('ujian_sesis');

        Schema::create('ujian_sesis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_soal_id')->constrained('bank_soal')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token_sesi', 64)->unique();
            $table->string('nama_peserta');
            $table->string('nomor_peserta')->nullable();
            $table->timestamp('waktu_mulai')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->integer('durasi_menit')->default(60);

            $table->string('status')->default('mengerjakan'); // 'mengerjakan', 'selesai'
            $table->decimal('skor_total', 8, 2)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('ujian_jawabans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_sesi_id')->constrained('ujian_sesis')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();
            $table->integer('nomor_urut')->default(1);
            $table->json('jawaban_pilihan')->nullable();
            $table->text('jawaban_teks')->nullable();
            $table->boolean('is_ragu')->default(false);
            $table->boolean('is_benar')->nullable();
            $table->decimal('skor_didapat', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['ujian_sesi_id', 'soal_id']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ujian_jawabans');
        Schema::dropIfExists('ujian_sesis');
    }
};
