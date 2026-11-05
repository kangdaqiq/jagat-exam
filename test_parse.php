<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$text = <<<'TXT'
# SOAL PENDIDIKAN AGAMA ISLAM DAN BUDI PEKERTI

Kelas: X SMK\
Semester: 1 (Ganjil)\
Materi: Bab 1–3\
Jumlah soal: 20 Pilihan Ganda dan 5 Essay

Materi disusun berdasarkan materi umum PAI dan Budi Pekerti kelas X Kurikulum Merdeka:

1. Bab 1 – Meraih Kesuksesan dengan Kompetisi dalam Kebaikan dan Etos Kerja.
2. Bab 2 – Memahami Hakikat dan Mewujudkan Ketauhidan dengan Syu'abul Iman (Cabang-Cabang Iman).
3. Bab 3 – Menjalani Hidup Penuh Manfaat dengan Menghindari Berfoya-foya, Riya', Sum'ah, Takabur, dan Hasad.

## A. Pilihan Ganda

Petunjuk: Pilihlah satu jawaban yang paling tepat dengan memberi tanda silang (X) pada huruf A, B, C, D, atau E!

1\. Dalam QS. Al-Maidah ayat 48, Allah Swt. memerintahkan manusia untuk berlomba-lomba dalam kebaikan. Istilah yang digunakan untuk menggambarkan perilaku tersebut adalah ....

A. Tawakal\
B. Fastabiqul khairat\
C. Takabur\
D. Tasamuh\
E. Uzlah

2\. Seorang siswa melihat temannya kesulitan memahami pelajaran. Ia kemudian menawarkan bantuan tanpa mengharapkan imbalan. Perilaku tersebut merupakan contoh ....

A. Persaingan tidak sehat\
B. Kompetisi dalam kebaikan\
C. Sikap ingin dipuji\
D. Perbuatan riya'\
E. Sikap individualis

3\. Berikut ini yang paling tepat menggambarkan etos kerja seorang muslim adalah ....

A. Bekerja hanya ketika mendapatkan pengawasan\
B. Mengutamakan hasil tanpa memperhatikan proses\
C. Bekerja keras, disiplin, jujur, dan bertanggung jawab\
D. Menghindari pekerjaan yang sulit\
E. Mengandalkan bantuan orang lain

4\. Seorang siswa SMK mendapatkan tugas praktik otomotif. Ia tetap berusaha menyelesaikannya meskipun mengalami beberapa kegagalan. Sikap tersebut mencerminkan ....

A. Putus asa\
B. Malas\
C. Etos kerja dan pantang menyerah\
D. Takabur\
E. Hasad

5\. Tujuan utama berlomba-lomba dalam kebaikan adalah ....

A. Mendapatkan rida Allah Swt.\
B. Mengalahkan semua orang\
C. Memperoleh popularitas\
D. Menunjukkan kelebihan diri\
E. Mendapatkan pujian dari manusia

20. Perhatikan pernyataan berikut!

1. Menggunakan uang sesuai kebutuhan.
2. Selalu memamerkan kekayaan.
3. Bersyukur atas rezeki yang diberikan Allah.
4. Merendahkan orang yang kurang mampu.
5. Membantu orang yang membutuhkan.

Perilaku yang mencerminkan hidup sederhana dan terhindar dari sifat tercela ditunjukkan oleh nomor ....

A. 1, 2, dan 3\
B. 1, 3, dan 5\
C. 2, 3, dan 4\
D. 2, 4, dan 5\
E. 1, 4, dan 5

## B. Essay

Petunjuk: Jawablah pertanyaan berikut dengan jelas, tepat, dan menggunakan bahasa sendiri!

1\. Jelaskan pengertian fastabiqul khairat dan berikan tiga contoh penerapannya dalam kehidupan sehari-hari di lingkungan sekolah!

2\. Jelaskan pengertian etos kerja dalam Islam. Mengapa seorang muslim harus memiliki sikap disiplin, jujur, dan bertanggung jawab dalam bekerja?

3\. Jelaskan pengertian syu'abul iman (cabang-cabang iman) serta sebutkan tiga contoh cabang iman yang berkaitan dengan hati, lisan, dan perbuatan!

4\. Jelaskan perbedaan antara riya' dan sum'ah. Berikan masing-masing satu contoh perilaku dalam kehidupan sehari-hari!

5\. Seorang siswa memiliki kebiasaan memamerkan barang mahal, merendahkan teman yang kurang mampu, dan merasa iri ketika temannya mendapatkan prestasi.

&#x20;  a. Identifikasilah tiga sifat tercela yang terdapat dalam kasus tersebut!

&#x20;  b. Jelaskan dampak negatif dari sifat-sifat tersebut!

&#x20;  c. Bagaimana cara menghindari sifat-sifat tercela tersebut menurut ajaran Islam?
TXT;

$service = app(\App\Services\AiQuestionService::class);
echo "Default Provider: " . config('ai.provider') . PHP_EOL;
echo "Gemini Key: " . substr(config('ai.gemini.api_key'), 0, 10) . "..." . PHP_EOL;

$apiKey = config('ai.gemini.api_key');
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('callGemini');
$method->setAccessible(true);

$sys = $reflection->getMethod('buildParseSystemPrompt');
$sys->setAccessible(true);
$systemPrompt = $sys->invoke($service);

$usr = $reflection->getMethod('buildParseUserPrompt');
$usr->setAccessible(true);
$userPrompt = $usr->invoke($service, $text, 2.0, []);

try {
    $res = $method->invoke($service, $apiKey, $systemPrompt, $userPrompt, ['model' => 'gemini-3.5-flash']);
    echo "Gemini Success! Count: " . count($res) . PHP_EOL;
} catch (Throwable $e) {
    echo "Gemini FAILED: " . $e->getMessage() . PHP_EOL;
}
