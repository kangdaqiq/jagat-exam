# Panduan Integrasi Web Manajemen Soal dengan Android ExamGuard

Dokumen ini adalah panduan teknis bagi Anda saat membangun Web Manajemen Soal (Backend & Frontend) agar dapat berinteraksi secara aman dan mulus dengan aplikasi Android **ExamGuard CBT**.

---

## 1. Verifikasi Sisi Server (Backend)

Siswa yang cerdik mungkin mencoba menyalin link ujian dan membukanya di laptop atau browser Google Chrome di HP lain. Anda dapat memblokir akses tersebut di level backend server Anda (PHP, Laravel, Node.js, Python, dll).

### A. Memeriksa HTTP Header Rahasia
Aplikasi Android mengirimkan header khusus pada setiap permintaan halaman:
```http
X-Exam-App: verified-secure-cbt
```

**Contoh Validasi di PHP (Laravel / Native):**
```php
$examHeader = $_SERVER['HTTP_X_EXAM_APP'] ?? '';

if ($examHeader !== 'verified-secure-cbt') {
    // Tolak akses jika tidak dibuka dari aplikasi Android resmi
    http_response_code(403);
    die("<h1>Akses Ditolak</h1><p>Ujian ini hanya dapat dikerjakan melalui aplikasi resmi ExamGuard CBT di Android.</p>");
}
```

**Contoh Validasi di Node.js (Express):**
```javascript
app.use('/ujian', (req, res, next) => {
    const authHeader = req.headers['x-exam-app'];
    if (authHeader !== 'verified-secure-cbt') {
        return res.status(403).send("Ujian hanya boleh diakses melalui aplikasi resmi ExamGuard!");
    }
    next();
});
```

### B. Memeriksa User-Agent
Aplikasi Android menambahkan substring berikut pada User-Agent:
```text
ExamGuard/1.0 (Android; SecureEngine; CBT)
```

---

## 2. Integrasi Sisi Klien (Frontend Web Ujian)

Aplikasi Android menyuntikkan objek global JavaScript bernama `window.AndroidExam`.

### A. Menyelesaikan Ujian Secara Sah (Membuka Kunci Layar)
Ketika siswa menekan tombol "Kirim Jawaban" dan konfirmasi selesai:
```javascript
function selesaikanUjian() {
    // 1. Kirim data jawaban ke server Anda via fetch/ajax
    fetch('/api/submit-jawaban', {
        method: 'POST',
        body: JSON.stringify({ jawaban: dataJawaban })
    })
    .then(response => response.json())
    .then(data => {
        // 2. Beri tahu aplikasi Android untuk melepas mode Kiosk & selesai secara aman
        if (window.AndroidExam) {
            window.AndroidExam.finishExam();
        } else {
            window.location.href = "/halaman-terimakasih";
        }
    });
}
```

### B. Memicu Alarm dari Web jika Siswa Berbuat Curang
Jika Anda memiliki script JavaScript di web yang mendeteksi tab disembunyikan atau kehilangan fokus:
```javascript
document.addEventListener("visibilitychange", function() {
    if (document.hidden) {
        if (window.AndroidExam) {
            // Memicu sirine keras di HP siswa
            window.AndroidExam.triggerViolation("Siswa terdeteksi menyembunyikan halaman web soal!");
        }
    }
});
```

### C. Mengambil Informasi Baterai HP
Anda dapat menampilkan persentase baterai siswa di sudut layar web ujian Anda:
```javascript
if (window.AndroidExam) {
    const sisaBaterai = window.AndroidExam.getBatteryLevel(); // Integer (0 - 100)
    document.getElementById("bateraiDisplay").innerText = "Baterai: " + sisaBaterai + "%";
}
```

---

## 3. Menghasilkan QR Code untuk Ujian

Untuk memudahkan siswa masuk tanpa salah ketik link:
1. Buat QR Code yang berisi URL halaman ujian (misal: `https://cbt.sekolahanda.sch.id/sesi/matematika-01`).
2. Tampilkan di proyektor kelas atau cetak di kertas nomor meja.
3. Siswa cukup menekan tombol **"Pindai QR Code"** di aplikasi Android ExamGuard, dan ujian akan langsung dimulai secara otomatis!

