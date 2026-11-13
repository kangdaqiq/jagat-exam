<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f4f7fb]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#041F45">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Examuna">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Examuna - Portal Pengawas Ujian' }}</title>

    {{-- PWA Manifest & Icons --}}
    <link rel="manifest" href="{{ asset('manifest-pengawas.json') }}?v=3">
    <link rel="icon" type="image/png" href="{{ asset('images/logo/pwa-pengawas-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/pwa-pengawas-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">

    @vite(['resources/css/app.css'])

    <style>
        [x-cloak] {
            display: none !important;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
    </style>

    @livewireStyles
</head>
<body class="min-h-[100dvh] text-slate-800 bg-[#f4f7fb] flex flex-col antialiased">
    {{ $slot }}

    @livewireScripts

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw-pengawas.js').catch((err) => {
                    console.warn('Service Worker Pengawas gagal didaftarkan:', err);
                });
            });
        }
    </script>
</body>
</html>

