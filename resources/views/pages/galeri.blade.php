<?php

use App\Models\GalleryAlbum;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        $albums = GalleryAlbum::with('photos')->latest()->get();

        $categories = $albums->pluck('category')->filter()->unique()->values();

        $galleryItems = $albums->map(function ($alb) {
            $photoUrls = $alb->photos->map(fn($p) => asset($p->image_path))->toArray();
            return [
                'id' => $alb->id,
                'title' => $alb->title,
                'category' => Str::slug($alb->category),
                'categoryName' => $alb->category,
                'location' => $alb->location ?? 'Vihara Sāmaggi Gāma',
                'date' => $alb->event_date ? $alb->event_date->translatedFormat('d M Y') : 'Dokumentasi',
                'desc' => $alb->description ?? '',
                'img' => $alb->cover_image ? (str_starts_with($alb->cover_image, 'http') || str_starts_with($alb->cover_image, 'images/') || str_starts_with($alb->cover_image, 'uploads/') ? asset($alb->cover_image) : asset($alb->cover_image)) : asset('images/gallery-altar.jpg'),
                'photos' => $photoUrls,
            ];
        })->toArray();

        return $this->view([
            'galleryItems' => $galleryItems,
            'categories' => $categories,
        ])->title('Galeri Dokumentasi & Arsip Visual - Vihara Sāmaggi Gāma');
    }
};
?>

