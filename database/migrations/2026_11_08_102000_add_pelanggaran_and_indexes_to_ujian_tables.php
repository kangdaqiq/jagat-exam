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
        Schema::table('ujian_sesis', function (Blueprint $table) {
            if (!Schema::hasColumn('ujian_sesis', 'jumlah_pelanggaran')) {
                $table->unsignedInteger('jumlah_pelanggaran')->default(0)->after('skor_total');
            }
            if (!Schema::hasColumn('ujian_sesis', 'terakhir_pelanggaran_at')) {
                $table->timestamp('terakhir_pelanggaran_at')->nullable()->after('jumlah_pelanggaran');
            }

            $table->index(['ujian_id', 'user_id', 'status'], 'ujian_sesis_ujian_user_status_idx');
        });

        Schema::table('ujian_pesertas', function (Blueprint $table) {
            $table->index(['ujian_id', 'status'], 'ujian_pesertas_ujian_status_idx');
            $table->index(['user_id', 'status'], 'ujian_pesertas_user_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ujian_pesertas', function (Blueprint $table) {
            $table->dropIndex('ujian_pesertas_ujian_status_idx');
            $table->dropIndex('ujian_pesertas_user_status_idx');
        });

        Schema::table('ujian_sesis', function (Blueprint $table) {
            $table->dropIndex('ujian_sesis_ujian_user_status_idx');
            if (Schema::hasColumn('ujian_sesis', 'terakhir_pelanggaran_at')) {
                $table->dropColumn('terakhir_pelanggaran_at');
            }
            if (Schema::hasColumn('ujian_sesis', 'jumlah_pelanggaran')) {
                $table->dropColumn('jumlah_pelanggaran');
            }
        });
    }
};

