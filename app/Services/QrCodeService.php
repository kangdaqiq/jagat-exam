<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * In-memory cache agar rendering puluhan kartu dalam 1 halaman sangat cepat.
     */
    protected static array $cache = [];

    /**
     * Generate pure SVG Data URI string untuk teks payload.
     */
    public static function svg(string $data, int $eccLevel = EccLevel::M): string
    {
        $hash = md5($data . '-' . $eccLevel);
        if (isset(static::$cache[$hash])) {
            return static::$cache[$hash];
        }

        $options = new QROptions([
            'outputInterface' => QROutputInterface::MARKUP_SVG,
            'eccLevel' => $eccLevel,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
        ]);

        $result = (new QRCode($options))->render($data);
        static::$cache[$hash] = $result;

        return $result;
    }

    /**
     * Format teks e-sign resmi untuk Tanda Tangan Elektronik Kepala Sekolah.
     * Ketika di-scan HP / Google Lens / Scanner akan memunculkan teks metadata validasi resmi ini.
     */
    public static function getEsignPayload(string $namaKepsek = 'Mahsun, M.Pd.', ?string $namaSiswa = null, ?string $nis = null): string
    {
        $lines = [
            "TANDA TANGAN ELEKTRONIK RESMI",
            "SMK Assuniyah Tumijajar",
            "Penandatangan : {$namaKepsek} (Kepala Sekolah)",
            "Dokumen       : Kartu Peserta Ujian CBT",
        ];

        if ($namaSiswa) {
            $lines[] = "Siswa         : " . strtoupper($namaSiswa) . ($nis ? " ({$nis})" : "");
        }

        $lines[] = "Status        : Terverifikasi Sah & Terdaftar";

        return implode("\n", $lines);
    }
}
