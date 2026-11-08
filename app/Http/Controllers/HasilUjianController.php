<?php

namespace App\Http\Controllers;

use App\Models\HasilUjian;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HasilUjianController extends Controller
{
    /**
     * Download file CSV rekap nilai yang kompatibel langsung dengan Microsoft Excel.
     */
    public function exportCsv(HasilUjian $ujian)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isGuru())) {
            abort(403, 'Akses tidak diizinkan.');
        }

        // Hak akses: Guru hanya dapat mengakses ujiannya
        if ($user->isGuru() && $ujian->guru_id !== $user->id) {
            abort(403, 'Anda hanya dapat mengunduh rekap ujian yang Anda ampu.');
        }

        $pesertas = $ujian->pesertas()
            ->with(['siswa.kelas', 'user'])
            ->join('siswas', 'ujian_pesertas.siswa_id', '=', 'siswas.id')
            ->select('ujian_pesertas.*')
            ->orderBy('siswas.nama', 'asc')
            ->get();

        $examSlug = Str::slug($ujian->nama_ujian, '_');
        $filename = "rekap_nilai_{$examSlug}_" . now()->format('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($pesertas) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No',
                'NIS',
                'Nama Lengkap Siswa',
                'Kelas / Rombel',
                'Status Ujian',
                'Waktu Mulai',
                'Waktu Selesai',
                'Nilai Akhir',
            ]);

            foreach ($pesertas as $index => $p) {
                fputcsv($handle, [
                    $index + 1,
                    $p->siswa?->nis ?: '-',
                    $p->siswa?->nama ?: ($p->user?->name ?: '-'),
                    $p->siswa?->kelas?->nama_kelas ?: '-',
                    $p->status,
                    $p->waktu_mulai ? $p->waktu_mulai->format('Y-m-d H:i:s') : '-',
                    $p->waktu_selesai ? $p->waktu_selesai->format('Y-m-d H:i:s') : '-',
                    $p->nilai !== null ? (float) $p->nilai : 0,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Tampilan cetak resmi A4 yang siap di-print atau di-save as PDF.
     */
    public function print(HasilUjian $ujian)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isGuru())) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($user->isGuru() && $ujian->guru_id !== $user->id) {
            abort(403, 'Anda hanya dapat mencetak rekap ujian yang Anda ampu.');
        }

        $pesertas = $ujian->pesertas()
            ->with(['siswa.kelas', 'user'])
            ->join('siswas', 'ujian_pesertas.siswa_id', '=', 'siswas.id')
            ->select('ujian_pesertas.*')
            ->orderBy('siswas.nama', 'asc')
            ->get();

        return view('print.rekap-hasil-ujian', [
            'ujian' => $ujian,
            'pesertas' => $pesertas,
        ]);
    }

    /**
     * Cetak Berita Acara & Daftar Hadir Pelaksanaan Ujian CBT (A4).
     */
    public function beritaAcara(\App\Models\Ujian $ujian)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isGuru())) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $ujian->autoCloseExpiredSessions();
        $ujian->loadMissing(['mapel', 'guru', 'kelas', 'bankSoal']);

        $pesertas = $ujian->pesertas()
            ->with(['siswa.kelas', 'user', 'ujianSesi'])
            ->join('siswas', 'ujian_pesertas.siswa_id', '=', 'siswas.id')
            ->select('ujian_pesertas.*')
            ->orderBy('siswas.nama', 'asc')
            ->get();

        return view('print.berita-acara-ujian', [
            'ujian' => $ujian,
            'pesertas' => $pesertas,
        ]);
    }

    /**
     * Cetak Kartu Peserta Ujian CBT (per Kelas, Pilihan Siswa, atau Seluruh Siswa).
     */
    public function cetakKartuPeserta(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isGuru())) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $hasAssignedKelas = $user->isGuru() && $user->kelases()->exists();

        $query = \App\Models\Siswa::query()
            ->with(['kelas', 'user'])
            ->where('is_active', true);

        if ($hasAssignedKelas) {
            $query->forUser($user);
        }

        $selectedKelas = null;
        if ($request->filled('kelas_id')) {
            $kelasId = (int) $request->input('kelas_id');
            $query->where('kelas_id', $kelasId);
            $selectedKelas = \App\Models\Kelas::find($kelasId);
        }

        if ($request->filled('ids')) {
            $ids = array_filter(array_map('intval', explode(',', (string) $request->input('ids'))));
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        $siswas = $query
            ->orderBy('kelas_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        $daftarKelasQuery = \App\Models\Kelas::query();
        if ($hasAssignedKelas) {
            $daftarKelasQuery->forUser($user);
        }

        $daftarKelas = $daftarKelasQuery
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        return view('print.kartu-peserta', [
            'siswas' => $siswas,
            'selectedKelas' => $selectedKelas,
            'daftarKelas' => $daftarKelas,
        ]);
    }
}

