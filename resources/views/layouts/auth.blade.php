<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Masuk ke Portal Umat — SĀMAGGI GĀMA' }}</title>
        <meta name="robots" content="noindex, nofollow">

        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo/favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo/apple-touch-icon.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body x-data="{ isDark: document.documentElement.classList.contains('dark') }" class="font-sans antialiased bg-[#F0E9DF] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 selection:bg-emerald-200 selection:text-emerald-950 h-full transition-colors duration-300">
        
        {{ $slot }}

        @livewireScripts
    </body>
</html>
