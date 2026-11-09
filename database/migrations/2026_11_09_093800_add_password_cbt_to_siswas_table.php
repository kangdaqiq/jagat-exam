<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            if (!Schema::hasColumn('siswas', 'password_cbt')) {
                $table->string('password_cbt', 64)->nullable()->after('nis');
            }
        });

        // Isi nilai awal password_cbt dengan NIS bagi siswa lama yang belum memiliki password_cbt
        DB::table('siswas')
            ->whereNull('password_cbt')
            ->whereNotNull('nis')
            ->update(['password_cbt' => DB::raw('nis')]);
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            if (Schema::hasColumn('siswas', 'password_cbt')) {
                $table->dropColumn('password_cbt');
            }
        });
    }
};

