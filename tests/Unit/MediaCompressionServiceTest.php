<?php

namespace Tests\Unit;

use App\Services\MediaCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaCompressionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_detect_media_type(): void
    {
        $service = app(MediaCompressionService::class);

        // Buat file dummy
        $tmpImg = tempnam(sys_get_temp_dir(), 'test_img') . '.jpg';
        $im = imagecreatetruecolor(10, 10);
        imagejpeg($im, $tmpImg);
        imagedestroy($im);

        $this->assertEquals('image', $service->detectMediaType($tmpImg));
        @unlink($tmpImg);

        $tmpVid = tempnam(sys_get_temp_dir(), 'test_vid') . '.mp4';
        file_put_contents($tmpVid, 'dummy video header');
        $this->assertEquals('video', $service->detectMediaType($tmpVid));
        @unlink($tmpVid);

        $tmpAud = tempnam(sys_get_temp_dir(), 'test_aud') . '.mp3';
        file_put_contents($tmpAud, 'dummy audio header');
        $this->assertEquals('audio', $service->detectMediaType($tmpAud));
        @unlink($tmpAud);
    }

    public function test_compress_oversized_image_to_webp(): void
    {
        $service = app(MediaCompressionService::class);

        // Buat gambar besar 2000 x 1500 px
        $im = imagecreatetruecolor(2000, 1500);
        $red = imagecolorallocate($im, 230, 50, 50);
        imagefill($im, 0, 0, $red);

        $srcPath = tempnam(sys_get_temp_dir(), 'test_src') . '.jpg';
        imagejpeg($im, $srcPath, 100);
        imagedestroy($im);

        $targetPath = tempnam(sys_get_temp_dir(), 'test_target') . '.webp';

        $result = $service->compressImage($srcPath, $targetPath, [
            'max_width' => 1280,
            'max_height' => 1280,
            'quality' => 80,
            'format' => 'webp',
        ]);

        $this->assertTrue($result['success']);
        $this->assertFileExists($targetPath);
        $this->assertLessThanOrEqual(1280, $result['width']);
        $this->assertLessThanOrEqual(1280, $result['height']);
        $this->assertLessThan($result['original_size'], $result['compressed_size']);
        $this->assertGreaterThan(0.0, $result['saved_percent']);

        @unlink($srcPath);
        @unlink($targetPath);
    }

    public function test_compress_and_store_image(): void
    {
        $service = app(MediaCompressionService::class);

        // Buat uploaded file gambar
        $srcPath = tempnam(sys_get_temp_dir(), 'test_up') . '.jpg';
        $im = imagecreatetruecolor(800, 600);
        imagejpeg($im, $srcPath, 90);
        imagedestroy($im);

        $uploaded = new UploadedFile($srcPath, 'test.jpg', 'image/jpeg', null, true);

        $relativePath = $service->compressAndStore($uploaded, 'soal-media', 'image');

        $this->assertStringStartsWith('soal-media/', $relativePath);
        $this->assertStringEndsWith('.webp', $relativePath);
        Storage::disk('public')->assertExists($relativePath);

        @unlink($srcPath);
    }

    public function test_is_ffmpeg_available_returns_boolean(): void
    {
        $service = app(MediaCompressionService::class);
        $this->assertIsBool($service->isFfmpegAvailable());
    }
}

