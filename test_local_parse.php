<?php

$rawText = file_get_contents(__DIR__ . '/user_prompt.txt');

function parseExamText(string $rawText, float $defaultBobot = 2.0): array
{
    // 1. Normalisasi Markdown & Karakter Khusus
    $text = html_entity_decode($rawText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Unescape dot setelah angka atau huruf: 1\. -> 1. atau A\. -> A.
    $text = preg_replace('/(?<=^|\s|\n)([A-Za-z]|\d+)\\\([\.\)])/', '$1$2', $text);
    // Hapus trailing backslash Markdown line break di akhir baris
    $text = preg_replace('/\\\\\s*$/m', '', $text);
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));

    // 2. Deteksi apakah teks memiliki bagian-bagian (Sections) seperti "## A. Pilihan Ganda", "## B. Essay", dll
    $sectionPattern = '/(?=(?:^|\n)\s*#{1,3}\s*(?:[A-Z]\.?\s*)?(?:Pilihan\s*Ganda|PG|Multiple\s*Choice|Essay|Uraian|Isian|Menjodohkan|Kunci\s*Jawaban|Pedoman\s*Penilaian))/i';
    $rawSections = preg_split($sectionPattern, $text, -1, PREG_SPLIT_NO_EMPTY);

    $parsedSections = [];
    if (count($rawSections) > 1) {
        foreach ($rawSections as $sec) {
            $sec = trim($sec);
            if (empty($sec)) continue;

            $firstLine = trim(strtok($sec, "\n"));

            // Lewati judul dokumen awal / preamble
            if (preg_match('/^#{1,3}\s*(?:SOAL|PETUNJUK|MATERI|KISI|UJIAN|PENILAIAN)/i', $firstLine) && !preg_match('/Pilihan\s*Ganda|PG|Essay|Uraian/i', $firstLine)) {
                continue;
            }

            // Lewati kunci jawaban guru / pedoman penilaian di akhir
            if (preg_match('/Kunci\s*Jawaban|Pedoman\s*Penilaian|Rubrik/i', $firstLine)) {
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

            // Hapus header section dan petunjuk umum jika ada
            $body = preg_replace('/^#{1,3}[^\n]+\n(?:Petunjuk[^\n]+\n)?/i', '', $sec);
            $parsedSections[] = [
                'type' => $type,
                'content' => trim($body),
            ];
        }
    } else {
        // Tanpa section eksplisit, bersihkan judul dokumen di awal sebelum soal No 1
        if (preg_match('/(?:^|\n)\s*1[\.\)]\s+/i', $text, $m, PREG_OFFSET_CAPTURE)) {
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
        // Pisahkan per baris untuk mendeteksi nomor soal utama vs nomor daftar dalam soal
        $lines = explode("\n", $secContent);
        $questionBlocks = [];
        $currentBlock = [];
        $expectedNumber = 1;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Cek apakah baris diawali nomor soal yang diharapkan (misal 1., 2., 3...)
            // Atau format "Soal 1", "Soal No. 1"
            if (preg_match('/^(?:Soal\s*(?:No\.?)?\s*)?(\d+)[\.\)]\s+(.*)$/i', $trimmed, $nMatch)) {
                $num = (int) $nMatch[1];
                // Jika nomor sesuai urutan berikutnya (atau nomor 1 di awal section)
                if ($num === $expectedNumber || ($expectedNumber === 1 && $num >= 1 && $num <= 2)) {
                    if (!empty($currentBlock)) {
                        $questionBlocks[] = implode("\n", $currentBlock);
                        $currentBlock = [];
                    }
                    $expectedNumber = $num + 1;
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

            if (!preg_match('/^(?:Soal\s*(?:No\.?)?\s*)?(\d+)[\.\)]\s+(.*)$/s', $block, $numMatch)) {
                continue;
            }

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

                    // Opsi A, B, C, D, E
                    if (preg_match('/^(?:[-*•]\s*)?(?:\(?([A-Ea-e])[\.\)]|\b([A-Ea-e])\.\s+)\s*(.*)$/', $trimmed, $optMatch)) {
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
                'bobot_nilai' => $defaultBobot,
                'pertanyaan' => $pertanyaanText,
                'opsi_jawabans' => $opsiJawabans,
                'kunci_isian' => ($tipe === 'isian') ? ($kunci ?? '') : '',
                'rubrik_essay' => ($tipe === 'essay') ? ($pembahasan ?: 'Pedoman penilaian essay...') : '',
                'pembahasan' => $pembahasan,
            ];
        }
    }

    return $allSoals;
}

$results = parseExamText($rawText);
echo "TOTAL HASIL PARSE: " . count($results) . PHP_EOL;
$pg = 0; $essay = 0;
foreach ($results as $i => $r) {
    if ($r['tipe_soal'] === 'pilihan_ganda') $pg++;
    if ($r['tipe_soal'] === 'essay') $essay++;
    echo ($i + 1) . ". [" . $r['tipe_soal'] . "] " . strip_tags(substr($r['pertanyaan'], 0, 60)) . " (Opsi: " . count($r['opsi_jawabans']) . ")" . PHP_EOL;
}
echo "Summary: PG = $pg, Essay = $essay" . PHP_EOL;
