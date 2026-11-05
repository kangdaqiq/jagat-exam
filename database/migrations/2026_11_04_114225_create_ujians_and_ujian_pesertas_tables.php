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
        // 1. Tabel Agenda / Jadwal Ujian
        Schema::create('ujians', function (Blueprint $table) {
            $table->id();
            $table->string('exam_id', 32)->unique();
            $table->string('nama_ujian');
            $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
            $table->foreignId('bank_soal_id')->constrained('bank_soal')->cascadeOnDelete();
            $table->foreignId('guru_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal')->nullable();
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->integer('durasi_menit')->default(60);

            // Lifecycle status: SCHEDULED -> ONGOING -> COMPLETED
            $table->string('status', 20)->default('SCHEDULED');
            $table->string('token_ujian', 16)->nullable()->index();
            $table->timestamp('token_generated_at')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // 2. Pivot Ujian dan Kelas (Satu ujian bisa diikuti beberapa kelas)
        Schema::create('ujian_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ujian_id', 'kelas_id']);
        });

        // 3. Tabel Peserta Ujian
        Schema::create('ujian_pesertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujians')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('BELUM_MULAI'); // BELUM_MULAI, SEDANG_MENGERJAKAN, SELESAI
            $table->foreignId('ujian_sesi_id')->nullable()->constrained('ujian_sesis')->nullOnDelete();
            $table->decimal('nilai', 8, 2)->nullable();
            $table->timestamp('waktu_mulai')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->timestamps();

            $table->unique(['ujian_id', 'siswa_id']);
        });

        // 4. Update ujian_sesis agar berelasi dengan ujian
        Schema::table('ujian_sesis', function (Blueprint $table) {
            if (!Schema::hasColumn('ujian_sesis', 'ujian_id')) {
                $table->foreignId('ujian_id')->nullable()->after('id')->constrained('ujians')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table) {
            if (Schema::hasColumn('ujian_sesis', 'ujian_id')) {
                $table->dropForeign(['ujian_id']);
                $table->dropColumn('ujian_id');
            }
        });

        Schema::dropIfExists('ujian_pesertas');
        Schema::dropIfExists('ujian_kelas');
        Schema::dropIfExists('ujians');
    }
};
