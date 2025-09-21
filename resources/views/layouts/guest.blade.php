<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $activeTheme ?? 'light' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <script>
        // Early theme (guest): prioriza localStorage, fallback cookie
        (function() {
            try {
                var c = document.cookie.match(/(?:^|; )theme=([^;]+)/);
                var ct = c ? decodeURIComponent(c[1]) : null;
                var s = localStorage.getItem('theme');
                var t = s || ct;
                if (t) {
                    document.documentElement.setAttribute('data-theme', t);
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-base text-base-color">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-base">
        <div>
            <a href="/">
                <x-application-logo class="w-48 text-muted" />
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 panel shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
</body>

</html>