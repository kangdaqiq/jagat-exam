<?php

use App\Models\Ujian;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ujian:auto-close', function () {
    $closed = Ujian::runGlobalAutoClose();
    $this->info("Auto-close ujian selesai. {$closed} sesi/peserta kedaluwarsa telah dikoreksi & ditutup otomatis.");
})->purpose('Otomatis menyelesaikan & mengoreksi sesi ujian siswa yang telah melewati batas waktu');

Schedule::command('ujian:auto-close')->everyMinute()->withoutOverlapping();
