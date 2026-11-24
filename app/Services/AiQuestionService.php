<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiQuestionService
{
    /**
     * Sumber hasil parsing terakhir: 'ai' | 'lokal' (parser regex) | 'mock' (testing).
     */
    public string $lastParseSource = 'lokal';

    /**
     * Seragamkan teks hasil copy-paste (Word / chatbot): karakter tak terlihat,
     * spasi khusus, tanda kutip melengkung, dan ujung baris.
     */
    public function normalizeRawText(string $text): string
    {
        $text = str_replace(["\u{FEFF}", "\u{200B}", "\u{200C}", "\u{200D}", "\u{00AD}"], '', $text);
        $text = str_replace(["\u{00A0}", "\u{2007}", "\u{202F}", "\t"], ' ', $text);
        $text = str_replace(["\u{201C}", "\u{201D}", "\u{201E}"], '"', $text);
        $text = str_replace(["\u{2018}", "\u{2019}"], "'", $text);
        $text = str_replace(["\r\n", "\r", "\u{2028}", "\u{2029}"], "\n", $text);
        // Hapus spasi di akhir baris & rapikan baris kosong berlebihan
        $text = preg_replace('/[ ]+$/m', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Beri peringatan per butir soal agar guru tahu mana yang perlu dicek ulang di pratinjau.
     * Menambahkan key 'peringatan' (array string) pada setiap butir.
     */
    public function annotateWarnings(array $soals): array
    {
        $opsiCounts = [];
        foreach ($soals as $s) {
            if (($s['tipe_soal'] ?? '') === 'pilihan_ganda' && !empty($s['opsi_jawabans'])) {
                $n = count($s['opsi_jawabans']);
                $opsiCounts[$n] = ($opsiCounts[$n] ?? 0) + 1;
            }
        }
        arsort($opsiCounts);
        $umumOpsi = $opsiCounts ? array_key_first($opsiCounts) : null;

        foreach ($soals as $i => $s) {
            $warn = [];
            $tipe = $s['tipe_soal'] ?? 'pilihan_ganda';
            $opsi = $s['opsi_jawabans'] ?? [];

            if (in_array($tipe, ['pilihan_ganda', 'pg_kompleks'], true)) {
                if (empty($opsi)) {
                    $warn[] = 'Tidak ada pilihan jawaban terdeteksi.';
                } else {
                    $benar = count(array_filter($opsi, fn ($o) => !empty($o['is_jawaban_benar'])));
                    if (!empty($s['kunci_otomatis'])) {
                        $warn[] = 'Kunci jawaban tidak ditemukan di teks (sementara diisi opsi A). Mohon periksa.';
                    } elseif ($benar === 0) {
                        $warn[] = 'Belum ada kunci jawaban.';
                    } elseif ($tipe === 'pilihan_ganda' && $benar > 1) {
                        $warn[] = 'Lebih dari satu kunci jawaban pada pilihan ganda.';
                    }

                    if ($tipe === 'pilihan_ganda' && $umumOpsi && count($opsi) !== $umumOpsi) {
                        $warn[] = 'Jumlah opsi (' . count($opsi) . ') berbeda dari soal lain (' . $umumOpsi . ').';
                    }
                }
            } elseif ($tipe === 'isian' && empty($s['kunci_isian'])) {
                $warn[] = 'Kunci isian kosong.';
            }

            if (trim(strip_tags($s['pertanyaan'] ?? '')) === '') {
                $warn[] = 'Teks pertanyaan kosong.';
            }

            $soals[$i]['peringatan'] = $warn;
        }

        return $soals;
    }

    /**
     * Inferensi Taksonomi Bloom (C1-C6) & Tingkat Kesulitan (LOTS, MOTS, HOTS) berdasarkan kata kerja operasional (KKO) teks
     */
    public static function inferBloomTaxonomy(string $pertanyaan): array
    {
        $text = mb_strtolower(strip_tags($pertanyaan));

        // C4-C6 (HOTS): Menganalisis, Mengevaluasi, Mengkreasi, Studi Kasus Kompleks, Problem Solving
        if (preg_match('/\b(analis|bandingkan|bedakan|telaah|kritik|evaluasi|simpulkan|merancang|buatlah|rancang|hipotesis|hubungan|kaitan|mengapa|argumen|kemungkinan|bukti|fakta dan opini|solusi|pecahkan masalah|bagaimana cara|interpretasi|konsekuensi|penyebab|dampak|kesalahan|faktor yang mempengaruhi|alasan|hikmah|keabsahan|sah atau batal|istinbath)\b/u', $text)) {
            // Bedakan apakah Evaluasi / Kreasi (C5-C6) atau Analisis (C4)
            if (preg_match('/\b(evaluasi|kritik|nilai keabsahan|justifikasi|merancang|ciptakan|buatlah desain|solusi baru|rekomendasi)\b/u', $text)) {
                return ['tingkat_kesulitan' => 'HOTS', 'taksonomi_bloom' => 'C5'];
            }
            return ['tingkat_kesulitan' => 'HOTS', 'taksonomi_bloom' => 'C4'];
        }

        // C3 (MOTS): Menerapkan, Menghitung, Skenario Kasus Kontekstual, Menganalisis Pernyataan
        if (preg_match('/\b(hitung|tentukan|berapakah|carilah|gunakan|terapkan|klasifikasi|urutan|tunjukkan|contohkan|bagaimana rumus|berdasarkan data|sesuai tabel|konversikan|ketika|sedang|apabila|jika|suatu hari|seorang|perhatikan pernyataan|perhatikan tabel|perhatikan gambar|perhatikan ilustrasi|perhatikan kasus|tindakan yang harus|apa yang harus dilakukan|hukumnya|cara yang tepat|manakah pernyataan)\b/u', $text)) {
            return ['tingkat_kesulitan' => 'MOTS', 'taksonomi_bloom' => 'C3'];
        }

        // C2 (LOTS): Memahami, Menjelaskan, Mengkategorikan
        if (preg_match('/\b(jelaskan|uraikan|maksud|makna|deskripsikan|rangkum|terangkan|apakah yang dimaksud|apa fungsi|tujuan utama)\b/u', $text)) {
            return ['tingkat_kesulitan' => 'LOTS', 'taksonomi_bloom' => 'C2'];
        }

        // C1 (LOTS): Mengingat, Menyebutkan Fakta Murni, Hafalan
        if (preg_match('/\b(siapakah|kapankah|di manakah|dimanakah|sebutkan|tuliskan|apa definisi|istilah untuk|nama kitab|tahun berapa|siapa nama)\b/u', $text)) {
            return ['tingkat_kesulitan' => 'LOTS', 'taksonomi_bloom' => 'C1'];
        }

        // Default jika ambigu
        return ['tingkat_kesulitan' => 'MOTS', 'taksonomi_bloom' => 'C3'];
    }

    /**
     * Hitung bobot nilai rekomendasi (rata / flat).
     *
     * @param string $kesulitan 'LOTS' | 'MOTS' | 'HOTS'
     * @param float $baseUnit Poin dasar (default 2.0)
     */
    public static function calculateBobotByKesulitan(string $kesulitan, float $baseUnit = 2.0): float
    {
        return round($baseUnit, 2);
    }

    /**
     * Distribusikan bobot sekumpulan soal secara otomatis sehingga totalnya tepat bernilai $targetTotal (default 100 poin)
     * dengan pembagian sama rata (flat).
     *
     * Contoh:
     * - 10 soal = masing-masing 10.0 poin (Total 100)
     * - 20 soal = masing-masing 5.0 poin (Total 100)
     * - 40 soal = masing-masing 2.5 poin (Total 100)
     * Sisa pembulatan otomatis disesuaikan pada butir terakhir agar jumlah pas 100.
     *
     * @param array $soals List butir soal (array of array)
     * @param float $targetTotal Total nilai keseluruhan (default 100.0)
     * @return array List butir soal dengan 'bobot_nilai' yang sudah terdistribusi
     */
    public static function distributeBobotByRasio(array $soals, float $targetTotal = 100.0): array
    {
        if (empty($soals)) {
            return [];
        }

        $count = count($soals);
        $unitValue = round($targetTotal / $count, 2);

        $accumulated = 0.0;
        $lastIdx = $count - 1;

        foreach ($soals as $idx => &$item) {
            if ($idx === $lastIdx) {
                // Sesuaikan selisih pembulatan pada soal terakhir agar tepat $targetTotal
                $val = round($targetTotal - $accumulated, 2);
            } else {
                $val = $unitValue;
                $accumulated += $val;
            }

            $item['bobot_nilai'] = max(0.1, $val);
        }
        unset($item);

        return $soals;
    }

    public static function getAvailableGeminiModels(): array
    {
        return [
            'gemini-3.1-flash-lite' => '⚡ Gemini 3.1 Flash-Lite (Tercepat ~1.5 Detik & Bebas Antrean - Rekomendasi Utama)',
            'gemini-3-flash-preview' => '🚀 Gemini 3.0 Flash (Sangat Cepat ~4 Detik & Akurasi Tinggi)',
            'gemini-3.5-flash-lite' => '🌱 Gemini 3.5 Flash-Lite (Stabil ~5 Detik & Bebas Antrean)',
            'gemini-flash-lite-latest' => '🔄 Gemini Flash-Lite Latest (Versi Stabil Otomatis)',
            'gemini-3.8-flash' => '🔥 Gemini 3.8 Flash (Otomatis Failover jika Sibuk)',
            'gemini-3.7-flash' => '⚡ Gemini 3.7 Flash (Otomatis Failover jika Sibuk)',
            'gemini-3.6-flash' => '⚡ Gemini 3.6 Flash (Otomatis Failover jika Sibuk)',
            'gemini-3.5-flash' => '🚀 Gemini 3.5 Flash',
            'gemini-3.5-pro' => '🧠 Gemini 3.5 Pro (Akurasi Maksimal & Penalaran Kompleks)',
        ];
    }

    /**
     * Daftar urutan model failover tercepat yang terbukti stabil (200 OK, 1.4s - 5.7s)
     * dan otomatis melewati model yang sedang mengalami lonjakan trafik (503 High Demand) dalam 5 menit terakhir.
     */
    protected function buildOrderedCandidateModels(?string $primaryModel): array
    {
        $primary = $primaryModel ?: config('ai.gemini.model', 'gemini-3.1-flash-lite');
        if (in_array($primary, ['gemini-2.5-pro', 'gemini-2.5-flash', 'gemini-2.5-flash-lite'], true)) {
            $primary = 'gemini-3.1-flash-lite';
        }

        $all = array_values(array_unique([
            $primary,
            'gemini-3.1-flash-lite',
            'gemini-3-flash-preview',
            'gemini-3.5-flash-lite',
            'gemini-flash-lite-latest',
            'gemini-3.6-flash',
            'gemini-3.8-flash',
            'gemini-3.7-flash',
        ]));

        // Pisahkan model yang sehat vs model yang baru saja terkena 503 High Demand / Timeout
        $healthy = [];
        $busy = [];
        foreach ($all as $m) {
            if (Cache::has("gemini_model_busy:{$m}")) {
                $busy[] = $m;
            } else {
                $healthy[] = $m;
            }
        }

        return array_merge($healthy, $busy);
    }

    protected function markModelBusy(string $model): void
    {
        Cache::put("gemini_model_busy:{$model}", true, now()->addMinutes(5));
    }

    /**
     * Generate butir soal menggunakan AI (Google Gemini atau OpenAI)
     *
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function generateSoals(array $params): array
    {
        $provider = $params['provider'] ?? config('ai.provider', 'gemini');
        $apiKey = $params['api_key'] ?? ($provider === 'gemini' ? ($this->getGeminiApiKeys()[0] ?? null) : config('ai.openai.api_key'));

        // Jika testing, selalu gunakan mock generator agar test suite deterministik dan offline-safe
        if (app()->environment('testing')) {
            return $this->generateMockSoals($params);
        }

        if (empty($apiKey)) {

            throw new Exception("API Key {$provider} belum diisi. Silakan masukkan API Key di formulir atau di file .env ({$this->getEnvKeyName($provider)}).");
        }

        $totalJumlah = max(1, min(50, (int) ($params['jumlah'] ?? 1)));

        // Jika <= 15 butir, proses dalam 1 request langsung
        if ($totalJumlah <= 15) {
            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt($params);

            if ($provider === 'gemini') {
                return $this->callGemini($apiKey, $systemPrompt, $userPrompt, $params);
            } else {
                return $this->callOpenAI($apiKey, $systemPrompt, $userPrompt, $params);
            }
        }

        // Jika > 15 butir (hingga maks 50 butir), pecah menjadi beberapa sub-batch aman (maks 15 per panggilan)
        // untuk mencegah batasan output token JSON terpotong dan mencegah HTTP timeout
        $results = [];
        $remaining = $totalJumlah;

        while ($remaining > 0) {
            $chunkSize = min(15, $remaining);
            $chunkParams = $params;
            $chunkParams['jumlah'] = $chunkSize;

            $systemPrompt = $this->buildSystemPrompt();
            $userPrompt = $this->buildUserPrompt($chunkParams);

            if ($provider === 'gemini') {
                $chunkResults = $this->callGemini($apiKey, $systemPrompt, $userPrompt, $chunkParams);
            } else {
                $chunkResults = $this->callOpenAI($apiKey, $systemPrompt, $userPrompt, $chunkParams);
            }

            foreach ($chunkResults as $item) {
                $results[] = $item;
                if (count($results) >= $totalJumlah) {
                    break 2;
                }
            }

            $remaining -= $chunkSize;
        }

        return $results;
    }

    /**
     * Parsing teks mentah butir soal (misalnya hasil copas dari AI chatbot seperti ChatGPT, Claude, DeepSeek, dll)
     * menjadi struktur butir soal CBT yang terstandarisasi.
     *
     * @param string $rawText
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function parseRawTextToSoals(string $rawText, array $params = []): array
    {
        $this->lastParseSource = 'lokal';
        $results = $this->parseRawTextToSoalsInternal($this->normalizeRawText($rawText), $params);

        return $this->annotateWarnings($results);
    }

    protected function parseRawTextToSoalsInternal(string $rawText, array $params = []): array
    {
        $rawText = trim($rawText);
        if (empty($rawText)) {
            throw new Exception("Teks sumber soal masih kosong.");
        }

        $defaultBobot = isset($params['default_bobot']) ? (float) $params['default_bobot'] : (isset($params['bobot_nilai']) ? (float) $params['bobot_nilai'] : null);
        $provider = $params['provider'] ?? config('ai.provider', 'gemini');
        $apiKey = $params['api_key'] ?? ($provider === 'gemini' ? ($this->getGeminiApiKeys()[0] ?? null) : config('ai.openai.api_key'));

        // Jika testing environment, gunakan fallback parser / mock agar test suite cepat dan offline-safe
        if (app()->environment('testing')) {
            $parsed = $this->fallbackLocalParse($rawText, $defaultBobot);
            if (!empty($parsed)) {
                return $parsed;
            }
            $this->lastParseSource = 'mock';
            return $this->generateMockSoals(['topik' => 'Hasil Parse Teks', 'jumlah' => 1]);
        }

        if (empty($apiKey)) {
            throw new Exception("API Key {$provider} belum diisi untuk membedah teks dengan AI. Silakan masukkan API Key di formulir atau di file .env ({$this->getEnvKeyName($provider)}).");
        }

        // 1. Ekstrak kunci jawaban terpisah di akhir dokumen & wacana bersama ("Bacaan untuk soal nomor X-Y")
        $separateAnswerKeys = $this->extractSeparateAnswerKeys($rawText);
        $sharedInfo = $this->extractSharedPassagesFromRawText($rawText);

        // Bersihkan tabel kunci jawaban di akhir teks agar token input ke AI lebih ringkas & cepat
        $textForAi = $this->stripTrailingAnswerKeySection($sharedInfo['cleaned_text']);

        // 2. Pecah teks panjang (misal 50 soal) menjadi beberapa bagian (maks 18 soal per batch)
        //    dan kirim secara PARALEL (serentak) ke AI agar selesai dalam ~12-20 detik (bebas Cloudflare HTTP 524 Timeout)
        $chunks = $this->splitRawTextIntoQuestionChunks($textForAi, 18);

        if ($provider === 'gemini' && count($chunks) > 1) {
            $results = $this->callGeminiParallelChunks($apiKey, $chunks, $defaultBobot, $separateAnswerKeys, $params);
        } else {
            $systemPrompt = $this->buildParseSystemPrompt();
            $userPrompt = $this->buildParseUserPrompt($textForAi, $defaultBobot, $params, $separateAnswerKeys);

            if ($provider === 'gemini') {
                $results = $this->callGemini($apiKey, $systemPrompt, $userPrompt, $params);
            } else {
                $results = $this->callOpenAI($apiKey, $systemPrompt, $userPrompt, $params);
            }
        }

        // 3. Rekonsiliasi kunci jawaban terpisah (safety net 100% akurat sesuai tabel kunci)
        if (!empty($separateAnswerKeys) && !empty($results)) {
            $results = $this->reconcileAnswerKeys($results, $separateAnswerKeys);
        }

        // 4. Distribusi otomatis teks bacaan/wacana bersama ("Bacaan untuk soal nomor 1-2", "4-6", dst.)
        //    ke setiap nomor soal terkait agar aman saat soal diacak di layar siswa
        if (!empty($results)) {
            $results = $this->distributeSharedPassagesToSoals($results, $sharedInfo['passages']);
        }

        // 5. Distribusikan bobot nilai secara proporsional ke total 100 poin
        if (empty($params['default_bobot']) && empty($params['bobot_nilai'])) {
            $results = self::distributeBobotByRasio($results, 100.0);
        }

        $this->lastParseSource = 'ai';
        return $results;
    }

    /**
     * Hapus bagian tabel/daftar kunci jawaban di akhir naskah setelah diekstrak
     * agar tidak memperlambat pemrosesan token AI.
     */
    protected function stripTrailingAnswerKeySection(string $text): string
    {
        $cleaned = preg_replace('/\n[ \t]*(?:#{1,4}[ \t]*|\*{1,2})?(?:[A-Z]\.?[ \t]*)?(?:Kunci\s*Jawaban|DAFTAR\s*KUNCI|Tabel\s*Kunci|Pedoman\s*Penilaian|Rubrik\s*Penilaian)(?![ \t]*[:=\-]?[ \t]*\(?[A-Ea-e]\)?[ \t]*(?:\n|$))[\s\S]*$/i', '', $text);
        $cleaned = preg_replace('/\n[ \t]*\|[ \t]*No\.?[ \t]*\|[\s\S]*$/i', '', $cleaned);
        return trim($cleaned);
    }

    /**
     * Pecah naskah soal panjang menjadi beberapa chunk berdasarkan batas nomor urut soal
     * (misal 50 soal dipecah menjadi 3 chunk @ ~17 soal) untuk diproses secara paralel oleh AI.
     *
     * @return array<int, array{text: string, start_num: int, end_num: int}>
     */
    protected function splitRawTextIntoQuestionChunks(string $text, int $maxPerChunk = 18): array
    {
        $lines = explode("\n", $text);
        $questionLineIndices = [];
        $lastNum = 0;

        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            if (preg_match('/^(?:\*{1,2})?(?:Soal\s*(?:No\.?)?\s*)?(\d{1,3})[\.\)](?:\*{1,2})?\s+/i', $trimmed, $m)) {
                $num = (int) $m[1];
                // Pastikan urutan nomor soal naik bertahap (bukan sub-list 1, 2, 3 di tengah soal nomor besar)
                if (($lastNum === 0 && $num === 1) || ($num === $lastNum + 1)) {
                    $questionLineIndices[] = ['line_idx' => $idx, 'num' => $num];
                    $lastNum = $num;
                }
            }
        }

        if (count($questionLineIndices) <= $maxPerChunk) {
            return [[
                'text' => $text,
                'start_num' => 1,
                'end_num' => max(1, count($questionLineIndices)),
            ]];
        }

        $chunks = [];
        $totalQuestions = count($questionLineIndices);
        for ($i = 0; $i < $totalQuestions; $i += $maxPerChunk) {
            $startLine = ($i === 0) ? 0 : $questionLineIndices[$i]['line_idx'];
            $nextIndex = $i + $maxPerChunk;
            $endLine = ($nextIndex < $totalQuestions)
                ? $questionLineIndices[$nextIndex]['line_idx'] - 1
                : count($lines) - 1;

            $sliceLines = array_slice($lines, $startLine, $endLine - $startLine + 1);
            $startNum = $questionLineIndices[$i]['num'];
            $endNum = $questionLineIndices[min($totalQuestions - 1, $nextIndex - 1)]['num'];

            $chunks[] = [
                'text' => trim(implode("\n", $sliceLines)),
                'start_num' => $startNum,
                'end_num' => $endNum,
            ];
        }

        return $chunks;
    }

    /**
     * Kirim beberapa chunk soal secara PARALEL (serentak) ke Google Gemini API menggunakan Http::pool()
     * agar pemrosesan 50 soal selesai dalam ~15 detik dan tidak terkena Cloudflare HTTP 524 Timeout.
     */
    protected function callGeminiParallelChunks(
        string $apiKey,
        array $chunks,
        ?float $defaultBobot,
        array $separateAnswerKeys,
        array $params
    ): array {
        $keys = $this->getGeminiApiKeys($apiKey);

        return $this->withKeyFailover($keys, function (string $activeKey) use ($chunks, $defaultBobot, $separateAnswerKeys, $params) {
            $primaryModel = $params['model'] ?? config('ai.gemini.model', 'gemini-3.1-flash-lite');
            $baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
            $systemPrompt = $this->buildParseSystemPrompt();

            $candidateModels = $this->buildOrderedCandidateModels($primaryModel);

            // Siapkan payload per chunk
            $chunkPayloads = [];
            foreach ($chunks as $cIdx => $chunk) {
                $chunkKeys = [];
                for ($n = $chunk['start_num']; $n <= $chunk['end_num']; $n++) {
                    if (isset($separateAnswerKeys[$n])) {
                        $chunkKeys[$n] = $separateAnswerKeys[$n];
                    }
                }
                $userPrompt = $this->buildParseUserPrompt($chunk['text'], $defaultBobot, $params, $chunkKeys);
                $chunkPayloads[$cIdx] = [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $systemPrompt . "\n\n" . $userPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                        'maxOutputTokens' => 32768,
                    ],
                ];
            }

            $lastError = null;
            $startedAt = microtime(true);
            $completedChunks = [];

            foreach ($candidateModels as $model) {
                if ((microtime(true) - $startedAt) > 65) {
                    break;
                }

                // Hanya kirim chunk yang belum berhasil (hemat waktu & tahan lonjakan "high demand")
                $pendingPayloads = [];
                foreach ($chunkPayloads as $cIdx => $payload) {
                    if (!isset($completedChunks[$cIdx])) {
                        $pendingPayloads[$cIdx] = $payload;
                    }
                }

                if (empty($pendingPayloads)) {
                    break;
                }

                $url = "{$baseUrl}/models/{$model}:generateContent?key={$activeKey}";

                $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($pendingPayloads, $url) {
                    foreach ($pendingPayloads as $cIdx => $payload) {
                        $pool->as("chunk_{$cIdx}")->timeout(45)->post($url, $payload);
                    }
                });

                foreach ($pendingPayloads as $cIdx => $_) {
                    $resp = $responses["chunk_{$cIdx}"] ?? null;
                    if (!$resp || ($resp instanceof \Throwable) || !$resp->successful()) {
                        $this->markModelBusy($model);
                        if ($resp instanceof \Throwable) {
                            $lastError = $resp->getMessage();
                        } elseif ($resp) {
                            $lastError = $resp->json('error.message') ?? $resp->body();
                        }
                        continue;
                    }

                    $rawJson = $resp->json('candidates.0.content.parts.0.text');
                    if (empty($rawJson)) {
                        $lastError = 'Respons teks dari Gemini kosong.';
                        continue;
                    }

                    try {
                        $parsedChunk = $this->parseJsonResponse($rawJson, $params);
                        if (!empty($parsedChunk)) {
                            $completedChunks[$cIdx] = $parsedChunk;
                        }
                    } catch (\Throwable $parseErr) {
                        $lastError = $parseErr->getMessage();
                    }
                }

                if (count($completedChunks) === count($chunkPayloads)) {
                    ksort($completedChunks);
                    $mergedResults = [];
                    foreach ($completedChunks as $chunkItems) {
                        foreach ($chunkItems as $item) {
                            $mergedResults[] = $item;
                        }
                    }
                    return $mergedResults;
                }

                Log::warning("Gemini parallel pool pada model {$model} baru menyelesaikan " . count($completedChunks) . "/" . count($chunkPayloads) . " chunk (Error: {$lastError}). Melanjutkan sisa chunk pada model berikutnya...");
            }

            throw new Exception("Google Gemini API Error: " . ($lastError ?? 'Gagal memproses teks soal dengan AI.'));
        });
    }

    /**
     * Rekonsiliasi kunci jawaban terpisah ke butir soal pilihan ganda
     *
     * @param array $soals
     * @param array<int, string> $separateAnswerKeys
     * @return array
     */
    public function reconcileAnswerKeys(array $soals, array $separateAnswerKeys): array
    {
        foreach ($soals as $idx => &$soal) {
            $num = $idx + 1;
            if (isset($separateAnswerKeys[$num]) && in_array($soal['tipe_soal'] ?? '', ['pilihan_ganda', 'benar_salah'])) {
                $targetKey = strtoupper($separateAnswerKeys[$num]);
                $hasCorrectTarget = false;
                if (!empty($soal['opsi_jawabans']) && is_array($soal['opsi_jawabans'])) {
                    foreach ($soal['opsi_jawabans'] as $opt) {
                        if (strtoupper($opt['label'] ?? '') === $targetKey && !empty($opt['is_jawaban_benar'])) {
                            $hasCorrectTarget = true;
                            break;
                        }
                    }
                    if (!$hasCorrectTarget) {
                        foreach ($soal['opsi_jawabans'] as &$opt) {
                            $opt['is_jawaban_benar'] = (strtoupper($opt['label'] ?? '') === $targetKey);
                        }
                    }
                }
            }
        }
        return $soals;
    }

    /**
     * Ekstrak kunci jawaban terpisah yang diletakkan di akhir dokumen (tabel Markdown atau list teks).
     *
     * @param string $text
     * @return array<int, string> [nomor_soal => label_kunci (A, B, C, D, E)]
     */
    public function extractSeparateAnswerKeys(string $text): array
    {
        $keys = [];

        // 1. Ekstrak dari tabel Markdown (contoh: | 1 | B | 14 | D | atau | 10 | A* |)
        if (preg_match_all('/\|\s*(\d{1,3})\s*\|\s*\*?([A-Ea-e])\*?\s*(?=\||$)/m', $text, $tableMatches, PREG_SET_ORDER)) {
            foreach ($tableMatches as $m) {
                $keys[(int) $m[1]] = strtoupper(trim($m[2]));
            }
        }

        // 2. Format daftar teks di bawah heading Kunci / Jawaban (bisa multi-line atau inline setelah titik dua)
        if (preg_match('/(?:^|\n)[ \t]*(?:#{1,4}[ \t]*)?(?:[A-Z]\.?[ \t]*)?(?:Kunci\s*(?:Jawaban)?|Answer\s*Key|KUNCI)[ \t]*(?:[:=-][ \t]*|\r?\n)([\s\S]*)$/i', $text, $secMatch)) {
            $kunciText = $secMatch[1];

            if (preg_match_all('/(?:^|\s|,|;)(\d{1,3})\s*(?:[\.\)\:\=\-]|(?:\s+))\s*\*?([A-Ea-e])\*?(?=\s|,|;|$)/m', $kunciText, $listMatches, PREG_SET_ORDER)) {
                foreach ($listMatches as $m) {
                    $num = (int) $m[1];
                    if (!isset($keys[$num])) {
                        $keys[$num] = strtoupper(trim($m[2]));
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * Ekstrak blok wacana / teks bacaan bersama yang digunakan untuk beberapa nomor soal sekaligus
     * (misal: "### Bacaan untuk soal nomor 1–2", "Teks untuk soal nomor 4 - 6", "Wacana soal no. 7 s.d. 9").
     *
     * Mengembalikan:
     * - 'passages': [nomor_soal => ['html' => string, 'plain' => string]]
     * - 'cleaned_text': teks sumber yang sudah dibersihkan dari blok wacana di sela-sela soal
     *   agar tidak tertempel ke ekor opsi jawaban soal sebelumnya.
     */
    public function extractSharedPassagesFromRawText(string $rawText): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $rawText);
        $lines = explode("\n", $text);

        $passagesByNumber = [];
        $cleanedLines = [];

        $isCollectingPassage = false;
        $currentRange = [];
        $currentPassageLines = [];

        $headerRegex = '/^(?:[ \t]*#{1,6}[ \t]*|[ \t]*\*{1,2}[ \t]*)?(?:(?:Bacalah|Perhatikan|Cermati)[ \t]+)?(?:teks|bacaan|wacana|kutipan(?:\s+\w+)?|cerita|puisi|dialog|artikel|paragraf)[ \t]+(?:berikut[ \t]+)?(?:ini[ \t]+)?(?:untuk[ \t]+(?:menjawab[ \t]+)?)?(?:soal|pertanyaan)[ \t]*(?:nomor|no\.?)?[ \t]*(\d{1,3})[ \t]*(?:[\-\x{2013}\x{2014}]|s\.?d\.?|s\/d|sampai(?:\s+dengan)?|hingga|dan|,)[ \t]*(\d{1,3})(?:[ \t]*(?:,|\s+dan\s+)[ \t]*(\d{1,3}))?[^\n]*$/iu';

        $flushPassage = function () use (&$isCollectingPassage, &$currentRange, &$currentPassageLines, &$passagesByNumber) {
            if (!$isCollectingPassage || empty($currentRange)) {
                $isCollectingPassage = false;
                $currentRange = [];
                $currentPassageLines = [];
                return;
            }

            $rawPassage = trim(implode("\n", $currentPassageLines));
            if ($rawPassage !== '') {
                $html = $this->formatPassageToHtml($rawPassage);
                $plain = trim(strip_tags($html));
                foreach ($currentRange as $qNum) {
                    $passagesByNumber[$qNum] = [
                        'html' => $html,
                        'plain' => $plain,
                        'raw' => $rawPassage,
                    ];
                }
            }

            $isCollectingPassage = false;
            $currentRange = [];
            $currentPassageLines = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Cek apakah baris ini adalah header "Bacaan untuk soal nomor X-Y"
            if (preg_match($headerRegex, $trimmed, $m)) {
                $flushPassage();

                $startNum = (int) $m[1];
                $endNum = !empty($m[3]) ? (int) $m[3] : (int) $m[2];
                if ($startNum > 0 && $endNum >= $startNum && ($endNum - $startNum) <= 25) {
                    $isCollectingPassage = true;
                    $currentRange = range($startNum, $endNum);
                    $currentPassageLines = [];
                    continue;
                }
            }

            if ($isCollectingPassage) {
                $expectedStart = $currentRange[0] ?? 1;
                // Berhenti mengumpulkan wacana ketika bertemu awal butir soal (misal "**1.** " atau "4. ")
                if (preg_match('/^(?:\*{1,2})?(?:Soal\s*(?:No\.?)?\s*)?(\d{1,3})[\.\)](?:\*{1,2})?\s+/i', $trimmed, $qm)) {
                    $detectedNum = (int) $qm[1];
                    if ($detectedNum === $expectedStart || in_array($detectedNum, $currentRange, true)) {
                        $flushPassage();
                        $cleanedLines[] = $line;
                        continue;
                    }
                }

                // Berhenti juga jika bertemu section baru (misal "## B. KUNCI JAWABAN")
                if (preg_match('/^#{1,3}\s*(?:[A-Z]\.?\s*)?(?:Kunci\s*Jawaban|Essay|Uraian)/i', $trimmed)) {
                    $flushPassage();
                    $cleanedLines[] = $line;
                    continue;
                }

                $currentPassageLines[] = $line;
                continue;
            }

            $cleanedLines[] = $line;
        }

        $flushPassage();

        return [
            'passages' => $passagesByNumber,
            'cleaned_text' => implode("\n", $cleanedLines),
        ];
    }

    /**
     * Format teks wacana / bacaan bersama menjadi kotak HTML stimulus yang rapi.
     */
    protected function formatPassageToHtml(string $rawPassage): string
    {
        $paragraphs = preg_split('/\n\s*\n/', trim($rawPassage)) ?: [];
        $pTags = [];

        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') continue;
            $pTags[] = '<p style="margin-bottom: 8px; text-align: justify;">' . $this->convertSimpleMarkdownToHtml($p) . '</p>';
        }

        $innerHtml = implode("\n", $pTags);

        return '<div class="cbt-wacana-box" style="padding: 12px 14px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #cbd5e1; border-left: 4px solid #3b82f6; background-color: #f8fafc;">'
            . '<p style="font-size: 12px; font-weight: 700; color: #1e40af; margin-bottom: 6px;">Bacalah teks berikut untuk menjawab soal ini!</p>'
            . $innerHtml
            . '</div>';
    }

    /**
     * Konversi markdown sederhana (**bold**, *italic*, newline) ke HTML aman.
     */
    protected function convertSimpleMarkdownToHtml(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Bold **teks**
        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);
        // Italic *teks*
        $escaped = preg_replace('/(?<!\*)\*([^\*\n]+?)\*(?!\*)/', '<em>$1</em>', $escaped);

        return nl2br($escaped);
    }

    /**
     * Sematkan teks bacaan bersama ke setiap butir soal yang masuk dalam rentang nomor soal,
     * termasuk mendeteksi soal lanjutan yang merujuk ke bacaan pada soal sebelumnya (misal:
     * "Perhatikan kembali kutipan cerpen pada soal nomor 30!" atau "dalam cerpen di atas").
     */
    public function distributeSharedPassagesToSoals(array $soals, array $passagesByNumber): array
    {
        $activePassageByNum = $passagesByNumber;

        foreach ($soals as $idx => &$soal) {
            $num = $idx + 1;
            $pertanyaan = (string) ($soal['pertanyaan'] ?? '');

            // 1. Bersihkan kalimat header lama "Bacaan untuk soal nomor X-Y" jika AI terlanjur memasukkannya
            $pertanyaan = preg_replace(
                '/(?:<p[^>]*>\s*)?(?:#{1,6}\s*|\*{1,2}\s*)?(?:Bacaan|Teks|Wacana)\s+untuk\s+(?:menjawab\s+)?soal\s*(?:nomor|no\.?)?\s*\d{1,3}\s*(?:[\-\x{2013}\x{2014}]|s\.?d\.?|sampai|hingga|dan)\s*\d{1,3}[^<\n]*(?:<\/p>)?/iu',
                '',
                $pertanyaan
            );

            // 2. Jika nomor soal ini terdaftar pada rentang "Bacaan untuk soal nomor X-Y"
            if (isset($passagesByNumber[$num])) {
                $passage = $passagesByNumber[$num];
                if (!$this->pertanyaanAlreadyContainsPassage($pertanyaan, $passage['raw'] ?? $passage['plain'])) {
                    $pertanyaan = $passage['html'] . "\n" . trim($pertanyaan);
                }
                $activePassageByNum[$num] = $passage;
                $soal['pertanyaan'] = $pertanyaan;
                continue;
            }

            // 3. Cek apakah soal ini memiliki bacaan/kutipan panjang sendiri di dalamnya
            //    (misal Soal 27: "Bacalah teks berikut ini! [3 paragraf cerpen] Tokoh aku dalam penggalan cerpen...")
            $embedded = $this->extractEmbeddedPassageFromQuestion($pertanyaan);
            if ($embedded !== null) {
                $activePassageByNum[$num] = $embedded;
                continue;
            }

            // 4. Cek apakah soal ini merujuk secara eksplisit ke nomor soal lain
            //    (misal Soal 31: "Perhatikan kembali kutipan cerpen pada soal nomor 30!")
            if (preg_match('/(?:perhatikan|bacalah|cermati|berdasarkan|lihat)?[^\.!\?\n<]*?\b(?:pada\s+)?soal\s*(?:nomor|no\.?)\s*(\d{1,3})\s*[!\.]?/iu', strip_tags($pertanyaan), $refMatch)) {
                $refNum = (int) $refMatch[1];
                if (isset($activePassageByNum[$refNum])) {
                    $refPassage = $activePassageByNum[$refNum];
                    // Hapus kalimat rujukan "Perhatikan kembali ... pada soal nomor 30!" karena nomor soal akan diacak
                    $pertanyaan = preg_replace(
                        '/(?:<p[^>]*>\s*)?(?:perhatikan|bacalah|cermati|berdasarkan|lihat)?[^\.!\?\n<]*?\b(?:pada\s+)?soal\s*(?:nomor|no\.?)\s*' . $refNum . '\s*[!\.]?(?:\s*<\/p>|\s*<br\s*\/?>)?/iu',
                        '',
                        $pertanyaan
                    );
                    if (!$this->pertanyaanAlreadyContainsPassage($pertanyaan, $refPassage['raw'] ?? $refPassage['plain'])) {
                        $pertanyaan = $refPassage['html'] . "\n" . trim($pertanyaan);
                    }
                    $activePassageByNum[$num] = $refPassage;
                    $soal['pertanyaan'] = $pertanyaan;
                    continue;
                }
            }

            // 5. Cek apakah soal ini merupakan soal pendek (tanpa wacana sendiri) yang merujuk ke wacana soal tepat sebelumnya (N-1)
            //    Contoh:
            //    - Soal 10: "Pembenaran berupa alasan yang sesuai dengan isi teks tersebut adalah ...." (setelah bacaan 7-9)
            //    - Soal 28: "Kata sepenuh hati dalam cerpen di atas bermakna ...." (setelah cerpen di Soal 27)
            //    - Soal 3: Mengutip 1 kalimat dari wacana 1-2 dan menanyakan "menghubungkannya dengan gagasan pada kalimat sebelumnya"
            $prevNum = $num - 1;
            if (isset($activePassageByNum[$prevNum])) {
                $prevPassage = $activePassageByNum[$prevNum];
                $plainPertanyaan = trim(html_entity_decode(strip_tags($pertanyaan), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $hasOwnIntro = (bool) preg_match('/^(?:bacalah|perhatikan|cermati)\s+(?:teks|wacana|bacaan|paragraf|kutipan\s+cerpen|cerita|iklan|poster|kalimat-kalimat|pernyataan)\s+(?:berikut|di\s+bawah)/iu', $plainPertanyaan);

                $isShortOrphanReference = !$hasOwnIntro
                    && mb_strlen($plainPertanyaan) < 230
                    && preg_match('/\b(?:teks\s+tersebut|bacaan\s+tersebut|wacana\s+tersebut|cerpen\s+di\s+atas|cerpen\s+tersebut|teks\s+di\s+atas|cerita\s+di\s+atas|kutipan\s+di\s+atas)\b/iu', $plainPertanyaan);

                $isQuoteFromPrevPassage = false;
                if (
                    preg_match('/\b(?:kalimat\s+sebelumnya|paragraf\s+sebelumnya|dalam\s+wacana)\b/iu', $plainPertanyaan)
                    && preg_match('/"([^"]{25,})"/u', $plainPertanyaan, $qm)
                ) {
                    $quotedSnippet = mb_substr(trim($qm[1]), 0, 35);
                    if ($quotedSnippet !== '' && mb_stripos($prevPassage['raw'] ?? $prevPassage['plain'], $quotedSnippet) !== false) {
                        $isQuoteFromPrevPassage = true;
                    }
                }

                if ($isShortOrphanReference || $isQuoteFromPrevPassage) {
                    if (!$this->pertanyaanAlreadyContainsPassage($pertanyaan, $prevPassage['raw'] ?? $prevPassage['plain'])) {
                        $pertanyaan = $prevPassage['html'] . "\n" . trim($pertanyaan);
                    }
                    $activePassageByNum[$num] = $prevPassage;
                    $soal['pertanyaan'] = $pertanyaan;
                }
            }
        }
        unset($soal);

        return $soals;
    }

    /**
     * Cek apakah teks pertanyaan sudah memuat isi wacana tersebut.
     */
    protected function pertanyaanAlreadyContainsPassage(string $pertanyaanHtml, string $passagePlain): bool
    {
        $cleanQuestion = preg_replace('/\s+/', ' ', trim(strip_tags($pertanyaanHtml)));
        $cleanPassage = preg_replace('/\s+/', ' ', trim(strip_tags($passagePlain)));

        if (mb_strlen($cleanPassage) < 20) {
            return false;
        }

        // Ambil cuplikan 35 karakter dari bagian awal/tengah bacaan
        $offset = min(20, max(0, (int) floor(mb_strlen($cleanPassage) * 0.15)));
        $snippet = mb_substr($cleanPassage, $offset, 35);

        return $snippet !== '' && mb_stripos($cleanQuestion, $snippet) !== false;
    }

    /**
     * Ekstrak wacana/kutipan cerpen yang tertanam langsung di dalam suatu soal
     * agar dapat diwariskan jika soal berikutnya menanyakan "pada soal nomor X" atau "dalam cerpen di atas".
     */
    protected function extractEmbeddedPassageFromQuestion(string $pertanyaanHtml): ?array
    {
        // Ubah tag <p> dan <br> menjadi baris baru untuk mendeteksi paragraf
        $textWithBreaks = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $pertanyaanHtml);
        $textWithBreaks = preg_replace('/<br\s*\/?>/i', "\n", $textWithBreaks);
        $plain = trim(strip_tags($textWithBreaks));

        $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $plain) ?: [])));
        if (count($paragraphs) < 2) {
            return null;
        }

        // Jika soal menanyakan hubungan dengan "kalimat sebelumnya" atau "paragraf sebelumnya",
        // berarti kutipan singkat di dalamnya membutuhkan konteks wacana penuh dari soal sebelumnya.
        if (preg_match('/\b(?:kalimat\s+sebelumnya|paragraf\s+sebelumnya)\b/iu', $plain)) {
            return null;
        }

        // Jika paragraf pertama adalah instruksi ("Bacalah teks berikut!"), dan ada minimal 1 paragraf bacaan + 1 kalimat tanya di akhir
        $startIndex = 0;
        if (preg_match('/^(?:bacalah|perhatikan|cermati)\s+[^\.!\n]{3,60}[!\.:]?$/iu', $paragraphs[0])) {
            $startIndex = 1;
        }

        // Paragraf terakhir biasanya adalah pokok pertanyaan ("Tokoh aku dalam penggalan cerpen di atas adalah ....")
        $passageParagraphs = array_slice($paragraphs, $startIndex, count($paragraphs) - $startIndex - 1);
        $combinedPassage = trim(implode("\n\n", $passageParagraphs));

        if (mb_strlen($combinedPassage) < 120) {
            return null;
        }

        return [
            'html' => $this->formatPassageToHtml($combinedPassage),
            'plain' => $combinedPassage,
            'raw' => $combinedPassage,
        ];
    }

    /**
     * Fallback parser lokal berbasis pemrosesan teks cerdas & regex jika AI offline atau testing
     */
    public function fallbackLocalParse(string $rawText, ?float $defaultBobot = null): array
    {
        $separateAnswerKeys = $this->extractSeparateAnswerKeys($rawText);
        // 1. Normalisasi Markdown, Karakter Khusus & HTML Entities
        $text = html_entity_decode($rawText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Unescape dot setelah angka atau huruf: 1\. -> 1. atau A\. -> A.
        $text = preg_replace('/(?<=^|\s|\n)([A-Za-z]|\d+)\\\([\.\)])/', '$1$2', $text);
        // Hapus trailing backslash Markdown line break di akhir baris
        $text = preg_replace('/\\\\\s*$/m', '', $text);
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));

        if (empty($text)) {
            return [];
        }

        // Ekstrak blok "Bacaan untuk soal nomor X-Y" terlebih dahulu agar tidak menempel ke opsi terakhir soal sebelumnya
        $sharedInfo = $this->extractSharedPassagesFromRawText($text);
        $sharedPassages = $sharedInfo['passages'];
        $text = $sharedInfo['cleaned_text'];

        // Bersihkan blok tabel kunci jawaban & catatan kaki di bagian akhir teks agar tidak menempel ke opsi soal terakhir
        $text = preg_replace('/\n[ \t]*(?:#{1,4}[ \t]*|\*{1,2})?(?:[A-Z]\.?[ \t]*)?(?:Kunci\s*Jawaban|DAFTAR\s*KUNCI|Tabel\s*Kunci|Pedoman\s*Penilaian|Rubrik\s*Penilaian)(?![ \t]*[:=\-]?[ \t]*\(?[A-Ea-e]\)?[ \t]*(?:\n|$))[\s\S]*$/i', '', $text);
        $text = preg_replace('/\n[ \t]*\|[ \t]*No\.?[ \t]*\|[\s\S]*$/i', '', $text);
        $text = preg_replace('/\n[ \t]*(?:\*{1,2})?Catatan(?:\s+penyusunan)?\s*:[\s\S]*$/i', '', $text);

        // 2. Deteksi apakah teks memiliki bagian-bagian (Sections) seperti "## A. Pilihan Ganda", "## A. SOAL PILIHAN GANDA", "## B. Essay", dll
        $sectionPattern = '/(?=(?:^|\n)\s*#{1,3}\s*(?:[A-Z]\.?\s*)?(?:SOAL\s+)?(?:Pilihan\s*Ganda|PG|Multiple\s*Choice|Essay|Uraian|Isian|Menjodohkan|Kunci\s*Jawaban|Pedoman\s*Penilaian))/i';
        $rawSections = preg_split($sectionPattern, $text, -1, PREG_SPLIT_NO_EMPTY);

        $parsedSections = [];
        if (count($rawSections) > 1) {
            foreach ($rawSections as $sec) {
                $sec = trim($sec);
                if (empty($sec)) continue;

                $firstLine = trim(strtok($sec, "\n"));

                // Lewati kunci jawaban guru / pedoman penilaian di akhir
                if (preg_match('/Kunci\s*Jawaban|Pedoman\s*Penilaian|Rubrik/i', $firstLine)) {
                    continue;
                }

                // Lewati judul dokumen awal HANYA JIKA section ini tidak memiliki daftar soal bernomor
                if (
                    preg_match('/^#{1,3}\s*(?:SOAL|PETUNJUK|MATERI|KISI|UJIAN|PENILAIAN)/i', $firstLine)
                    && !preg_match('/Pilihan\s*Ganda|PG|Essay|Uraian/i', $firstLine)
                    && !preg_match('/(?:^|\n)\s*(?:\*{1,2})?1[\.\)](?:\*{1,2})?\s+/m', $sec)
                ) {
                    continue;
                }

                // Lewati bagian pembuka dokumen (judul, identitas, daftar bab/materi bernomor) yang berada
                // sebelum section soal pertama: bukan header section soal & tidak punya opsi jawaban A/B/C...
                if (
                    !preg_match('/Pilihan\s*Ganda|\bPG\b|Multiple\s*Choice|Essay|Uraian|Isian|Menjodohkan|Benar\s*Salah/i', $firstLine)
                    && !preg_match('/^\s*(?:[-*•]\s*)?(?:\*{1,2})?\(?[A-Ea-e][\.\)]/m', $sec)
                ) {
                    continue;
                }

                $type = 'pilihan_ganda';
                if (preg_match('/Essay|Uraian/i', $firstLine)) {
                    $type = 'essay';
                } elseif (preg_match('/Isian/i', $firstLine)) {
                    $type = 'isian';
                } elseif (preg_match('/Benar\s*Salah/i', $firstLine)) {
                    $type = 'benar_salah';
                }

                // Hapus header section dan petunjuk umum sebelum soal nomor 1
                if (preg_match('/(?:^|\n)\s*(?:\*{1,2})?1[\.\)](?:\*{1,2})?\s+/m', $sec, $m, PREG_OFFSET_CAPTURE)) {
                    $body = substr($sec, $m[0][1]);
                } else {
                    $body = preg_replace('/^#{1,3}[^\n]+\n(?:(?:\*{1,2})?Petunjuk[^\n]+\n)?/i', '', $sec);
                }
                $parsedSections[] = [
                    'type' => $type,
                    'content' => trim($body),
                ];
            }
        } else {
            // Tanpa section eksplisit, bersihkan judul dokumen di awal sebelum soal No 1
            if (preg_match('/(?:^|\n)\s*(?:\*{1,2})?1[\.\)](?:\*{1,2})?\s+/i', $text, $m, PREG_OFFSET_CAPTURE)) {
                $text = substr($text, $m[0][1]);
            }
            $parsedSections[] = [
                'type' => 'auto',
                'content' => $text,
            ];
        }

        $allSoals = [];

        foreach ($parsedSections as $section) {
            $secContent = $section['content'];
            $secType = $section['type'];

            // Split per nomor urut soal bertahap: 1., 2., 3., dst
            $lines = explode("\n", $secContent);
            $questionBlocks = [];
            $currentBlock = [];
            $lastQuestionNumber = 0;

            foreach ($lines as $line) {
                $trimmed = trim($line);

                // Cek apakah baris diawali nomor soal (mendukung format biasa "1. " maupun Markdown bold "**1.** "):
                // Harus lebih besar dari nomor soal sebelumnya dalam section yang sama (mencegah nomor 1, 2, 3 di dalam soal terpecah)
                if (preg_match('/^(?:\*{1,2})?(?:Soal\s*(?:No\.?)?\s*)?(\d+)[\.\)](?:\*{1,2})?\s+(.*)$/i', $trimmed, $nMatch)) {
                    $num = (int) $nMatch[1];
                    if (($lastQuestionNumber === 0 && $num <= 2) || ($num > $lastQuestionNumber && $num <= $lastQuestionNumber + 50)) {
                        if (!empty($currentBlock)) {
                            $questionBlocks[] = implode("\n", $currentBlock);
                            $currentBlock = [];
                        }
                        $lastQuestionNumber = $num;
                    }
                }

                $currentBlock[] = $line;
            }

            if (!empty($currentBlock)) {
                $questionBlocks[] = implode("\n", $currentBlock);
            }

            foreach ($questionBlocks as $block) {
                $block = trim($block);
                if (empty($block)) continue;

                if (!preg_match('/^(?:\*{1,2})?(?:Soal\s*(?:No\.?)?\s*)?(\d+)[\.\)](?:\*{1,2})?\s+(.*)$/s', $block, $numMatch)) {
                    continue;
                }

                $qNum = (int) $numMatch[1];
                $body = trim($numMatch[2]);

                // Ekstrak Pembahasan jika ada
                $pembahasan = '';
                if (preg_match('/(?:^|\n)\s*(?:Pembahasan|Penjelasan|Alasan|Keterangan)\s*[:=-]\s*([\s\S]*)$/i', $body, $pMatch)) {
                    $pembahasan = trim($pMatch[1]);
                    $body = trim(substr($body, 0, strrpos($body, $pMatch[0])));
                }

                // Ekstrak Kunci Jawaban jika ada di bawah soal
                $kunci = null;
                if (preg_match('/(?:^|\n)\s*(?:Kunci\s*(?:Jawaban)?|Jawaban\s*(?:Benar)?|Answer)\s*[:=-]\s*([A-Ea-e1-5]|Benar|Salah|[^\n]+)/i', $body, $kMatch)) {
                    $kunciRaw = trim($kMatch[1]);
                    $body = trim(str_replace($kMatch[0], '', $body));
                    $kunci = strtoupper(substr($kunciRaw, 0, 1));
                }

                // Jika kunci belum ditemukan inline, cocokkan dengan kunci jawaban terpisah (tabel/list di akhir dokumen)
                if (!$kunci) {
                    $currentIdx = count($allSoals) + 1;
                    if ($qNum && isset($separateAnswerKeys[$qNum])) {
                        $kunci = $separateAnswerKeys[$qNum];
                    } elseif (isset($separateAnswerKeys[$currentIdx])) {
                        $kunci = $separateAnswerKeys[$currentIdx];
                    }
                }

                // Tentukan tipe
                $tipe = $secType;

                // Pisahkan teks pertanyaan dan opsi (hanya jika BUKAN essay)
                $lines = explode("\n", $body);
                $questionLines = [];
                $options = [];
                $currentLabel = null;
                $currentOptionText = '';
                $isParsingOptions = false;

                if ($tipe !== 'essay') {
                    foreach ($lines as $line) {
                        $trimmed = trim($line);

                        // Opsi A, B, C, D, E (mendukung "A. ", "**A.** ", "- A. ")
                        if (preg_match('/^(?:[-*•]\s*)?(?:\*{1,2})?(?:\(?([A-Ea-e])[\.\)](?:\*{1,2})?|\b([A-Ea-e])\.\s+)\s*(.*)$/', $trimmed, $optMatch)) {
                            $isParsingOptions = true;
                            if ($currentLabel !== null) {
                                $options[] = [
                                    'label' => $currentLabel,
                                    'teks' => trim($currentOptionText),
                                ];
                            }
                            $currentLabel = strtoupper(!empty($optMatch[1]) ? $optMatch[1] : $optMatch[2]);
                            $currentOptionText = trim($optMatch[3]);
                        } elseif ($isParsingOptions) {
                            $currentOptionText .= "\n" . $trimmed;
                        } else {
                            $questionLines[] = $line;
                        }
                    }

                    if ($currentLabel !== null) {
                        $options[] = [
                            'label' => $currentLabel,
                            'teks' => trim($currentOptionText),
                        ];
                    }
                } else {
                    $questionLines = $lines;
                }

                $pertanyaanText = trim(implode("\n", $questionLines));
                if (empty($pertanyaanText) && empty($options)) {
                    continue;
                }

                if ($tipe === 'auto') {
                    $tipe = !empty($options) ? 'pilihan_ganda' : 'essay';
                }

                $opsiJawabans = [];
                $kunciOtomatis = false;
                if (!empty($options)) {
                    $hasCorrect = false;
                    foreach ($options as $opt) {
                        $lbl = $opt['label'];
                        $teksOpsi = $opt['teks'];
                        $isBenar = false;

                        if ($kunci && $lbl === $kunci) {
                            $isBenar = true;
                        } elseif (preg_match('/\((?:kunci|benar|jawaban benar)\)/i', $teksOpsi)) {
                            $isBenar = true;
                            $teksOpsi = trim(preg_replace('/\((?:kunci|benar|jawaban benar)\)/i', '', $teksOpsi));
                        }

                        if ($isBenar) $hasCorrect = true;

                        $opsiJawabans[] = [
                            'label' => $lbl,
                            'teks_opsi' => $teksOpsi,
                            'gambar_opsi' => null,
                            'is_jawaban_benar' => $isBenar,
                            'pasangan_matching' => null,
                        ];
                    }

                    if (!$hasCorrect && !empty($opsiJawabans) && $tipe === 'pilihan_ganda') {
                        $opsiJawabans[0]['is_jawaban_benar'] = true;
                        $kunciOtomatis = true;
                    }
                }

                if (!str_starts_with($pertanyaanText, '<p>') && !str_starts_with($pertanyaanText, '<div')) {
                    $pertanyaanText = '<p>' . $this->convertSimpleMarkdownToHtml($pertanyaanText) . '</p>';
                }

                $bloom = self::inferBloomTaxonomy($pertanyaanText);
                $calculatedBobot = $defaultBobot !== null
                    ? (float) $defaultBobot
                    : self::calculateBobotByKesulitan($bloom['tingkat_kesulitan'], 2.0);

                $allSoals[] = [
                    'tipe_soal' => $tipe,
                    'bobot_nilai' => $calculatedBobot,
                    'tingkat_kesulitan' => $bloom['tingkat_kesulitan'],
                    'taksonomi_bloom' => $bloom['taksonomi_bloom'],
                    'pertanyaan' => $pertanyaanText,
                    'opsi_jawabans' => $opsiJawabans,
                    'kunci_isian' => ($tipe === 'isian') ? ($kunci ?? '') : '',
                    'rubrik_essay' => ($tipe === 'essay') ? ($pembahasan ?: 'Pedoman penilaian essay...') : '',
                    'pembahasan' => $pembahasan,
                    'kunci_otomatis' => $kunciOtomatis,
                ];
            }
        }

        // Sematkan wacana / teks bacaan bersama ke setiap nomor soal yang menggunakannya
        if (!empty($allSoals)) {
            $allSoals = $this->distributeSharedPassagesToSoals($allSoals, $sharedPassages);
        }

        // Jika tidak ada bobot kustom per soal, otomatis distribusikan bobot total 100 poin secara sama rata
        if ($defaultBobot === null) {
            $allSoals = self::distributeBobotByRasio($allSoals, 100.0);
        }

        return $allSoals;
    }

    /**
     * Bangun System Prompt untuk Parsing Teks Mentah / Chatbot
     */
    protected function buildParseSystemPrompt(): string
    {
        return <<<PROMPT
Anda adalah AI Expert Parser Butir Soal Ujian CBT & Kurikulum Nasional Indonesia (Kemendikbudristek & Kemenag).
Tugas Anda adalah membaca teks acak/mentah hasil copy-paste dari AI Chatbot (seperti ChatGPT, Claude, DeepSeek, Google Gemini, dll) atau dokumen soal, lalu membedah dan mengubahnya menjadi format data JSON terstruktur yang valid, akurat, dan siap uji.

ATURAN PENGURAIAN (PARSING) WAJIB:
1. IDENTIFIKASI DAN EKSTRAKSI BUTIR SOAL:
   - Ekstrak setiap butir soal secara berurutan.
   - Bersihkan label nomor soal di awal teks pertanyaan (contoh: "1.", "Soal 1:", "[1]", "No. 1") agar teks pertanyaan bersih dan siap pakai.
   - Format teks pertanyaan dalam tag HTML dasar (<p>...</p>). Jika ada stimulus (tabel, teks bacaan, paragraf), susun rapi dalam tag HTML.

2. WACANA / TEKS BACAAN BERSAMA UNTUK BEBERAPA SOAL (SANGAT KRUSIAL UNTUK CBT ACAK SOAL):
   - Jika terdapat teks bacaan / wacana / kutipan cerita yang digunakan untuk beberapa nomor soal sekaligus (misalnya "Bacaan untuk soal nomor 1–2", "Bacaan untuk soal nomor 4–6", atau soal berikutnya merujuk pada "kutipan cerpen pada soal nomor 30" / "dalam cerpen di atas"), Anda WAJIB menyalin dan menyematkan teks bacaan tersebut secara LENGKAP ke dalam field 'pertanyaan' pada SETIAP butir soal yang menggunakannya (contoh: bacaan 1–2 wajib ada di Soal 1 DAN di Soal 2; bacaan 4–6 wajib ada di Soal 4, Soal 5, DAN Soal 6).
   - JANGAN menulis kalimat "Bacaan untuk soal nomor 4–6" atau "pada soal nomor 30" karena urutan soal di layar siswa akan diacak; ganti kalimat pengantarnya menjadi "Bacalah teks berikut untuk menjawab soal ini!".

3. TIPE SOAL & PILIHAN JAWABAN:
   - Tipe soal: "pilihan_ganda", "pg_kompleks", "benar_salah", "menjodohkan", "isian", atau "essay".
   - Jika pilihan ganda: ekstrak huruf label ke 'label' (A, B, C, D, E) dan teks opsi ke 'teks_opsi' (bersihkan huruf label dari teks_opsi).
   - KUNCI JAWABAN (SANGAT KRUSIAL):
     a) Kunci Melekat: Cari tanda kunci jawaban langsung di bawah atau di samping butir soal (misal "Kunci: A", "Jawaban: B", "Kunci Jawaban: C", opsi yang dicetak tebal, atau tanda centang).
     b) KUNCI TERPISAH DI AKHIR (WAJIB DIBACA): Seringkali kunci jawaban diletakkan terpisah di bagian bawah dokumen dalam bentuk tabel Markdown (contoh: "## C. Kunci Jawaban Pilihan Ganda | No. | Jawaban | ...") atau daftar teks ("1. B, 2. C, 3. D..."). Anda WAJIB membaca tabel/daftar kunci jawaban di akhir ini dan memetakan setiap nomor kunci ke butir soal yang bersesuaian, sehingga opsi yang cocok mendapatkan 'is_jawaban_benar': true. TEPAT 1 kunci benar untuk pilihan ganda.
     c) JANGAN membuat tabel kunci jawaban atau pedoman penilaian di akhir dokumen menjadi butir soal baru!
   - Jika tipe isian: isi field 'kunci_isian'.
   - Jika tipe essay: isi field 'rubrik_essay'.

