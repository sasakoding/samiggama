<?php

use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        $dbArticles = Article::where('status', 'published')
            ->orderByDesc('published_at')
            ->get();

        $articles = $dbArticles->map(function ($a) {
            return [
                'id' => $a->id,
                'slug' => $a->slug,
                'title' => $a->title,
                'category' => $a->category_slug,
                'categoryName' => $a->category,
                'date' => $a->formatted_date,
                'readTime' => $a->read_time,
                'views' => number_format($a->views_count, 0, ',', '.') . 'x',
                'author' => $a->author_name,
                'badge' => $a->category,
                'img' => $a->cover_image_url,
                'excerpt' => $a->excerpt ?: Str::words(strip_tags($a->content ?? ''), 30),
                'content' => $a->content ?? '',
            ];
        })->values();

        // Get active categories from database
        $activeCategories = ArticleCategory::where('status', 'aktif')->get();
        if ($activeCategories->count() > 0) {
            $categories = $activeCategories->map(function ($cat) use ($dbArticles) {
                return [
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'count' => $dbArticles->where('category', $cat->name)->count(),
                ];
            })->values();
        } else {
            $categories = $dbArticles->groupBy('category')->map(function ($group, $cat) {
                return [
                    'name' => $cat,
                    'slug' => Str::slug($cat),
                    'count' => $group->count(),
                ];
            })->values();
        }

        $headline = $articles->first();


        return $this->view([
            'articles' => $articles,
            'categories' => $categories,
            'headline' => $headline,
            'totalCount' => $articles->count(),
        ])->title('Berita & Kajian Dhamma - Vihara Sāmaggi Gāma');
    }
};
?>

