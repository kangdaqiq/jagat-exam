<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Path FFmpeg untuk Kompresi Video
    |--------------------------------------------------------------------------
    | Lokasi binary executable ffmpeg di server. Jika berada di system PATH,
    | cukup gunakan 'ffmpeg'. Jika custom, isi path lengkapnya (misal: 'C:\ffmpeg\bin\ffmpeg.exe').
    */
    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Kompresi Gambar
    |--------------------------------------------------------------------------
    | Gambar stimulus soal dikompresi agar berukuran kecil (kB) namun tetap tajam.
    | Format WebP dipilih karena memberikan ukuran ~80% lebih kecil dibanding JPEG/PNG.
    */
    'image' => [
        'max_width' => (int) env('IMAGE_MAX_WIDTH', 1280),
        'max_height' => (int) env('IMAGE_MAX_HEIGHT', 1280),
        'quality' => (int) env('IMAGE_QUALITY', 80),
        'format' => env('IMAGE_FORMAT', 'webp'), // 'webp' | 'jpeg' | 'original'
    ],

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Kompresi Video
    |--------------------------------------------------------------------------
    | Video stimulus soal ditranscode ke H.264/AAC dengan flag faststart agar
    | siswa dapat langsung memutar video secara instan tanpa menunggu download tuntas.
    */
    'video' => [
        'max_width' => (int) env('VIDEO_MAX_WIDTH', 720),
        'crf' => (int) env('VIDEO_CRF', 28), // 24-28 memberikan efisiensi tinggi
        'preset' => env('VIDEO_PRESET', 'faster'),
        'audio_bitrate' => env('VIDEO_AUDIO_BITRATE', '96k'),
    ],
];