3. KLASIFIKASI TAKSONOMI BLOOM & TINGKAT KESULITAN (WAJIB):
   - Analisis kata kerja operasional (KKO) dan tingkat pemikiran butir soal:
     * LOTS (C1-C2): C1 (Mengingat/Menyebutkan/Definisi), C2 (Memahami/Menjelaskan).
     * MOTS (C3): C3 (Menerapkan/Menghitung rumus/Mengimplementasikan).
     * HOTS (C4-C6): C4 (Menganalisis/Menelaah/Membandingkan), C5 (Mengevaluasi/Kritik/Justifikasi), C6 (Mengkreasi/Merancang).
   - Field 'tingkat_kesulitan': "LOTS" | "MOTS" | "HOTS"
   - Field 'taksonomi_bloom': "C1" | "C2" | "C3" | "C4" | "C5" | "C6"
   - Field 'bobot_nilai': Sesuaikan bobot berdasarkan kesulitan (misal LOTS = 2.0, MOTS = 3.0, HOTS = 4.0 atau kelipatannya).

4. PEMBAHASAN & PENJELASAN:
   - Jika teks sumber mencantumkan pembahasan, simpan ke field 'pembahasan'.
   - PENTING: Jika teks sumber TIDAK mencantumkan pembahasan, biarkan field `"pembahasan": ""` (string kosong). JANGAN mengarang teks pembahasan baru agar proses ekstraksi soal berjalan sangat cepat dan hemat token!

