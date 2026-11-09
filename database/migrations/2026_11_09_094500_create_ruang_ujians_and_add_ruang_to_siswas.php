<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ruang_ujians')) {
            Schema::create('ruang_ujians', function (Blueprint $table) {
                $table->id();
                $table->string('kode_ruang', 30)->unique();
                $table->string('nama_ruang', 100);
                $table->unsignedInteger('kapasitas')->default(36);
                $table->foreignId('pengawas_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('token_ruang', 12)->nullable();
                $table->timestamp('token_generated_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('siswas', function (Blueprint $table) {
            if (!Schema::hasColumn('siswas', 'ruang_ujian_id')) {
                $table->foreignId('ruang_ujian_id')
                    ->nullable()
                    ->after('kelas_id')
                    ->constrained('ruang_ujians')
                    ->nullOnDelete();
            }
        });

        Schema::table('ujian_sesis', function (Blueprint $table) {
            if (!Schema::hasColumn('ujian_sesis', 'ruang_ujian_id')) {
                $table->foreignId('ruang_ujian_id')
                    ->nullable()
                    ->after('ujian_id')
                    ->constrained('ruang_ujians')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ujian_sesis', function (Blueprint $table) {
            if (Schema::hasColumn('ujian_sesis', 'ruang_ujian_id')) {
                $table->dropConstrainedForeignId('ruang_ujian_id');
            }
        });

        Schema::table('siswas', function (Blueprint $table) {
            if (Schema::hasColumn('siswas', 'ruang_ujian_id')) {
                $table->dropConstrainedForeignId('ruang_ujian_id');
            }
        });

        Schema::dropIfExists('ruang_ujians');
    }
};