<main id="main-content" class="min-h-screen bg-[#F0E9DF] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 font-sans selection:bg-emerald-200 selection:text-emerald-950">

    <!-- ==========================================
         SECTION 1: HERO HEADER (SACRED ATMOSPHERE)
         ========================================== -->
    <header class="w-full bg-[#FAF5ED] dark:bg-[#091711] border-b border-emerald-950/10 dark:border-emerald-500/15 relative overflow-hidden pt-12 sm:pt-16 pb-16 sm:pb-20 px-6 sm:px-12 lg:px-16">
        
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

        <div class="max-w-5xl mx-auto text-center space-y-6 relative z-10">
            
            <!-- Breadcrumbs -->
            <nav class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-stone-200/60 dark:bg-emerald-950/60 border border-stone-300/40 dark:border-emerald-500/20 text-xs font-semibold text-stone-600 dark:text-stone-300" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-[#0D6E42] dark:hover:text-emerald-400 transition-colors">Beranda</a>
                <span class="text-stone-400">/</span>
                <span class="text-[#0D6E42] dark:text-emerald-300 font-bold">Galeri & Dokumentasi</span>
            </nav>

            <!-- Eyebrow Badge -->
            <div>
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Arsip Visual & Momen Luhur Vihara</span>
                </div>
            </div>

            <!-- Page Title -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-tight">
                Galeri Suasana, Puja Bakti & <br class="hidden sm:inline" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0D5B3A] via-[#8C6B1F] to-[#0D5B3A] dark:from-emerald-300 dark:via-amber-300 dark:to-emerald-300">
                    Momen Keberkahan
                </span>
            </h1>

            <!-- Narrative Paragraph -->
            <p class="max-w-3xl mx-auto text-stone-600 dark:text-stone-300 text-sm sm:text-base lg:text-lg leading-relaxed">
                Jelajahi rekaman keindahan spiritual, suasana teduh vihara, perayaan hari suci Buddhis, kehangatan persaudaraan umat, dan jejak cinta kasih dalam pelayanan sesama.
            </p>
        </div>
    </header>

    <!-- Main Content Container with Alpine.js Lightbox & Filter System -->
    <div 
        x-data="{ 
            activeTab: 'all',
            previewModal: false,
            currentIndex: 0,
            galleryItems: @js($galleryItems),
            openLightbox(index) {
                this.currentIndex = index;
                this.previewModal = true;
            },
            nextImage() {
                if (this.filteredItems.length > 0) {
                    this.currentIndex = (this.currentIndex + 1) % this.filteredItems.length;
                }
            },
            prevImage() {
                if (this.filteredItems.length > 0) {
                    this.currentIndex = (this.currentIndex - 1 + this.filteredItems.length) % this.filteredItems.length;
                }
            },
            get filteredItems() {
                if (this.activeTab === 'all') return this.galleryItems;
                return this.galleryItems.filter(item => item.category === this.activeTab);
            }
        }"
        class="max-w-[1440px] mx-auto px-6 sm:px-10 lg:px-16 py-12 sm:py-20 space-y-16"
    >

        <!-- ==========================================
             SECTION 2: FILTER TABS & CATEGORY CONTROLS
             ========================================== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-stone-300/60 dark:border-emerald-500/20">
            
            <div class="space-y-1">
                <h2 class="text-xl sm:text-2xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                    Koleksi Album Foto Dokumentasi
                </h2>
                <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                    Klik foto mana saja untuk melihat tampilan layar penuh (Lightbox) resolusi tinggi.
                </p>
            </div>

            <!-- Category Filter Pills -->
            <div class="flex flex-wrap items-center gap-2">
                <button 
                    @click="activeTab = 'all'" 
                    type="button" 
                    :class="activeTab === 'all' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 cursor-pointer"
                >
                    Semua Foto (<span x-text="galleryItems.length"></span>)
                </button>
                @foreach ($categories as $cat)
                    <button 
                        @click="activeTab = '{{ Str::slug($cat) }}'" 
                        type="button" 
                        :class="activeTab === '{{ Str::slug($cat) }}' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                        class="px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 cursor-pointer"
                    >
                        {{ $cat }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- ==========================================
             SECTION 3: MASONRY & CARD GRID SHOWCASE
             ========================================== -->
        <div x-show="filteredItems.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <template x-for="(item, index) in filteredItems" :key="item.id">
                <article 
                    @click="openLightbox(index)"
                    class="group rounded-[2rem] bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 hover:border-amber-500/50 dark:hover:border-emerald-400/50 shadow-md hover:shadow-2xl hover:shadow-emerald-950/15 dark:hover:shadow-black/50 overflow-hidden cursor-pointer transition-all duration-500 transform hover:-translate-y-1.5 flex flex-col justify-between"
                >
                    <!-- 1. FULL PHOTO CONTAINER (Clean, 100% visible) -->
                    <div class="relative aspect-[16/10] overflow-hidden bg-stone-900">
                        <img 
                            :src="item.img" 
                            :alt="item.title"
                            class="w-full h-full object-cover group-hover:scale-108 transition-transform duration-700 ease-out"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300 pointer-events-none"></div>
                        
                        <!-- Floating Category Pill -->
                        <div class="absolute top-3.5 left-3.5 z-10">
                            <span 
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#FAF5ED]/95 dark:bg-[#081610]/95 backdrop-blur-md border border-emerald-500/20 text-[11px] font-extrabold uppercase tracking-wider text-[#143D2D] dark:text-emerald-200 shadow-md"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span x-text="item.categoryName"></span>
                            </span>
                        </div>

                        <!-- Quick View Magnifier Icon -->
                        <div class="absolute top-3.5 right-3.5 z-10 w-9 h-9 rounded-full bg-black/40 backdrop-blur-md border border-white/20 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-75 group-hover:scale-100 shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                        </div>
                    </div>

                    <!-- 2. CARD CONTENT (Placed neatly beneath the photo) -->
                    <div class="p-6 space-y-3 flex flex-col justify-between grow">
                        <div class="space-y-2">
                            <!-- Location & Date Pill -->
                            <div class="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-400 font-semibold">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span x-text="item.location"></span>
                                </span>
                                <span>•</span>
                                <span x-text="item.date"></span>
                            </div>

                            <!-- Title -->
                            <h3 class="text-base sm:text-lg font-extrabold text-[#143D2D] dark:text-[#E8F3EE] leading-snug line-clamp-1 group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors" x-text="item.title"></h3>

                            <!-- Narrative Excerpt -->
                            <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed line-clamp-2" x-text="item.desc"></p>
                        </div>

                        <!-- Card Footer CTA -->
                        <div class="pt-3 border-t border-stone-200/80 dark:border-emerald-900/40 flex items-center justify-between text-xs font-bold text-[#0D6E42] dark:text-emerald-400 group-hover:translate-x-1 transition-transform">
                            <span>Lihat Foto Resolusi Penuh</span>
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>
                    </div>
                </article>
            </template>
        </div>

        <!-- Empty State (Jika Kosong) -->
        <div x-show="filteredItems.length === 0" x-cloak class="py-16 text-center space-y-3">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-stone-200/50 dark:bg-emerald-950/40 border border-stone-300/40 dark:border-emerald-500/20 flex items-center justify-center text-stone-400 dark:text-stone-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <p class="text-sm font-bold text-stone-500 dark:text-stone-400">Belum ada album dokumentasi yang dipublikasikan.</p>
        </div>

        <!-- ==========================================
             SECTION 4: ALPINE.JS LIGHTBOX MODAL
             ========================================== -->
        <template x-teleport="body">
            <div 
                x-show="previewModal" 
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 bg-black/90 backdrop-blur-xl flex items-center justify-center p-4 sm:p-8"
                @keydown.escape.window="previewModal = false"
                @keydown.arrow-right.window="nextImage()"
                @keydown.arrow-left.window="prevImage()"
            >
                <template x-if="filteredItems.length > 0 && filteredItems[currentIndex]">
                    <div 
                        @click.away="previewModal = false"
                        class="relative max-w-5xl w-full bg-[#FAF5ED] dark:bg-[#0c1813] rounded-[2.5rem] overflow-hidden border border-amber-500/30 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[90vh]"
                    >
                        <!-- Top Lightbox Bar -->
                        <div class="p-4 sm:p-6 bg-gradient-to-r from-[#143D2D] to-[#0A261B] text-white flex items-center justify-between border-b border-emerald-800/40">
                            <div class="space-y-0.5">
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 font-bold text-[10px] uppercase" x-text="filteredItems[currentIndex].categoryName"></span>
                                <h3 class="text-base sm:text-xl font-extrabold text-[#FAF5ED] line-clamp-1" x-text="filteredItems[currentIndex].title"></h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="text-xs text-stone-300 font-mono hidden sm:inline" x-text="(currentIndex + 1) + ' / ' + filteredItems.length"></span>
                                <button 
                                    @click="previewModal = false" 
                                    type="button" 
                                    class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer"
                                    aria-label="Tutup"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Main Image & Navigation Buttons -->
                        <div class="relative flex-1 bg-stone-950 flex items-center justify-center overflow-hidden min-h-[300px] max-h-[60vh]">
                            <img 
                                :src="filteredItems[currentIndex].img" 
                                :alt="filteredItems[currentIndex].title"
                                class="max-w-full max-h-[60vh] object-contain select-none"
                            />

                            <!-- Prev Button -->
                            <button 
                                @click.stop="prevImage()" 
                                type="button" 
                                class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-black/60 hover:bg-black/80 text-white border border-white/20 flex items-center justify-center transition-all hover:scale-110 cursor-pointer shadow-lg"
                                aria-label="Foto Sebelumnya"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            </button>

                            <!-- Next Button -->
                            <button 
                                @click.stop="nextImage()" 
                                type="button" 
                                class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-black/60 hover:bg-black/80 text-white border border-white/20 flex items-center justify-center transition-all hover:scale-110 cursor-pointer shadow-lg"
                                aria-label="Foto Berikutnya"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>

                        <!-- Lightbox Narrative Footer -->
                        <div class="p-6 sm:p-8 bg-[#FAF5ED] dark:bg-[#0c1813] space-y-3 overflow-y-auto">
                            <div class="flex flex-wrap items-center justify-between gap-4 text-xs font-semibold text-stone-500 dark:text-stone-400 border-b border-stone-200 dark:border-emerald-950 pb-3">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#0D6E42] dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="text-stone-800 dark:text-stone-200 font-bold" x-text="filteredItems[currentIndex].location"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span x-text="filteredItems[currentIndex].date"></span>
                                </div>
                            </div>
                            <p class="text-xs sm:text-sm text-stone-700 dark:text-stone-300 leading-relaxed" x-text="filteredItems[currentIndex].desc"></p>
                        </div>
                    </div>
                </template>
            </div>
        </template>

    </div>
</main>
