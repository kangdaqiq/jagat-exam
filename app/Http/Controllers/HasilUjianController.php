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
     * Cetak Daftar Hadir Peserta Ujian per Ruangan (A4) - Sebelum Ujian Dimulai
     */
    public function cetakDaftarHadir(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isGuru())) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $ruangId = $request->input('ruang_ujian_id');
        $ruang = $ruangId ? \App\Models\RuangUjian::with('pengawas')->find($ruangId) : null;

        $ujianId = $request->input('ujian_id');
        $ujian = $ujianId ? \App\Models\Ujian::with(['mapel', 'guru'])->find($ujianId) : null;

        $jadwalId = $request->input('jadwal_ruang_id');
        $jadwalRuang = $jadwalId ? \App\Models\JadwalRuang::with(['ruangUjian', 'ujian', 'pengawas'])->find($jadwalId) : null;

        if ($jadwalRuang) {
            $ruang = $ruang ?: $jadwalRuang->ruangUjian;
            $ujian = $ujian ?: $jadwalRuang->ujian;
        }

        // Ambil siswa di ruangan tersebut (atau seluruh siswa jika ruangan belum difilter)
        $query = \App\Models\Siswa::query()
            ->with(['kelas', 'user', 'ruangUjian'])
            ->where('is_active', true);

        if ($ruang) {
            $query->where('ruang_ujian_id', $ruang->id);
        }

        $siswas = $query
            ->orderBy('kelas_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        $tanggalObj = $jadwalRuang?->tanggal ?: ($ujian?->tanggal ?: now());
        $hariIndonesia = match($tanggalObj->dayOfWeek) {
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Senin',
        };

        $waktuMulai = $jadwalRuang?->waktu_mulai ? substr($jadwalRuang->waktu_mulai, 0, 5) : ($ujian?->waktu_mulai ? substr($ujian->waktu_mulai, 0, 5) : '07:30');
        $waktuSelesai = $jadwalRuang?->waktu_selesai ? substr($jadwalRuang->waktu_selesai, 0, 5) : ($ujian?->waktu_selesai ? substr($ujian->waktu_selesai, 0, 5) : '09:30');

        $meta = [
            'kota' => $request->input('kota', 'KAB. TULANG BAWANG BARAT'),
            'kode_kota' => $request->input('kode_kota', '13'),
            'sekolah' => $request->input('sekolah', 'SMKS ASSUNIYAH'),
            'kode_sekolah' => $request->input('kode_sekolah', '0106'),
            'id_server' => $request->input('id_server', ($ruang?->kode_ruang ? 'K12130106-' . strtoupper($ruang->kode_ruang) : 'K12130106-AJ9K')),
            'nama_ruang' => $request->input('nama_ruang', $ruang?->nama_ruang ?: 'RUANG 1'),
            'sesi' => $request->input('sesi', (string) ($jadwalRuang?->sesi_ke ?: '1')),
            'hari' => $request->input('hari', $hariIndonesia),
            'tanggal' => $request->input('tanggal', $tanggalObj->translatedFormat('d F Y')),
            'pukul' => $request->input('pukul', "{$waktuMulai}-{$waktuSelesai}"),
            'nama_ujian' => $request->input('nama_ujian', $ujian?->nama_ujian ?: 'TES KEMAMPUAN AKADEMIK SMK'),
            'tahun_pelajaran' => $request->input('tahun_pelajaran', '2026/2027'),
            'nama_proktor' => $request->input('proktor', $user->name),
            'nama_pengawas' => $request->input('pengawas', $jadwalRuang?->pengawas?->name ?: ($ruang?->pengawas?->name ?: 'ATHIQ NUR AZIZAH')),
        ];

        $daftarJadwal = $ruang
            ? $ruang->jadwalRuangs()->with(['ujian.mapel', 'pengawas'])->orderBy('tanggal')->orderBy('waktu_mulai')->get()
            : collect();

        return view('print.daftar-hadir-ruang', [
            'ruang' => $ruang,
            'ujian' => $ujian,
            'jadwalRuang' => $jadwalRuang,
            'siswas' => $siswas,
            'meta' => $meta,
            'daftarRuang' => \App\Models\RuangUjian::where('is_active', true)->orderBy('nama_ruang')->get(),
            'daftarJadwal' => $daftarJadwal,
        ]);
    }

    /**
     * Cetak Berita Acara Pelaksanaan (BAP) Ujian per Ruangan (A4) - Setelah Ujian Selesai
     */
    public function cetakBeritaAcara(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isGuru())) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $bapId = $request->input('id');
        $bap = $bapId ? \App\Models\BeritaAcara::find($bapId) : null;

        $ruangId = $request->input('ruang_ujian_id', $bap?->ruang_ujian_id);
        $ruang = $ruangId ? \App\Models\RuangUjian::with('pengawas')->find($ruangId) : null;

        $ujianId = $request->input('ujian_id', $bap?->ujian_id);
        $ujian = $ujianId ? \App\Models\Ujian::with(['mapel', 'guru'])->find($ujianId) : null;

        $jadwalId = $request->input('jadwal_ruang_id', $bap?->jadwal_ruang_id);
        $jadwalRuang = $jadwalId ? \App\Models\JadwalRuang::with(['ruangUjian', 'ujian', 'pengawas'])->find($jadwalId) : null;

        if ($jadwalRuang) {
            $ruang = $ruang ?: $jadwalRuang->ruangUjian;
            $ujian = $ujian ?: $jadwalRuang->ujian;
        }

        // Ambil siswa untuk ruang ini
        $query = \App\Models\Siswa::query()
            ->with(['kelas', 'user', 'ruangUjian'])
            ->where('is_active', true);
        if ($ruang) {
            $query->where('ruang_ujian_id', $ruang->id);
        }
        $siswas = $query->get();
        $totalSiswaRuang = $siswas->count();

        $tanggalObj = $bap?->tanggal ?: ($jadwalRuang?->tanggal ?: ($ujian?->tanggal ?: now()));
        $hariIndonesia = $bap?->hari ?: match($tanggalObj->dayOfWeek) {
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => 'Senin',
        };

        $waktuMulai = $bap?->waktu_mulai ?: ($jadwalRuang?->waktu_mulai ? substr($jadwalRuang->waktu_mulai, 0, 5) : ($ujian?->waktu_mulai ? substr($ujian->waktu_mulai, 0, 5) : '07:30'));
        $waktuSelesai = $bap?->waktu_selesai ?: ($jadwalRuang?->waktu_selesai ? substr($jadwalRuang->waktu_selesai, 0, 5) : ($ujian?->waktu_selesai ? substr($ujian->waktu_selesai, 0, 5) : '09:30'));

        $seharusnya = (int) $request->input('jumlah_peserta_seharusnya', $bap?->jumlah_peserta_seharusnya ?? $totalSiswaRuang);
        $tidakHadir = (int) $request->input('jumlah_tidak_hadir', $bap?->jumlah_tidak_hadir ?? 0);
        $hadir = (int) $request->input('jumlah_hadir', $bap?->jumlah_hadir ?? max(0, $seharusnya - $tidakHadir));

        $rawUsernamesTidakHadir = $request->input('usernames_tidak_hadir', $bap?->usernames_tidak_hadir);
        if (is_array($rawUsernamesTidakHadir)) {
            $usernamesStr = implode(', ', $rawUsernamesTidakHadir);
        } elseif (is_string($rawUsernamesTidakHadir)) {
            $usernamesStr = $rawUsernamesTidakHadir;
        } else {
            $usernamesStr = '-';
        }

        $meta = [
            'hari' => $request->input('hari', $hariIndonesia),
            'tanggal' => $tanggalObj,
            'bulan' => $tanggalObj->translatedFormat('F'),
            'tahun' => $tanggalObj->format('Y'),
            'tahun_pelajaran' => $request->input('tahun_pelajaran', $bap?->tahun_pelajaran ?: '2026'),
            'nama_ujian' => $request->input('nama_ujian', $bap?->nama_ujian ?: ($ujian?->nama_ujian ?: 'TES KEMAMPUAN AKADEMIK SMK')),
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'username_sekolah' => $request->input('username_sekolah', $bap?->username_sekolah ?: 'K12130106'),
            'nama_sekolah' => $request->input('nama_sekolah', $bap?->nama_sekolah ?: 'SMKS ASSUNIYAH'),
            'id_server' => $request->input('id_server', $bap?->id_server ?: ($ruang?->kode_ruang ? 'K12130106-' . strtoupper($ruang->kode_ruang) : 'K12130106-AJ9K')),
            'nama_ruang' => $request->input('nama_ruang', $bap?->ruang ?: ($ruang?->nama_ruang ?: 'RUANG 1')),
            'sesi' => $request->input('sesi', $bap?->sesi ?: (string) ($jadwalRuang?->sesi_ke ?: '1')),
            'jumlah_seharusnya' => $seharusnya,
            'jumlah_hadir' => $hadir,
            'jumlah_tidak_hadir' => $tidakHadir,
            'username_tidak_hadir' => $usernamesStr,
            'catatan' => $request->input('catatan', $bap?->catatan ?: 'Pelaksanaan ujian berlangsung tertib, aman, dan lancar tanpa kendala.'),
            'nama_pengawas' => $request->input('nama_pengawas', $bap?->nama_pengawas ?: ($jadwalRuang?->pengawas?->name ?: ($ruang?->pengawas?->name ?: 'ATHIQ NUR AZIZAH'))),
            'nip_pengawas' => $request->input('nip_pengawas', $bap?->nip_pengawas ?: ($jadwalRuang?->pengawas?->nip ?: ($ruang?->pengawas?->nip ?: '198705122010012004'))),
            'nama_ketua_ujian' => $request->input('nama_ketua_ujian', $bap?->nama_ketua_ujian ?: 'Ahmad Daqiqi Syahrulloh, S.H.'),
            'nip_ketua_ujian' => $request->input('nip_ketua_ujian', $bap?->nip_ketua_ujian ?: '198904152014021003'),
            'nama_kepala_sekolah' => $request->input('nama_kepala_sekolah', $bap?->nama_kepala_sekolah ?: 'Mahsun, M.Pd.'),
            'nip_kepala_sekolah' => $request->input('nip_kepala_sekolah', $bap?->nip_kepala_sekolah ?: '197508102005011008'),
        ];

        $daftarJadwal = $ruang
            ? $ruang->jadwalRuangs()->with(['ujian.mapel', 'pengawas'])->orderBy('tanggal')->orderBy('waktu_mulai')->get()
            : collect();

        return view('print.berita-acara-ruang', [
            'bap' => $bap,
            'ruang' => $ruang,
            'ujian' => $ujian,
            'jadwalRuang' => $jadwalRuang,
            'meta' => $meta,
            'siswas' => $siswas,
            'daftarRuang' => \App\Models\RuangUjian::where('is_active', true)->orderBy('nama_ruang')->get(),
            'daftarJadwal' => $daftarJadwal,
        ]);
    }

    /**
     * Cetak Berita Acara & Daftar Hadir Pelaksanaan Ujian CBT (A4) - Rekap per Ujian
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
            ->with(['kelas', 'user', 'ruangUjian'])
            ->where('is_active', true);

        if ($request->filled('ruang_ujian_id')) {
            $query->where('ruang_ujian_id', (int) $request->input('ruang_ujian_id'));
        }

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

        $urutanMode = (string) $request->input('urutan', 'selang_seling');

        $siswas = $query
            ->orderBy('ruang_ujian_id', 'asc')
            ->orderBy('kelas_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        // Susun urutan meja dalam setiap ruangan secara berselang-seling antar kelas
        $siswas = $this->arrangeSiswasForMejaUjian($siswas, $urutanMode);

        // Aksi cepat dari halaman Cetak Kartu: Generate 6 angka acak atau Reset ke Default NIS
        if (in_array($request->input('reset_mode'), ['generator', 'nis'], true)) {
            $mode = $request->input('reset_mode');
            foreach ($siswas as $s) {
                $newPass = $mode === 'generator'
                    ? \App\Models\Siswa::generateSixDigitPin()
                    : ($s->nis ?: \App\Models\Siswa::generateSixDigitPin());
                $s->resetPassword($newPass);
            }

            return redirect()->route('kartu-peserta.print', $request->except('reset_mode'));
        }

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
            'daftarRuang' => \App\Models\RuangUjian::orderBy('nama_ruang')->get(),
        ]);
    }

    /**
     * Susun urutan siswa dalam ruangan agar meja berselang-seling antar kelas
     */
    protected function arrangeSiswasForMejaUjian($siswas, string $mode = 'selang_seling')
    {
        if ($siswas->isEmpty()) {
            return $siswas;
        }

        // Kelompokkan siswa per ruangan
        $byRuang = $siswas->groupBy(fn ($s) => (int) ($s->ruang_ujian_id ?? 0));
        $ordered = collect();

        foreach ($byRuang as $ruangId => $roomSiswas) {
            $sortedRoomSiswas = $mode === 'selang_seling'
                ? $this->interleaveSiswasByKelas($roomSiswas)
                : $roomSiswas->values();

            // Tetapkan nomor meja berurutan mulai dari 01 per ruangan
            foreach ($sortedRoomSiswas as $idx => $s) {
                $s->nomor_meja = sprintf('%02d', $idx + 1);
                $ordered->push($s);
            }
        }

        return $ordered;
    }

    /**
     * Interleave siswa dalam 1 ruangan agar kelas berselang-seling (tidak berdekatan)
     */
    protected function interleaveSiswasByKelas($siswasInRoom)
    {
        if ($siswasInRoom->count() <= 2) {
            return $siswasInRoom->values();
        }

        // Kelompokkan siswa berdasarkan kelas
        $byKelas = [];
        foreach ($siswasInRoom as $s) {
            $kId = $s->kelas_id ?? 0;
            $byKelas[$kId][] = $s;
        }

        // Jika hanya ada 1 kelas di ruangan ini, kembalikan terurut nama
        if (count($byKelas) <= 1) {
            return $siswasInRoom->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        // Urutkan kelas dari yang siswanya terbanyak
        uasort($byKelas, fn ($a, $b) => count($b) <=> count($a));

        // Ambil kelas terbesar sebagai basis bucket
        $classes = array_values($byKelas);
        $largestClass = array_shift($classes);
        usort($largestClass, fn ($a, $b) => strcasecmp($a->nama, $b->nama));

        $bucketCount = count($largestClass);
        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = [$largestClass[$i]];
        }

        // Kumpulkan siswa dari kelas-kelas lainnya
        $otherStudents = [];
        foreach ($classes as $classMembers) {
            usort($classMembers, fn ($a, $b) => strcasecmp($a->nama, $b->nama));
            foreach ($classMembers as $m) {
                $otherStudents[] = $m;
            }
        }

        // Masukkan sisa siswa ke dalam bucket secara berputar (round-robin)
        $bucketIdx = 0;
        foreach ($otherStudents as $st) {
            $buckets[$bucketIdx][] = $st;
            $bucketIdx = ($bucketIdx + 1) % $bucketCount;
        }

        // Rangkai kembali seluruh bucket menjadi urutan meja berselang-seling
        $result = [];
        foreach ($buckets as $b) {
            foreach ($b as $item) {
                $result[] = $item;
            }
        }

        return collect($result);
    }
}

