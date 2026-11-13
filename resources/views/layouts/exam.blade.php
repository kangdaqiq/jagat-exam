<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#041F45">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Examuna - Digital Examination System | SMK Assuniyah Tumijajar' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo/pwa-pengawas-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    {{-- KaTeX Lokal (Offline-Ready) & Font Non-Blocking --}}
    <link rel="stylesheet" href="{{ asset('vendor/katex/katex.min.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&family=Outfit:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">

    @vite(['resources/css/app.css'])

    <style>
        [x-cloak] {
            display: none !important;
        }

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

    {{-- Script KaTeX Lokal dengan Defer (Tidak Memblokir Render Halaman) --}}
    <script defer src="{{ asset('vendor/katex/katex.min.js') }}"></script>
    <script defer src="{{ asset('vendor/katex/contrib/auto-render.min.js') }}"></script>

    @livewireStyles
</head>
<body class="h-full text-slate-800 bg-slate-50 flex flex-col antialiased" oncontextmenu="return false;">
    {{ $slot }}

    @livewireScripts

    <script>
        // Render rumus matematika KaTeX hanya jika halaman mengandung delimiter rumus
        function renderMath() {
            if (typeof renderMathInElement !== 'function' || !document.body) return;
            const text = document.body.textContent || '';
            if (!text.includes('$') && !text.includes('\\(') && !text.includes('\\[')) return;

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