5. RUMUS MATEMATIKA & EKSAK (LaTeX):
   - Gunakan format KaTeX inline \$rumus\$ atau display \$\$rumus\$\$.
   - PENTING DALAM JSON: Setiap simbol backslash rumus LaTeX wajib di-escape ganda (contoh: \\\\frac{a}{b}, \\\\sqrt{x}, \\\\pm, \\\\times, \\\\log).
   - Pertahankan teks Arab berharakat jika ada.

6. FORMAT OUTPUT WAJIB:
   Kembalikan HANYA JSON murni (tanpa format markdown pembungkus ```json).
   JSON harus berupa objek dengan key "soals" yang berisi array butir soal.

Struktur JSON per butir soal:
{
  "soals": [
    {
      "tipe_soal": "pilihan_ganda" | "pg_kompleks" | "benar_salah" | "menjodohkan" | "isian" | "essay",
      "tingkat_kesulitan": "LOTS" | "MOTS" | "HOTS",
      "taksonomi_bloom": "C1" | "C2" | "C3" | "C4" | "C5" | "C6",
      "bobot_nilai": 2.0,
      "pertanyaan": "<p>Teks stimulus / pokok soal...</p>",
      "opsi_jawabans": [
        { "label": "A", "teks_opsi": "...", "is_jawaban_benar": false },
        { "label": "B", "teks_opsi": "...", "is_jawaban_benar": true },
        { "label": "C", "teks_opsi": "...", "is_jawaban_benar": false },
        { "label": "D", "teks_opsi": "...", "is_jawaban_benar": false }
      ],
      "kunci_isian": "",
      "rubrik_essay": "",
      "pembahasan": ""
    }
  ]
}
PROMPT;
    }

    /**
     * Bangun User Prompt untuk Parsing Teks Mentah
     */
    protected function buildParseUserPrompt(string $rawText, ?float $defaultBobot, array $params, ?array $preExtractedKeys = null): string
    {
        $bobotLabel = $defaultBobot ?? 2.0;
        $prompt = "Tolong uraikan (parse) teks butir soal hasil copas berikut menjadi format JSON sesuai pedoman.\n";
        $prompt .= "Bobot nilai default per butir: {$bobotLabel}.\n\n";

        $keys = $preExtractedKeys ?? $this->extractSeparateAnswerKeys($rawText);
        if (!empty($keys)) {
            $prompt .= "PETUNJUK TAMBAHAN - DAFTAR KUNCI JAWABAN TERPISAH TERDETEKSI:\n";
            $prompt .= "Sistem mendeteksi kunci jawaban berikut:\n";
            $keyPairs = [];
            foreach ($keys as $no => $k) {
                $keyPairs[] = "No.{$no}={$k}";
            }
            $prompt .= implode(', ', $keyPairs) . "\n";
            $prompt .= "Pastikan setiap butir soal di bawah mendapatkan 'is_jawaban_benar': true pada opsi yang sesuai dengan kunci tersebut!\n\n";
        }

        $prompt .= "--- TEKS MENTAH HASIL COPAS GURU DARI AI CHATBOT ---\n";
        $prompt .= $rawText . "\n";
        $prompt .= "--- AKHIR TEKS MENTAH ---";

        return $prompt;
    }

    /**
     * Generate pembahasan mendalam untuk butir soal yang sudah ada
     */
    public function generatePembahasan(string $pertanyaan, array $opsi = [], ?string $apiKey = null, string $provider = 'gemini', ?string $model = null): string
    {
        if ($provider !== 'gemini' || app()->environment('testing')) {
            return $this->generatePembahasanSingleKey($pertanyaan, $opsi, $apiKey, $provider, $model);
        }

        $keys = $this->getGeminiApiKeys($apiKey);
        if (empty($keys)) {
            return $this->generatePembahasanSingleKey($pertanyaan, $opsi, null, $provider, $model);
        }

        return $this->withKeyFailover($keys, fn (string $k) => $this->generatePembahasanSingleKey($pertanyaan, $opsi, $k, $provider, $model));
    }

    protected function generatePembahasanSingleKey(string $pertanyaan, array $opsi = [], ?string $apiKey = null, string $provider = 'gemini', ?string $model = null): string
    {
        $key = $apiKey ?: ($provider === 'gemini' ? ($this->getGeminiApiKeys()[0] ?? null) : config('ai.openai.api_key'));

        if (app()->environment('testing')) {
            return "Pembahasan dibuat otomatis oleh AI: Berdasarkan konsep materi, langkah penyelesaian dilakukan dengan mengidentifikasi variabel utama lalu menerapkan rumus yang relevan.";
        }


        if (empty($key)) {
            throw new Exception("API Key {$provider} belum diisi.");
        }

        $prompt = "Buatkan teks pembahasan dan langkah penyelesaian rinci yang edukatif untuk butir soal ujian berikut.\n\n";
        $prompt .= "Teks Soal: {$pertanyaan}\n";
        if (!empty($opsi)) {
            $prompt .= "Pilihan Jawaban:\n";
            foreach ($opsi as $o) {
                $label = $o['label'] ?? '';
                $teks = $o['teks_opsi'] ?? '';
                $kunci = !empty($o['is_jawaban_benar']) ? ' (KUNCI BENAR)' : '';
                $prompt .= "- Opsi {$label}: {$teks}{$kunci}\n";
            }
        }
        $prompt .= "\nAturan:\n";
        $prompt .= "1. Gunakan Bahasa Indonesia baku, ramah untuk siswa dan guru.\n";
        $prompt .= "2. Jika ada rumus matematika/eksak, tuliskan format LaTeX dengan tanda dollar: \$rumus\$ atau \$\$rumus\$\$.\n";
        $prompt .= "3. Jelaskan mengapa kunci jawaban tersebut benar dan mengapa opsi lain salah.\n";
        $prompt .= "4. Kembalikan teks pembahasan langsung tanpa format JSON.";

        if ($provider === 'gemini') {
            $primaryModel = $model ?: config('ai.gemini.model', 'gemini-3.1-flash-lite');
            $baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
            $candidateModels = $this->buildOrderedCandidateModels($primaryModel);
            $primaryError = null;
            $lastError = null;

            foreach ($candidateModels as $candidate) {
                try {
                    $url = "{$baseUrl}/models/{$candidate}:generateContent?key={$key}";

                    $response = Http::timeout(20)->post($url, [
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => $prompt]]]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.6,
                        ]
                    ]);

                    if ($response->successful()) {
                        $text = $response->json('candidates.0.content.parts.0.text');
                        if (!empty($text)) {
                            return $text;
                        }
                    }

                    $this->markModelBusy($candidate);
                    $err = $response->json('error.message') ?? $response->body();
                    if ($candidate === $primaryModel) {
                        $primaryError = $err;
                    }
                    $lastError = $err;
                } catch (\Throwable $e) {
                    $this->markModelBusy($candidate);
                    if ($candidate === $primaryModel) {
                        $primaryError = $e->getMessage();
                    }
                    $lastError = $e->getMessage();
                }
            }

            throw new Exception("Gemini API Error ({$primaryModel}): " . ($primaryError ?? $lastError ?? 'Pembahasan tidak dapat dibuat.'));
        } else {
            $model = config('ai.openai.model', 'gpt-4o-mini');
            $url = rtrim(config('ai.openai.base_url'), '/') . '/chat/completions';

            $response = Http::withToken($key)->timeout(30)->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.6,
            ]);

            if (!$response->successful()) {
                throw new Exception("OpenAI API Error: " . ($response->json('error.message') ?? $response->body()));
            }

            return $response->json('choices.0.message.content') ?? 'Pembahasan tidak dapat dibuat.';
        }
    }

    /**
     * Panggil Google Gemini API
     */
    protected function callGemini(string $apiKey, string $systemPrompt, string $userPrompt, array $params): array
    {
        $keys = $this->getGeminiApiKeys($apiKey);

        return $this->withKeyFailover($keys, fn (string $k) => $this->callGeminiSingleKey($k, $systemPrompt, $userPrompt, $params));
    }

    protected function callGeminiSingleKey(string $apiKey, string $systemPrompt, string $userPrompt, array $params): array
    {
        $primaryModel = $params['model'] ?? config('ai.gemini.model', 'gemini-3.1-flash-lite');
        $baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $candidateModels = $this->buildOrderedCandidateModels($primaryModel);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\n" . $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'responseMimeType' => 'application/json',
                'maxOutputTokens' => 65536,
            ],
        ];

        $primaryError = null;
        $lastError = null;
        $startedAt = microtime(true);

        foreach ($candidateModels as $model) {
            // Cegah akumulasi waktu melebihi 55 detik agar tidak terkena Cloudflare HTTP 524 Timeout (~100s)
            if ((microtime(true) - $startedAt) > 55) {
                break;
            }

            // Timeout lebih singkat (12s) pada model non-lite yang rentan antre panjang agar cepat failover ke model lite (1.5s)
            $perModelTimeout = str_contains($model, 'lite') || $model === 'gemini-3-flash-preview' ? 28 : 12;

            try {
                $url = "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout($perModelTimeout)->post($url, $payload);

                if ($response->successful()) {
                    $rawText = $response->json('candidates.0.content.parts.0.text');
                    if (!empty($rawText)) {
                        return $this->parseJsonResponse($rawText);
                    }
                }

                $this->markModelBusy($model);
                $err = $response->json('error.message') ?? $response->body();
                if ($model === $primaryModel) {
                    $primaryError = $err;
                }
                $lastError = $err;
                Log::warning("Gemini model {$model} respons gagal: {$err}. Mencoba model alternatif...");
            } catch (\Throwable $e) {
                $this->markModelBusy($model);
                if ($model === $primaryModel) {
                    $primaryError = $e->getMessage();
                }
                $lastError = $e->getMessage();
                Log::warning("Gemini model {$model} timeout/koneksi gagal: {$lastError}. Mencoba model alternatif...");
            }
        }

        $finalError = $primaryError ?? $lastError;
        Log::error('Gemini API All Models Failed', ['primary_model' => $primaryModel, 'error' => $finalError]);
        throw new Exception("Google Gemini API Error ({$primaryModel}): {$finalError}");
    }

    /**
     * Panggil OpenAI / Compatible API
     */
    protected function callOpenAI(string $apiKey, string $systemPrompt, string $userPrompt, array $params): array
    {
        $model = $params['model'] ?? config('ai.openai.model', 'gpt-4o-mini');
        $baseUrl = rtrim(config('ai.openai.base_url', 'https://api.openai.com/v1'), '/');
        $url = "{$baseUrl}/chat/completions";

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7,
        ];

        $response = Http::withToken($apiKey)->timeout(60)->post($url, $payload);

        if (!$response->successful()) {
            $errMsg = $response->json('error.message') ?? $response->body();
            Log::error('OpenAI API Error', ['response' => $response->body()]);
            throw new Exception("OpenAI API Error: {$errMsg}");
        }

        $rawText = $response->json('choices.0.message.content');
        if (empty($rawText)) {
            throw new Exception("OpenAI tidak memberikan balasan teks.");
        }

        return $this->parseJsonResponse($rawText);
    }

    /**
     * Parsing JSON dari balasan AI dengan proteksi struktur dan perbaikan karakter LaTeX
     */
    protected function parseJsonResponse(string $rawText): array
    {
        $clean = trim($rawText);

        // 1. Ekstrak JSON array atau object jika dibungkus code fence ```json ... ``` atau teks lain
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $clean, $matches)) {
            $clean = trim($matches[1]);
        } elseif (preg_match('/(\[[\s\S]*\]|\{[\s\S]*\})/i', $clean, $matches)) {
            $clean = trim($matches[1]);
        }

        $data = json_decode($clean, true);

        // Jika decode gagal (sering terjadi karena rumus LaTeX memiliki backslash tunggal seperti \implies, \frac, \sqrt, dll)
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $fixed = $this->fixJsonWithLatex($clean);
            $data = json_decode($fixed, true);
        }

        // Jika masih gagal karena respons JSON terpotong di tengah (misal saat memproses 50 soal sekaligus),
        // pulihkan seluruh objek butir soal yang sudah utuh sebelum titik potong.
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $recovered = $this->recoverTruncatedJson($this->fixJsonWithLatex($clean));
            if ($recovered !== null) {
                $data = $recovered;
            }
        }

        if (!is_array($data)) {
            Log::warning('Gagal decode JSON AI', ['raw' => $rawText, 'clean' => $clean, 'json_err' => json_last_error_msg()]);
            $errDetail = json_last_error_msg() !== 'No error' ? json_last_error_msg() : 'Respons tidak berbentuk objek/array JSON yang valid.';
            throw new Exception("Format JSON dari AI tidak valid: {$errDetail}");
        }

        // Normalisasi struktur (apakah root array atau root object dengan key 'soals'/'data')
        $items = [];
        if (isset($data['soals']) && is_array($data['soals'])) {
            $items = $data['soals'];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $items = $data['data'];
        } elseif (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        } elseif (array_is_list($data)) {
            $items = $data;
        } elseif (isset($data['pertanyaan'])) {
            $items = [$data];
        }

        if (empty($items)) {
            throw new Exception("AI tidak menghasilkan butir soal yang sesuai format.");
        }

        // Sanitasi dan format tiap butir soal
        $formatted = [];
        foreach ($items as $index => $item) {
            $tipe = $item['tipe_soal'] ?? 'pilihan_ganda';
            $pertanyaan = $item['pertanyaan'] ?? '';

            // Pastikan format HTML dasar pada pertanyaan
            if (!str_starts_with(trim($pertanyaan), '<p>')) {
                $pertanyaan = '<p>' . nl2br(htmlspecialchars($pertanyaan)) . '</p>';
            }

            // Normalisasi opsi jawaban
            $opsiJawabans = [];
            $rawOpsi = $item['opsi_jawabans'] ?? $item['opsi'] ?? [];

            if (is_array($rawOpsi) && !empty($rawOpsi)) {
                $hasCorrect = false;
                foreach ($rawOpsi as $i => $opt) {
                    $label = $opt['label'] ?? chr(65 + $i);
                    $teksOpsi = $opt['teks_opsi'] ?? $opt['teks'] ?? (is_string($opt) ? $opt : '');
                    $isBenar = !empty($opt['is_jawaban_benar']) || !empty($opt['benar']);

                    if ($isBenar) $hasCorrect = true;

                    $opsiJawabans[] = [
                        'label' => $label,
                        'teks_opsi' => $teksOpsi,
                        'gambar_opsi' => null,
                        'is_jawaban_benar' => $isBenar,
                        'pasangan_matching' => $opt['pasangan_matching'] ?? null,
                    ];
                }

                // Fallback: jika pilihan ganda tapi belum ada kunci benar, jadikan opsi A sebagai kunci
                if (!$hasCorrect && in_array($tipe, ['pilihan_ganda', 'benar_salah']) && !empty($opsiJawabans)) {
                    $opsiJawabans[0]['is_jawaban_benar'] = true;
                }
            }

            // Inferensi / fallback taksonomi jika AI belum menyertakan
            $kesulitan = strtoupper(trim((string) ($item['tingkat_kesulitan'] ?? '')));
            $requestedKesulitan = strtoupper(trim((string) ($params['kesulitan'] ?? '')));

            if (!in_array($kesulitan, ['LOTS', 'MOTS', 'HOTS'], true)) {
                // PRIORITAS 1: Gunakan tingkat kesulitan yang diminta oleh guru saat generate!
                if (in_array($requestedKesulitan, ['LOTS', 'MOTS', 'HOTS'], true)) {
                    $kesulitan = $requestedKesulitan;
                    $bloomTax = match ($kesulitan) {
                        'HOTS' => 'C4',
                        'LOTS' => 'C2',
                        default => 'C3',
                    };
                } else {
                    // Fallback inferensi teks jika tidak ada parameter requested
                    $bloom = self::inferBloomTaxonomy($pertanyaan);
                    $kesulitan = $bloom['tingkat_kesulitan'];
                    $bloomTax = $bloom['taksonomi_bloom'];
                }
            } else {
                $bloomTax = strtoupper(trim((string) ($item['taksonomi_bloom'] ?? '')));
                if (!preg_match('/^C[1-6]$/', $bloomTax)) {
                    $bloomTax = match ($kesulitan) {
                        'HOTS' => 'C4',
                        'LOTS' => 'C2',
                        default => 'C3',
                    };
                }
            }

            // Hitung bobot berjenjang berdasarkan kesulitan jika ada bobot dasar
            $defaultBaseBobot = !empty($params['bobot_nilai']) ? (float) $params['bobot_nilai'] : null;
            $bobotCalculated = isset($item['bobot_nilai']) && !empty($item['bobot_nilai'])
                ? (float) $item['bobot_nilai']
                : self::calculateBobotByKesulitan($kesulitan, $defaultBaseBobot ?? 2.0);

            $formatted[] = [
                'tipe_soal' => $tipe,
                'tingkat_kesulitan' => $kesulitan,
                'taksonomi_bloom' => $bloomTax,
                'bobot_nilai' => $bobotCalculated,
                'pertanyaan' => $pertanyaan,
                'opsi_jawabans' => $opsiJawabans,
                'kunci_isian' => $item['kunci_isian'] ?? '',
                'rubrik_essay' => $item['rubrik_essay'] ?? '',
                'pembahasan' => $item['pembahasan'] ?? '',
            ];
        }

        // Jika tidak ada bobot kustom per soal, otomatis distribusikan bobot total 100 poin secara sama rata
        if (empty($params['default_bobot']) && empty($params['bobot_nilai'])) {
            $formatted = self::distributeBobotByRasio($formatted, 100.0);
        }

        return $formatted;
    }

    /**
     * Memperbaiki string JSON yang memuat karakter backslash rumus LaTeX yang tidak ter-escape ganda
     */
    protected function fixJsonWithLatex(string $json): string
    {
        // 1. Perbaiki perintah LaTeX multi-huruf seperti \implies, \frac, \sqrt, \times, \begin, \end, \log, \sin, dll
        $clean = preg_replace('/(?<!\\\\)\\\([a-zA-Z]{2,})/', '\\\\\\\\$1', $json);

        // 2. Perbaiki escape karakter LaTeX atau invalid JSON single letter seperti \i, \s, \l, \c, \d, \p, dll
        $clean = preg_replace('/(?<!\\\\)\\\([acdeghijklmopqsvwxyzACDEFGHIJKLMNOPQRSTUVWXYZ])/', '\\\\\\\\$1', $clean);

        // 3. Perbaiki kurung kurawal LaTeX seperti \{ dan \}
        $clean = preg_replace('/(?<!\\\\)\\\([{}])/', '\\\\\\\\$1', $clean);

        return $clean;
    }

    /**
     * Pulihkan JSON array butir soal apabila terpotong di akhir oleh batas token AI
     */
    protected function recoverTruncatedJson(string $brokenJson): ?array
    {
        // Cari posisi elemen soal terakhir yang utuh (ditutup dengan } sebelum koma dan objek berikutnya)
        $pos = strlen($brokenJson);
        while (($pos = strrpos(substr($brokenJson, 0, $pos), '}')) !== false) {
            $candidate = substr($brokenJson, 0, $pos + 1);
            foreach (["]}", "]"] as $suffix) {
                $decoded = json_decode($candidate . $suffix, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                    return $decoded;
                }
            }
        }
        return null;
    }

    /**
     * Bangun System Prompt
     */
    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
Anda adalah AI Expert Pembuat Soal Ujian CBT & Kurikulum Nasional Indonesia (Kemendikbudristek & Kemenag).
Tugas Anda adalah membuat butir soal ujian berkualitas tinggi, edukatif, kontekstual, dan siap uji.

ATURAN WAJIB FORMAT & KONTEN:
1. RUMUS MATEMATIKA & EKSAK (LaTeX):
   - Gunakan format KaTeX inline \$rumus\$ atau display \$\$rumus\$\$.
   - Contoh: \$f(x) = x^2 - 4x + 4 = 0\$, \$x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}\$, \$H_2O\$, \$\\int_0^1 x^2 dx\$.
   - PENTING DALAM FORMAT JSON: Setiap simbol backslash rumus LaTeX wajib di-escape ganda (contoh: \\\\frac{a}{b}, \\\\sqrt{x}, \\\\pm, \\\\times, \\\\log).
   - JANGAN gunakan format Unicode aneh atau teks gambar; wajib sintaks LaTeX murni diapit \$.

2. TEKS ARAB & AL-QUR'AN:
   - Wajib gunakan harakat lengkap dan tanda baca yang benar untuk ayat atau kosakata Arab.

3. KUALITAS BUTIR SOAL & TAKSONOMI BLOOM:
   - WAJIB cantumkan field 'tingkat_kesulitan' ("LOTS", "MOTS", atau "HOTS") sesuai permintaan.
   - WAJIB cantumkan field 'taksonomi_bloom' ("C1", "C2", "C3", "C4", "C5", atau "C6").
   - Soal HOTS (C4-C6): Harus berupa analisis masalah, studi kasus kontekstual, evaluasi, atau perancangan solusi.
   - Soal MOTS (C3): Penerapan rumus, prosedur, atau implementasi kaidah pada skenario tertentu.
   - Soal LOTS (C1-C2): Pemahaman definisi, ingatan fakta, atau konsep dasar.
   - Opsi pengecoh (distractor) harus logis, realistis, dan berbobot (bukan opsi asal-asalan).
   - Hindari opsi negatif ganda ("kecuali yang bukan").
   - Panjang pilihan jawaban relatif homogen/seimbang.

4. FORMAT OUTPUT WAJIB:
   Kembalikan HANYA JSON murni (tanpa pembuka/penutup markdown seperti ```json).
   JSON harus berupa objek dengan key "soals" yang berisi array butir soal.

