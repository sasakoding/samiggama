<?php

use App\Models\Setting;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        $aboutBadge = Setting::get('about_badge', 'Tentang Vihara Sāmaggi Gāma');
        $aboutTitle = Setting::get('about_title', 'Oase Ketenangan untuk Membina Batin & Menjalin Kerukunan');
        
        $defaultContent = '<p>Nama <strong class="text-[#143D2D] dark:text-emerald-300 font-semibold">Sāmaggi Gāma</strong> berakar dari bahasa Pali yang bermakna <em>"Desa Kerukunan dan Persatuan"</em>. Vihara ini didirikan sebagai wadah spiritual yang terbuka bagi seluruh umat dan masyarakat untuk belajar, mempraktikkan ajaran Buddha, dan menumbuhkan cinta kasih universal.</p><p>Di tengah hiruk pikuk kehidupan modern, kami hadir menyediakan lingkungan yang asri dan sejuk bagi siapa saja yang ingin melatih kesadaran penuh (<em>mindfulness</em>), memperdalam pemahaman Dhamma, serta mempererat tali persaudaraan dalam kebajikan.</p><p>Vihara Sāmaggi Gāma senantiasa berkomitmen untuk menjadi ladang subur bagi tumbuhnya kebajikan melalui kegiatan puja bakti rutin, meditasi terpandu, Sekolah Minggu Buddhis, aksi sosial kemanusiaan, serta kajian kitab suci Tipitaka.</p>';
        $aboutContent = Setting::get('about_content', $defaultContent);

        $aboutImage = Setting::get('about_image', 'images/gallery-dhammasala.jpg');
        $aboutImageBadge = Setting::get('about_image_badge', 'Dhammasala Utama');
        $aboutImageQuote = Setting::get('about_image_quote', '“Dalam keheningan batin, terbit kebijaksanaan luhur.”');

        $pillar1Title = Setting::get('about_pillar1_title', 'Sīla (Kemoralan)');
        $pillar1Desc = Setting::get('about_pillar1_desc', 'Fondasi perilaku luhur demi ketenteraman diri dan sesama.');
        $pillar2Title = Setting::get('about_pillar2_title', 'Samādhi (Meditasi)');
        $pillar2Desc = Setting::get('about_pillar2_desc', 'Pemusatan pikiran & kejernihan kesadaran batin.');
        $pillar3Title = Setting::get('about_pillar3_title', 'Paññā (Kebijaksanaan)');
        $pillar3Desc = Setting::get('about_pillar3_desc', 'Pemahaman hakikat kehidupan dalam terang Dhamma.');

        $foundationName = Setting::get('foundation_name', 'Yayasan Vihara Sāmaggi Gāma');
        $foundationAddress = Setting::get('foundation_address', 'Jl. Sāmaggi Raya No. 8, Candi Dhyana, Indonesia');
        $foundationMapUrl = Setting::get('foundation_map_url');
        $foundationPhone = Setting::get('foundation_phone', '+62 811-2345-6789');
        $foundationEmail = Setting::get('foundation_email', 'sekretariat@samaggigama.org');
        $legalDocument = Setting::get('legal_document');

        return $this->view([
            'aboutBadge' => $aboutBadge,
            'aboutTitle' => $aboutTitle,
            'aboutContent' => $aboutContent,
            'aboutImage' => $aboutImage,
            'aboutImageBadge' => $aboutImageBadge,
            'aboutImageQuote' => $aboutImageQuote,
            'pillar1Title' => $pillar1Title,
            'pillar1Desc' => $pillar1Desc,
            'pillar2Title' => $pillar2Title,
            'pillar2Desc' => $pillar2Desc,
            'pillar3Title' => $pillar3Title,
            'pillar3Desc' => $pillar3Desc,
            'foundationName' => $foundationName,
            'foundationAddress' => $foundationAddress,
            'foundationMapUrl' => $foundationMapUrl,
            'foundationPhone' => $foundationPhone,
            'foundationEmail' => $foundationEmail,
            'legalDocument' => $legalDocument,
        ])->title('Profil Lengkap Tentang Kami - Vihara Sāmaggi Gāma');
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
                <span class="text-[#0D6E42] dark:text-emerald-300 font-bold">Profil Tentang Kami</span>
            </nav>

            <!-- Eyebrow Pill Badge -->
            <div>
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>{{ $aboutBadge }}</span>
                </div>
            </div>

            <!-- Page Title -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-tight">
                {{ $aboutTitle }}
            </h1>

            <!-- Narrative Subtitle -->
            <p class="max-w-2xl mx-auto text-stone-600 dark:text-stone-300 text-sm sm:text-base leading-relaxed">
                Mengenal lebih dekat visi, sejarah, nilai luhur, dan komitmen pelayanan spiritual Vihara Sāmaggi Gāma bagi umat dan masyarakat luas.
            </p>
        </div>
    </header>

    <!-- Main Container -->
    <div class="max-w-[1440px] w-full mx-auto px-6 sm:px-10 lg:px-16 py-12 sm:py-20 space-y-20 overflow-hidden">

        <!-- ==========================================
             SECTION 2: PROFIL LENGKAP & FOTO DHAMMASALA
             ========================================== -->
        <section aria-labelledby="profil-lengkap-heading" class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
            
            <!-- LEFT COLUMN: Foto Megah dengan Frame Mewah (Col 1-5) -->
            <div class="lg:col-span-5 flex justify-center sticky top-24">
                <div class="relative w-full max-w-[500px] lg:max-w-none group overflow-hidden rounded-[2.5rem] p-1">
                    
                    <!-- Glow Aura (Constrained inside wrapper) -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-amber-500/20 via-emerald-600/15 to-amber-600/20 rounded-[2.5rem] blur-xl opacity-75 group-hover:opacity-100 transition duration-500 animate-pulse-glow"></div>

                    <!-- Frame Card -->
                    <div class="relative rounded-[2.2rem] sm:rounded-[2.5rem] p-3 bg-[#FAF5ED]/90 dark:bg-[#0c1813]/90 backdrop-blur-xl border border-amber-500/30 dark:border-amber-400/20 shadow-2xl shadow-emerald-950/15 dark:shadow-black/60 overflow-hidden card-shine">
                        
                        <div class="relative overflow-hidden rounded-[1.7rem] sm:rounded-[2rem] aspect-[4/5] sm:aspect-[3/4] lg:aspect-[4/5] bg-stone-900">
                            <img 
                                src="{{ asset($aboutImage) }}" 
                                alt="{{ $aboutTitle }}" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
                                loading="lazy"
                            />
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-[#143D2D]/85 via-transparent to-black/20 pointer-events-none"></div>

                            <!-- Top Floating Badge -->
                            @if (!empty($aboutImageBadge))
                                <div class="absolute top-4 left-4 z-10 inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#FAF5ED]/90 dark:bg-[#0c1813]/90 backdrop-blur-md border border-amber-500/30 shadow-md">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-xs font-bold tracking-wider uppercase text-[#143D2D] dark:text-emerald-200">
                                        {{ $aboutImageBadge }}
                                    </span>
                                </div>
                            @endif

                            <!-- Bottom Floating Micro Quote -->
                            @if (!empty($aboutImageQuote))
                                <div class="absolute bottom-4 inset-x-4 z-10 p-4 rounded-2xl bg-black/50 backdrop-blur-md border border-white/15 text-white">
                                    <p class="text-xs sm:text-sm font-medium text-amber-200 italic leading-relaxed">
                                        {{ $aboutImageQuote }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Narasi Lengkap Artikel Tentang Kami (Col 6-12) -->
            <div class="lg:col-span-7 space-y-8 min-w-0">
                
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Sejarah, Visi & Makna Nama</span>
                    </div>
                    <h2 id="profil-lengkap-heading" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-snug">
                        Membangun Kebersamaan dalam Semangat Mettā & Karuṇā
                    </h2>
                    <div class="w-24 h-1.5 rounded-full bg-gradient-to-r from-amber-500 via-emerald-600 to-transparent"></div>
                </div>

                <!-- Full Rich Text Narrative from WYSIWYG Editor (Protected against horizontal overflow) -->
                <div class="prose prose-stone dark:prose-invert max-w-none text-stone-700 dark:text-stone-300 text-sm sm:text-base leading-[1.9] space-y-6 break-words [word-break:break-word] overflow-hidden">
                    {!! $aboutContent !!}
                </div>

            </div>

        </section>

        <!-- ==========================================
             SECTION 3: 3 NILAI LUHUR & PILAR SPIRITUAL
             ========================================== -->
        <section aria-labelledby="pilar-heading" class="space-y-8 pt-8 border-t border-stone-300/60 dark:border-emerald-500/20">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Fondasi Kehidupan Beragama</span>
                </div>
                <h3 id="pilar-heading" class="text-2xl sm:text-3xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                    Tiga Pilar Utama Vihara Sāmaggi Gāma
                </h3>
                <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                    Pedoman pembinaan batin dan praktik kebajikan yang kami junjung tinggi bersama.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Pillar 1 -->
                <div class="p-8 rounded-[2rem] bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 shadow-md space-y-4 relative overflow-hidden card-shine">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-[#0D6E42] dark:text-emerald-300 flex items-center justify-center font-black text-lg border border-emerald-500/30">
                        1
                    </div>
                    <h4 class="text-lg sm:text-xl font-extrabold text-[#143D2D] dark:text-emerald-200">
                        {{ $pillar1Title }}
                    </h4>
                    <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed">
                        {{ $pillar1Desc }}
                    </p>
                </div>

                <!-- Pillar 2 -->
                <div class="p-8 rounded-[2rem] bg-[#FAF5ED] dark:bg-[#0b1c15] border border-amber-500/30 dark:border-amber-500/20 shadow-md space-y-4 relative overflow-hidden card-shine">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-800 dark:text-amber-300 flex items-center justify-center font-black text-lg border border-amber-500/30">
                        2
                    </div>
                    <h4 class="text-lg sm:text-xl font-extrabold text-[#143D2D] dark:text-amber-200">
                        {{ $pillar2Title }}
                    </h4>
                    <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed">
                        {{ $pillar2Desc }}
                    </p>
                </div>

                <!-- Pillar 3 -->
                <div class="p-8 rounded-[2rem] bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 shadow-md space-y-4 relative overflow-hidden card-shine">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-[#0D6E42] dark:text-emerald-300 flex items-center justify-center font-black text-lg border border-emerald-500/30">
                        3
                    </div>
                    <h4 class="text-lg sm:text-xl font-extrabold text-[#143D2D] dark:text-emerald-200">
                        {{ $pillar3Title }}
                    </h4>
                    <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed">
                        {{ $pillar3Desc }}
                    </p>
                </div>

            </div>
        </section>

    </div>
</main>
