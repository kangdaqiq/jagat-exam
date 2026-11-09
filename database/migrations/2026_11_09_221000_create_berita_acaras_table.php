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
        Schema::create('berita_acaras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruang_ujian_id')->nullable()->constrained('ruang_ujians')->nullOnDelete();
            $table->foreignId('ujian_id')->nullable()->constrained('ujians')->nullOnDelete();
            $table->foreignId('jadwal_ruang_id')->nullable()->constrained('jadwal_ruangs')->nullOnDelete();
            $table->string('hari')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('waktu_mulai', 10)->nullable();
            $table->string('waktu_selesai', 10)->nullable();
            $table->string('nama_ujian')->nullable();
            $table->string('tahun_pelajaran')->nullable();
            $table->string('username_sekolah')->nullable();
            $table->string('nama_sekolah')->nullable();
            $table->string('id_server')->nullable();
            $table->string('ruang')->nullable();
            $table->string('sesi')->nullable();
            $table->unsignedInteger('jumlah_peserta_seharusnya')->default(0);
            $table->unsignedInteger('jumlah_hadir')->default(0);
            $table->unsignedInteger('jumlah_tidak_hadir')->default(0);
            $table->json('usernames_tidak_hadir')->nullable();
            $table->text('catatan')->nullable();
            $table->string('nama_pengawas')->nullable();
            $table->string('nip_pengawas')->nullable();
            $table->string('nama_ketua_ujian')->nullable();
            $table->string('nip_ketua_ujian')->nullable();
            $table->string('nama_kepala_sekolah')->nullable();
            $table->string('nip_kepala_sekolah')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berita_acaras');
    }
};

