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
        Schema::create('mapels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_id')->nullable()->index();
            $table->string('nama_mapel');
            $table->string('kode')->nullable();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('school_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sync_id')->nullable()->index();
            $table->string('nama_kelas');
            $table->string('tingkat')->nullable();
            $table->string('jurusan')->nullable();
            $table->string('wali_kelas')->nullable();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('school_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('mapels');
    }
};

