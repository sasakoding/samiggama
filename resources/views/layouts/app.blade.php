@php
    $siteName = \App\Models\Setting::get('foundation_name', 'Vihara Sāmaggi Gāma');
    $siteAddress = \App\Models\Setting::get('foundation_address', 'Jl. Sāmaggi Raya No. 8, Candi Dhyana, Indonesia');
    $sitePhone = \App\Models\Setting::get('foundation_phone', '+62 811-2345-6789');
    $siteEmail = \App\Models\Setting::get('foundation_email', 'sekretariat@samaggigama.org');
    $siteLogo = asset(\App\Models\Setting::get('foundation_logo', 'images/logo/android-chrome-192x192.png'));
    $siteHero = asset('images/hero-vihara.jpg');
    
    $currentArticle = null;
    if (request()->routeIs('berita.detail') && request()->route('slug')) {
        $currentArticle = \App\Models\Article::where('slug', request()->route('slug'))->where('status', 'published')->first();
    }

    $metaTitle = $currentArticle 
        ? $currentArticle->title . ' — ' . $siteName
        : (isset($title) && !empty($title) 
            ? (str_contains($title, 'Sāmaggi Gāma') || str_contains($title, 'Samaggi Gama') ? $title : $title . ' — ' . $siteName)
            : $siteName . ' — Oase Ketenangan Batin & Pusat Pembinaan Buddha Dhamma');
        
    $metaDescription = $currentArticle 
        ? \Illuminate\Support\Str::words(strip_tags($currentArticle->content ?? $currentArticle->title), 30)
        : ($description ?? 'Website resmi ' . $siteName . '. Wadah pembinaan spiritual, meditasi terpandu, puja bakti rutin, pelestarian Buddha Dhamma, Sekolah Minggu Buddhis, dan penyaluran dana kebajikan terpercaya.');
    
    $metaKeywords = $keywords ?? 'Vihara Samaggi Gama, Samaggi Gama, Vihara, Agama Buddha, Buddha Dhamma, Meditasi, Puja Bakti, Jadwal Kebaktian, Sangha, Bhikkhu, Tipitaka, Sekolah Minggu Buddhis, Dana Paramita, Donasi Vihara, Pattidana, Anumodana, Yayasan Vihara Samaggi Gama';
    
    $currentUrl = url()->current();
    $ogImageUrl = $currentArticle && $currentArticle->cover_image ? asset($currentArticle->cover_image) : ($ogImage ?? $siteHero);
    $ogTypeVal = $currentArticle ? 'article' : ($ogType ?? 'website');

    $socialUrls = array_values(array_filter([
        \App\Models\Setting::get('social_youtube'),
        \App\Models\Setting::get('social_instagram'),
        \App\Models\Setting::get('social_facebook'),
        \App\Models\Setting::get('social_tiktok'),
        \App\Models\Setting::get('social_whatsapp'),
    ]));

    $schemaGraph = [
        [
            "@type" => ["BuddhistTemple", "PlaceOfWorship", "Organization"],
            "@id" => route('home') . "#organization",
            "name" => $siteName,
            "alternateName" => "Yayasan Vihara Sāmaggi Gāma",
            "url" => route('home'),
            "logo" => [
                "@type" => "ImageObject",
                "url" => $siteLogo,
                "caption" => $siteName,
            ],
            "image" => $siteHero,
            "description" => $metaDescription,
            "telephone" => $sitePhone,
            "email" => $siteEmail,
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => $siteAddress,
                "addressCountry" => "ID",
            ],
            "sameAs" => $socialUrls,
        ],
        [
            "@type" => "WebSite",
            "@id" => route('home') . "#website",
            "url" => route('home'),
            "name" => $siteName,
            "description" => $metaDescription,
            "publisher" => [
                "@id" => route('home') . "#organization",
            ],
            "inLanguage" => "id-ID",
        ],
    ];

    if ($currentArticle) {
        $schemaGraph[] = [
            "@type" => "NewsArticle",
            "headline" => $currentArticle->title,
            "image" => [
                asset($currentArticle->cover_image ?: 'images/hero-vihara.jpg')
            ],
            "datePublished" => $currentArticle->published_at ? $currentArticle->published_at->toIso8601String() : now()->toIso8601String(),
            "dateModified" => $currentArticle->updated_at ? $currentArticle->updated_at->toIso8601String() : now()->toIso8601String(),
            "author" => [
                [
                    "@type" => "Person",
                    "name" => $currentArticle->author_name ?: 'Redaksi ' . $siteName,
                    "url" => route('tentang-kami'),
                ]
            ],
            "publisher" => [
                "@id" => route('home') . "#organization",
            ],
            "description" => \Illuminate\Support\Str::words(strip_tags($currentArticle->content ?? $currentArticle->title), 30),
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => $currentUrl,
            ]
        ];
    }

    $schemaJsonLd = [
        "@context" => "https://schema.org",
        "@graph" => $schemaGraph,
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <!-- Primary Meta SEO Tags -->
        <title>{{ $metaTitle }}</title>
        <meta name="title" content="{{ $metaTitle }}">
        <meta name="description" content="{{ $metaDescription }}">
        <meta name="keywords" content="{{ $metaKeywords }}">
        <meta name="author" content="{{ $currentArticle ? ($currentArticle->author_name ?: $siteName) : $siteName }}">
        <meta name="publisher" content="{{ $siteName }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
        <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
        <meta name="language" content="id, en">
        <meta name="geo.region" content="ID">
        <meta name="geo.placename" content="Indonesia">
        <link rel="canonical" href="{{ $currentUrl }}">

        <!-- Open Graph / Facebook / WhatsApp / LinkedIn -->
        <meta property="og:type" content="{{ $ogTypeVal }}">
        <meta property="og:url" content="{{ $currentUrl }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:image" content="{{ $ogImageUrl }}">
        <meta property="og:image:secure_url" content="{{ $ogImageUrl }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $metaTitle }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:locale" content="id_ID">
        <?php if ($currentArticle): ?>
            <meta property="article:published_time" content="{{ $currentArticle->published_at ? $currentArticle->published_at->toIso8601String() : now()->toIso8601String() }}">
            <meta property="article:author" content="{{ $currentArticle->author_name ?: $siteName }}">
            <meta property="article:section" content="{{ $currentArticle->category }}">
        <?php endif; ?>

        <!-- Twitter / X Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ $currentUrl }}">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $ogImageUrl }}">
        <meta name="twitter:image:alt" content="{{ $metaTitle }}">

        <!-- Mobile & PWA Theme Settings -->
        <meta name="theme-color" content="#0D5B3A" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#07130E" media="(prefers-color-scheme: dark)">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Sāmaggi Gāma">
        <meta name="format-detection" content="telephone=no">

        <!-- Favicons & App Icons -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo/favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo/apple-touch-icon.png') }}">

        <!-- Typography & Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

        <!-- JSON-LD Structured Data for Rich Snippets (Schema.org) -->
        <script type="application/ld+json">
        {!! json_encode($schemaJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>

        @stack('seo')
        @stack('jsonld')

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
    <body x-data="{ mobileMenuOpen: false, isDark: document.documentElement.classList.contains('dark') }" class="font-sans antialiased bg-[#F0E9DF] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 selection:bg-emerald-200 selection:text-emerald-950 min-h-screen flex flex-col transition-colors duration-300">
        
        <!-- Top Sacred Blessing & Quick Info Micro-Bar -->
        <aside aria-label="Informasi Cepat" class="hidden lg:block bg-[#0f3325] dark:bg-[#05110B] text-stone-200 text-[11px] py-2 px-6 sm:px-10 md:px-14 lg:px-16 border-b border-emerald-900/40 dark:border-emerald-950/80 relative z-30">
            <div class="max-w-[1440px] mx-auto flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-amber-400/20 text-amber-300">
                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 7.4h7.6l-6.2 4.5 2.4 7.4-6.2-4.5-6.2 4.5 2.4-7.4-6.2-4.5h7.6z"/></svg>
                    </span>
                    <span class="text-amber-200/95 font-semibold tracking-wide italic">“Sabbe Sattā Bhavantu Sukhitattā”</span>
                    <span class="text-emerald-300/70 text-[10.5px]">— Semoga Semua Makhluk Berbahagia</span>
                </div>
                <div class="flex items-center gap-5 text-stone-300">
                    <div class="flex items-center gap-2">
                        
                    </div>
                </div>
            </div>
        </aside>

        <!-- Top Header Navigation -->
        <header 
            x-data="{ scrolled: false }" 
            @scroll.window="scrolled = (window.pageYOffset > 15)" 
            :class="scrolled ? 'bg-[#FAF5ED]/95 dark:bg-[#091711]/95 shadow-lg shadow-emerald-950/5 dark:shadow-black/50 py-2.5 sm:py-3 border-emerald-950/10 dark:border-emerald-500/15' : 'bg-[#FAF5ED]/85 dark:bg-[#091711]/85 py-3.5 sm:py-4 border-emerald-950/8 dark:border-emerald-500/10'"
            class="sticky top-0 z-40 w-full backdrop-blur-xl border-b transition-all duration-300"
        >
            <!-- Top Hairline Gold & Emerald Accent Gradient -->
            <div class="absolute top-0 inset-x-0 h-[1.5px] bg-linear-to-r from-transparent via-amber-400/50 to-transparent pointer-events-none"></div>

            <div class="max-w-360 mx-auto px-5 sm:px-8 md:px-12 lg:px-16 flex items-center justify-between">
                
                <!-- Brand Logo & Official Emblem -->
                <a href="{{ route('home') }}" class="group flex items-center gap-3 sm:gap-3.5 text-stone-900 dark:text-stone-100 transition-all duration-200">
                    <div class="relative flex items-center justify-center p-1.5 sm:p-2 rounded-2xl bg-linear-to-br from-amber-500/10 via-emerald-900/5 to-emerald-900/15 dark:from-amber-400/10 dark:to-emerald-500/10 border border-amber-500/25 dark:border-amber-400/25 shadow-sm group-hover:shadow-md group-hover:shadow-amber-500/10 group-hover:border-amber-500/50 group-hover:scale-105 transition-all duration-300">
                        <img src="{{ asset(\App\Models\Setting::get('foundation_logo', 'images/logo/android-chrome-192x192.png')) }}" alt="Logo Vihara Sāmaggi Gāma" class="w-8 h-8 sm:w-10 sm:h-10 object-contain drop-shadow-sm transition-transform duration-300">
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5">
                            <span class="text-lg sm:text-xl md:text-2xl font-black tracking-tight text-[#143D2D] dark:text-[#E8F3EE] group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors leading-none font-sans">
                                SĀMAGGI GĀMA
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-0.5 sm:mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span class="text-[9px] sm:text-[10px] md:text-[10.5px] font-bold tracking-[0.2em] text-[#8C6B1F] dark:text-amber-300/90 uppercase">
                                Vihara & Pusat Dhamma
                            </span>
                        </div>
                    </div>
                </a>

                <!-- Desktop Navigation Links (Floating Glass Pill Island) -->
                <nav class="hidden lg:flex items-center" aria-label="Navigasi Utama">
                    <a href="{{ route('tentang-kami') }}" class="px-3.5 py-2 rounded-full {{ request()->routeIs('tentang-kami*') ? 'text-[#0D6E42] dark:text-emerald-300 bg-white/90 dark:bg-emerald-950/80 font-bold shadow-xs' : 'text-stone-700 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-300 hover:bg-white/90 dark:hover:bg-emerald-950/60 font-semibold' }} text-sm transition-all duration-150 relative group">
                        <span>Tentang Kami</span>
                        <span class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-[#0D6E42] dark:bg-emerald-400 rounded-full {{ request()->routeIs('tentang-kami*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' }} transition-opacity"></span>
                    </a>
                    <a href="{{ route('kegiatan') }}" class="px-3.5 py-2 rounded-full {{ request()->routeIs('kegiatan*') ? 'text-[#0D6E42] dark:text-emerald-300 bg-white/90 dark:bg-emerald-950/80 font-bold shadow-xs' : 'text-stone-700 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-300 hover:bg-white/90 dark:hover:bg-emerald-950/60 font-semibold' }} text-sm transition-all duration-150 relative group">
                        <span>Kegiatan</span>
                        <span class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-[#0D6E42] dark:bg-emerald-400 rounded-full {{ request()->routeIs('kegiatan*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' }} transition-opacity"></span>
                    </a>
                    <a href="{{ route('galeri') }}" class="px-4 py-2 rounded-full {{ request()->routeIs('galeri*') ? 'text-[#0D6E42] dark:text-emerald-300 bg-white/90 dark:bg-emerald-950/80 font-bold shadow-xs' : 'text-stone-700 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-300 hover:bg-white/90 dark:hover:bg-emerald-950/60 font-semibold' }} text-sm transition-all duration-150 relative group">
                        <span>Galeri</span>
                        <span class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-[#0D6E42] dark:bg-emerald-400 rounded-full {{ request()->routeIs('galeri*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' }} transition-opacity"></span>
                    </a>
                    <a href="{{ route('berita') }}" class="px-4 py-2 rounded-full {{ request()->routeIs('berita*') ? 'text-[#0D6E42] dark:text-emerald-300 bg-white/90 dark:bg-emerald-950/80 font-bold shadow-xs' : 'text-stone-700 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-300 hover:bg-white/90 dark:hover:bg-emerald-950/60 font-semibold' }} text-sm transition-all duration-150 relative group">
                        <span>Berita</span>
                        <span class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-[#0D6E42] dark:bg-emerald-400 rounded-full {{ request()->routeIs('berita*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' }} transition-opacity"></span>
                    </a>
                    <a href="{{ route('donasi') }}" class="px-4 py-2 rounded-full {{ request()->routeIs('donasi*') ? 'bg-amber-400/20 text-amber-900 dark:text-amber-200 font-extrabold shadow-xs' : 'text-amber-800 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-200 hover:bg-amber-400/10 font-bold' }} text-sm transition-all duration-150 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 fill-amber-500/20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        <span>Dana</span>
                    </a>
                </nav>

                <!-- Desktop CTA & Theme Switcher Button -->
                <div class="hidden md:flex items-center gap-3">
                    
                    <!-- Dark / Light Theme Switcher Button -->
                    <button 
                        @click="
                            isDark = !isDark; 
                            if (isDark) { 
                                document.documentElement.classList.add('dark'); 
                                localStorage.theme = 'dark'; 
                            } else { 
                                document.documentElement.classList.remove('dark'); 
                                localStorage.theme = 'light'; 
                            }
                        " 
                        type="button" 
                        class="p-2.5 rounded-full bg-stone-200/70 hover:bg-stone-300/70 dark:bg-emerald-950/80 dark:hover:bg-emerald-900/80 text-stone-700 dark:text-amber-300 border border-stone-300/70 dark:border-emerald-500/25 transition-all duration-200 shadow-sm cursor-pointer active:scale-95"
                        :aria-label="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
                    >
                        <!-- Sun Icon (Dark Mode active -> click for light) -->
                        <svg x-show="isDark" x-cloak class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <!-- Moon Icon (Light Mode active -> click for dark) -->
                        <svg x-show="!isDark" class="w-4 h-4 text-stone-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>

                    <a href="{{ request()->routeIs('home') ? '#kontak' : route('home') . '#kontak' }}" class="group relative inline-flex items-center gap-2.5 bg-linear-to-r from-[#0D5B3A] to-[#143D2D] hover:from-[#0F6B44] hover:to-[#174836] text-amber-100 font-semibold px-5 py-2.5 rounded-full shadow-md shadow-emerald-950/15 hover:shadow-lg hover:shadow-emerald-950/25 border border-amber-400/30 transition-all duration-200 text-sm transform hover:-translate-y-0.5 active:translate-y-0">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-300"></span>
                        </span>
                        <span>Hubungi Kami</span>
                        <svg class="w-4 h-4 text-amber-300 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

                <!-- Mobile Hamburger, Theme Switcher & Quick Action Buttons -->
                <div class="flex items-center gap-2 md:hidden">
                    <!-- Mobile Theme Toggle -->
                    <button 
                        @click="
                            isDark = !isDark; 
                            if (isDark) { 
                                document.documentElement.classList.add('dark'); 
                                localStorage.theme = 'dark'; 
                            } else { 
                                document.documentElement.classList.remove('dark'); 
                                localStorage.theme = 'light'; 
                            }
                        " 
                        type="button" 
                        class="p-2.5 rounded-xl bg-stone-900/5 dark:bg-emerald-950/80 text-stone-700 dark:text-amber-300 border border-stone-900/10 dark:border-emerald-500/25 active:scale-95 transition-transform" 
                        aria-label="Ubah Tema"
                    >
                        <svg x-show="isDark" x-cloak class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg x-show="!isDark" class="w-4 h-4 text-stone-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                </div>
            </div>
        </header>

        <div class="flex-1 pb-16 md:pb-0">
            {{ $slot }}
        </div>

        <!-- Mobile Bottom Navigation Bar (Khusus Layar HP / Mobile) -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-[#FAF5ED]/95 dark:bg-[#0c1813]/95 backdrop-blur-md border-t border-emerald-950/10 dark:border-emerald-500/15 shadow-lg px-2 py-1.5 flex items-center justify-around text-stone-600 dark:text-stone-300" aria-label="Navigasi Bawah Mobile">
            
            <!-- 1. Beranda -->
            <a href="{{ route('home') }}" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl {{ request()->routeIs('home') ? 'text-[#0D6E42] dark:text-emerald-400 font-bold' : 'text-stone-600 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-400' }} transition-colors group">
                <svg class="w-5 h-5 mb-0.5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-[10px] font-semibold tracking-tight">Beranda</span>
            </a>

            <!-- 2. Kegiatan -->
            <a href="{{ route('kegiatan') }}" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl {{ request()->routeIs('kegiatan*') ? 'text-[#0D6E42] dark:text-emerald-400 font-bold' : 'text-stone-600 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-400' }} transition-colors group">
                <svg class="w-5 h-5 mb-0.5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-[10px] font-semibold tracking-tight">Kegiatan</span>
            </a>

            <!-- 3. Dana Paramita (Aksen Menonjol di Tengah) -->
            <a href="{{ route('donasi') }}" class="flex flex-col items-center justify-center -mt-4 group">
                <div class="w-11 h-11 rounded-full bg-linear-to-tr from-[#0D5B3A] to-[#143D2D] text-white flex items-center justify-center shadow-md shadow-emerald-950/20 group-hover:scale-105 group-active:scale-95 transition-all border-2 border-[#FAF5ED] dark:border-[#0c1813]">
                    <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold text-[#0D5B3A] dark:text-emerald-400 tracking-tight mt-0.5">Dana</span>
            </a>

            <!-- 4. Galeri -->
            <a href="{{ route('galeri') }}" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl {{ request()->routeIs('galeri*') ? 'text-[#0D6E42] dark:text-emerald-400 font-bold' : 'text-stone-600 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-400' }} transition-colors group">
                <svg class="w-5 h-5 mb-0.5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-[10px] font-semibold tracking-tight">Galeri</span>
            </a>

            <!-- 5. Kontak -->
            <a href="{{ request()->routeIs('home') ? '#kontak' : route('home') . '#kontak' }}" class="flex flex-col items-center justify-center py-1 px-2 rounded-xl text-stone-600 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-400 active:text-[#0D6E42] transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mb-0.5 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                <span class="text-[10px] font-semibold tracking-tight">Kontak</span>
            </a>
        </nav>

        <!-- ==========================================
             FOOTER LENGKAP & BERKAH
             ========================================== -->
        @php
            $foundationName = \App\Models\Setting::get('foundation_name', 'Yayasan Vihara Sāmaggi Gāma');
            $foundationLogo = \App\Models\Setting::get('foundation_logo', 'images/logo/android-chrome-192x192.png');
            $kemenagNo = \App\Models\Setting::get('foundation_kemenag') ?: \App\Models\Setting::get('foundation_kemenag_id');
            $notaryNo = \App\Models\Setting::get('foundation_notary') ?: \App\Models\Setting::get('foundation_notary_id');
            $foundationAddress = \App\Models\Setting::get('foundation_address', 'Jl. Samaggi Gāma No. 108, Jakarta');
            $foundationMapUrl = \App\Models\Setting::get('foundation_map_url');
            $foundationPhone = \App\Models\Setting::get('foundation_phone', '+62 812-3456-7890');
            $foundationEmail = \App\Models\Setting::get('foundation_email', 'sekretariat@samaggigama.org');
            $phoneDigits = \App\Models\AdminContact::getRandomActivePhone($foundationPhone);
        @endphp

        <footer id="kontak" class="bg-[#143D2D] dark:bg-[#07160F] text-stone-300 py-16 px-6 sm:px-10 lg:px-16 border-t border-emerald-950/40 dark:border-emerald-900/30">
            <div class="max-w-[1440px] mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                
                <!-- Col 1: Brand Info & Legalitas Yayasan -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset($foundationLogo) }}" alt="Logo {{ $foundationName }}" class="w-10 h-10 object-contain">
                        <div>
                            <span class="text-lg sm:text-xl font-extrabold text-white tracking-tight block">{{ $foundationName }}</span>
                            <span class="text-[10px] uppercase tracking-widest text-amber-300 font-bold">Vihara & Pusat Dhamma</span>
                        </div>
                    </div>
                    <p class="text-xs sm:text-sm text-stone-400 leading-relaxed">
                        Wadah pembinaan batin, pelestarian Buddha Dhamma, serta penabur cinta kasih dan keharmonisan bagi seluruh makhluk hidup.
                    </p>

                    <div class="pt-1 text-xs font-semibold text-amber-300 italic">
                        Sabbe Sattā Bhavantu Sukhitattā<br>
                        <span class="text-stone-400 not-italic font-normal">Semoga Semua Makhluk Berbahagia</span>
                    </div>
                </div>

                <!-- Col 2: Navigasi Cepat -->
                <div class="space-y-3">
                    <h3 class="text-white font-bold text-sm uppercase tracking-wider">Navigasi Utama</h3>
                    <ul class="space-y-2 text-xs sm:text-sm text-stone-400">
                        <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">Beranda Utama</a></li>
                        <li><a href="{{ route('tentang-kami') }}" class="hover:text-white transition-colors">Tentang Kami</a></li>
                        <li><a href="{{ route('pengurus') }}" class="hover:text-white transition-colors">Pengurus & Pembina</a></li>
                        <li><a href="{{ route('kegiatan', ['tab' => 'kalender']) }}" class="hover:text-white transition-colors">Kalender Hari Besar Buddhis</a></li>
                        <li><a href="{{ route('kegiatan', ['tab' => 'kegiatan']) }}" class="hover:text-white transition-colors">Daftar Event & Jadwal Petugas</a></li>
                        <li><a href="{{ route('donasi') }}" class="hover:text-white transition-colors">Program Dāna Paramita</a></li>
                        <li><a href="{{ route('pengeluaran.public') }}" class="hover:text-amber-200 text-amber-300 font-semibold transition-colors">Laporan Pengeluaran Dāna</a></li>
                        <li><a href="{{ route('galeri') }}" class="hover:text-white transition-colors">Galeri Dokumentasi</a></li>
                        <li><a href="{{ route('berita') }}" class="hover:text-white transition-colors">Berita Samaggi Gāma</a></li>
                    </ul>
                </div>
               
                <!-- Col 4: Kontak & Sekretariat (Dynamic from DB) -->
                <div class="space-y-3">
                    <h3 class="text-white font-bold text-sm uppercase tracking-wider">Sekretariat</h3>
                    <div class="text-xs sm:text-sm text-stone-400 leading-relaxed space-y-2">
                        @if (!empty($foundationAddress))
                            <p class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                @if (!empty($foundationMapUrl))
                                    <a href="{{ $foundationMapUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-amber-300 transition-colors inline-flex items-center gap-1 group" title="Buka Lokasi di Google Maps">
                                        <span>{{ $foundationAddress }}</span>
                                    </a>
                                @else
                                    <span>{{ $foundationAddress }}</span>
                                @endif
                            </p>
                        @endif
                        @if (!empty($foundationPhone))
                            <p class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>WhatsApp: {{ $foundationPhone }}</span>
                            </p>
                        @endif
                        @if (!empty($foundationEmail))
                            <p class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <a href="mailto:{{ $foundationEmail }}" class="hover:text-white transition-colors">{{ $foundationEmail }}</a>
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Col 4: Legalitas Resmi Yayasan & Statistik Kunjungan -->
                <div class="space-y-5">
                    @if (!empty($kemenagNo) || !empty($notaryNo))
                        <div class="space-y-2 text-xs text-stone-300">
                            <div class="text-[10px] font-black uppercase tracking-wider text-amber-300 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span>Badan Hukum</span>
                            </div>
                            @if (!empty($kemenagNo))
                                <div class="text-[11px] leading-tight space-y-0.5">
                                    <span class="text-stone-400 block text-[10px]">Tanda Daftar Kemenag RI:</span>
                                    <strong class="text-stone-100 font-bold font-mono">{{ $kemenagNo }}</strong>
                                </div>
                            @endif
                            @if (!empty($notaryNo))
                                <div class="text-[11px] leading-tight space-y-0.5 {{ !empty($kemenagNo) ? 'pt-1.5 border-t border-emerald-900/80' : '' }}">
                                    <span class="text-stone-400 block text-[10px]">Akta Notaris Yayasan:</span>
                                    <strong class="text-stone-100 font-bold font-mono">{{ $notaryNo }}</strong>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Premium Visitor Stats Widget Card -->
                    @php
                        $visitorStats = \App\Models\VisitorLog::getPublicFooterStats();
                    @endphp
                    <div class="p-3.5 rounded-2xl bg-emerald-950/70 dark:bg-black/40 border border-emerald-800/40 space-y-3 shadow-inner">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-amber-300 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Statistik Pengunjung</span>
                            </span>
                            <span class="text-[9.5px] text-emerald-300 font-semibold px-2 py-0.5 rounded-full bg-emerald-900/80 border border-emerald-700/40 font-mono flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span>Live</span>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <!-- Hari Ini -->
                            <div class="p-2.5 rounded-xl bg-emerald-900/40 dark:bg-[#07160F]/60 border border-emerald-800/30">
                                <span class="text-[10px] text-stone-400 block font-medium">Hari Ini</span>
                                <span class="text-base font-black text-emerald-300 font-mono tracking-tight block mt-0.5">
                                    +{{ number_format($visitorStats['today_views'], 0, ',', '.') }}
                                </span>
                            </div>

                            <!-- Total Kunjungan -->
                            <div class="p-2.5 rounded-xl bg-emerald-900/40 dark:bg-[#07160F]/60 border border-emerald-800/30">
                                <span class="text-[10px] text-stone-400 block font-medium">Total Kunjungan</span>
                                <span class="text-base font-black text-amber-300 font-mono tracking-tight block mt-0.5">
                                    {{ number_format($visitorStats['total_views'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-[1440px] mx-auto mt-12 pt-6 border-t border-emerald-900/60 flex flex-col sm:flex-row items-center justify-between text-xs text-stone-400 gap-4">
                <div class="flex items-center gap-2 text-center sm:text-left">
                    <span>© {{ date('Y') }} {{ $foundationName }}. Hak Cipta Dilindungi.</span>
                </div>
                
                <!-- Social Media Channels (Dynamic from DB Settings) -->
                @php
                    $socYt = \App\Models\Setting::get('social_youtube', 'https://youtube.com/@samaggigama');
                    $socIg = \App\Models\Setting::get('social_instagram', 'https://instagram.com/samaggigama');
                    $socFb = \App\Models\Setting::get('social_facebook', 'https://facebook.com/samaggigama');
                    $socTt = \App\Models\Setting::get('social_tiktok', 'https://tiktok.com/@samaggigama');
                    $socWa = \App\Models\Setting::get('social_whatsapp', 'https://wa.me/6281123456789');
                    $socSp = \App\Models\Setting::get('social_spotify', '');
                @endphp
                <div class="flex items-center gap-2.5">
                    @if ($socYt)
                        <!-- YouTube -->
                        <a href="{{ $socYt }}" target="_blank" rel="noopener noreferrer" class="group relative flex items-center justify-center w-8 h-8 rounded-full bg-emerald-900/70 hover:bg-[#FF0000] text-stone-300 hover:text-white border border-emerald-700/50 hover:border-[#FF0000] transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5" aria-label="YouTube Vihara Samaggi Gama" title="YouTube">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    @endif

                    @if ($socIg)
                        <!-- Instagram -->
                        <a href="{{ $socIg }}" target="_blank" rel="noopener noreferrer" class="group relative flex items-center justify-center w-8 h-8 rounded-full bg-emerald-900/70 hover:bg-linear-to-tr hover:from-amber-500 hover:via-pink-500 hover:to-purple-600 text-stone-300 hover:text-white border border-emerald-700/50 hover:border-pink-500 transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5" aria-label="Instagram Vihara Samaggi Gama" title="Instagram">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    @endif

                    @if ($socFb)
                        <!-- Facebook -->
                        <a href="{{ $socFb }}" target="_blank" rel="noopener noreferrer" class="group relative flex items-center justify-center w-8 h-8 rounded-full bg-emerald-900/70 hover:bg-[#1877F2] text-stone-300 hover:text-white border border-emerald-700/50 hover:border-[#1877F2] transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5" aria-label="Facebook Vihara Samaggi Gama" title="Facebook">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                    @endif

                    @if ($socTt)
                        <!-- TikTok -->
                        <a href="{{ $socTt }}" target="_blank" rel="noopener noreferrer" class="group relative flex items-center justify-center w-8 h-8 rounded-full bg-emerald-900/70 hover:bg-stone-900 text-stone-300 hover:text-amber-300 border border-emerald-700/50 hover:border-amber-400 transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5" aria-label="TikTok Vihara Samaggi Gama" title="TikTok">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        </a>
                    @endif

                    @if ($socSp)
                        <!-- Spotify -->
                        <a href="{{ $socSp }}" target="_blank" rel="noopener noreferrer" class="group relative flex items-center justify-center w-8 h-8 rounded-full bg-emerald-900/70 hover:bg-[#1DB954] text-stone-300 hover:text-white border border-emerald-700/50 hover:border-[#1DB954] transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5" aria-label="Spotify Podcast Vihara Samaggi Gama" title="Spotify Podcast">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.48.66.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>
                        </a>
                    @endif
                </div>
            </div>
        </footer>

        <!-- Floating Interactive Back-to-Top Button with Dynamic Circular Scroll Progress -->
        <div 
            x-data="{ 
                showTopBtn: false,
                scrollProgress: 0,
                calcProgress() {
                    const winScroll = window.pageYOffset || document.documentElement.scrollTop;
                    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                    this.scrollProgress = height > 0 ? Math.min(100, Math.max(0, Math.round((winScroll / height) * 100))) : 0;
                    this.showTopBtn = winScroll > 280;
                }
            }" 
            x-init="calcProgress()"
            @scroll.window="calcProgress()"
            class="fixed bottom-20 md:bottom-7 right-5 sm:right-8 z-40"
        >
            <div 
                x-show="showTopBtn" 
                x-cloak 
                x-transition:enter="transition cubic-bezier(0.16, 1, 0.3, 1) duration-400 transform" 
                x-transition:enter-start="opacity-0 translate-y-8 scale-75 rotate-45" 
                x-transition:enter-end="opacity-100 translate-y-0 scale-100 rotate-0" 
                x-transition:leave="transition ease-in duration-250 transform" 
                x-transition:leave-start="opacity-100 translate-y-0 scale-100 rotate-0" 
                x-transition:leave-end="opacity-0 translate-y-8 scale-75 rotate-45"
                class="relative flex items-center justify-center group"
            >
                <!-- Outer Animated Pulse Glow Ring -->
                <div class="absolute -inset-1.5 rounded-full bg-linear-to-tr from-amber-500/40 via-emerald-500/40 to-amber-400/40 blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none animate-pulse-glow"></div>

                <!-- Main Interactive Circular Button -->
                <button 
                    @click="window.scrollTo({ top: 0, behavior: 'smooth' })" 
                    type="button" 
                    class="relative w-12 h-12 rounded-full bg-[#FAF5ED]/95 dark:bg-[#0c1813]/95 backdrop-blur-xl border border-amber-500/30 dark:border-emerald-500/30 text-[#0D5B3A] dark:text-emerald-300 group-hover:text-amber-500 dark:group-hover:text-amber-300 shadow-xl hover:shadow-2xl shadow-emerald-950/20 dark:shadow-black/70 transition-all duration-300 cursor-pointer flex items-center justify-center hover:scale-110 active:scale-95"
                    aria-label="Kembali ke Atas"
                >
                    <!-- SVG Circular Scroll Progress Indicator -->
                    <svg class="absolute inset-0 w-full h-full -rotate-90 pointer-events-none p-0.5" viewBox="0 0 36 36">
                        <!-- Track Circle -->
                        <path 
                            class="text-stone-300/40 dark:text-emerald-950/80 stroke-current" 
                            stroke-width="2.5" 
                            fill="none" 
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                        />
                        <!-- Animated Progress Stroke -->
                        <path 
                            class="text-amber-500 dark:text-emerald-400 stroke-current transition-all duration-150 ease-out" 
                            stroke-dasharray="100, 100" 
                            :stroke-dashoffset="100 - scrollProgress" 
                            stroke-linecap="round" 
                            stroke-width="2.5" 
                            fill="none" 
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                        />
                    </svg>

                    <!-- Animated Arrow Icon with Spring Lift on Hover -->
                    <div class="relative z-10 flex flex-col items-center justify-center transition-transform duration-300 group-hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.8" d="M5 11l7-7m0 0l7 7M12 4v16"/>
                        </svg>
                    </div>
                </button>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
