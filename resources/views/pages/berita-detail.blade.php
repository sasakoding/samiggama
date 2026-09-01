<?php

use App\Models\Article;
use App\Models\RoutineSchedule;
use App\Models\Schedule;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public $slug;
    public $article;
    public $popularPosts = [];
    public $relatedPosts = [];
    public $prevPost = null;
    public $nextPost = null;
    public $upcomingEvent = null;

    public function mount($slug = null)
    {
        $this->slug = $slug;

        // 1. Fetch current article
        $art = null;
        if ($this->slug) {
            $art = Article::where('slug', $this->slug)->where('status', 'published')->first();
        }

        if (!$art) {
            $art = Article::where('status', 'published')->orderByDesc('published_at')->first();
        }

        if (!$art) {
            abort(404, 'Berita tidak ditemukan.');
        }

        // Increment views count silently
        $art->increment('views_count');

        $this->article = [
            'id' => $art->id,
            'slug' => $art->slug,
            'title' => $art->title,
            'category' => $art->category_slug,
            'categoryName' => $art->category,
            'date' => $art->formatted_date,
            'readTime' => $art->read_time,
            'views' => number_format($art->views_count, 0, ',', '.') . 'x',
            'author' => $art->author_name,
            'authorRole' => 'Kontributor Resmi Vihara Sāmaggi Gāma',
            'badge' => $art->category,
            'img' => $art->cover_image_url,
            'photoCredit' => 'Dokumentasi Resmi Vihara Sāmaggi Gāma',
            'excerpt' => $art->excerpt ?: Str::words(strip_tags($art->content ?? ''), 35),
            'content' => $art->content ?? '',
        ];

        // 2. Fetch popular posts (ranked by views count)
        $this->popularPosts = Article::where('status', 'published')
            ->where('id', '!=', $art->id)
            ->orderByDesc('views_count')
            ->limit(4)
            ->get()
            ->map(function ($p) {
                return [
                    'slug' => $p->slug,
                    'title' => $p->title,
                    'categoryName' => $p->category,
                    'date' => $p->formatted_date,
                    'readTime' => $p->read_time,
                    'views' => number_format($p->views_count, 0, ',', '.') . 'x',
                    'img' => $p->cover_image_url,
                ];
            })
            ->toArray();

        // 3. Fetch related posts (same category, or latest if few)
        $this->relatedPosts = Article::where('status', 'published')
            ->where('id', '!=', $art->id)
            ->where('category', $art->category)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get()
            ->map(function ($p) {
                return [
                    'slug' => $p->slug,
                    'title' => $p->title,
                    'categoryName' => $p->category,
                    'date' => $p->formatted_date,
                    'readTime' => $p->read_time,
                    'views' => number_format($p->views_count, 0, ',', '.') . 'x',
                    'img' => $p->cover_image_url,
                    'excerpt' => $p->excerpt ?: Str::words(strip_tags($p->content ?? ''), 25),
                ];
            })
            ->toArray();

        if (count($this->relatedPosts) < 3) {
            $existingIds = array_merge([$art->id], array_column($this->relatedPosts, 'slug'));
            $extraPosts = Article::where('status', 'published')
                ->where('id', '!=', $art->id)
                ->whereNotIn('slug', $existingIds)
                ->orderByDesc('published_at')
                ->limit(3 - count($this->relatedPosts))
                ->get()
                ->map(function ($p) {
                    return [
                        'slug' => $p->slug,
                        'title' => $p->title,
                        'categoryName' => $p->category,
                        'date' => $p->formatted_date,
                        'readTime' => $p->read_time,
                        'views' => number_format($p->views_count, 0, ',', '.') . 'x',
                        'img' => $p->cover_image_url,
                        'excerpt' => $p->excerpt ?: Str::words(strip_tags($p->content ?? ''), 25),
                    ];
                })
                ->toArray();
            $this->relatedPosts = array_merge($this->relatedPosts, $extraPosts);
        }

        // 4. Prev & Next Posts
        $prev = Article::where('status', 'published')->where('id', '<', $art->id)->orderByDesc('id')->first();
        if ($prev) {
            $this->prevPost = [
                'slug' => $prev->slug,
                'title' => $prev->title,
                'img' => $prev->cover_image_url,
            ];
        }

        $next = Article::where('status', 'published')->where('id', '>', $art->id)->orderBy('id')->first();
        if ($next) {
            $this->nextPost = [
                'slug' => $next->slug,
                'title' => $next->title,
                'img' => $next->cover_image_url,
            ];
        }

        // 5. Dynamic Upcoming Event / Schedule for sidebar widget
        $nextSpecial = Schedule::where('status', 'aktif')
            ->whereNotNull('event_date')
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date', 'asc')
            ->first();

        $nextRoutine = RoutineSchedule::where('status', 'aktif')
            ->whereNotNull('event_date')
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date', 'asc')
            ->first();

        if ($nextSpecial && $nextRoutine) {
            $this->upcomingEvent = ($nextSpecial->event_date <= $nextRoutine->event_date) ? [
                'title' => $nextSpecial->title,
                'category' => $nextSpecial->category ?: 'Agenda Terdekat',
                'date' => $nextSpecial->event_date->translatedFormat('d M Y'),
                'time' => substr($nextSpecial->start_time, 0, 5) . ($nextSpecial->end_time ? ' - ' . substr($nextSpecial->end_time, 0, 5) : ''),
                'location' => $nextSpecial->location ?: 'Dhammasala Utama',
                'speaker_or_leader' => $nextSpecial->leader,
            ] : [
                'title' => $nextRoutine->activity_name,
                'category' => 'Kebaktian Rutin',
                'date' => $nextRoutine->event_date->translatedFormat('d M Y'),
                'time' => $nextRoutine->time_range ?: '08:30 - 10:30',
                'location' => 'Dhammasala Utama',
                'speaker_or_leader' => 'Penceramah: ' . $nextRoutine->speaker . ($nextRoutine->topic ? ' (“' . $nextRoutine->topic . '”)' : ''),
            ];
        } elseif ($nextSpecial) {
            $this->upcomingEvent = [
                'title' => $nextSpecial->title,
                'category' => $nextSpecial->category ?: 'Agenda Terdekat',
                'date' => $nextSpecial->event_date->translatedFormat('d M Y'),
                'time' => substr($nextSpecial->start_time, 0, 5) . ($nextSpecial->end_time ? ' - ' . substr($nextSpecial->end_time, 0, 5) : ''),
                'location' => $nextSpecial->location ?: 'Dhammasala Utama',
                'speaker_or_leader' => $nextSpecial->leader,
            ];
        } elseif ($nextRoutine) {
            $this->upcomingEvent = [
                'title' => $nextRoutine->activity_name,
                'category' => 'Kebaktian Rutin',
                'date' => $nextRoutine->event_date->translatedFormat('d M Y'),
                'time' => $nextRoutine->time_range ?: '08:30 - 10:30',
                'location' => 'Dhammasala Utama',
                'speaker_or_leader' => 'Penceramah: ' . $nextRoutine->speaker . ($nextRoutine->topic ? ' (“' . $nextRoutine->topic . '”)' : ''),
            ];
        } else {
            $latestSchedule = Schedule::where('status', 'aktif')->orderByDesc('event_date')->first();
            if ($latestSchedule) {
                $this->upcomingEvent = [
                    'title' => $latestSchedule->title,
                    'category' => $latestSchedule->category ?: 'Agenda Terdekat',
                    'date' => $latestSchedule->event_date ? $latestSchedule->event_date->translatedFormat('d M Y') : 'Setiap Minggu',
                    'time' => substr($latestSchedule->start_time, 0, 5) . ($latestSchedule->end_time ? ' - ' . substr($latestSchedule->end_time, 0, 5) : ''),
                    'location' => $latestSchedule->location ?: 'Dhammasala Utama',
                    'speaker_or_leader' => $latestSchedule->leader,
                ];
            }
        }
    }

    public function render()
    {
        return $this->view()->title(($this->article['title'] ?? 'Berita') . ' - Vihara Sāmaggi Gāma');
    }
};
?>

