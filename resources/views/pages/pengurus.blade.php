<?php

use App\Models\Officer;
use App\Models\Setting;
use Livewire\Component;

new class extends Component
{
    public string $searchQuery = '';
    public string $selectedDivision = 'all';

    public function setDivision(string $division): void
    {
        $this->selectedDivision = $division;
    }

    public function resetFilters(): void
    {
        $this->searchQuery = '';
        $this->selectedDivision = 'all';
    }

    public function render()
    {
        $query = Officer::where('status', 'aktif')->orderBy('sort_order', 'asc');

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(title) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(division) LIKE ?', [$search]);
            });
        }

        if ($this->selectedDivision !== 'all') {
            $query->where('division', $this->selectedDivision);
        }

        $officers = $query->get();

        // Get all unique divisions for filter tabs
        $divisions = Officer::where('status', 'aktif')
            ->distinct()
            ->orderBy('sort_order', 'asc')
            ->pluck('division')
            ->filter()
            ->values();

        // Grouped lists for default overview
        $pembinaList = Officer::where('status', 'aktif')
            ->where(function ($q) {
                $q->where('division', 'like', '%Pembina%')
                  ->orWhere('title', 'like', '%Pembina%')
                  ->orWhere('title', 'like', '%Sangha%');
            })
            ->orderBy('sort_order', 'asc')
            ->get();

        $pengurusHarianList = Officer::where('status', 'aktif')
            ->where(function ($q) {
                $q->where('division', 'like', '%Pengurus%')
                  ->orWhere('division', 'like', '%Harian%');
            })
            ->whereNotIn('id', $pembinaList->pluck('id'))
            ->orderBy('sort_order', 'asc')
            ->get();

        $lainnyaList = Officer::where('status', 'aktif')
            ->whereNotIn('id', $pembinaList->pluck('id')->merge($pengurusHarianList->pluck('id')))
            ->orderBy('sort_order', 'asc')
            ->get();

        $foundationName = Setting::get('foundation_name', 'Yayasan Vihara Sāmaggi Gāma');
        $foundationPhone = Setting::get('foundation_phone', '+62 812-3456-7890');

        return $this->view([
            'officers' => $officers,
            'divisions' => $divisions,
            'pembinaList' => $pembinaList,
            'pengurusHarianList' => $pengurusHarianList,
            'lainnyaList' => $lainnyaList,
            'foundationName' => $foundationName,
            'foundationPhone' => $foundationPhone,
        ])->title('Susunan Pengurus & Dewan Pembina - Vihara Sāmaggi Gāma');
    }
};
?>

