<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$key = config('ai.gemini.api_key');
$models = ['gemini-3.8-flash', 'gemini-3.7-flash', 'gemini-3.6-flash', 'gemini-3.5-flash', 'gemini-3.5-flash-lite'];

foreach ($models as $m) {
    echo "Testing $m... ";
    try {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key={$key}";
        $res = Http::timeout(25)->post($url, [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => 'Halo, kembalikan teks OK']]]
            ]
        ]);
        if ($res->successful()) {
            echo "SUCCESS: " . trim($res->json('candidates.0.content.parts.0.text')) . PHP_EOL;
        } else {
            echo "FAIL: " . ($res->json('error.message') ?? $res->body()) . PHP_EOL;
        }
    } catch (Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . PHP_EOL;
    }
}
