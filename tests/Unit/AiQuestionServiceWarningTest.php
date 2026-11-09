<?php

namespace Tests\Unit;

use App\Services\AiQuestionService;
use Tests\TestCase;

class AiQuestionServiceWarningTest extends TestCase
{
    public function test_normalize_membersihkan_karakter_hasil_copy_word(): void
    {
        $svc = app(AiQuestionService::class);
        $out = $svc->normalizeRawText("1.\u{00A0}Soal \u{201C}kutip\u{201D}\u{200B}  \r\nA. satu   \n\n\n\nB. dua");

        $this->assertStringNotContainsString("\u{00A0}", $out);
        $this->assertStringNotContainsString("\u{200B}", $out);
        $this->assertStringContainsString('"kutip"', $out);
        $this->assertStringNotContainsString("\n\n\n", $out);
    }

    public function test_peringatan_untuk_kunci_otomatis(): void
    {
        $svc = app(AiQuestionService::class);
        $raw = "1. Soal tanpa kunci\nA. a\nB. b\nC. c\nD. d\n\n2. Soal dengan kunci\nA. a\nB. b\nC. c\nD. d\nKunci: B";
        $res = $svc->parseRawTextToSoals($raw);

        $this->assertNotEmpty($res[0]['peringatan']);
        $this->assertStringContainsString('Kunci jawaban tidak ditemukan', $res[0]['peringatan'][0]);
        $this->assertEmpty($res[1]['peringatan']);
    }

    public function test_parse_key_list_dan_failover_pool(): void
    {
        $raw = "AIzaKey1, AIzaKey2; AIzaKey3\nAIzaKey4  AIzaKey1";
        $keys = AiQuestionService::parseKeyList($raw);

        $this->assertCount(4, $keys);
        $this->assertEquals(['AIzaKey1', 'AIzaKey2', 'AIzaKey3', 'AIzaKey4'], $keys);

        config([
            'ai.gemini.api_key' => 'AIzaCentralMain',
            'ai.gemini.api_keys' => ['AIzaCentralBackup1', 'AIzaCentralBackup2'],
        ]);

        $svc = app(AiQuestionService::class);
        $pool = $svc->getGeminiApiKeys();

        $this->assertEquals(['AIzaCentralMain', 'AIzaCentralBackup1', 'AIzaCentralBackup2'], $pool);
    }

    public function test_infer_bloom_taxonomy_dan_bobot_kesulitan(): void
    {
        $lots = AiQuestionService::inferBloomTaxonomy('Sebutkan nama ibukota negara Indonesia!');
        $this->assertEquals('LOTS', $lots['tingkat_kesulitan']);
        $this->assertEquals('C1', $lots['taksonomi_bloom']);

        $mots = AiQuestionService::inferBloomTaxonomy('Berapakah nilai gaya jika massa benda 2 kg dan percepatan 5 m/s²? Tentukan hasilnya!');
        $this->assertEquals('MOTS', $mots['tingkat_kesulitan']);
        $this->assertEquals('C3', $mots['taksonomi_bloom']);

        $hots = AiQuestionService::inferBloomTaxonomy('Analisislah hubungan antara deforestasi dengan pemanasan global serta buatlah solusinya!');
        $this->assertEquals('HOTS', $hots['tingkat_kesulitan']);
        $this->assertContains($hots['taksonomi_bloom'], ['C4', 'C5']);

        // Uji perhitungan bobot flat
        $this->assertEquals(2.0, AiQuestionService::calculateBobotByKesulitan('LOTS', 2.0));
        $this->assertEquals(2.0, AiQuestionService::calculateBobotByKesulitan('MOTS', 2.0));
        $this->assertEquals(2.0, AiQuestionService::calculateBobotByKesulitan('HOTS', 2.0));
    }

    public function test_distribute_bobot_by_rasio_flat(): void
    {
        // Kasus 1: 3 soal -> 100 / 3 = 33.33, 33.33, 33.34
        $soals = [
            ['pertanyaan' => 'Soal 1', 'tingkat_kesulitan' => 'LOTS'],
            ['pertanyaan' => 'Soal 2', 'tingkat_kesulitan' => 'MOTS'],
            ['pertanyaan' => 'Soal 3', 'tingkat_kesulitan' => 'HOTS'],
        ];

        $distributed = AiQuestionService::distributeBobotByRasio($soals, 100.0);

        $this->assertEquals(33.33, $distributed[0]['bobot_nilai']);
        $this->assertEquals(33.33, $distributed[1]['bobot_nilai']);
        $this->assertEquals(33.34, $distributed[2]['bobot_nilai']);

        $totalScore = array_sum(array_column($distributed, 'bobot_nilai'));
        $this->assertEquals(100.0, $totalScore);

        // Kasus 2: 10 soal (3 LOTS, 5 MOTS, 2 HOTS) -> Semua sama rata 10.0 poin
        $mixedSoals = [
            ['pertanyaan' => 'L1', 'tingkat_kesulitan' => 'LOTS'],
            ['pertanyaan' => 'L2', 'tingkat_kesulitan' => 'LOTS'],
            ['pertanyaan' => 'L3', 'tingkat_kesulitan' => 'LOTS'],
            ['pertanyaan' => 'M1', 'tingkat_kesulitan' => 'MOTS'],
            ['pertanyaan' => 'M2', 'tingkat_kesulitan' => 'MOTS'],
            ['pertanyaan' => 'M3', 'tingkat_kesulitan' => 'MOTS'],
            ['pertanyaan' => 'M4', 'tingkat_kesulitan' => 'MOTS'],
            ['pertanyaan' => 'M5', 'tingkat_kesulitan' => 'MOTS'],
            ['pertanyaan' => 'H1', 'tingkat_kesulitan' => 'HOTS'],
            ['pertanyaan' => 'H2', 'tingkat_kesulitan' => 'HOTS'],
        ];

        $resMixed = AiQuestionService::distributeBobotByRasio($mixedSoals, 100.0);
        $totalMixed = array_sum(array_column($resMixed, 'bobot_nilai'));

        // Harus tepat 100.0
        $this->assertEquals(100.0, round($totalMixed, 2));

        // Semua butir soal bernilai sama rata 10.00
        $this->assertEquals(10.0, $resMixed[0]['bobot_nilai']);
        $this->assertEquals(10.0, $resMixed[3]['bobot_nilai']);
        $this->assertEquals(10.0, $resMixed[9]['bobot_nilai']);
    }
}
