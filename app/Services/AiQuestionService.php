<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiQuestionService
{
    /**
     * Daftar model Google Gemini yang didukung (Lini Gemini 3.6, 3.5 & Flash)
     */
    public static function getAvailableGeminiModels(): array
    {
        return [
            'gemini-3.6-flash' => '⚡ Gemini 3.6 Flash (Sangat Cepat & Paling Stabil - Rekomendasi Utama)',
            'gemini-3.5-flash' => '🚀 Gemini 3.5 Flash (Cepat & Andal)',
            'gemini-3.5-flash-lite' => '🌱 Gemini 3.5 Flash-Lite (Hemat Kuota)',
            'gemini-3.5-pro' => '🧠 Gemini 3.5 Pro (Penalaran Mendalam / Soal HOTS)',
            'gemini-3.1-pro-preview' => '🔬 Gemini 3.1 Pro Preview (Alternatif Pro Terbaru)',
            'gemini-flash-latest' => '🔄 Gemini Flash Latest (Versi Terbaru Otomatis)',
        ];
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
        $apiKey = $params['api_key'] ?? ($provider === 'gemini' ? config('ai.gemini.api_key') : config('ai.openai.api_key'));

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
        $rawText = trim($rawText);
        if (empty($rawText)) {
            throw new Exception("Teks sumber soal masih kosong.");
        }

        $defaultBobot = isset($params['default_bobot']) ? (float) $params['default_bobot'] : (isset($params['bobot_nilai']) ? (float) $params['bobot_nilai'] : null);
        $provider = $params['provider'] ?? config('ai.provider', 'gemini');
        $apiKey = $params['api_key'] ?? ($provider === 'gemini' ? config('ai.gemini.api_key') : config('ai.openai.api_key'));

        // Jika testing environment, gunakan fallback parser / mock agar test suite cepat dan offline-safe
        if (app()->environment('testing')) {
            $parsed = $this->fallbackLocalParse($rawText, $defaultBobot);
            if (!empty($parsed)) {
                return $parsed;
            }
            return $this->generateMockSoals(['topik' => 'Hasil Parse Teks', 'jumlah' => 1]);
        }

        if (empty($apiKey)) {
            // Jika belum ada API key, gunakan fallback regex parser lokal
            $localParsed = $this->fallbackLocalParse($rawText, $defaultBobot);
            if (!empty($localParsed)) {
                return $localParsed;
            }
            throw new Exception("API Key {$provider} belum diisi untuk membedah teks dengan AI. Silakan masukkan API Key di formulir atau di file .env ({$this->getEnvKeyName($provider)}).");
        }

        $systemPrompt = $this->buildParseSystemPrompt();
        $userPrompt = $this->buildParseUserPrompt($rawText, $defaultBobot, $params);

        try {
            if ($provider === 'gemini') {
                $results = $this->callGemini($apiKey, $systemPrompt, $userPrompt, $params);
            } else {
                $results = $this->callOpenAI($apiKey, $systemPrompt, $userPrompt, $params);
            }

            // Rekonsiliasi dengan separateAnswerKeys jika ada (safety net)
            $separateAnswerKeys = $this->extractSeparateAnswerKeys($rawText);
            if (!empty($separateAnswerKeys) && !empty($results)) {
                $results = $this->reconcileAnswerKeys($results, $separateAnswerKeys);
            }

            return $results;
        } catch (\Throwable $e) {
            // Jika AI gagal (misal kuota/timeout), coba fallback regex lokal sebelum melempar exception
            $localParsed = $this->fallbackLocalParse($rawText, $defaultBobot);
            if (!empty($localParsed)) {
                Log::info('Berhasil menggunakan fallback regex parser lokal setelah AI gagal: ' . $e->getMessage());
                return $localParsed;
            }
            throw $e;
        }
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

        // 1. Ekstrak dari tabel Markdown (contoh: | 1 | B | 14 | D |)
        if (preg_match_all('/\|\s*(\d{1,3})\s*\|\s*([A-Ea-e])\s*(?=\||$)/m', $text, $tableMatches, PREG_SET_ORDER)) {
            foreach ($tableMatches as $m) {
                $keys[(int) $m[1]] = strtoupper(trim($m[2]));
            }
        }

        // 2. Format daftar teks di bawah heading Kunci / Jawaban (bisa multi-line atau inline setelah titik dua)
        if (preg_match('/(?:^|\n)[ \t]*(?:#{1,4}[ \t]*)?(?:[A-Z]\.?[ \t]*)?(?:Kunci\s*(?:Jawaban)?|Answer\s*Key|KUNCI)[ \t]*(?:[:=-][ \t]*|\r?\n)([\s\S]*)$/i', $text, $secMatch)) {
            $kunciText = $secMatch[1];

            if (preg_match_all('/(?:^|\s|,|;)(\d{1,3})\s*(?:[\.\)\:\=\-]|(?:\s+))\s*([A-Ea-e])(?=\s|,|;|$)/m', $kunciText, $listMatches, PREG_SET_ORDER)) {
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

        // Bersihkan blok tabel kunci jawaban & catatan kaki di bagian akhir teks agar tidak menempel ke opsi soal terakhir
        $text = preg_replace('/\n[ \t]*(?:#{1,4}[ \t]*|\*{1,2})?(?:[A-Z]\.?[ \t]*)?(?:Kunci\s*Jawaban|DAFTAR\s*KUNCI|Tabel\s*Kunci|Pedoman\s*Penilaian|Rubrik\s*Penilaian)[\s\S]*$/i', '', $text);
        $text = preg_replace('/\n[ \t]*\|[ \t]*No\.?[ \t]*\|[\s\S]*$/i', '', $text);
        $text = preg_replace('/\n[ \t]*(?:\*{1,2})?Catatan(?:\s+penyusunan)?\s*:[\s\S]*$/i', '', $text);

        // 2. Deteksi apakah teks memiliki bagian-bagian (Sections) seperti "## A. Pilihan Ganda", "## B. Essay", dll
        $sectionPattern = '/(?=(?:^|\n)\s*#{1,3}\s*(?:[A-Z]\.?\s*)?(?:Pilihan\s*Ganda|PG|Multiple\s*Choice|Essay|Uraian|Isian|Menjodohkan|Kunci\s*Jawaban|Pedoman\s*Penilaian))/i';
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
                    && !preg_match('/(?:^|\n)\s*(?:\*{1,2})?1[\.\)]\s+/m', $sec)
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
                if (preg_match('/(?:^|\n)\s*(?:\*{1,2})?1[\.\)]\s+/m', $sec, $m, PREG_OFFSET_CAPTURE)) {
                    $body = substr($sec, $m[0][1]);
                } else {
                    $body = preg_replace('/^#{1,3}[^\n]+\n(?:Petunjuk[^\n]+\n)?/i', '', $sec);
                }
                $parsedSections[] = [
                    'type' => $type,
                    'content' => trim($body),
                ];
            }
        } else {
            // Tanpa section eksplisit, bersihkan judul dokumen di awal sebelum soal No 1
            if (preg_match('/(?:^|\n)\s*(?:\*{1,2})?1[\.\)]\s+/i', $text, $m, PREG_OFFSET_CAPTURE)) {
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
                    }
                }

                if (!str_starts_with($pertanyaanText, '<p>')) {
                    $pertanyaanText = '<p>' . nl2br(htmlspecialchars($pertanyaanText)) . '</p>';
                }

                $allSoals[] = [
                    'tipe_soal' => $tipe,
                    'bobot_nilai' => $defaultBobot ?? 2.0,
                    'pertanyaan' => $pertanyaanText,
                    'opsi_jawabans' => $opsiJawabans,
                    'kunci_isian' => ($tipe === 'isian') ? ($kunci ?? '') : '',
                    'rubrik_essay' => ($tipe === 'essay') ? ($pembahasan ?: 'Pedoman penilaian essay...') : '',
                    'pembahasan' => $pembahasan,
                ];
            }
        }

        // Seimbangkan bobot_nilai agar total 100 poin (Objektif : Subjektif = 75 : 25 atau 100%) jika tidak ada bobot kustom
        if ($defaultBobot === null) {
            $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
            $objIndices = [];
            $subIndices = [];
            foreach ($allSoals as $idx => $f) {
                if (in_array($f['tipe_soal'], $objTypes)) {
                    $objIndices[] = $idx;
                } else {
                    $subIndices[] = $idx;
                }
            }

            $hasObj = !empty($objIndices);
            $hasSub = !empty($subIndices);

            $targetObj = ($hasObj && $hasSub) ? 75.0 : ($hasObj ? 100.0 : 0.0);
            $targetSub = ($hasObj && $hasSub) ? 25.0 : ($hasSub ? 100.0 : 0.0);

            if ($hasObj) {
                $bobotObj = round($targetObj / count($objIndices), 2);
                foreach ($objIndices as $idx) {
                    $allSoals[$idx]['bobot_nilai'] = $bobotObj;
                }
            }

            if ($hasSub) {
                $bobotSub = round($targetSub / count($subIndices), 2);
                foreach ($subIndices as $idx) {
                    $allSoals[$idx]['bobot_nilai'] = $bobotSub;
                }
            }
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

2. TIPE SOAL & PILIHAN JAWABAN:
   - Tipe soal: "pilihan_ganda", "pg_kompleks", "benar_salah", "menjodohkan", "isian", atau "essay".
   - Jika pilihan ganda: ekstrak huruf label ke 'label' (A, B, C, D, E) dan teks opsi ke 'teks_opsi' (bersihkan huruf label dari teks_opsi).
   - KUNCI JAWABAN (SANGAT KRUSIAL):
     a) Kunci Melekat: Cari tanda kunci jawaban langsung di bawah atau di samping butir soal (misal "Kunci: A", "Jawaban: B", "Kunci Jawaban: C", opsi yang dicetak tebal, atau tanda centang).
     b) KUNCI TERPISAH DI AKHIR (WAJIB DIBACA): Seringkali kunci jawaban diletakkan terpisah di bagian bawah dokumen dalam bentuk tabel Markdown (contoh: "## C. Kunci Jawaban Pilihan Ganda | No. | Jawaban | ...") atau daftar teks ("1. B, 2. C, 3. D..."). Anda WAJIB membaca tabel/daftar kunci jawaban di akhir ini dan memetakan setiap nomor kunci ke butir soal yang bersesuaian, sehingga opsi yang cocok mendapatkan 'is_jawaban_benar': true. TEPAT 1 kunci benar untuk pilihan ganda.
     c) JANGAN membuat tabel kunci jawaban atau pedoman penilaian di akhir dokumen menjadi butir soal baru!
   - Jika tipe isian: isi field 'kunci_isian'.
   - Jika tipe essay: isi field 'rubrik_essay'.