Struktur JSON per butir soal:
{
  "soals": [
    {
      "tipe_soal": "pilihan_ganda" | "pg_kompleks" | "benar_salah" | "menjodohkan" | "isian" | "essay",
      "tingkat_kesulitan": "LOTS" | "MOTS" | "HOTS",
      "taksonomi_bloom": "C1" | "C2" | "C3" | "C4" | "C5" | "C6",
      "bobot_nilai": 2.0,
      "pertanyaan": "<p>Teks stimulus / pokok soal...</p>",
      "opsi_jawabans": [
        { "label": "A", "teks_opsi": "...", "is_jawaban_benar": true },
        { "label": "B", "teks_opsi": "...", "is_jawaban_benar": false },
        { "label": "C", "teks_opsi": "...", "is_jawaban_benar": false },
        { "label": "D", "teks_opsi": "...", "is_jawaban_benar": false }
      ],
      "kunci_isian": "...", // hanya jika tipe_soal = isian
      "rubrik_essay": "...", // hanya jika tipe_soal = essay
      "pembahasan": "Penjelasan rinci mengapa kunci jawaban tersebut benar dan langkah penyelesaiannya..."
    }
  ]
}
PROMPT;
    }

    /**
     * Bangun User Prompt berdasarkan parameter input
     */
    protected function buildUserPrompt(array $params): string
    {
        $topik = $params['topik'] ?? 'Pengetahuan Umum';
        $mapel = $params['mapel'] ?? 'Mata Pelajaran Umum';
        $tingkat = $params['tingkat'] ?? 'SMA/SMK';
        $tipeSoal = $params['tipe_soal'] ?? 'pilihan_ganda';
        $kesulitan = $params['kesulitan'] ?? 'MOTS';
        $jumlah = max(1, min(50, (int) ($params['jumlah'] ?? 1)));
        $instruksi = $params['instruksi_tambahan'] ?? '';

        $prompt = "Buatkan {$jumlah} butir soal ujian dengan rincian berikut:\n";
        $prompt .= "- Mata Pelajaran: {$mapel}\n";
        $prompt .= "- Tingkat / Kelas: {$tingkat}\n";
        $prompt .= "- Topik / Materi Pokok: {$topik}\n";
        $prompt .= "- Tipe Soal: {$tipeSoal}\n";
        $prompt .= "- Tingkat Kesulitan / Kognitif: {$kesulitan} (Wajib cantumkan \"tingkat_kesulitan\": \"{$kesulitan}\" pada setiap butir soal)\n";

        if ($tipeSoal === 'pilihan_ganda') {
            $prompt .= "- Aturan Opsi: Buat 4 opsi (A, B, C, D) untuk SMP atau 5 opsi (A, B, C, D, E) untuk SMA/SMK. TEPAT 1 opsi yang is_jawaban_benar: true.\n";
        } elseif ($tipeSoal === 'benar_salah') {
            $prompt .= "- Aturan Opsi: Tepat 2 opsi (A: Benar, B: Salah). TEPAT 1 opsi yang is_jawaban_benar: true.\n";
        } elseif ($tipeSoal === 'pg_kompleks') {
            $prompt .= "- Aturan Opsi: Buat 4 atau 5 opsi. Setidaknya 2 opsi yang is_jawaban_benar: true.\n";
        } elseif ($tipeSoal === 'menjodohkan') {
            $prompt .= "- Aturan Opsi: Buat 3 hingga 5 pasangan matching (teks_opsi sebagai pernyataan kiri, pasangan_matching sebagai jawaban kanan).\n";
        } elseif ($tipeSoal === 'isian') {
            $prompt .= "- Aturan Opsi: Kosongkan opsi_jawabans, isi field kunci_isian dengan jawaban pasti (kata kunci singkat atau angka).\n";
        } elseif ($tipeSoal === 'essay') {
            $prompt .= "- Aturan Opsi: Kosongkan opsi_jawabans, isi field rubrik_essay dengan pedoman penskoran & kata kunci wajib.\n";
        }

        if (filled($instruksi)) {
            $prompt .= "- Instruksi Tambahan dari Guru: {$instruksi}\n";
        }

        return $prompt;
    }

    /**
     * Mock generator untuk unit testing / offline demo
     */
    protected function generateMockSoals(array $params): array
    {
        $topik = $params['topik'] ?? 'Sistem Persamaan Linier';
        $tipe = $params['tipe_soal'] ?? 'pilihan_ganda';
        $kesulitan = strtoupper($params['kesulitan'] ?? 'MOTS');
        $bloom = match($kesulitan) {
            'HOTS' => 'C4',
            'LOTS' => 'C2',
            default => 'C3',
        };
        $jumlah = max(1, min(50, (int) ($params['jumlah'] ?? 1)));

        $result = [];
        for ($i = 1; $i <= $jumlah; $i++) {
            if ($tipe === 'pilihan_ganda') {
                $result[] = [
                    'tipe_soal' => 'pilihan_ganda',
                    'tingkat_kesulitan' => $kesulitan,
                    'taksonomi_bloom' => $bloom,
                    'bobot_nilai' => 2.0,
                    'pertanyaan' => "<p>Berdasarkan konsep materi <strong>{$topik}</strong> (Butir {$i}), jika diketahui persamaan kuadrat \$f(x) = x^2 - 4x + 4 = 0\$, tentukan nilai \$x\$ yang memenuhi:</p>",
                    'opsi_jawabans' => [
                        ['label' => 'A', 'teks_opsi' => '$x = 2$', 'gambar_opsi' => null, 'is_jawaban_benar' => true, 'pasangan_matching' => null],
                        ['label' => 'B', 'teks_opsi' => '$x = -2$', 'gambar_opsi' => null, 'is_jawaban_benar' => false, 'pasangan_matching' => null],
                        ['label' => 'C', 'teks_opsi' => '$x = 4$', 'gambar_opsi' => null, 'is_jawaban_benar' => false, 'pasangan_matching' => null],
                        ['label' => 'D', 'teks_opsi' => '$x = 0$', 'gambar_opsi' => null, 'is_jawaban_benar' => false, 'pasangan_matching' => null],
                    ],
                    'kunci_isian' => '',
                    'rubrik_essay' => '',
                    'pembahasan' => "Pembahasan: Faktorkan persamaan \$x^2 - 4x + 4 = (x - 2)^2 = 0\$, sehingga diperoleh akar kembar \$x = 2\$.",
                ];
            } else {
                $result[] = [
                    'tipe_soal' => $tipe,
                    'tingkat_kesulitan' => $kesulitan,
                    'taksonomi_bloom' => $bloom,
                    'bobot_nilai' => 2.0,
                    'pertanyaan' => "<p>Jelaskan atau selesaikan permasalahan terkait <strong>{$topik}</strong> berikut ini secara komprehensif:</p>",
                    'opsi_jawabans' => [],
                    'kunci_isian' => '2',
                    'rubrik_essay' => 'Rubrik penilaian: Jawaban memuat langkah penyelesaian logis dan kesimpulan akhir yang benar (Skor maks: 100).',
                    'pembahasan' => "Pembahasan mendalam untuk butir soal materi {$topik}.",
                ];
            }
        }

        return $result;
    }

    /**
     * Simpan API key ke file .env
     */
    public function saveApiKeyToEnv(string $apiKey, string $provider = 'gemini'): void
    {
        if ($provider === 'gemini') {
            // Satu atau lebih key (pisah koma / spasi / baris baru): key pertama = utama, sisanya = cadangan
            $keys = self::parseKeyList($apiKey);
            if (empty($keys)) return;

            $this->writeEnvValue('GEMINI_API_KEY', $keys[0]);
            $this->writeEnvValue('GEMINI_API_KEYS', implode(',', array_slice($keys, 1)));
            config(['ai.gemini.api_key' => $keys[0], 'ai.gemini.api_keys' => array_slice($keys, 1)]);

            return;
        }

        $this->writeEnvValue($this->getEnvKeyName($provider), trim($apiKey));
    }

    protected function writeEnvValue(string $keyName, string $value): void
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) return;

        $content = File::get($envPath);
        $line = "{$keyName}={$value}";

        if (preg_match("/^{$keyName}=.*/m", $content)) {
            $content = preg_replace_callback("/^{$keyName}=.*/m", fn () => $line, $content);
        } else {
            $content = rtrim($content) . "\n{$line}\n";
        }

        File::put($envPath, $content);
    }

    /**
     * Pecah daftar API key (dipisah koma, titik koma, spasi, atau baris baru) menjadi array unik.
     */
    public static function parseKeyList(?string $input): array
    {
        if ($input === null) return [];

        return array_values(array_unique(array_filter(preg_split('/[\s,;]+/', trim($input)) ?: [])));
    }

    /**
     * Kumpulan API key Gemini terpusat (GEMINI_API_KEY + GEMINI_API_KEYS), berurutan.
     * $preferred (opsional) diutamakan di urutan pertama.
     */
    public function getGeminiApiKeys(?string $preferred = null): array
    {
        $pool = array_merge(
            self::parseKeyList($preferred),
            self::parseKeyList((string) config('ai.gemini.api_key')),
            self::parseKeyList(implode(',', (array) config('ai.gemini.api_keys', [])))
        );

        return array_values(array_unique($pool));
    }

    /**
     * Jalankan $fn dengan API key berurutan; jika sebuah key kena limit/kuota/tidak valid,
     * tandai sementara (cooldown) lalu otomatis pindah ke key cadangan berikutnya.
     */
    protected function withKeyFailover(array $keys, callable $fn)
    {
        if (empty($keys)) {
            return $fn('');
        }

        $available = array_values(array_filter($keys, fn ($k) => !Cache::has($this->keyCooldownCacheKey($k))));
        $candidates = !empty($available) ? $available : $keys; // semua sedang cooldown: coba lagi semuanya

        $lastError = null;
        foreach ($candidates as $i => $key) {
            try {
                return $fn($key);
            } catch (\Throwable $e) {
                if (!$this->isKeyLimitError($e->getMessage())) {
                    throw $e;
                }

                $lastError = $e;
                Cache::put($this->keyCooldownCacheKey($key), true, now()->addMinutes($this->cooldownMinutesFor($e->getMessage())));
                Log::warning('API Key AI #' . ($i + 1) . ' terkena limit/tidak valid, pindah ke key cadangan: ' . $e->getMessage());
            }
        }

        throw new Exception('Semua API Key AI terpusat (' . count($candidates) . ' key) sedang limit/tidak valid. ' . ($lastError ? $lastError->getMessage() : ''));
    }

    protected function keyCooldownCacheKey(string $key): string
    {
        return 'ai_key_cooldown:' . sha1($key);
    }

    protected function isKeyLimitError(string $message): bool
    {
        return (bool) preg_match('/429|quota|rate.?limit|RESOURCE_EXHAUSTED|exceeded|API key not valid|API_KEY_INVALID|PERMISSION_DENIED|\b403\b|billing|too many requests/i', $message);
    }

    protected function cooldownMinutesFor(string $message): int
    {
        if (preg_match('/per day|daily|API key not valid|API_KEY_INVALID|PERMISSION_DENIED|\b403\b|billing/i', $message)) {
            return 60;
        }

        return 2;
    }

    public function getEnvKeyName(string $provider): string
    {
        return $provider === 'gemini' ? 'GEMINI_API_KEY' : 'OPENAI_API_KEY';
    }
}

