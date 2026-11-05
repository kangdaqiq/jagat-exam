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
        Schema::table('bank_soal', function (Blueprint $table) {
            $table->boolean('acak_soal')->default(true)->after('deskripsi');
            $table->boolean('acak_opsi')->default(true)->after('acak_soal');
        });

        Schema::table('ujians', function (Blueprint $table) {
            $table->boolean('acak_soal')->default(true)->after('durasi_menit');
            $table->boolean('acak_opsi')->default(true)->after('acak_soal');
        });

        Schema::table('ujian_jawabans', function (Blueprint $table) {
            $table->json('urutan_opsi')->nullable()->after('nomor_urut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_soal', function (Blueprint $table) {
            $table->dropColumn(['acak_soal', 'acak_opsi']);
        });

        Schema::table('ujians', function (Blueprint $table) {
            $table->dropColumn(['acak_soal', 'acak_opsi']);
        });

        Schema::table('ujian_jawabans', function (Blueprint $table) {
            $table->dropColumn('urutan_opsi');
        });
    }
};
