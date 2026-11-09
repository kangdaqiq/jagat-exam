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
        Schema::create('jadwal_ruangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruang_ujian_id')->constrained('ruang_ujians')->cascadeOnDelete();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->foreignId('pengawas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pengawas_pendamping_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal');
            $table->string('sesi_ke', 50)->default('1');
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->string('token_ruang', 10)->nullable();
            $table->dateTime('token_generated_at')->nullable();
            $table->string('status', 30)->default('SCHEDULED'); // SCHEDULED, ONGOING, COMPLETED, CANCELLED
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['ruang_ujian_id', 'tanggal', 'sesi_ke']);
            $table->index(['pengawas_id', 'tanggal']);
            $table->index(['ujian_id', 'ruang_ujian_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_ruangs');
    }
};