<main id="main-content" class="min-h-screen bg-[#F0E9DF] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 font-sans selection:bg-emerald-200 selection:text-emerald-950">

    <!-- ==========================================
         SECTION 1: HERO HEADER WITH TOPOGRAPHY
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
                <span class="text-[#0D6E42] dark:text-emerald-300 font-bold">Kabar & Berita Dhamma</span>
            </nav>

            <!-- Eyebrow Pill Badge -->
            <div>
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Berita Resmi, Liputan Kegiatan & Mutiara Dhamma</span>
                </div>
            </div>

            <!-- Page Title -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-tight">
                Berita Vihara Sāmaggi Gāma & <br class="hidden sm:inline" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0D5B3A] via-[#8C6B1F] to-[#0D5B3A] dark:from-emerald-300 dark:via-amber-300 dark:to-emerald-300">
                    Kajian Kebijaksanaan
                </span>
            </h1>

            <!-- Narrative Paragraph -->
            <p class="max-w-3xl mx-auto text-stone-600 dark:text-stone-300 text-sm sm:text-base lg:text-lg leading-relaxed">
                Kumpulan kabar terkini seputar aktivitas sosial umat, pengumuman resmi yayasan, artikel renungan batin, serta panduan praktis ajaran Sang Buddha untuk menenteramkan kehidupan sehari-hari.
            </p>
        </div>
    </header>

    <!-- Main Content Container with Alpine.js News Engine -->
    <div 
        x-data="{ 
            activeCategory: 'all',
            searchQuery: '',
            articles: @js($articles),
            matchesFilter(item) {
                const matchCat = (this.activeCategory === 'all') || (item.category === this.activeCategory);
                const matchQuery = this.searchQuery === '' || 
                    item.title.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                    item.excerpt.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    item.author.toLowerCase().includes(this.searchQuery.toLowerCase());
                return matchCat && matchQuery;
            }
        }"
        class="max-w-[1440px] mx-auto px-6 sm:px-10 lg:px-16 py-12 sm:py-20 space-y-16"
    >

        <!-- ==========================================
             SECTION 2: FEATURED HEADLINE ARTICLE (HERO CARD)
             ========================================== -->
        @if ($headline)
            <section aria-labelledby="headline-news-heading" class="relative rounded-[2.5rem] overflow-hidden bg-[#FAF5ED] dark:bg-[#0b1c15] border border-amber-500/30 dark:border-emerald-500/25 shadow-xl reveal-on-scroll">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-0 items-stretch">
                    
                    <!-- Left Photo Thumbnail -->
                    <a href="{{ route('berita.detail', ['slug' => $headline['slug']]) }}" class="lg:col-span-6 relative aspect-[16/10] lg:aspect-auto min-h-[320px] lg:min-h-[440px] overflow-hidden bg-stone-900 block group">
                        <img 
                            src="{{ $headline['img'] }}" 
                            alt="{{ $headline['title'] }}" 
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t lg:bg-gradient-to-r from-black/80 via-black/30 to-transparent pointer-events-none"></div>
                        
                        <div class="absolute top-5 left-5 z-10">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-amber-500 text-stone-950 font-black text-xs shadow-lg uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span>Berita Terkini</span>
                            </span>
                        </div>
                    </a>

                    <!-- Right Article Details -->
                    <div class="lg:col-span-6 p-8 sm:p-10 lg:p-12 flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-3 text-xs text-stone-500 dark:text-stone-400">
                                <span class="px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-[#0D6E42] dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-800/40">
                                    {{ $headline['categoryName'] }}
                                </span>
                                <span>•</span>
                                <span>{{ $headline['date'] }}</span>
                                <span>•</span>
                                <span>Oleh {{ $headline['author'] }}</span>
                            </div>

                            <h2 id="headline-news-heading" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-snug">
                                <a href="{{ route('berita.detail', ['slug' => $headline['slug']]) }}" class="hover:text-[#0D6E42] dark:hover:text-emerald-400 transition-colors">
                                    {{ $headline['title'] }}
                                </a>
                            </h2>

                            <p class="text-stone-600 dark:text-stone-300 text-sm sm:text-base leading-relaxed line-clamp-3">
                                {{ $headline['excerpt'] }}
                            </p>
                        </div>

                        <!-- CTA Action -->
                        <div class="pt-4 border-t border-stone-200 dark:border-emerald-900/40 flex items-center justify-between">
                            <a 
                                href="{{ route('berita.detail', ['slug' => $headline['slug']]) }}"
                                class="inline-flex items-center gap-2 bg-[#0D5B3A] hover:bg-[#09472D] dark:bg-emerald-600 dark:hover:bg-emerald-500 text-white font-bold py-3 px-6 rounded-xl text-xs sm:text-sm shadow-md transition-all duration-200 transform hover:-translate-y-0.5"
                            >
                                <span>Baca Berita Lengkap</span>
                                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        <!-- ==========================================
             SECTION 3: LIVE SEARCH & CATEGORY FILTER TABS
             ========================================== -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-stone-300/60 dark:border-emerald-500/20">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Arsip Berita & Kajian</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                    Jelajahi Berita & Tulisan Dhamma
                </h2>
                <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm">
                    Gunakan filter kategori atau kotak pencarian untuk menemukan tulisan yang Anda butuhkan.
                </p>
            </div>

            <!-- Live Search Bar -->
            <div class="relative w-full md:w-80">
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari judul berita, topik, penulis..." 
                    class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 text-xs text-stone-800 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-emerald-600 dark:focus:border-emerald-400 transition-colors shadow-inner"
                />
                <svg class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>

        <!-- Filter Category Tabs -->
        <div class="flex flex-wrap items-center gap-2">
            <button 
                @click="activeCategory = 'all'" 
                type="button" 
                :class="activeCategory === 'all' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                class="px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 cursor-pointer"
            >
                Semua Berita ({{ $totalCount }})
            </button>
            @foreach ($categories as $cat)
                <button 
                    @click="activeCategory = '{{ $cat['slug'] }}'" 
                    type="button" 
                    :class="activeCategory === '{{ $cat['slug'] }}' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 cursor-pointer"
                >
                    {{ $cat['name'] }} ({{ $cat['count'] }})
                </button>
            @endforeach
        </div>

        <!-- ==========================================
             SECTION 4: ARTICLES GRID
             ========================================== -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <template x-for="item in articles" :key="item.id">
                <article 
                    x-show="matchesFilter(item)" 
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-[2rem] border border-stone-300/60 dark:border-emerald-500/20 hover:border-emerald-500/60 shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden flex flex-col justify-between group card-shine hover:-translate-y-1.5"
                >
                    <div class="space-y-4">
                        <!-- Photo Thumbnail -->
                        <a :href="'{{ route('berita') }}/' + item.slug" class="block relative aspect-[16/10] overflow-hidden bg-stone-200 dark:bg-stone-900">
                            <img 
                                :src="item.img" 
                                :alt="item.title" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20 pointer-events-none"></div>

                            <!-- Category Badge -->
                            <div class="absolute top-3.5 left-3.5 z-10">
                                <span class="px-3 py-1 rounded-full bg-[#FAF5ED]/95 dark:bg-[#081610]/95 backdrop-blur-md text-[#0D6E42] dark:text-emerald-300 text-[11px] font-extrabold border border-emerald-500/20 shadow-sm" x-text="item.categoryName"></span>
                            </div>

                            <!-- Read Time Badge -->
                            <div class="absolute top-3.5 right-3.5 z-10">
                                <span class="px-3 py-1 rounded-full bg-black/60 backdrop-blur-md text-amber-300 text-[10.5px] font-bold border border-white/20" x-text="item.readTime"></span>
                            </div>
                        </a>

                        <!-- Article Content Info -->
                        <div class="p-6 pt-0 space-y-3">
                            <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                                <span x-text="item.date"></span>
                                <span>•</span>
                                <span x-text="'Oleh ' + item.author"></span>
                            </div>

                            <h3 class="text-lg sm:text-xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] leading-snug group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors line-clamp-2">
                                <a :href="'{{ route('berita') }}/' + item.slug" x-text="item.title"></a>
                            </h3>

                            <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm leading-relaxed line-clamp-3" x-text="item.excerpt"></p>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="p-6 pt-0">
                        <div class="pt-4 border-t border-stone-200 dark:border-emerald-900/40 flex items-center justify-between">
                            <a 
                                :href="'{{ route('berita') }}/' + item.slug"
                                class="font-bold text-xs sm:text-sm text-[#0D5B3A] dark:text-emerald-400 hover:text-amber-600 dark:hover:text-amber-300 transition-colors flex items-center justify-between gap-1.5 w-full"
                            >
                                <span>Baca Selengkapnya</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                </article>
            </template>
        </div>

    </div>
</main>
