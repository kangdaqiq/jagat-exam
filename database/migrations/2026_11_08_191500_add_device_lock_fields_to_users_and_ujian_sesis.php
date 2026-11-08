<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'active_session_token')) {
                $table->string('active_session_token', 100)->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('active_session_token');
            }
        });

        Schema::table('ujian_sesis', function (Blueprint $table) {
            if (!Schema::hasColumn('ujian_sesis', 'device_token')) {
                $table->string('device_token', 100)->nullable()->after('token_sesi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('users', 'active_session_token')) {
                $cols[] = 'active_session_token';
            }
            if (Schema::hasColumn('users', 'last_login_at')) {
                $cols[] = 'last_login_at';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('ujian_sesis', function (Blueprint $table) {
            if (Schema::hasColumn('ujian_sesis', 'device_token')) {
                $table->dropColumn('device_token');
            }
        });
    }
};