<main id="main-content" class="min-h-screen bg-[#F0E9DF] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 font-sans selection:bg-emerald-200 selection:text-emerald-950 w-full max-w-full overflow-x-hidden">

    <!-- ==========================================
         SECTION 1: HERO HEADER WITH TOPOGRAPHY
         ========================================== -->
    <header class="w-full max-w-full bg-[#FAF5ED] dark:bg-[#091711] border-b border-emerald-950/10 dark:border-emerald-500/15 relative overflow-hidden pt-12 sm:pt-16 pb-16 sm:pb-20 px-6 sm:px-12 lg:px-16">
        
        <!-- Background Topography Lines -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-15 dark:opacity-10 stroke-emerald-900 dark:stroke-emerald-400 fill-none" viewBox="0 0 1440 600" preserveAspectRatio="none" aria-hidden="true">
            <path d="M-50,120 C300,40 450,220 800,90 C1100,0 1300,140 1500,60" stroke-width="1.2" />
            <path d="M-50,180 C280,110 420,280 780,160 C1120,60 1280,210 1500,130" stroke-width="1.2" />
            <path d="M-50,250 C250,180 390,340 760,220 C1100,130 1260,280 1500,190" stroke-width="1.2" />
            <path d="M-50,320 C220,240 360,400 740,290 C1080,190 1240,340 1500,260" stroke-width="1.2" />
        </svg>

        <!-- Ambient Glow Aura -->
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-amber-500/10 dark:bg-emerald-500/10 rounded-full blur-3xl pointer-events-none animate-pulse-glow"></div>
        <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-emerald-600/10 dark:bg-amber-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-4xl mx-auto text-center space-y-6 relative z-10">
            
            <!-- Breadcrumbs -->
            <nav class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-stone-200/60 dark:bg-emerald-950/60 border border-stone-300/40 dark:border-emerald-500/20 text-xs font-semibold text-stone-600 dark:text-stone-300" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#0D6E42] dark:hover:text-emerald-400 transition-colors">Beranda</a>
                <span class="text-stone-400">/</span>
                <a href="{{ route('tentang-kami') }}" class="hover:text-[#0D6E42] dark:hover:text-emerald-400 transition-colors">Tentang Kami</a>
                <span class="text-stone-400">/</span>
                <span class="text-[#0D6E42] dark:text-emerald-300 font-bold">Pengurus & Pembina</span>
            </nav>

            <!-- Eyebrow Pill Badge -->
            <div>
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Struktur Organisasi & Pelayanan Seva</span>
                </div>
            </div>

            <!-- Page Title -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-tight">
                Susunan Pengurus & Pembina
            </h1>

            <!-- Narrative Subtitle -->
            <p class="max-w-2xl mx-auto text-stone-600 dark:text-stone-300 text-sm sm:text-base leading-relaxed">
                Mengenal para Bhikkhu Sangha Pembina, Penasihat, dan jajaran Pengurus Harian yang berdedikasi mengabdi dengan ketulusan hati demi kemajuan Dhamma dan pelayanan umat Vihara Sāmaggi Gāma.
            </p>
        </div>
    </header>

    <!-- Main Container -->
    <div class="max-w-[1440px] w-full mx-auto px-6 sm:px-10 lg:px-16 py-12 sm:py-16 space-y-14 overflow-hidden">

        <!-- ==========================================
             SECTION 2: FILTER TABS & SEARCH BAR
             ========================================== -->
        <section aria-label="Filter dan Pencarian Pengurus" class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-4 sm:p-5 rounded-3xl bg-[#FAF5ED] dark:bg-[#0c1813] border border-stone-300/70 dark:border-emerald-500/20 shadow-md">
                
                <!-- Filter Chips by Division -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 custom-scrollbar">
                    <button 
                        wire:click="setDivision('all')" 
                        type="button" 
                        class="px-4 py-2 rounded-full text-xs font-extrabold whitespace-nowrap transition-all cursor-pointer {{ $selectedDivision === 'all' ? 'bg-[#0D5B3A] text-amber-200 shadow-md border border-amber-400/30' : 'bg-white dark:bg-emerald-950/60 text-stone-700 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-emerald-900/60 border border-stone-200 dark:border-emerald-500/20' }}"
                    >
                        Semua Divisi ({{ $officers->count() }})
                    </button>
                    @foreach ($divisions as $div)
                        <button 
                            wire:click="setDivision('{{ $div }}')" 
                            type="button" 
                            class="px-4 py-2 rounded-full text-xs font-extrabold whitespace-nowrap transition-all cursor-pointer {{ $selectedDivision === $div ? 'bg-[#0D5B3A] text-amber-200 shadow-md border border-amber-400/30' : 'bg-white dark:bg-emerald-950/60 text-stone-700 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-emerald-900/60 border border-stone-200 dark:border-emerald-500/20' }}"
                        >
                            {{ $div }}
                        </button>
                    @endforeach
                </div>

                <!-- Search Input -->
                <div class="relative w-full md:w-72 shrink-0">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input 
                        wire:model.live.debounce.300ms="searchQuery" 
                        type="text" 
                        placeholder="Cari nama atau jabatan..." 
                        class="w-full pl-10 pr-9 py-2 rounded-full bg-white dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 transition-all font-medium"
                    />
                    @if ($searchQuery)
                        <button 
                            wire:click="$set('searchQuery', '')" 
                            type="button" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>

            </div>
        </section>

        <!-- ==========================================
             SECTION 3: PENGURUS CARDS DISPLAY
             ========================================== -->
        @if ($selectedDivision === 'all' && empty($searchQuery))
            
            <!-- VIEW A: STRUCTURED OVERVIEW BY DIVISION -->
            <div class="space-y-16">
                
                <!-- 1. DEWAN PEMBINA & SANGHA NAYAKA (HIGHLIGHTED VIP) -->
                @if ($pembinaList->isNotEmpty())
                    <section aria-labelledby="dewan-pembina-heading" class="space-y-6">
                        <div class="space-y-1">
                            <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-amber-700 dark:text-amber-400">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                <span>Bimbingan Spiritual & Sangha</span>
                            </div>
                            <h2 id="dewan-pembina-heading" class="text-2xl sm:text-3xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                                Dewan Pembina & Sangha Nayaka
                            </h2>
                            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                                Para Y.M. Bhikkhu Sangha dan tetua yang membimbing arah spiritual serta menjaga keluhuran Buddha Dhamma di Vihara Sāmaggi Gāma.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                            @foreach ($pembinaList as $officer)
                                @php
                                    $photoUrl = null;
                                    if (!empty($officer->photo)) {
                                        if (file_exists(public_path($officer->photo))) {
                                            $photoUrl = asset($officer->photo);
                                        } elseif (file_exists(public_path('uploads/officers/' . basename($officer->photo)))) {
                                            $photoUrl = asset('uploads/officers/' . basename($officer->photo));
                                        }
                                    }
                                    if (!$photoUrl && (str_contains(strtolower($officer->title), 'bhikkhu') || str_contains(strtolower($officer->name), 'bhante'))) {
                                        $photoUrl = asset('images/bhikkhu-thera.jpg');
                                    }
                                @endphp

                                <div class="group relative rounded-3xl bg-[#FAF5ED] dark:bg-[#0c1813] border-2 border-amber-500/30 dark:border-amber-400/25 shadow-xl hover:shadow-2xl hover:border-amber-500/60 transition-all duration-300 overflow-hidden flex flex-col justify-between card-shine transform hover:-translate-y-1">
                                    
                                    <!-- Photo Aspect Ratio -->
                                    <div class="relative aspect-[4/4.5] overflow-hidden bg-stone-900">
                                        @if ($photoUrl)
                                            <img 
                                                src="{{ $photoUrl }}" 
                                                alt="{{ $officer->name }}" 
                                                class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-700 ease-out"
                                                loading="lazy"
                                            />
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-amber-500/20 via-emerald-900/30 to-stone-900 text-amber-300 font-extrabold text-4xl">
                                                {{ strtoupper(substr($officer->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        
                                        <!-- Gradient Overlay -->
                                        <div class="absolute inset-0 bg-gradient-to-t from-[#0B2117] via-transparent to-transparent pointer-events-none opacity-80"></div>
                                        
                                        <!-- Division Pill Badge -->
                                        <div class="absolute top-4 left-4 z-10">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-500/90 text-stone-950 border border-amber-300 shadow-md">
                                                <span class="w-1.5 h-1.5 rounded-full bg-stone-950"></span>
                                                <span>{{ $officer->division }}</span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Content Info -->
                                    <div class="p-6 space-y-2 bg-[#FAF5ED] dark:bg-[#0c1813] relative z-10 flex-1 flex flex-col justify-between">
                                        <div class="space-y-1">
                                            <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 block">
                                                {{ $officer->title }}
                                            </span>
                                            <h3 class="text-lg sm:text-xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-snug group-hover:text-[#0D6E42] dark:group-hover:text-emerald-300 transition-colors">
                                                {{ $officer->name }}
                                            </h3>
                                        </div>
                                        <div class="pt-3 border-t border-stone-200/80 dark:border-emerald-950/60 flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span>Pengabdi Buddha Dhamma</span>
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- 2. PENGURUS HARIAN & OPERASIONAL YAYASAN -->
                @if ($pengurusHarianList->isNotEmpty())
                    <section aria-labelledby="pengurus-harian-heading" class="space-y-6 pt-6 border-t border-stone-300/60 dark:border-emerald-500/20">
                        <div class="space-y-1">
                            <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>Manajemen & Operasional Pelayanan</span>
                            </div>
                            <h2 id="pengurus-harian-heading" class="text-2xl sm:text-3xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                                Pengurus Harian Yayasan
                            </h2>
                            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                                Jajaran kepengurusan harian yang bertugas mengelola operasional vihara, pelayanan ibadah, transparansi keuangan, dan kegiatan umat.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach ($pengurusHarianList as $officer)
                                @php
                                    $photoUrl = null;
                                    if (!empty($officer->photo)) {
                                        if (file_exists(public_path($officer->photo))) {
                                            $photoUrl = asset($officer->photo);
                                        } elseif (file_exists(public_path('uploads/officers/' . basename($officer->photo)))) {
                                            $photoUrl = asset('uploads/officers/' . basename($officer->photo));
                                        }
                                    }
                                @endphp

                                <div class="group relative rounded-3xl bg-[#FAF5ED] dark:bg-[#0c1813] border border-stone-300/80 dark:border-emerald-500/20 shadow-md hover:shadow-xl hover:border-emerald-500/40 transition-all duration-300 overflow-hidden flex flex-col justify-between card-shine transform hover:-translate-y-1">
                                    
                                    <div class="relative aspect-[4/4.2] overflow-hidden bg-stone-800">
                                        @if ($photoUrl)
                                            <img 
                                                src="{{ $photoUrl }}" 
                                                alt="{{ $officer->name }}" 
                                                class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-700 ease-out"
                                                loading="lazy"
                                            />
                                        @else
                                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-900/30 via-stone-800 to-[#071710] text-emerald-300 font-extrabold text-3xl">
                                                {{ strtoupper(substr($officer->name, 0, 2)) }}
                                            </div>
                                        @endif

                                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent pointer-events-none opacity-70"></div>
                                        
                                        <div class="absolute top-3.5 left-3.5 z-10">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-[#FAF5ED]/90 dark:bg-[#0c1813]/90 text-[#0D6E42] dark:text-emerald-300 border border-emerald-500/25 shadow-xs">
                                                <span>{{ $officer->division }}</span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="p-5 space-y-2 flex-1 flex flex-col justify-between">
                                        <div class="space-y-1">
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 block">
                                                {{ $officer->title }}
                                            </span>
                                            <h3 class="text-base font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-snug group-hover:text-[#0D6E42] dark:group-hover:text-emerald-300 transition-colors">
                                                {{ $officer->name }}
                                            </h3>
                                        </div>
                                        <div class="pt-2.5 border-t border-stone-200/70 dark:border-emerald-950/60 text-[11px] text-stone-400">
                                            Vihara Sāmaggi Gāma
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- 3. SEKSI / BIDANG PELAYANAN LAINNYA (JIKA ADA) -->
                @if ($lainnyaList->isNotEmpty())
                    <section aria-labelledby="seksi-lainnya-heading" class="space-y-6 pt-6 border-t border-stone-300/60 dark:border-emerald-500/20">
                        <div class="space-y-1">
                            <h2 id="seksi-lainnya-heading" class="text-2xl sm:text-3xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                                Seksi & Bidang Pelayanan
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            @foreach ($lainnyaList as $officer)
                                @php
                                    $photoUrl = null;
                                    if (!empty($officer->photo)) {
                                        if (file_exists(public_path($officer->photo))) {
                                            $photoUrl = asset($officer->photo);
                                        } elseif (file_exists(public_path('uploads/officers/' . basename($officer->photo)))) {
                                            $photoUrl = asset('uploads/officers/' . basename($officer->photo));
                                        }
                                    }
                                @endphp

                                <div class="group rounded-3xl bg-[#FAF5ED] dark:bg-[#0c1813] border border-stone-300/80 dark:border-emerald-500/20 shadow-md p-4 flex items-center gap-4 transition-all hover:shadow-lg hover:border-emerald-500/40">
                                    <div class="w-14 h-14 rounded-2xl overflow-hidden shrink-0 bg-stone-800">
                                        @if ($photoUrl)
                                            <img src="{{ $photoUrl }}" alt="{{ $officer->name }}" class="w-full h-full object-cover" loading="lazy">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center font-bold text-amber-300 text-sm bg-stone-800">
                                                {{ strtoupper(substr($officer->name, 0, 2)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 space-y-0.5">
                                        <span class="text-[10.5px] font-bold text-amber-600 dark:text-amber-400 block truncate">{{ $officer->title }}</span>
                                        <h3 class="text-xs sm:text-sm font-extrabold text-stone-900 dark:text-stone-100 truncate">{{ $officer->name }}</h3>
                                        <span class="text-[10px] text-stone-400 block truncate">{{ $officer->division }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

            </div>

        @else
            
            <!-- VIEW B: FILTERED / SEARCH RESULTS GRID -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-[#143D2D] dark:text-[#E8F3EE]">
                            Hasil Pencarian Pengurus
                        </h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            Menampilkan {{ $officers->count() }} data pengurus sesuai kriteria.
                        </p>
                    </div>
                    <button 
                        wire:click="resetFilters" 
                        type="button" 
                        class="text-xs font-bold text-[#0D5B3A] dark:text-emerald-400 hover:underline cursor-pointer"
                    >
                        Reset Filter
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse ($officers as $officer)
                        @php
                            $photoUrl = null;
                            if (!empty($officer->photo)) {
                                if (file_exists(public_path($officer->photo))) {
                                    $photoUrl = asset($officer->photo);
                                } elseif (file_exists(public_path('uploads/officers/' . basename($officer->photo)))) {
                                    $photoUrl = asset('uploads/officers/' . basename($officer->photo));
                                }
                            }
                        @endphp

                        <div class="group relative rounded-3xl bg-[#FAF5ED] dark:bg-[#0c1813] border border-stone-300/80 dark:border-emerald-500/20 shadow-md hover:shadow-xl hover:border-emerald-500/40 transition-all duration-300 overflow-hidden flex flex-col justify-between card-shine transform hover:-translate-y-1">
                            
                            <div class="relative aspect-[4/4.2] overflow-hidden bg-stone-800">
                                @if ($photoUrl)
                                    <img 
                                        src="{{ $photoUrl }}" 
                                        alt="{{ $officer->name }}" 
                                        class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-700 ease-out"
                                        loading="lazy"
                                    />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-900/30 via-stone-800 to-[#071710] text-emerald-300 font-extrabold text-3xl">
                                        {{ strtoupper(substr($officer->name, 0, 2)) }}
                                    </div>
                                @endif

                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent pointer-events-none opacity-70"></div>
                                
                                <div class="absolute top-3.5 left-3.5 z-10">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-[#FAF5ED]/90 dark:bg-[#0c1813]/90 text-[#0D6E42] dark:text-emerald-300 border border-emerald-500/25 shadow-xs">
                                        <span>{{ $officer->division }}</span>
                                    </span>
                                </div>
                            </div>

                            <div class="p-5 space-y-2 flex-1 flex flex-col justify-between">
                                <div class="space-y-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 block">
                                        {{ $officer->title }}
                                    </span>
                                    <h3 class="text-base font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-snug group-hover:text-[#0D6E42] dark:group-hover:text-emerald-300 transition-colors">
                                        {{ $officer->name }}
                                    </h3>
                                </div>
                                <div class="pt-2.5 border-t border-stone-200/70 dark:border-emerald-950/60 text-[11px] text-stone-400">
                                    Vihara Sāmaggi Gāma
                                </div>
                            </div>

                        </div>
                    @empty
                        <div class="col-span-full py-16 text-center space-y-4 rounded-3xl bg-[#FAF5ED] dark:bg-[#0c1813] border border-dashed border-stone-300 dark:border-emerald-950">
                            <div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-700 dark:text-amber-400 flex items-center justify-center mx-auto text-xl font-bold">
                                🔍
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-extrabold text-stone-800 dark:text-stone-200 text-base">
                                    Tidak ada data pengurus yang sesuai
                                </h3>
                                <p class="text-xs text-stone-400 max-w-sm mx-auto">
                                    Coba gunakan kata kunci pencarian lain atau pilih kategori divisi berbeda.
                                </p>
                            </div>
                            <button 
                                wire:click="resetFilters" 
                                type="button" 
                                class="px-5 py-2 rounded-full bg-[#0D5B3A] hover:bg-emerald-800 text-white font-bold text-xs shadow-md transition-all cursor-pointer"
                            >
                                Tampilkan Semua Pengurus
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>

        @endif

    </div>

</main>