<main 
    id="main-content" 
    x-data="{
        copiedLink: false,
        fontSize: 'normal', // 'normal' | 'large'
        copyCurrentUrl() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href);
                this.copiedLink = true;
                setTimeout(() => this.copiedLink = false, 3000);
            }
        }
    }"
    x-init="window.addEventListener('scroll', () => updateProgress())"
    class="min-h-screen bg-[#F7F2EA] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 font-sans selection:bg-emerald-200 selection:text-emerald-950 transition-colors duration-300"
>
    <!-- ==========================================
         SECTION 1: EDITORIAL HEADER & HERO ARTICLE
         ========================================== -->
    <header class="w-full bg-[#FAF5ED] dark:bg-[#091711] border-b border-stone-300/50 dark:border-emerald-500/15 relative overflow-hidden pt-10 sm:pt-16 pb-12 sm:pb-20 px-6 sm:px-10 lg:px-16">
        
        <!-- Subtle Zen Waves Background Accent -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-20 dark:opacity-10 stroke-emerald-900 dark:stroke-emerald-400 fill-none" viewBox="0 0 1440 600" preserveAspectRatio="none" aria-hidden="true">
            <path d="M-50,140 C300,60 450,240 800,110 C1100,20 1300,160 1500,80" stroke-width="1.2" />
            <path d="M-50,200 C280,130 420,300 780,180 C1120,80 1280,230 1500,150" stroke-width="1.2" />
        </svg>

        <!-- Ambient Glow Aura -->
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-amber-500/10 dark:bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="space-y-8 relative z-10">
            
            <!-- Navigation Toolbar: Back Link & Breadcrumb -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <a 
                    href="{{ route('berita') }}" 
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-200/70 dark:bg-emerald-950/70 hover:bg-[#0D5B3A] hover:text-white dark:hover:bg-emerald-600 dark:hover:text-white text-xs font-bold text-[#0D5B3A] dark:text-emerald-300 border border-stone-300/60 dark:border-emerald-500/30 shadow-xs transition-all group cursor-pointer"
                >
                    <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Kembali</span>
                </a>

                <nav class="hidden sm:inline-flex items-center gap-2 text-xs font-medium text-stone-500 dark:text-stone-400" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-[#0D6E42] dark:hover:text-emerald-400 transition-colors">Beranda</a>
                    <span class="text-stone-400">/</span>
                    <a href="{{ route('berita') }}" class="hover:text-[#0D6E42] dark:hover:text-emerald-400 transition-colors">Berita</a>
                    <span class="text-stone-400">/</span>
                    <span class="text-[#0D6E42] dark:text-emerald-300 font-bold truncate max-w-[180px]">{{ $article['categoryName'] }}</span>
                </nav>
            </div>

            <!-- Title & Meta Badges -->
            <div class="space-y-5">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="px-3.5 py-1 rounded-full bg-[#0D5B3A] text-white dark:bg-emerald-600 text-xs font-black shadow-xs tracking-wide">
                        {{ $article['categoryName'] }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/15 text-amber-900 dark:text-amber-300 text-xs font-extrabold border border-amber-500/30">
                        <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <span>{{ $article['badge'] }}</span>
                    </span>
                    <span class="px-3 py-1 rounded-full bg-stone-200/80 dark:bg-emerald-950/60 text-stone-600 dark:text-stone-300 text-xs font-semibold border border-stone-300/40 dark:border-emerald-500/20">
                        {{ $article['readTime'] }}
                    </span>
                </div>

                <h1 class="text-2xl sm:text-4xl lg:text-[2.75rem] font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-[1.25] text-balance">
                    {{ $article['title'] }}
                </h1>
            </div>

            <!-- Author & Editorial Metadata Header -->
            <div class="pt-6 border-t border-stone-300/60 dark:border-emerald-500/20 flex flex-wrap items-center justify-between gap-4">
                
                <!-- Author Profile Card -->
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#0D5B3A] to-[#143D2D] dark:from-emerald-700 dark:to-emerald-900 text-white flex items-center justify-center shadow-md shrink-0 border border-amber-400/30">
                        <svg class="w-6 h-6 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-extrabold text-sm sm:text-base text-[#143D2D] dark:text-[#E8F3EE]">{{ $article['author'] }}</span>
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 fill-current" viewBox="0 0 20 20" title="Terverifikasi Yayasan"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                        <div class="text-xs text-stone-500 dark:text-stone-400">{{ $article['authorRole'] }}</div>
                    </div>
                </div>

                <!-- Date & Views Stats -->
                <div class="flex items-center gap-3 sm:gap-4 text-xs font-semibold text-stone-500 dark:text-stone-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ $article['date'] }}</span>
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1.5 text-amber-700 dark:text-amber-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>{{ $article['views'] }}</span>
                    </span>
                </div>

            </div>

        </div>
    </header>

    <!-- ==========================================
         SECTION 2: MAIN EDITORIAL CONTENT & SIDEBAR
         ========================================== -->
    <div class="max-w-[1440px] mx-auto px-6 sm:px-10 lg:px-16 py-10 sm:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-start">
            
            <!-- LEFT MAIN CONTENT COLUMN (8 COLS) -->
            <article class="lg:col-span-8 space-y-10">
                
                <!-- 1. Hero Cover Image Card -->
                <figure class="rounded-3xl sm:rounded-[2.5rem] overflow-hidden bg-stone-900 border border-stone-300/60 dark:border-emerald-500/20 shadow-xl relative aspect-[16/10] sm:aspect-[16/9]">
                    <img 
                        src="{{ asset($article['img']) }}" 
                        alt="{{ $article['title'] }}" 
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent pointer-events-none"></div>
                    <figcaption class="absolute bottom-4 left-5 right-5 sm:left-7 sm:right-7 text-xs text-stone-200 flex items-center justify-between">
                        <span class="font-medium drop-shadow-sm">{{ $article['photoCredit'] }}</span>
                        <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/50 backdrop-blur-md border border-white/15 text-[11px]">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Dokumentasi Vihara</span>
                        </span>
                    </figcaption>
                </figure>

                <!-- 2. Reading Preferences Island (Font Size & Floating Share) -->
                <div class="p-4 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 shadow-sm flex items-center justify-between gap-4">
                    
                    <!-- Quick Social Share Icons -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-stone-500 dark:text-stone-400 hidden sm:inline">Bagikan:</span>
                        
                        <!-- WhatsApp -->
                        <a 
                            href="https://wa.me/?text={{ urlencode($article['title'] . ' - ' . route('berita.detail', ['slug' => $article['slug']])) }}" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="p-2.5 rounded-xl bg-[#25D366]/15 hover:bg-[#25D366] text-[#1EBE5B] hover:text-white transition-all cursor-pointer"
                            title="Bagikan ke WhatsApp"
                        >
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        </a>

                        <!-- Facebook -->
                        <a 
                            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('berita.detail', ['slug' => $article['slug']])) }}" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="p-2.5 rounded-xl bg-[#1877F2]/15 hover:bg-[#1877F2] text-[#1877F2] hover:text-white transition-all cursor-pointer"
                            title="Bagikan ke Facebook"
                        >
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>

                        <!-- Telegram -->
                        <a 
                            href="https://t.me/share/url?url={{ urlencode(route('berita.detail', ['slug' => $article['slug']])) }}&text={{ urlencode($article['title']) }}" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="p-2.5 rounded-xl bg-[#0088cc]/15 hover:bg-[#0088cc] text-[#0088cc] hover:text-white transition-all cursor-pointer"
                            title="Bagikan ke Telegram"
                        >
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.458c.538-.196 1.006.128.832.943z"/></svg>
                        </a>

                        <!-- Copy Link -->
                        <button 
                            @click="copyCurrentUrl()" 
                            type="button" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-stone-200 dark:bg-emerald-950/70 hover:bg-stone-300 dark:hover:bg-emerald-900 text-xs font-bold text-stone-700 dark:text-stone-200 transition-colors cursor-pointer border border-stone-300/60 dark:border-emerald-500/20"
                        >
                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span x-text="copiedLink ? 'Tersalin!' : 'Salin'"></span>
                        </button>
                    </div>

                    <!-- Reading Font Size Switcher -->
                    <div class="flex items-center gap-1 bg-stone-200/60 dark:bg-emerald-950/60 p-1 rounded-xl border border-stone-300/40 dark:border-emerald-500/20">
                        <button 
                            @click="fontSize = 'normal'" 
                            type="button" 
                            :class="fontSize === 'normal' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white'"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer"
                            title="Ukuran Font Standar"
                        >
                            A
                        </button>
                        <button 
                            @click="fontSize = 'large'" 
                            type="button" 
                            :class="fontSize === 'large' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white'"
                            class="px-2.5 py-1 rounded-lg text-sm font-black transition-all cursor-pointer"
                            title="Ukuran Font Lebih Besar"
                        >
                            A+
                        </button>
                    </div>

                </div>

                <!-- 3. Clean Article Body (WYSIWYG / Content Editor Format) -->
                <div 
                    class="prose prose-stone dark:prose-invert max-w-none text-stone-700 dark:text-stone-300 leading-[1.85] space-y-6 pt-2"
                    :class="fontSize === 'large' ? 'text-lg sm:text-xl' : 'text-base sm:text-lg'"
                >
                    {!! $article['content'] !!}
                </div>

                <!-- 4. Prev / Next Navigation Banner -->
                @if ($prevPost || $nextPost)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                        @if ($prevPost)
                            <a 
                                href="{{ route('berita.detail', ['slug' => $prevPost['slug']]) }}" 
                                class="p-5 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 hover:border-emerald-500/60 shadow-sm transition-all group flex flex-col justify-between space-y-2.5"
                            >
                                <span class="text-[11px] font-bold text-stone-400 group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    <span>Artikel Sebelumnya</span>
                                </span>
                                <h5 class="text-sm font-extrabold text-[#143D2D] dark:text-[#E8F3EE] group-hover:text-[#0D6E42] dark:group-hover:text-emerald-300 transition-colors line-clamp-2 leading-snug">
                                    {{ $prevPost['title'] }}
                                </h5>
                            </a>
                        @else
                            <div></div>
                        @endif

                        @if ($nextPost)
                            <a 
                                href="{{ route('berita.detail', ['slug' => $nextPost['slug']]) }}" 
                                class="p-5 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 hover:border-emerald-500/60 shadow-sm transition-all group flex flex-col justify-between space-y-2.5 text-right"
                            >
                                <span class="text-[11px] font-bold text-stone-400 group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors flex items-center justify-end gap-1.5">
                                    <span>Artikel Selanjutnya</span>
                                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </span>
                                <h5 class="text-sm font-extrabold text-[#143D2D] dark:text-[#E8F3EE] group-hover:text-[#0D6E42] dark:group-hover:text-emerald-300 transition-colors line-clamp-2 leading-snug">
                                    {{ $nextPost['title'] }}
                                </h5>
                            </a>
                        @endif
                    </div>
                @endif

            </article>

            <!-- RIGHT SIDEBAR COLUMN (4 COLS) -->
            <aside class="lg:col-span-4 space-y-8">
                
                <!-- WIDGET 1: POSTINGAN POPULER (TRENDING ARTICLES) -->
                @if (count($popularPosts) > 0)
                    <div class="p-6 sm:p-7 rounded-[2rem] bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/70 dark:border-emerald-500/20 shadow-md space-y-6 relative overflow-hidden">
                        
                        <!-- Ambient Glow Flare -->
                        <div class="absolute -top-12 -right-12 w-32 h-32 bg-amber-500/10 dark:bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

                        <!-- Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-stone-300/60 dark:border-emerald-900/40 relative z-10">
                            <div class="space-y-1">
                                <div class="inline-flex items-center gap-1.5 text-[11px] font-black uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>Trending Berita</span>
                                </div>
                                <h3 class="text-lg font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                                    Postingan Populer
                                </h3>
                            </div>

                            <div class="w-10 h-10 rounded-2xl bg-amber-500/15 dark:bg-emerald-950/70 border border-amber-500/30 dark:border-emerald-500/30 flex items-center justify-center text-amber-700 dark:text-amber-300 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/></svg>
                            </div>
                        </div>

                        <!-- Ranked Article Cards -->
                        <div class="space-y-3.5 relative z-10">
                            @foreach($popularPosts as $index => $pop)
                                <a 
                                    href="{{ route('berita.detail', ['slug' => $pop['slug']]) }}" 
                                    class="flex items-center gap-3.5 p-3 rounded-2xl bg-white/70 dark:bg-[#071710] hover:bg-white dark:hover:bg-[#0c221a] border border-stone-200/70 dark:border-emerald-500/15 hover:border-emerald-500/40 shadow-xs hover:shadow-md transition-all duration-300 group transform hover:-translate-y-0.5"
                                >
                                    <!-- Thumbnail Container with Rank Overlay Badge -->
                                    <div class="w-18 h-18 sm:w-20 sm:h-20 rounded-xl sm:rounded-2xl overflow-hidden shrink-0 relative bg-stone-200 dark:bg-stone-900 border border-stone-200 dark:border-emerald-500/20 shadow-xs">
                                        <img 
                                            src="{{ $pop['img'] }}" 
                                            alt="{{ $pop['title'] }}" 
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                            loading="lazy"
                                        />
                                        
                                        <!-- Rank Ribbon Badge -->
                                        <div class="absolute top-1.5 left-1.5 z-10">
                                            @if($index === 0)
                                                <span class="w-6 h-6 rounded-lg bg-gradient-to-tr from-amber-500 to-amber-400 text-stone-950 font-black text-xs flex items-center justify-center shadow-md ring-1 ring-amber-300">
                                                    01
                                                </span>
                                            @elseif($index === 1)
                                                <span class="w-6 h-6 rounded-lg bg-gradient-to-tr from-[#0D5B3A] to-emerald-600 text-white font-black text-xs flex items-center justify-center shadow-md ring-1 ring-emerald-400/40">
                                                    02
                                                </span>
                                            @elseif($index === 2)
                                                <span class="w-6 h-6 rounded-lg bg-[#143D2D] text-emerald-200 font-bold text-xs flex items-center justify-center shadow-xs border border-emerald-500/30">
                                                    03
                                                </span>
                                            @else
                                                <span class="w-6 h-6 rounded-lg bg-stone-300/90 dark:bg-emerald-950/80 text-stone-700 dark:text-stone-300 font-bold text-xs flex items-center justify-center border border-stone-400/30 dark:border-emerald-500/20">
                                                    04
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Text Content -->
                                    <div class="flex-1 min-w-0 space-y-1.5">
                                        <div class="flex items-center justify-between gap-2 text-[10.5px]">
                                            <span class="font-extrabold text-[#0D6E42] dark:text-emerald-400 uppercase tracking-wider truncate">
                                                {{ $pop['categoryName'] }}
                                            </span>
                                            <span class="flex items-center gap-1 font-bold text-amber-700 dark:text-amber-400 shrink-0">
                                                <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>{{ $pop['views'] }}</span>
                                            </span>
                                        </div>

                                        <h4 class="text-xs sm:text-[13px] font-extrabold text-[#143D2D] dark:text-[#E8F3EE] group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors leading-snug line-clamp-2">
                                            {{ $pop['title'] }}
                                        </h4>

                                        <div class="flex items-center gap-2 text-[10px] text-stone-500 dark:text-stone-400 font-medium">
                                            <span>{{ $pop['date'] }}</span>
                                            <span>•</span>
                                            <span>{{ $pop['readTime'] }}</span>
                                        </div>
                                    </div>

                                    <!-- Subtle Action Arrow -->
                                    <div class="hidden sm:flex text-stone-400 group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transform group-hover:translate-x-1 transition-all shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <!-- Footer Link -->
                        <div class="pt-2 border-t border-stone-200 dark:border-emerald-900/40 relative z-10">
                            <a 
                                href="{{ route('berita') }}" 
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-stone-200/70 dark:bg-emerald-950/70 hover:bg-[#0D5B3A] hover:text-white dark:hover:bg-emerald-600 dark:hover:text-white text-xs font-bold text-[#0D5B3A] dark:text-emerald-300 border border-stone-300/60 dark:border-emerald-500/20 shadow-xs transition-all group cursor-pointer"
                            >
                                <span>Jelajahi Semua Berita</span>
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>

                    </div>
                @endif

                <!-- WIDGET 2: DAILY MINDFULNESS WISDOM CARD -->
                <div class="p-6 sm:p-7 rounded-[2rem] bg-gradient-to-br from-[#143D2D] via-[#0E2E21] to-[#071710] text-white border border-emerald-500/30 shadow-lg space-y-3.5 relative overflow-hidden">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-300 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-amber-300">Renungan Batin Hari Ini</h4>
                        <p class="text-xs sm:text-sm leading-relaxed text-stone-200 italic font-serif">
                            "Kendalikan pikiran dari kebencian, basuh hati dengan cinta kasih, dan senantiasa bersyukur atas nafas kehidupan."
                        </p>
                    </div>
                    <div class="text-[11px] text-emerald-300/70 pt-1 font-semibold">
                        — Dhammapada Sāmaggi Gāma
                    </div>
                </div>

                <!-- WIDGET 3: UPCOMING EVENT CALLOUT (DYNAMIC FROM DB) -->
                @if ($upcomingEvent)
                    <div class="p-6 sm:p-7 rounded-[2rem] bg-[#FAF5ED] dark:bg-[#0b1c15] border border-amber-500/25 dark:border-emerald-500/20 shadow-sm space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-[#0D6E42] dark:text-emerald-300 text-[10.5px] font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>{{ $upcomingEvent['category'] }}</span>
                                </div>
                                <span class="text-[10.5px] font-bold text-amber-700 dark:text-amber-400">
                                    {{ $upcomingEvent['date'] }}
                                </span>
                            </div>
                            <h4 class="text-base font-extrabold text-[#143D2D] dark:text-[#E8F3EE] line-clamp-2 leading-snug">
                                {{ $upcomingEvent['title'] }}
                            </h4>
                            <div class="space-y-1 text-xs text-stone-600 dark:text-stone-400">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>{{ $upcomingEvent['time'] }}</span>
                                    <span>•</span>
                                    <span>{{ $upcomingEvent['location'] }}</span>
                                </div>
                                @if (!empty($upcomingEvent['speaker_or_leader']))
                                    <div class="text-[11px] text-[#0D6E42] dark:text-emerald-300 font-semibold line-clamp-1">
                                        {{ $upcomingEvent['speaker_or_leader'] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <a 
                            href="{{ route('kegiatan') }}" 
                            class="w-full inline-flex items-center justify-center gap-2 bg-[#0D5B3A] hover:bg-[#09472D] dark:bg-emerald-700 dark:hover:bg-emerald-600 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-xs transition-all cursor-pointer"
                        >
                            <span>Lihat Jadwal Selengkapnya</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                @endif

            </aside>
        </div>

        <!-- ==========================================
             SECTION 3: POSTINGAN TERKAIT (RELATED POSTS)
             ========================================== -->
        @if (count($relatedPosts) > 0)
            <section class="mt-20 pt-12 border-t border-stone-300/60 dark:border-emerald-500/20 space-y-8">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>Rekomendasi Berita</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                            Postingan Terkait Lainnya
                        </h3>
                    </div>

                    <a 
                        href="{{ route('berita') }}" 
                        class="font-bold text-xs sm:text-sm text-[#0D5B3A] dark:text-emerald-400 hover:text-amber-600 dark:hover:text-amber-300 transition-colors flex items-center gap-1.5"
                    >
                        <span>Lihat Semua Berita</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach($relatedPosts as $rel)
                        <article class="bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-[2rem] border border-stone-300/60 dark:border-emerald-500/20 hover:border-emerald-500/60 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between group card-shine hover:-translate-y-1.5">
                            <div class="space-y-4">
                                <a href="{{ route('berita.detail', ['slug' => $rel['slug']]) }}" class="block relative aspect-[16/10] overflow-hidden bg-stone-200 dark:bg-stone-900">
                                    <img 
                                        src="{{ $rel['img'] }}" 
                                        alt="{{ $rel['title'] }}" 
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy"
                                    />
                                    <div class="absolute top-3.5 left-3.5 z-10">
                                        <span class="px-3 py-1 rounded-full bg-[#FAF5ED]/95 dark:bg-[#081610]/95 backdrop-blur-md text-[#0D6E42] dark:text-emerald-300 text-[11px] font-bold border border-emerald-500/20 shadow-xs">
                                            {{ $rel['categoryName'] }}
                                        </span>
                                    </div>
                                </a>

                                <div class="p-6 pt-0 space-y-2.5">
                                    <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                                        <span>{{ $rel['date'] }}</span>
                                        <span>•</span>
                                        <span>{{ $rel['readTime'] }}</span>
                                    </div>

                                    <h4 class="text-base sm:text-lg font-bold text-[#143D2D] dark:text-[#E8F3EE] leading-snug group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors line-clamp-2">
                                        <a href="{{ route('berita.detail', ['slug' => $rel['slug']]) }}">
                                            {{ $rel['title'] }}
                                        </a>
                                    </h4>

                                    <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm leading-relaxed line-clamp-2">
                                        {{ $rel['excerpt'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="p-6 pt-0">
                                <div class="pt-4 border-t border-stone-200 dark:border-emerald-900/40 flex items-center justify-between text-xs">
                                    <span class="text-stone-400">{{ $rel['views'] }}</span>
                                    <a href="{{ route('berita.detail', ['slug' => $rel['slug']]) }}" class="font-bold text-[#0D5B3A] dark:text-emerald-300 hover:text-amber-600 dark:hover:text-amber-300 transition-colors flex items-center gap-1">
                                        <span>Baca Berita</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

    </div>
</main>
