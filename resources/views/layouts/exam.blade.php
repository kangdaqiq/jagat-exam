<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Examuna - Digital Examination System | SMK Assuniyah Tumijajar' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/examuna-logo.png') }}">

    {{-- KaTeX & Font Amiri untuk Rumus Matematika dan Tulisan Arab --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;
            -webkit-tap-highlight-color: transparent;
        }

        .font-arabic, .arabic-text {
            font-family: 'Amiri', serif !important;
            font-size: 1.45rem !important;
            line-height: 2.3rem !important;
            direction: rtl;
            text-align: right;
        }

        /* KaTeX formula rendering polish */
        .katex {
            font-size: 1.1em !important;
            max-width: 100%;
        }
        .katex-display {
            margin: 0.5em 0 !important;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 0.25rem 0;
            -webkit-overflow-scrolling: touch;
        }

        /* Font Size Zoom Levels */
        .zoom-sm { font-size: 0.875rem; }
        .zoom-md { font-size: 1rem; }
        .zoom-lg { font-size: 1.15rem; }
        .zoom-xl { font-size: 1.3rem; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 9999px;
        }
    </style>

    {{-- Script KaTeX Library --}}
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js" crossorigin="anonymous"></script>

    @livewireStyles
</head>
<body class="h-full text-gray-900 bg-slate-100 flex flex-col antialiased" oncontextmenu="return false;">
    {{ $slot }}

    @livewireScripts

    <script>
        // Render semua rumus matematika KaTeX saat konten dimuat atau Livewire berpindah soal
        function renderMath() {
            if (typeof renderMathInElement === 'function') {
                renderMathInElement(document.body, {
                    delimiters: [
                        {left: '$$', right: '$$', display: true},
                        {left: '$', right: '$', display: false},
                        {left: '\\(', right: '\\)', display: false},
                        {left: '\\[', right: '\\]', display: true}
                    ],
                    throwOnError: false
                });
            }
        }

        document.addEventListener('DOMContentLoaded', renderMath);
        document.addEventListener('livewire:navigated', renderMath);
        document.addEventListener('livewire:initialized', () => {
            renderMath();
            if (window.Livewire) {
                Livewire.hook('morph.updated', () => {
                    requestAnimationFrame(renderMath);
                });
                Livewire.hook('commit', () => {
                    requestAnimationFrame(renderMath);
                });
            }
        });
        window.addEventListener('soal-rendered', () => {
            requestAnimationFrame(renderMath);
        });

        // Integrasi ExamGuard Android Bridge (Keamanan & Kiosk)
        window.addEventListener('DOMContentLoaded', () => {
            if (window.AndroidExam) {
                // Beritahu Livewire bahwa siswa menggunakan aplikasi resmi
                const battery = window.AndroidExam.getBatteryLevel ? window.AndroidExam.getBatteryLevel() : null;
                if (window.Livewire) {
                    window.dispatchEvent(new CustomEvent('exam-app-connected', { detail: { battery } }));
                }
            }
        });

        // Dengarkan event selesai ujian dari Livewire untuk melepas kunci Kiosk Android
        window.addEventListener('ujian-selesai-resmi', () => {
            if (window.AndroidExam && typeof window.AndroidExam.finishExam === 'function') {
                window.AndroidExam.finishExam();
            }
        });
    </script>
</body>
</html>

