<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaCompressionService
{
    /**
     * Kompresi dan simpan file media yang diunggah ke storage public.
     *
     * @param UploadedFile|TemporaryUploadedFile|string $file
     * @param string $directory Subdirektori penyimpanan di disk public (default: 'soal-media')
     * @param string|null $explicitType 'image' | 'video' | 'audio' | null (auto-detect)
     * @return string Relative path file di disk public (misal: 'soal-media/abc123.webp')
     */
    public function compressAndStore(mixed $file, string $directory = 'soal-media', ?string $explicitType = null): string
    {
        $realPath = $this->resolveFilePath($file);
        if (! file_exists($realPath)) {
            throw new Exception("File sumber tidak ditemukan di path: {$realPath}");
        }

        $detectedType = $explicitType ?: $this->detectMediaType($realPath);
        $randomName = Str::random(32);
        $disk = Storage::disk('public');
        $targetDir = $disk->path($directory);

        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        if ($detectedType === 'image') {
            $format = config('media.image.format', 'webp');
            $extension = ($format === 'webp') ? 'webp' : 'jpg';
            $targetRelativePath = "{$directory}/{$randomName}.{$extension}";
            $targetAbsolutePath = $disk->path($targetRelativePath);

            $this->compressImage($realPath, $targetAbsolutePath, [
                'max_width' => config('media.image.max_width', 1280),
                'max_height' => config('media.image.max_height', 1280),
                'quality' => config('media.image.quality', 80),
                'format' => $format,
            ]);

            return $targetRelativePath;
        }

        if ($detectedType === 'video') {
            $targetRelativePath = "{$directory}/{$randomName}.mp4";
            $targetAbsolutePath = $disk->path($targetRelativePath);

            $result = $this->compressVideo($realPath, $targetAbsolutePath, [
                'max_width' => config('media.video.max_width', 720),
                'crf' => config('media.video.crf', 28),
                'preset' => config('media.video.preset', 'faster'),
                'audio_bitrate' => config('media.video.audio_bitrate', '96k'),
            ]);

            // Jika kompresi video gagal atau ffmpeg tidak ada, salin file asli
            if (! $result['success'] || ! file_exists($targetAbsolutePath)) {
                $ext = pathinfo($realPath, PATHINFO_EXTENSION) ?: 'mp4';
                $targetRelativePath = "{$directory}/{$randomName}.{$ext}";
                $targetAbsolutePath = $disk->path($targetRelativePath);
                File::copy($realPath, $targetAbsolutePath);
            }

            return $targetRelativePath;
        }

        // Tipe audio atau lainnya: simpan langsung
        $origExtension = pathinfo($realPath, PATHINFO_EXTENSION) ?: 'dat';
        if ($file instanceof UploadedFile || $file instanceof TemporaryUploadedFile) {
            $origExtension = $file->getClientOriginalExtension() ?: $origExtension;
        }
        $targetRelativePath = "{$directory}/{$randomName}.{$origExtension}";
        $targetAbsolutePath = $disk->path($targetRelativePath);
        File::copy($realPath, $targetAbsolutePath);

        return $targetRelativePath;
    }

    /**
     * Kompresi file gambar menggunakan ekstensi PHP GD bawaan:
     * - Downscale jika dimensi > batas maksimal (default 1280px)
     * - Menjaga rasio aspek (aspect ratio)
     * - Otomatis koreksi orientasi EXIF (foto smartphone portrait/landscape)
     * - Menjaga transparansi PNG / WebP
     * - Konversi & simpan ke format WebP terkompresi (atau JPEG)
     *
     * @param string $sourcePath Path file gambar asli
     * @param string $targetPath Path file gambar hasil kompresi
     * @param array $options
     * @return array Ringkasan hasil kompresi (ukuran lama, baru, persentase penghematan)
     */
    public function compressImage(string $sourcePath, string $targetPath, array $options = []): array
    {
        $maxWidth = (int) ($options['max_width'] ?? 1280);
        $maxHeight = (int) ($options['max_height'] ?? 1280);
        $quality = (int) ($options['quality'] ?? 80);
        $format = strtolower($options['format'] ?? 'webp');

        $origSize = file_exists($sourcePath) ? filesize($sourcePath) : 0;
        $imageInfo = @getimagesize($sourcePath);

        if (! $imageInfo) {
            // Jika bukan format gambar standar GD, salin langsung
            File::copy($sourcePath, $targetPath);
            return [
                'success' => false,
                'original_size' => $origSize,
                'compressed_size' => filesize($targetPath),
                'saved_percent' => 0.0,
            ];
        }

        [$origW, $origH, $imageType] = $imageInfo;

        // Buka gambar sumber
        $src = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            IMAGETYPE_GIF => @imagecreatefromgif($sourcePath),
            default => false,
        };

        if (! $src) {
            File::copy($sourcePath, $targetPath);
            return [
                'success' => false,
                'original_size' => $origSize,
                'compressed_size' => filesize($targetPath),
                'saved_percent' => 0.0,
            ];
        }

        // Koreksi rotasi orientasi EXIF (biasa terjadi pada kamera HP)
        if ($imageType === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($sourcePath);
            $orientation = $exif['Orientation'] ?? 1;
            $src = match ($orientation) {
                3 => imagerotate($src, 180, 0),
                6 => imagerotate($src, -90, 0),
                8 => imagerotate($src, 90, 0),
                default => $src,
            };
            $origW = imagesx($src);
            $origH = imagesy($src);
        }

        // Hitung dimensi target proporsional (hanya downscale, tidak pernah upscale gambar kecil)
        $ratio = min($maxWidth / max(1, $origW), $maxHeight / max(1, $origH), 1.0);
        $targetW = (int) max(1, round($origW * $ratio));
        $targetH = (int) max(1, round($origH * $ratio));

        $dst = imagecreatetruecolor($targetW, $targetH);

        // Menjaga transparansi jika gambar asli PNG / WebP / GIF
        if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF])) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
        }

        // Resample kualitas tinggi
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

        // Simpan ke format target
        $targetDir = dirname($targetPath);
        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        if ($format === 'webp' && function_exists('imagewebp')) {
            imagewebp($dst, $targetPath, $quality);
        } else {
            imagejpeg($dst, $targetPath, $quality);
        }

        imagedestroy($src);
        imagedestroy($dst);

        $newSize = file_exists($targetPath) ? filesize($targetPath) : 0;
        $savedBytes = max(0, $origSize - $newSize);
        $savedPercent = $origSize > 0 ? round(($savedBytes / $origSize) * 100, 1) : 0.0;

        return [
            'success' => true,
            'original_size' => $origSize,
            'compressed_size' => $newSize,
            'saved_bytes' => $savedBytes,
            'saved_percent' => $savedPercent,
            'width' => $targetW,
            'height' => $targetH,
        ];
    }

    /**
     * Kompresi file video menggunakan FFmpeg:
     * - Rescale resolusi ke maksimal 720p (scale='min(720,iw):-2')
     * - H.264 video codec dengan CRF 28 (kompresi tinggi, kualitas visual jernih)
     * - AAC audio codec 96 kbps
     * - Flag '+faststart' (memindahkan header moov atom ke awal file sehingga video dapat langsung diputar siswa tanpa menunggu download selesai)
     *
     * @param string $sourcePath Path video asli
     * @param string $targetPath Path video hasil kompresi (.mp4)
     * @param array $options
     * @return array Status dan informasi kompresi
     */
    public function compressVideo(string $sourcePath, string $targetPath, array $options = []): array
    {
        $origSize = file_exists($sourcePath) ? filesize($sourcePath) : 0;

        if (! $this->isFfmpegAvailable()) {
            Log::info("Kompresi video dilewati karena FFmpeg tidak tersedia di server. Menggunakan video asli.");
            return [
                'success' => false,
                'reason' => 'FFmpeg tidak ditemukan di server.',
                'original_size' => $origSize,
                'compressed_size' => $origSize,
                'saved_percent' => 0.0,
            ];
        }

        $ffmpegBin = $this->getFfmpegBinary();
        $maxWidth = (int) ($options['max_width'] ?? 720);
        $crf = (int) ($options['crf'] ?? 28);
        $preset = $options['preset'] ?? 'faster';
        $audioBitrate = $options['audio_bitrate'] ?? '96k';

        $targetDir = dirname($targetPath);
        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        // Temp path untuk proses encode
        $tempOutput = $targetPath . '.temp.mp4';

        $command = [
            $ffmpegBin,
            '-y',
            '-i', $sourcePath,
            '-vcodec', 'libx264',
            '-crf', (string) $crf,
            '-preset', $preset,
            '-vf', "scale=min({$maxWidth}\,iw):-2",
            '-acodec', 'aac',
            '-b:a', $audioBitrate,
            '-movflags', '+faststart',
            $tempOutput,
        ];

        try {
            $process = Process::timeout(300)->run($command);

            if ($process->successful() && file_exists($tempOutput) && filesize($tempOutput) > 0) {
                if (file_exists($targetPath)) {
                    @unlink($targetPath);
                }
                rename($tempOutput, $targetPath);

                $newSize = filesize($targetPath);
                $savedBytes = max(0, $origSize - $newSize);
                $savedPercent = $origSize > 0 ? round(($savedBytes / $origSize) * 100, 1) : 0.0;

                return [
                    'success' => true,
                    'original_size' => $origSize,
                    'compressed_size' => $newSize,
                    'saved_bytes' => $savedBytes,
                    'saved_percent' => $savedPercent,
                ];
            }

            if (file_exists($tempOutput)) {
                @unlink($tempOutput);
            }

            Log::warning("FFmpeg video compression failed: " . $process->errorOutput());
        } catch (Exception $e) {
            if (file_exists($tempOutput)) {
                @unlink($tempOutput);
            }
            Log::warning("Exception saat kompresi video: " . $e->getMessage());
        }

        return [
            'success' => false,
            'reason' => 'Proses transcode FFmpeg gagal.',
            'original_size' => $origSize,
            'compressed_size' => $origSize,
            'saved_percent' => 0.0,
        ];
    }

    protected static ?bool $ffmpegAvailableCache = null;

    /**
     * Periksa apakah binary executable FFmpeg tersedia di server.
     */
    public function isFfmpegAvailable(): bool
    {
        if (self::$ffmpegAvailableCache !== null) {
            return self::$ffmpegAvailableCache;
        }

        $bin = $this->getFfmpegBinary();
        if (empty($bin)) {
            return self::$ffmpegAvailableCache = false;
        }

        try {
            $process = Process::timeout(2)->run([$bin, '-version']);
            return self::$ffmpegAvailableCache = $process->successful();
        } catch (\Throwable) {
            return self::$ffmpegAvailableCache = false;
        }
    }

    /**
     * Dapatkan path binary executable FFmpeg.
     */
    public function getFfmpegBinary(): string
    {
        $configured = config('media.ffmpeg_path', env('FFMPEG_PATH', 'ffmpeg'));

        // Jika path langsung ditemukan
        if (file_exists($configured)) {
            return $configured;
        }

        // Cek path umum di sistem Windows jika 'ffmpeg' biasa
        if (PHP_OS_FAMILY === 'Windows') {
            $commonWindowsPaths = [
                'C:\\ffmpeg\\bin\\ffmpeg.exe',
                'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
                'C:\\laragon\\bin\\ffmpeg\\bin\\ffmpeg.exe',
            ];
            foreach ($commonWindowsPaths as $p) {
                if (file_exists($p)) {
                    return $p;
                }
            }
        }

        return $configured ?: 'ffmpeg';
    }

    /**
     * Deteksi tipe media ('image' | 'video' | 'audio' | 'unknown') dari file.
     */
    public function detectMediaType(string $filePath): string
    {
        if (! file_exists($filePath)) {
            return 'unknown';
        }

        $mime = @mime_content_type($filePath) ?: '';

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'])) {
            return 'image';
        }
        if (in_array($extension, ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'])) {
            return 'video';
        }
        if (in_array($extension, ['mp3', 'm4a', 'wav', 'ogg', 'aac', 'flac'])) {
            return 'audio';
        }

        return 'unknown';
    }

    /**
     * Ekstrak path lokal absolut dari berbagai tipe input file upload.
     */
    protected function resolveFilePath(mixed $file): string
    {
        if (is_string($file)) {
            return $file;
        }

        if ($file instanceof UploadedFile || $file instanceof TemporaryUploadedFile) {
            return $file->getRealPath() ?: $file->path();
        }

        if (is_object($file) && method_exists($file, 'getRealPath')) {
            return (string) $file->getRealPath();
        }

        if (is_object($file) && method_exists($file, 'path')) {
            return (string) $file->path();
        }

        return (string) $file;
    }
}
