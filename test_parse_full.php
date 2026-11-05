<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fullText = file_get_contents(__DIR__ . '/user_prompt.txt');
$service = app(\App\Services\AiQuestionService::class);

echo "Calling parseRawTextToSoals with user text... length: " . strlen($fullText) . PHP_EOL;

try {
    $res = $service->parseRawTextToSoals($fullText, [
        'bobot_nilai' => 2.0,
        'model' => 'gemini-3.6-flash',
    ]);
    echo "SUCCESS: Parsed " . count($res) . " questions!" . PHP_EOL;
    $pg = 0; $essay = 0;
    foreach ($res as $i => $q) {
        if ($q['tipe_soal'] === 'pilihan_ganda') $pg++;
        if ($q['tipe_soal'] === 'essay') $essay++;
        echo ($i + 1) . ". [" . $q['tipe_soal'] . "] " . strip_tags(substr($q['pertanyaan'], 0, 50)) . " (Opsi: " . count($q['opsi_jawabans']) . ")" . PHP_EOL;
    }
    echo "Summary: PG = $pg, Essay = $essay" . PHP_EOL;
} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . PHP_EOL;
}
