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
        Schema::table('soals', function (Blueprint $table) {
            $table->string('tingkat_kesulitan', 20)->default('MOTS')->after('bobot_nilai')->comment('LOTS | MOTS | HOTS');
            $table->string('taksonomi_bloom', 10)->default('C3')->after('tingkat_kesulitan')->comment('C1 | C2 | C3 | C4 | C5 | C6');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soals', function (Blueprint $table) {
            $table->dropColumn(['tingkat_kesulitan', 'taksonomi_bloom']);
        });
    }
};