3. PEMBAHASAN & PENJELASAN:
   - Jika teks memuat teks pembahasan, penjelasan, atau langkah penyelesaian, simpan ke field 'pembahasan'.

4. RUMUS MATEMATIKA & EKSAK (LaTeX):
   - Gunakan format KaTeX inline \$rumus\$ atau display \$\$rumus\$\$.
   - PENTING DALAM JSON: Setiap simbol backslash rumus LaTeX wajib di-escape ganda (contoh: \\\\frac{a}{b}, \\\\sqrt{x}, \\\\pm, \\\\times, \\\\log).
   - Pertahankan teks Arab berharakat jika ada.

5. FORMAT OUTPUT WAJIB:
   Kembalikan HANYA JSON murni (tanpa format markdown pembungkus ```json).
   JSON harus berupa objek dengan key "soals" yang berisi array butir soal.

Struktur JSON per butir soal:
{
  "soals": [
    {
      "tipe_soal": "pilihan_ganda" | "pg_kompleks" | "benar_salah" | "menjodohkan" | "isian" | "essay",
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
      "pembahasan": "Penjelasan solusi dan langkah penyelesaian..."
    }
  ]
}
PROMPT;
    }

    /**
     * Bangun User Prompt untuk Parsing Teks Mentah
     */
    protected function buildParseUserPrompt(string $rawText, ?float $defaultBobot, array $params): string
    {
        $bobotLabel = $defaultBobot ?? 2.0;
        $prompt = "Tolong uraikan (parse) teks butir soal hasil copas berikut menjadi format JSON sesuai pedoman.\n";
        $prompt .= "Bobot nilai default per butir: {$bobotLabel}.\n\n";

        $keys = $this->extractSeparateAnswerKeys($rawText);
        if (!empty($keys)) {
            $prompt .= "PETUNJUK TAMBAHAN - DAFTAR KUNCI JAWABAN TERPISAH TERDETEKSI DI BAGIAN AKHIR:\n";
            $prompt .= "Sistem mendeteksi kunci jawaban untuk " . count($keys) . " butir soal:\n";
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
        $key = $apiKey ?: ($provider === 'gemini' ? config('ai.gemini.api_key') : config('ai.openai.api_key'));

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
            $primaryModel = $model ?: config('ai.gemini.model', 'gemini-3.6-flash');
            if ($primaryModel === 'gemini-2.5-pro') {
                $primaryModel = 'gemini-3.6-flash';
            }
            $baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
            $candidateModels = array_values(array_unique([
                $primaryModel,
                'gemini-3.6-flash',
                'gemini-3.5-flash',
                'gemini-3.5-flash-lite',
                'gemini-flash-latest',
            ]));
            $primaryError = null;
            $lastError = null;

            foreach ($candidateModels as $candidate) {
                try {
                    $url = "{$baseUrl}/models/{$candidate}:generateContent?key={$key}";

                    $response = Http::timeout(45)->post($url, [
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

                    $err = $response->json('error.message') ?? $response->body();
                    if ($candidate === $primaryModel) {
                        $primaryError = $err;
                    }
                    $lastError = $err;
                } catch (\Throwable $e) {
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
        $primaryModel = $params['model'] ?? config('ai.gemini.model', 'gemini-3.6-flash');
        if ($primaryModel === 'gemini-2.5-pro') {
            $primaryModel = 'gemini-3.6-flash';
        }
        $baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        $candidateModels = array_values(array_unique([
            $primaryModel,
            'gemini-3.6-flash',
            'gemini-3.5-flash',
            'gemini-3.5-flash-lite',
            'gemini-flash-latest',
        ]));

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

        foreach ($candidateModels as $model) {
            try {
                $url = "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout(120)->post($url, $payload);

                if ($response->successful()) {
                    $rawText = $response->json('candidates.0.content.parts.0.text');
                    if (!empty($rawText)) {
                        return $this->parseJsonResponse($rawText);
                    }
                }

                $err = $response->json('error.message') ?? $response->body();
                if ($model === $primaryModel) {
                    $primaryError = $err;
                }
                $lastError = $err;
                Log::warning("Gemini model {$model} respons gagal: {$err}. Mencoba model alternatif...");
            } catch (\Throwable $e) {
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

            $formatted[] = [
                'tipe_soal' => $tipe,
                'bobot_nilai' => (float) ($item['bobot_nilai'] ?? 2.0),
                'pertanyaan' => $pertanyaan,
                'opsi_jawabans' => $opsiJawabans,
                'kunci_isian' => $item['kunci_isian'] ?? '',
                'rubrik_essay' => $item['rubrik_essay'] ?? '',
                'pembahasan' => $item['pembahasan'] ?? '',
            ];
        }

        // Seimbangkan bobot_nilai agar total 100 poin (Objektif : Subjektif = 75 : 25 atau 100%) jika tidak ada bobot kustom
        if (empty($params['default_bobot'])) {
            $objTypes = ['pilihan_ganda', 'pg_kompleks', 'benar_salah', 'menjodohkan'];
            $objIndices = [];
            $subIndices = [];
            foreach ($formatted as $idx => $f) {
                if (in_array($f['tipe_soal'], $objTypes)) {
                    $objIndices[] = $idx;
                } else {
                    $subIndices[] = $idx;
                }
            }

            $hasObj = !empty($objIndices);
            $hasSub = !empty($subIndices);

            $targetObj = ($hasObj && $hasSub) ? 75.0 : ($hasObj ? 100.0 : 0.0);
            $targetSub = ($hasObj && $hasSub) ? 25.0 : ($hasSub ? 100.0 : 0.0);

            if ($hasObj) {
                $bobotObj = round($targetObj / count($objIndices), 2);
                foreach ($objIndices as $idx) {
                    $formatted[$idx]['bobot_nilai'] = $bobotObj;
                }
            }

            if ($hasSub) {
                $bobotSub = round($targetSub / count($subIndices), 2);
                foreach ($subIndices as $idx) {
                    $formatted[$idx]['bobot_nilai'] = $bobotSub;
                }
            }
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

3. KUALITAS BUTIR SOAL:
   - Sesuai dengan Taksonomi Bloom (LOTS = C1-C2, MOTS = C3, HOTS = C4-C6).
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
        $prompt .= "- Tingkat Kesulitan / Kognitif: {$kesulitan}\n";

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
        $jumlah = max(1, min(50, (int) ($params['jumlah'] ?? 1)));

        $result = [];
        for ($i = 1; $i <= $jumlah; $i++) {
            if ($tipe === 'pilihan_ganda') {
                $result[] = [
                    'tipe_soal' => 'pilihan_ganda',
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
        $envPath = base_path('.env');
        if (!File::exists($envPath)) return;

        $keyName = $this->getEnvKeyName($provider);
        $content = File::get($envPath);

        if (str_contains($content, "{$keyName}=")) {
            $content = preg_replace("/^{$keyName}=.*/m", "{$keyName}={$apiKey}", $content);
        } else {
            $content .= "\n{$keyName}={$apiKey}";
        }

        File::put($envPath, $content);
    }

    public function getEnvKeyName(string $provider): string
    {
        return $provider === 'gemini' ? 'GEMINI_API_KEY' : 'OPENAI_API_KEY';
    }
}

