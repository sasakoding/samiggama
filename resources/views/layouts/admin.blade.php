<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Dashboard' }} — Admin SĀMAGGI GĀMA</title>
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
    <body 
        x-data="{ 
            sidebarOpen: false, 
            isDark: document.documentElement.classList.contains('dark'),
            searchOpen: false,
            notificationsOpen: false,
            userDropdownOpen: false
        }" 
        class="h-full font-sans antialiased bg-[#F4EFEA] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 selection:bg-emerald-200 selection:text-emerald-950 flex overflow-hidden transition-colors duration-300"
    >

        <!-- =========================================================================
             MOBILE SIDEBAR BACKDROP OVERLAY
             ========================================================================= -->
        <div 
            x-show="sidebarOpen" 
            x-cloak
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false" 
            class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
        ></div>

        <!-- =========================================================================
             SIDEBAR NAVIGASI (DESKTOP & MOBILE DRAWER)
             ========================================================================= -->
        <aside 
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0B2117] dark:bg-[#05110B] text-stone-200 flex flex-col justify-between transition-transform duration-300 ease-in-out border-r border-emerald-900/50 dark:border-emerald-950/80 shadow-2xl lg:static lg:translate-x-0 shrink-0"
            aria-label="Navigasi Utama Admin"
        >
            <!-- TOP BRAND EMBLEM -->
            <div class="p-5 border-b border-emerald-900/60 dark:border-emerald-950 flex items-center justify-between">
                <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-3 transition-all">
                    <div class="relative flex items-center justify-center p-2 rounded-2xl bg-gradient-to-br from-amber-500/20 via-emerald-600/30 to-amber-600/20 border border-amber-500/40 shadow-sm group-hover:scale-105 transition-transform">
                        <img src="{{ asset(\App\Models\Setting::get('foundation_logo', 'images/logo/android-chrome-192x192.png')) }}" alt="Logo Vihara" class="w-8 h-8 object-contain drop-shadow-sm">
                    </div>
                    <div class="flex flex-col">
                        <span class="font-extrabold tracking-tight text-base text-[#FAF5ED] group-hover:text-amber-300 transition-colors">
                            SĀMAGGI GĀMA
                        </span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            <span class="text-[9.5px] font-bold tracking-widest text-amber-300/90 uppercase">
                                Panel Pengurus
                            </span>
                        </div>
                    </div>
                </a>

                <!-- Close button on mobile -->
                <button 
                    @click="sidebarOpen = false" 
                    type="button" 
                    class="p-2 rounded-xl text-stone-400 hover:text-white hover:bg-emerald-900/50 lg:hidden cursor-pointer"
                    aria-label="Tutup Sidebar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- SCROLLABLE NAVIGATION MENU LIST -->
            <div class="flex-1 px-3.5 py-4 space-y-6 overflow-y-auto custom-scrollbar">
                
                @if (auth()->user()?->isBendahara())
                    <!-- ==============================================
                         BENDAHARA MENU ITEMS (RESTRICTED TO FINANCE)
                         ============================================== -->
                    <!-- GROUP 1: DASHBOARD KEUANGAN -->
                    <div class="space-y-1">
                        <div class="px-3 text-[10px] font-black uppercase tracking-wider text-amber-400/80 flex items-center justify-between">
                            <span>Kas & Keuangan</span>
                            <span class="px-1.5 py-0.5 rounded bg-amber-400/20 text-amber-300 text-[9px] font-extrabold">Bendahara</span>
                        </div>

                        <a 
                            href="{{ route('admin.dashboard') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all {{ request()->routeIs('admin.dashboard*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.dashboard*') ? 'text-amber-400' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span>Dashboard Keuangan</span>
                        </a>
                    </div>

                    <!-- GROUP 2: KELOLA KEUANGAN -->
                    <div class="space-y-1">
                        <div class="px-3 text-[10px] font-black uppercase tracking-wider text-emerald-400/70">
                            Mutasi & Pembukuan
                        </div>

                        <a 
                            href="{{ route('admin.transaksi') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.transaksi*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.transaksi*') ? 'text-amber-300' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Transaksi & Donatur</span>
                        </a>

                        <a 
                            href="{{ route('admin.pengeluaran') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.pengeluaran*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.pengeluaran*') ? 'text-amber-300' : 'text-rose-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            <span>Pengeluaran Dāna</span>
                        </a>
                    </div>
                @else
                    <!-- ==============================================
                         ADMINISTRATOR MENU ITEMS (FULL ACCESS)
                         ============================================== -->
                    <!-- GROUP 1: UTAMA & STATISTIK -->
                    <div class="space-y-1">
                        <div class="px-3 text-[10px] font-black uppercase tracking-wider text-emerald-400/70">
                            Menu Utama
                        </div>

                        <a 
                            href="{{ route('admin.dashboard') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all {{ request()->routeIs('admin.dashboard*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.dashboard*') ? 'text-amber-400' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <!-- GROUP 2: DĀNA PARAMITA & KEUANGAN -->
                    <div class="space-y-1">
                        <div class="px-3 text-[10px] font-black uppercase tracking-wider text-emerald-400/70">
                            Layanan Dāna Paramita
                        </div>

                        <a 
                            href="{{ route('admin.donasi') }}" 
                            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.donasi*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 {{ request()->routeIs('admin.donasi*') ? 'text-amber-300' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                <span>Program Donasi</span>
                            </div>
                        </a>

                        <a 
                            href="{{ route('admin.transaksi') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.transaksi*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.transaksi*') ? 'text-amber-300' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Transaksi & Donatur</span>
                        </a>

                        <a 
                            href="{{ route('admin.pengeluaran') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.pengeluaran*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.pengeluaran*') ? 'text-amber-300' : 'text-rose-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            <span>Pengeluaran Dāna</span>
                        </a>

                        <a 
                            href="{{ route('admin.piagam') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.piagam*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.piagam*') ? 'text-amber-300' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Piagam Anumodana</span>
                        </a>
                    </div>

                    <!-- GROUP 3: AKTIVITAS & KEUMATAN -->
                    <div class="space-y-1">
                        <div class="px-3 text-[10px] font-black uppercase tracking-wider text-emerald-400/70">
                            Aktivitas & Keumatan
                        </div>

                        <a 
                            href="{{ route('admin.jadwal-rutin') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.jadwal-rutin*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.jadwal-rutin*') ? 'text-amber-300' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <span>Jadwal Petugas Rutin</span>
                        </a>

                        <a 
                            href="{{ route('admin.agenda') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.agenda*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.agenda*') ? 'text-amber-300' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Agenda & Event Khusus</span>
                        </a>

                        <a 
                            href="{{ route('admin.pengurus') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.pengurus*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.pengurus*') ? 'text-amber-300' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Kelola Pengurus</span>
                        </a>
                    </div>

                    <!-- GROUP 4: KONTEN & PUBLIKASI -->
                    <div class="space-y-1">
                        <div class="px-3 text-[10px] font-black uppercase tracking-wider text-emerald-400/70">
                            Konten & Media
                        </div>

                        <a 
                            href="{{ route('admin.berita') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.berita*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.berita*') ? 'text-amber-300' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                            <span>Kelola Berita</span>
                        </a>

                        <a 
                            href="{{ route('admin.berita.kategori') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.berita.kategori*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.berita.kategori*') ? 'text-amber-300' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span>Kategori Berita</span>
                        </a>

                        <a 
                            href="{{ route('admin.galeri') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.galeri*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.galeri*') ? 'text-amber-300' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Foto & Dokumentasi</span>
                        </a>

                        <a 
                            href="{{ route('admin.tentang-kami') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.tentang-kami*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.tentang-kami*') ? 'text-amber-300' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>Tentang Kami</span>
                        </a>
                    </div>

                    <!-- GROUP 5: PENGATURAN -->
                    <div class="space-y-1">
                        <div class="px-3 text-[10px] font-black uppercase tracking-wider text-emerald-400/70">
                            Sistem & Yayasan
                        </div>

                        <a 
                            href="{{ route('admin.users') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.users*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.users*') ? 'text-amber-300' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Kelola Pengguna</span>
                        </a>

                        <a 
                            href="{{ route('admin.pengaturan') }}" 
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all {{ request()->routeIs('admin.pengaturan*') ? 'bg-[#0D5B3A] text-amber-200 shadow-md shadow-black/20 border border-amber-500/30 font-bold' : 'text-stone-300 hover:text-white hover:bg-emerald-900/40' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.pengaturan*') ? 'text-amber-300' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Pengaturan Web</span>
                        </a>
                    </div>
                @endif

            </div>

            <!-- BOTTOM USER PROFILE & LOGOUT -->
            @php
                $currentUser = auth()->user();
                $userName = $currentUser?->name ?? 'Administrator';
                $userRole = $currentUser?->isBendahara() ? 'Bendahara' : ($currentUser?->role ?? 'Admin');
                $nameParts = explode(' ', trim($userName));
                $initials = count($nameParts) >= 2 
                    ? mb_strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1))
                    : mb_strtoupper(mb_substr($userName, 0, 2));
            @endphp
            <div class="p-3.5 border-t border-emerald-900/60 dark:border-emerald-950 bg-black/20 space-y-3">
                <a 
                    href="{{ route('admin.profil') }}" 
                    class="flex items-center justify-between p-2 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 transition-colors group cursor-pointer"
                    title="Edit Profil & Ubah Password"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-amber-500 text-white font-bold text-xs flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            {{ $initials }}
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-xs font-bold text-white group-hover:text-amber-300 transition-colors truncate max-w-[120px]">
                                {{ $userName }}
                            </span>
                            <span class="text-[10px] text-amber-300/80 truncate max-w-[120px]">
                                {{ $userRole }}
                            </span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-stone-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <div class="grid grid-cols-2 gap-2">
                    <a 
                        href="{{ route('home') }}" 
                        target="_blank" 
                        class="py-2 px-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-stone-300 hover:text-white text-[11px] font-semibold transition-colors flex items-center justify-center gap-1.5 border border-white/10"
                        title="Buka Website Publik"
                    >
                        <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        <span>Web Publik</span>
                    </a>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button 
                            type="submit" 
                            class="w-full py-2 px-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 hover:text-rose-200 text-[11px] font-semibold transition-colors flex items-center justify-center gap-1.5 border border-rose-500/20 cursor-pointer"
                            title="Keluar Sesi Admin"
                        >
                            <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>

        </aside>

        <!-- =========================================================================
             MAIN CONTENT WRAPPER WITH TOPBAR & BODY
             ========================================================================= -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
            
            <!-- TOPBAR HEADER -->
            <header class="h-16 px-4 sm:px-8 bg-[#FAF5ED]/90 dark:bg-[#091711]/90 backdrop-blur-xl border-b border-stone-300/60 dark:border-emerald-500/15 flex items-center justify-between shrink-0 z-30 transition-colors">
                
                <!-- Left: Hamburger + Page Title Breadcrumb -->
                <div class="flex items-center gap-3">
                    <button 
                        @click="sidebarOpen = true" 
                        type="button" 
                        class="p-2 rounded-xl text-stone-600 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-emerald-950/60 lg:hidden cursor-pointer"
                        aria-label="Buka Menu"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>

                <!-- Right Utility Actions: Quick Search, Dark Mode, Notifications & Profile -->
                <div class="flex items-center gap-2.5 sm:gap-3.5">
                    <!-- Dark / Light Mode Toggle Button -->
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
                        class="p-2 rounded-xl bg-white dark:bg-[#0c2218] border border-stone-300/70 dark:border-emerald-500/25 text-stone-700 dark:text-amber-300 hover:bg-stone-100 dark:hover:bg-emerald-900/50 transition-all shadow-xs cursor-pointer"
                        :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
                        :aria-label="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
                    >
                        <svg x-show="isDark" x-cloak class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg x-show="!isDark" class="w-4 h-4 text-stone-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                </div>

            </header>

            <!-- MAIN SCROLLABLE CONTENT BODY -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
                {{ $slot }}
            </main>

        </div>

        @livewireScripts
    </body>
</html>
