<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jadwal_ruang_ujian') && ! Schema::hasColumn('jadwal_ruang_ujian', 'urutan')) {
            Schema::table('jadwal_ruang_ujian', function (Blueprint $table) {
                $table->unsignedInteger('urutan')->default(1)->after('ujian_id');
            });
        }

        if (Schema::hasTable('ujian_sesis') && ! Schema::hasColumn('ujian_sesis', 'jadwal_ruang_id')) {
            Schema::table('ujian_sesis', function (Blueprint $table) {
                $table->foreignId('jadwal_ruang_id')
                    ->nullable()
                    ->after('ruang_ujian_id')
                    ->constrained('jadwal_ruangs')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ujian_sesis') && Schema::hasColumn('ujian_sesis', 'jadwal_ruang_id')) {
            Schema::table('ujian_sesis', function (Blueprint $table) {
                $table->dropForeign(['jadwal_ruang_id']);
                $table->dropColumn('jadwal_ruang_id');
            });
        }

        if (Schema::hasTable('jadwal_ruang_ujian') && Schema::hasColumn('jadwal_ruang_ujian', 'urutan')) {
            Schema::table('jadwal_ruang_ujian', function (Blueprint $table) {
                $table->dropColumn('urutan');
            });
        }
    }
};

