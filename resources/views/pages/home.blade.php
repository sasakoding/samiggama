<?php 

use App\Models\Article;
use App\Models\CertificateTemplate;
use App\Models\Donation;
use App\Models\DonationProgram;
use App\Models\Expense;
use App\Models\GalleryAlbum;
use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component 
{
    public function render()
    {
        // 1. Profil / Tentang Kami
        $heroImage = Setting::get('hero_image', 'images/vihara-samaggi-gama.jpg');
        $aboutBadge = Setting::get('about_badge', 'Tentang Vihara Sāmaggi Gāma');
        $aboutTitle = Setting::get('about_title', 'Oase Ketenangan untuk Membina Batin & Menjalin Kerukunan');
        $defaultContent = '<p>Nama <strong class="text-[#143D2D] dark:text-emerald-300 font-semibold">Sāmaggi Gāma</strong> berakar dari bahasa Pali yang bermakna <em>"Desa Kerukunan dan Persatuan"</em>. Vihara ini didirikan sebagai wadah spiritual yang terbuka bagi seluruh umat dan masyarakat untuk belajar, mempraktikkan ajaran Buddha, dan menumbuhkan cinta kasih universal.</p><p>Di tengah hiruk pikuk kehidupan modern, kami hadir menyediakan lingkungan yang asri dan sejuk bagi siapa saja yang ingin melatih kesadaran penuh (<em>mindfulness</em>), memperdalam pemahaman Dhamma, serta mempererat tali persaudaraan dalam kebajikan.</p>';
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

        // 2. Galeri Suasana (Dynamic from DB)
        $galleryAlbums = GalleryAlbum::with('photos')->latest()->take(6)->get();
        $galleryCategories = $galleryAlbums->pluck('category')->unique()->values();

        // 3. Program Donasi (Dynamic from DB)
        $donationPrograms = DonationProgram::with(['donations' => function($q) {
            $q->where('status', 'verified');
        }])->latest()->get();

        // Format Donors List for Alpine JS modal
        $verifiedDonations = Donation::with('donationProgram')
            ->where('status', 'verified')
            ->latest()
            ->get();

        // Prepare Certificate Templates from DB
        $allTemplates = CertificateTemplate::where('status', 'aktif')->get();
        $defaultTemplate = $allTemplates->firstWhere('is_default', true) ?? $allTemplates->first();
        $pattidanaTemplate = $allTemplates->first(function($t) {
            return str_contains(strtolower($t->name), 'pattidana') || str_contains(strtolower($t->category_target ?? ''), 'pattidana');
        });

        $donorsList = $verifiedDonations->map(function ($d) use ($allTemplates, $defaultTemplate, $pattidanaTemplate) {
            $isAlm = str_contains(strtolower($d->donor_name), 'alm.') || str_contains(strtolower($d->donor_name), 'almh.') || str_contains(strtolower($d->donor_name), 'pattidana');
            
            $matchedTemplate = null;
            if ($d->issuedCertificate && $d->issuedCertificate->certificate_template_id) {
                $matchedTemplate = $allTemplates->firstWhere('id', $d->issuedCertificate->certificate_template_id);
            }
            
            if (!$matchedTemplate && $d->donationProgram?->certificate_template_id) {
                $matchedTemplate = $allTemplates->firstWhere('id', $d->donationProgram->certificate_template_id);
            }

            $hasCertificate = !empty($matchedTemplate) && !empty($matchedTemplate->background_image);
            $bgImage = $hasCertificate ? asset($matchedTemplate->background_image) : null;
            $tplName = $hasCertificate ? $matchedTemplate->name : null;

            return [
                'id' => $d->id,
                'campaignId' => $d->donation_program_id,
                'name' => $d->donor_name,
                'isAlm' => (bool) $isAlm,
                'program' => $d->donationProgram?->title ?? 'Dāna Umum Operasional Vihara',
                'amount' => 'Rp ' . number_format($d->amount, 0, ',', '.'),
                'rawAmount' => (int) $d->amount,
                'date' => $d->created_at ? $d->created_at->translatedFormat('d F Y') : 'Baru saja',
                'ref' => $d->invoice_number,
                'certNo' => $d->issuedCertificate?->certificate_number ?? ('PA/' . ($d->created_at ? $d->created_at->format('Y/m') : date('Y/m')) . '/' . str_pad($d->id, 4, '0', STR_PAD_LEFT)),
                'status' => 'Tervalidasi',
                'hasCertificate' => (bool) $hasCertificate,
                'templateBg' => $bgImage,
                'templateName' => $tplName,
            ];
        })->toArray();

        // 4. Berita & Kajian Dhamma (Dynamic from DB)
        $articles = Article::published()->latest()->take(4)->get();
        $featuredArticle = $articles->first();
        $latestArticles = $articles->slice(1)->take(3);
        $totalArticlesCount = Article::published()->count();

        // 5. Rekening Bank & QRIS Settings (Sinkron Database Settings)
        $bankAccounts = [];
        for ($i = 1; $i <= 4; $i++) {
            $bName = Setting::get("bank_name_{$i}");
            $bAcc = Setting::get("bank_account_{$i}");
            $bHold = Setting::get("bank_holder_{$i}");
            if ($bName && $bAcc) {
                $bankAccounts[] = [
                    'id' => 'bank_' . $i,
                    'name' => $bName,
                    'account' => $bAcc,
                    'cleanAccount' => preg_replace('/[^0-9]/', '', $bAcc),
                    'holder' => $bHold ?: 'Yayasan Vihara Samaggi Gama',
                ];
            }
        }

        // Fallback default bank accounts if not configured
        if (empty($bankAccounts)) {
            $b1Name = Setting::get('bank_name_1', 'Bank Central Asia (BCA)');
            $b1Acc = Setting::get('bank_account_1', Setting::get('bank_bca_norek', '088-789-2233'));
            $b1Hold = Setting::get('bank_holder_1', Setting::get('bank_bca_holder', 'Yayasan Vihara Samaggi Gama'));
            if ($b1Acc) {
                $bankAccounts[] = [
                    'id' => 'bank_1',
                    'name' => $b1Name,
                    'account' => $b1Acc,
                    'cleanAccount' => preg_replace('/[^0-9]/', '', $b1Acc),
                    'holder' => $b1Hold,
                ];
            }

            $b2Name = Setting::get('bank_name_2', 'Bank Mandiri');
            $b2Acc = Setting::get('bank_account_2', Setting::get('bank_mandiri_norek', '123-00-9988776-5'));
            $b2Hold = Setting::get('bank_holder_2', Setting::get('bank_mandiri_holder', 'Yayasan Vihara Samaggi Gama'));
            if ($b2Acc) {
                $bankAccounts[] = [
                    'id' => 'bank_2',
                    'name' => $b2Name,
                    'account' => $b2Acc,
                    'cleanAccount' => preg_replace('/[^0-9]/', '', $b2Acc),
                    'holder' => $b2Hold,
                ];
            }
        }

        $bankName1 = $bankAccounts[0]['name'] ?? 'Bank Central Asia (BCA)';
        $bankAccount1 = $bankAccounts[0]['account'] ?? '088-789-2233';
        $bankHolder1 = $bankAccounts[0]['holder'] ?? 'Yayasan Vihara Samaggi Gama';

        $bankName2 = $bankAccounts[1]['name'] ?? 'Bank Mandiri';
        $bankAccount2 = $bankAccounts[1]['account'] ?? '123-00-9988776-5';
        $bankHolder2 = $bankAccounts[1]['holder'] ?? 'Yayasan Vihara Samaggi Gama';

        $bankName3 = $bankAccounts[2]['name'] ?? '';
        $bankAccount3 = $bankAccounts[2]['account'] ?? '';
        $bankHolder3 = $bankAccounts[2]['holder'] ?? '';

        $qrisMerchantId = Setting::get('qris_nmid') ?: Setting::get('qris_merchant_id', 'ID1020038891024');
        $qrisImage = Setting::get('qris_image', 'images/qris-vihara.jpg');
        $foundationPhone = Setting::get('foundation_phone', '+62 811-2345-6789');
        $foundationWhatsApp = \App\Models\AdminContact::getRandomActivePhone($foundationPhone);

        $sanghaMembers = \App\Models\SanghaMember::active()->get();
        if ($sanghaMembers->isEmpty()) {
            $sanghaMembers = collect([
                (object)[
                    'name' => Setting::get('sangha_title', 'Bhikkhu Sangha Vihara Sāmaggi Gāma'),
                    'title' => 'Pembina Spiritual',
                    'photo_url' => asset(Setting::get('sangha_photo', 'images/bhikkhu-sangha.jpg')),
                ]
            ]);
        }

        $totalDonationsCollected = Donation::where('status', 'verified')->sum('amount');
        $totalExpensesCollected = Expense::sum('amount');
        $totalDonorsCount = Donation::where('status', 'verified')->count();
        $activeProgramsCount = $donationPrograms->where('status', 'aktif')->count();

        return $this->view([
            'heroImage' => $heroImage,
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
            'galleryAlbums' => $galleryAlbums,
            'galleryCategories' => $galleryCategories,
            'donationPrograms' => $donationPrograms,
            'donorsList' => $donorsList,
            'totalDonationsCollected' => $totalDonationsCollected,
            'totalExpensesCollected' => $totalExpensesCollected,
            'totalDonorsCount' => $totalDonorsCount,
            'activeProgramsCount' => $activeProgramsCount,
            'featuredArticle' => $featuredArticle,
            'latestArticles' => $latestArticles,
            'totalArticlesCount' => $totalArticlesCount,
            'bankAccounts' => $bankAccounts,
            'bankName1' => $bankName1,
            'bankAccount1' => $bankAccount1,
            'bankHolder1' => $bankHolder1,
            'bankName2' => $bankName2,
            'bankAccount2' => $bankAccount2,
            'bankHolder2' => $bankHolder2,
            'bankName3' => $bankName3,
            'bankAccount3' => $bankAccount3,
            'bankHolder3' => $bankHolder3,
            'qrisMerchantId' => $qrisMerchantId,
            'qrisImage' => $qrisImage,
            'foundationPhone' => $foundationPhone,
            'foundationWhatsApp' => $foundationWhatsApp,
            'sanghaMembers' => $sanghaMembers,
        ])->title('Vihara Sāmaggi Gāma -  Ketenangan & Persaudaraan Luhur');
    }
};

?>

<main id="main-content" class="min-h-screen bg-[#F0E9DF] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 font-sans selection:bg-emerald-200 selection:text-emerald-950">

    <!--  SECTION 1: HERO SECTION  -->
    <section id="hero" aria-label="Beranda Utama" class="flex items-center justify-center">
        <!-- Main Card Container matching reference layout with Vihara Color Theme -->
        <div class="w-full bg-[#FAF5ED] dark:bg-[#091711] border-b border-emerald-950/10 dark:border-emerald-500/15 relative overflow-hidden flex flex-col justify-between pt-0 px-6 sm:px-16">
            
            <!-- Background Topographic / Contour Lines (Emerald & Amber Nuance) -->
            <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-15 dark:opacity-10 stroke-emerald-900 dark:stroke-emerald-400 fill-none" viewBox="0 0 1440 900" preserveAspectRatio="none" aria-hidden="true">
                <path d="M-50,150 C300,50 450,280 800,120 C1100,0 1300,180 1500,80" stroke-width="1.2" />
                <path d="M-50,220 C280,140 420,340 780,200 C1120,80 1280,260 1500,160" stroke-width="1.2" />
                <path d="M-50,300 C250,220 390,420 760,280 C1100,160 1260,340 1500,240" stroke-width="1.2" />
                <path d="M-50,380 C220,300 360,500 740,360 C1080,240 1240,420 1500,320" stroke-width="1.2" />
                <path d="M-50,460 C200,380 330,580 720,440 C1060,320 1220,500 1500,400" stroke-width="1.2" />
                <path d="M-50,540 C180,460 300,660 700,520 C1040,400 1200,580 1500,480" stroke-width="1.2" />
                <path d="M-50,620 C150,540 270,740 680,600 C1020,480 1180,660 1500,560" stroke-width="1.2" />
                <path d="M-50,700 C120,620 240,820 660,680 C1000,560 1160,740 1500,640" stroke-width="1.2" />
                <path d="M-50,780 C100,700 220,900 640,760 C980,640 1140,820 1500,720" stroke-width="1.2" />
                <path d="M-50,860 C80,780 200,980 620,840 C960,720 1120,900 1500,800" stroke-width="1.2" />
            </svg>

            <!-- Main Hero Grid -->
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-10 items-center py-10 sm:py-14 lg:py-16">
                
                <!-- Left Column Content (Col 1-7) -->
                <div class="lg:col-span-7 flex flex-col justify-center space-y-5 sm:space-y-6 max-w-2xl reveal-on-scroll is-visible">
                    
                    <!-- Eyebrow Subheading / Tagline with Jade & Gold nuance -->
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 w-fit transition-transform hover:scale-105 duration-200">
                        <span class="w-2 h-2 rounded-full bg-[#0D6E42] dark:bg-emerald-400 animate-ping"></span>
                        <span class="text-xs sm:text-sm font-semibold tracking-wide text-emerald-950 dark:text-emerald-300">
                            Svāgataṃ — Selamat Datang di Vihara
                        </span>
                    </div>

                    <!-- Giant Bold Hero Heading (Single H1 for SEO) -->
                    <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-[3.35rem] xl:text-[3.75rem] font-extrabold text-[#153428] dark:text-[#E8F3EE] tracking-tight leading-[1.1] lg:leading-[1.12]">
                        Tumbuhkan Kebijaksanaan,<br class="hidden sm:inline">
                        Ciptakan Kedamaian<br class="hidden sm:inline">
                        Dalam Hati
                    </h1>

                    <!-- Intro Description -->
                    <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm md:text-base leading-relaxed max-w-xl">
                        Selamat datang di Portal Vihara Sāmaggi Gāma, wadah pembinaan batin, sarana meditasi hening, serta perajut kerukunan umat dalam terang ajaran luhur Sang Buddha.
                    </p>

                    <!-- CTA Action Buttons -->
                    <div class="pt-2 hidden sm:flex flex-wrap items-center gap-4">
                        <a href="{{ route('berita') }}" class="inline-flex items-center justify-center gap-2.5 bg-[#0D5B3A] hover:bg-[#09472D] active:bg-[#073823] text-stone-50 font-semibold px-7 py-3.5 rounded-xl shadow-md shadow-emerald-950/20 hover:shadow-lg hover:shadow-emerald-950/30 border border-emerald-700/30 transition-all duration-200 text-sm sm:text-base transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                            <span>Berita Samaggi</span>
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                        <a href="{{ route('tentang-kami') }}" class="inline-flex items-center justify-center gap-2 bg-stone-200/60 dark:bg-emerald-900/40 hover:bg-stone-300/60 dark:hover:bg-emerald-900/70 text-[#143D2D] dark:text-emerald-200 font-semibold px-6 py-3.5 rounded-xl border border-transparent dark:border-emerald-700/40 transition-all duration-200 text-sm sm:text-base hover:-translate-y-0.5 cursor-pointer">
                            <span>Pelajari Profil Kami</span>
                        </a>
                    </div>
                </div>

                <!-- Right Column Visual (Col 8-12: Grand Architectural Frame) -->
                <div class="lg:col-span-5 flex items-center justify-center lg:justify-end relative reveal-on-scroll is-visible">
                    <div class="relative w-full max-w-[480px] sm:max-w-[540px] lg:max-w-none group">
                        
                        <!-- Ambient Radial Glow Aura -->
                        <div class="absolute -inset-3 sm:-inset-4 bg-gradient-to-tr from-amber-500/25 via-emerald-600/20 to-amber-600/25 rounded-[3rem] blur-2xl opacity-75 group-hover:opacity-100 transition duration-700 pointer-events-none animate-pulse-glow"></div>

                        <!-- Master Luxury Frame with Gold/Emerald Border -->
                        <div class="relative rounded-[2.2rem] sm:rounded-[2.6rem] bg-[#FAF5ED]/90 dark:bg-[#081812]/90 backdrop-blur-xl border border-amber-500/35 dark:border-amber-400/25 shadow-2xl shadow-emerald-950/15 dark:shadow-black/70 overflow-hidden card-shine transition-transform duration-500 group-hover:scale-[1.01]">
                            
                            <!-- Main Photo Frame -->
                            <div class="relative rounded-[1.8rem] sm:rounded-[2.1rem] overflow-hidden aspect-[4/3] sm:aspect-[16/11] bg-stone-900 shadow-inner">
                                <img 
                                    src="{{ asset($heroImage) }}" 
                                    alt="Bangunan Utama Dhammasala Vihara Sāmaggi Gāma" 
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out select-none"
                                    loading="eager"
                                    decoding="async"
                                />
                                
                                <!-- Atmospheric Gradient Vignette Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-black/20 pointer-events-none"></div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: TENTANG KAMI (SUPER PREMIUM 2-COLUMN SHOWCASE) -->
    <section id="tentang-kami" aria-labelledby="about-heading" class="py-20 sm:py-28 px-6 sm:px-10 lg:px-16 max-w-[1440px] mx-auto relative overflow-hidden">
        
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-gradient-to-br from-amber-500/10 via-emerald-600/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-gradient-to-tl from-emerald-800/10 via-amber-500/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
            
            <!-- LEFT COLUMN: Foto Megah dengan Bingkai & Dekorasi Super Premium -->
            <div class="lg:col-span-5 xl:col-span-5 flex justify-center reveal-on-scroll">
                <div class="relative w-full max-w-[500px] lg:max-w-none group">
                    
                    <div class="absolute -inset-3 bg-gradient-to-tr from-amber-500/20 via-emerald-600/15 to-amber-600/20 rounded-[2.5rem] blur-xl opacity-75 group-hover:opacity-100 transition duration-500 animate-pulse-glow"></div>

                    <div class="relative rounded-[2.2rem] sm:rounded-[2.5rem] p-2.5 sm:p-3 bg-[#FAF5ED]/90 dark:bg-[#0c1813]/90 backdrop-blur-xl border border-amber-500/30 dark:border-amber-400/20 shadow-2xl shadow-emerald-950/15 dark:shadow-black/60 overflow-hidden card-shine transition-transform duration-500 group-hover:scale-[1.01]">
                        
                        <div class="relative overflow-hidden rounded-[1.7rem] sm:rounded-[2rem] aspect-[4/5] sm:aspect-[3/4] lg:aspect-[4/5]">
                            <img 
                                src="{{ asset($aboutImage) }}" 
                                alt="{{ $aboutTitle }}" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
                                loading="lazy"
                            />
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-[#143D2D]/80 via-transparent to-black/20 pointer-events-none"></div>

                            @if (!empty($aboutImageBadge))
                                <div class="absolute top-4 left-4 z-10 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#FAF5ED]/90 dark:bg-[#0c1813]/90 backdrop-blur-md border border-amber-500/30 shadow-md">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-[11px] font-bold tracking-wider uppercase text-[#143D2D] dark:text-emerald-200">
                                        {{ $aboutImageBadge }}
                                    </span>
                                </div>
                            @endif

                            @if (!empty($aboutImageQuote))
                                <div class="absolute bottom-4 inset-x-4 z-10 p-3.5 rounded-2xl bg-black/40 backdrop-blur-md border border-white/15 text-white">
                                    <p class="text-xs font-medium text-amber-200/95 italic leading-snug">
                                        {{ $aboutImageQuote }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Narasi Deskripsi, 3 Nilai Luhur & Dekorasi -->
            <div class="lg:col-span-7 xl:col-span-7 space-y-6 sm:space-y-7 reveal-on-scroll">
                
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-[13px] font-bold uppercase tracking-wider transition-transform hover:scale-105 duration-200">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>{{ $aboutBadge }}</span>
                </div>

                <div class="space-y-2">
                    <h2 id="about-heading" class="text-2xl sm:text-3xl md:text-4xl lg:text-[2.65rem] font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-[1.2]">
                        {{ $aboutTitle }}
                    </h2>
                    <div class="w-20 h-1 rounded-full bg-gradient-to-r from-amber-500 via-emerald-600 to-transparent"></div>
                </div>

                <!-- Narrative Paragraphs (Summary Portion) -->
                <div class="space-y-3.5 text-stone-600 dark:text-stone-300 text-sm sm:text-[15px] leading-relaxed line-clamp-4">
                    {!! $aboutContent !!}
                </div>

                <!-- CTA Button to Full About Us Page -->
                <div class="pt-1">
                    <a 
                        href="{{ route('tentang-kami') }}" 
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-[#0D5B3A] hover:bg-[#09472D] dark:bg-emerald-600 dark:hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm shadow-md transition-all duration-200 transform hover:-translate-y-0.5 group cursor-pointer"
                    >
                        <span>Baca Selengkapnya Tentang Kami</span>
                        <svg class="w-4 h-4 text-amber-300 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

                <!-- 3 Core Values Mini Cards Stack with Staggered Scroll Reveals -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 pt-2">
                    <!-- Pillar 1 -->
                    <div class="p-4 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 hover:border-emerald-700/40 dark:hover:border-emerald-400/40 shadow-sm transition-all duration-300 group card-shine hover:-translate-y-1.5 hover:shadow-lg reveal-on-scroll reveal-stagger-1">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-[#0D6E42] dark:text-emerald-300 flex items-center justify-center font-bold text-sm mb-2.5 border border-emerald-200 dark:border-emerald-800/40 group-hover:scale-110 transition-transform">
                            1
                        </div>
                        <h3 class="font-bold text-[#143D2D] dark:text-emerald-200 text-sm">{{ $pillar1Title }}</h3>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1 leading-snug">
                            {{ $pillar1Desc }}
                        </p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute -right-5 bottom-0 size-22 opacity-7"><path d="M12 5a3 3 0 1 1 3 3m-3-3a3 3 0 1 0-3 3m3-3v1M9 8a3 3 0 1 0 3 3M9 8h1m5 0a3 3 0 1 1-3 3m3-3h-1m-2 3v-1"/><circle cx="12" cy="8" r="2"/><path d="M12 10v12"/><path d="M12 22c4.2 0 7-1.667 7-5-4.2 0-7 1.667-7 5Z"/><path d="M12 22c-4.2 0-7-1.667-7-5 4.2 0 7 1.667 7 5Z"/></svg>
                    </div>

                    <!-- Pillar 2 -->
                    <div class="p-4 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-amber-500/20 hover:border-amber-700/40 dark:hover:border-amber-400/40 shadow-sm transition-all duration-300 group card-shine hover:-translate-y-1.5 hover:shadow-lg reveal-on-scroll reveal-stagger-2">
                        <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 flex items-center justify-center font-bold text-sm mb-2.5 border border-amber-200 dark:border-amber-800/40 group-hover:scale-110 transition-transform">
                            2
                        </div>
                        <h3 class="font-bold text-[#143D2D] dark:text-amber-200 text-sm">{{ $pillar2Title }}</h3>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1 leading-snug">
                            {{ $pillar2Desc }}
                        </p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute -right-5 bottom-0 size-22 opacity-7"><path d="M12 5a3 3 0 1 1 3 3m-3-3a3 3 0 1 0-3 3m3-3v1M9 8a3 3 0 1 0 3 3M9 8h1m5 0a3 3 0 1 1-3 3m3-3h-1m-2 3v-1"/><circle cx="12" cy="8" r="2"/><path d="M12 10v12"/><path d="M12 22c4.2 0 7-1.667 7-5-4.2 0-7 1.667-7 5Z"/><path d="M12 22c-4.2 0-7-1.667-7-5 4.2 0 7 1.667 7 5Z"/></svg>
                    </div>

                    <!-- Pillar 3 -->
                    <div class="col-span-2 sm:col-span-1 p-4 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 hover:border-emerald-700/40 dark:hover:border-emerald-400/40 shadow-sm transition-all duration-300 group card-shine hover:-translate-y-1.5 hover:shadow-lg reveal-on-scroll reveal-stagger-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-[#0D6E42] dark:text-emerald-300 flex items-center justify-center font-bold text-sm mb-2.5 border border-emerald-200 dark:border-emerald-800/40 group-hover:scale-110 transition-transform">
                            3
                        </div>
                        <h3 class="font-bold text-[#143D2D] dark:text-emerald-200 text-sm">{{ $pillar3Title }}</h3>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-1 leading-snug">
                            {{ $pillar3Desc }}
                        </p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute -right-5 bottom-0 size-22 opacity-7"><path d="M12 5a3 3 0 1 1 3 3m-3-3a3 3 0 1 0-3 3m3-3v1M9 8a3 3 0 1 0 3 3M9 8h1m5 0a3 3 0 1 1-3 3m3-3h-1m-2 3v-1"/><circle cx="12" cy="8" r="2"/><path d="M12 10v12"/><path d="M12 22c4.2 0 7-1.667 7-5-4.2 0-7 1.667-7 5Z"/><path d="M12 22c-4.2 0-7-1.667-7-5 4.2 0 7 1.667 7 5Z"/></svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: PROGRAM DONASI & PENYALURAN DANA (DYNAMIC FROM DB) -->
    <section 
        id="donasi" 
        aria-labelledby="donasi-heading" 
        x-data="homeDonasiController(@js($donorsList))" 
        class="py-20 sm:py-28 px-6 sm:px-10 lg:px-16 max-w-[1440px] mx-auto relative overflow-hidden"
    >
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-[900px] h-[400px] bg-gradient-to-r from-amber-500/10 via-emerald-600/10 to-amber-600/10 blur-3xl pointer-events-none rounded-full"></div>

        <!-- Section Header -->
        <header class="text-center max-w-3xl mx-auto space-y-4 mb-12 sm:mb-16 relative z-10 reveal-on-scroll">
            <div class="flex flex-wrap items-center justify-center gap-2.5">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-sm font-bold uppercase tracking-wider transition-transform hover:scale-105 duration-200">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Dāna Paramita — Ladang Kebajikan</span>
                </div>
            </div>
            <h2 id="donasi-heading" class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-tight">
                Program Donasi & Penyaluran Dana Umat
            </h2>
            <p class="text-stone-600 dark:text-stone-300 text-sm sm:text-base leading-relaxed">
                Salurkan kebajikan dan cinta kasih Anda melalui berbagai program dana resmi Vihara Sāmaggi Gāma yang dikelola secara transparan, akuntabel, dan dinaungi oleh Bhikkhu Sangha.
            </p>

            <!-- Filter Tabs -->
            <div class="pt-4 flex flex-wrap items-center justify-center gap-2 sm:gap-3">
                <button 
                    @click="danaTab = 'all'; visibleProgramsCount = 6" 
                    type="button" 
                    :class="danaTab === 'all' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md border-emerald-700 dark:border-emerald-500 scale-105' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                    class="px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border transition-all duration-200 cursor-pointer hover:-translate-y-0.5"
                >
                    Semua Program ({{ $donationPrograms->count() }})
                </button>
                <button 
                    @click="danaTab = 'open'; visibleProgramsCount = 6" 
                    type="button" 
                    :class="danaTab === 'open' ? 'bg-emerald-600 dark:bg-emerald-600 text-white shadow-md border-emerald-700 dark:border-emerald-500 scale-105' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                    class="px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border transition-all duration-200 cursor-pointer hover:-translate-y-0.5 flex items-center gap-1.5"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sedang Dibuka ({{ $donationPrograms->where('status', 'aktif')->count() }})</span>
                </button>
                <button 
                    @click="danaTab = 'closed'; visibleProgramsCount = 6" 
                    type="button" 
                    :class="danaTab === 'closed' ? 'bg-stone-700 dark:bg-stone-700 text-white shadow-md border-stone-800 scale-105' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                    class="px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border transition-all duration-200 cursor-pointer hover:-translate-y-0.5 flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Telah Ditutup ({{ $donationPrograms->where('status', '!=', 'aktif')->count() }})</span>
                </button>
            </div>
        </header>

        <!-- =========================================================================
             TRANSPARENCY METRICS DASHBOARD
             ========================================================================= -->
        <div class="mb-12 relative reveal-on-scroll">
            <div class="absolute -right-12 -top-12 w-64 h-64 bg-amber-500/10 dark:bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-12 -bottom-12 w-64 h-64 bg-emerald-600/10 dark:bg-amber-400/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 items-stretch">
                
                <!-- 1. Total Dana Akumulasi Terkumpul -->
                <div class="p-5 sm:p-6 rounded-3xl bg-white/90 dark:bg-[#071610]/90 backdrop-blur-md border border-emerald-500/30 dark:border-emerald-500/25 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-[11px] font-black uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            <span>Total Dāna Terkumpul</span>
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-[#0D5B3A] via-[#8C6B1F] to-[#0D5B3A] dark:from-emerald-300 dark:via-amber-300 dark:to-emerald-300 tracking-tight">
                            Rp {{ number_format($totalDonationsCollected, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="pt-2 border-t border-stone-200/80 dark:border-emerald-950/80 flex items-center justify-between text-[11px]">
                        <span class="text-stone-500 dark:text-stone-400 font-medium">Akuntabilitas:</span>
                        <a href="{{ route('donasi') . '#donatur' }}" class="font-bold text-[#0D6E42] dark:text-emerald-300 hover:underline flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>Terverifikasi</span>
                            <svg class="w-3 h-3 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>

                <!-- 2. Total Pengeluaran & Penyaluran Dana -->
                <div class="p-5 sm:p-6 rounded-3xl bg-white/90 dark:bg-[#071610]/90 backdrop-blur-md border border-rose-500/25 dark:border-rose-500/20 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-3">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-[11px] font-black uppercase tracking-wider text-rose-800 dark:text-rose-300">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span>Total Pengeluaran Dāna</span>
                            </div>
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-rose-700 dark:text-rose-400 tracking-tight">
                            Rp {{ number_format($totalExpensesCollected, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="pt-2 border-t border-stone-200/80 dark:border-emerald-950/80 flex items-center justify-between text-[11px]">
                        <span class="text-stone-500 dark:text-stone-400 font-medium">Penggunaan:</span>
                        <a href="{{ route('pengeluaran.public') }}" class="font-bold text-stone-700 dark:text-stone-300 hover:text-rose-600 dark:hover:text-rose-400 flex items-center gap-1 transition-colors">
                            <svg class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            <span>Laporan Dana</span>
                        </a>
                    </div>
                </div>

                <!-- 3. Partisipasi Donatur Umat -->
                <div class="p-5 sm:p-6 rounded-3xl bg-white/70 dark:bg-[#071610]/70 backdrop-blur-md border border-stone-200/80 dark:border-emerald-950/60 shadow-xs flex flex-col justify-between space-y-3">
                    <div class="space-y-1">
                        <div class="text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                            Partisipasi Donatur
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-[#143D2D] dark:text-[#E8F3EE]">
                            {{ number_format($totalDonorsCount, 0, ',', '.') }} <span class="text-xs font-bold text-stone-500">Umat</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-stone-200/60 dark:border-emerald-950/60 text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Maha Dānapati & Kalyānamitta</span>
                    </div>
                </div>

                <!-- 4. Program Donasi Aktif -->
                <div class="p-5 sm:p-6 rounded-3xl bg-white/70 dark:bg-[#071610]/70 backdrop-blur-md border border-stone-200/80 dark:border-emerald-950/60 shadow-xs flex flex-col justify-between space-y-3">
                    <div class="space-y-1">
                        <div class="text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                            Program Kebajikan
                        </div>
                        <div class="text-xl sm:text-2xl font-black text-[#143D2D] dark:text-[#E8F3EE]">
                            {{ $donationPrograms->count() }} <span class="text-xs font-bold text-stone-500">Program</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-stone-200/60 dark:border-emerald-950/60 text-[11px] text-amber-700 dark:text-amber-400 font-semibold flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>{{ $activeProgramsCount }} Sedang Berjalan</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Dynamic Campaigns Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 relative z-10">
            <x-items.donasi-items :data="$donationPrograms" :sanghaMembers="$sanghaMembers" />
        </div>

        <!-- Load More Button if > 6 -->
        @if ($donationPrograms->count() > 6)
            <template x-if="visibleProgramsCount < {{ $donationPrograms->count() }}">
                <div class="pt-10 flex flex-col items-center justify-center gap-3 relative z-10">
                    <button 
                        type="button" 
                        @click="loadMorePrograms()" 
                        class="px-8 py-3.5 rounded-2xl bg-white dark:bg-[#071811] hover:bg-[#0D5B3A] hover:text-white dark:hover:bg-[#0D5B3A] text-stone-800 dark:text-stone-200 border border-stone-300 dark:border-emerald-500/30 text-xs sm:text-sm font-extrabold shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2.5 cursor-pointer group"
                    >
                        <svg class="w-4 h-4 text-amber-500 group-hover:text-amber-300 transition-transform duration-300 group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        <span>Tampilkan Program Lainnya</span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] bg-stone-100 dark:bg-emerald-950 text-stone-600 dark:text-stone-300 group-hover:bg-black/20 group-hover:text-white font-bold" x-text="'+' + Math.min(6, {{ $donationPrograms->count() }} - visibleProgramsCount)"></span>
                    </button>
                </div>
            </template>
        @endif

        @if($donationPrograms->count() > 0)
            <!-- Bottom Link to Portal Donasi -->
            <div class="pt-10 text-center relative z-10">
                <a 
                    href="{{ route('donasi') }}" 
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-[#FAF5ED] dark:bg-[#071710] hover:bg-[#0D5B3A] hover:text-white dark:hover:bg-[#0D5B3A] text-stone-800 dark:text-stone-200 font-extrabold text-xs sm:text-sm border border-stone-300/80 dark:border-emerald-500/30 transition-all duration-200 shadow-sm"
                >
                    <span>Lihat Semua Donasi</span>
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        @endif

        <!-- =========================================================================
             MODAL 0: DETAIL LENGKAP PROGRAM DONASI (DESKRIPSI LENGKAP)
             ========================================================================= -->
        <template x-teleport="body">
            <div 
                x-show="programDetailModal" 
                x-cloak 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[105] w-screen h-screen min-h-screen bg-black/85 backdrop-blur-2xl flex items-center justify-center p-3 sm:p-6 overflow-y-auto"
                @keydown.escape.window="programDetailModal = false"
            >
                <div 
                    @click.away="programDetailModal = false" 
                    class="relative max-w-3xl w-full bg-[#FAF5ED] dark:bg-[#071710] rounded-[2.5rem] overflow-hidden border border-amber-500/40 dark:border-emerald-500/30 shadow-2xl max-h-[92vh] flex flex-col my-auto"
                >
                    <template x-if="selectedProgramDetail">
                        <div class="flex flex-col h-full overflow-hidden">
                            
                            <!-- Banner Image Header with Gradient & Badges -->
                            <div class="relative aspect-[21/9] sm:aspect-[2.4/1] w-full overflow-hidden bg-stone-900 shrink-0">
                                <img 
                                    :src="selectedProgramDetail.coverImg" 
                                    :alt="selectedProgramDetail.title" 
                                    class="w-full h-full object-cover"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-[#071710] via-black/40 to-black/30 pointer-events-none"></div>

                                <!-- Close Button Top Right -->
                                <button 
                                    @click="programDetailModal = false" 
                                    type="button" 
                                    class="absolute top-4 right-4 z-20 p-2.5 rounded-full bg-black/60 hover:bg-black/80 text-white transition-colors cursor-pointer border border-white/20 shadow-md"
                                    aria-label="Tutup Detail Program"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>

                                <!-- Category & Status Top Left -->
                                <div class="absolute top-4 left-4 z-10 flex flex-wrap items-center gap-2">
                                    <span class="px-3 py-1 rounded-full bg-black/60 backdrop-blur-md text-amber-300 text-[10.5px] font-black uppercase tracking-wider border border-white/20 shadow-xs" x-text="selectedProgramDetail.category"></span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10.5px] font-extrabold uppercase tracking-wider backdrop-blur-md border shadow-xs" :class="selectedProgramDetail.isOpen ? 'bg-emerald-600/90 text-white border-emerald-400/40' : 'bg-stone-800/90 text-amber-300 border-amber-500/30'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="selectedProgramDetail.isOpen ? 'bg-emerald-300 animate-ping' : 'bg-amber-400'"></span>
                                        <span x-text="selectedProgramDetail.isOpen ? 'Sedang Dibuka' : 'Target Terpenuhi'"></span>
                                    </span>
                                </div>

                                <!-- Title Overlaid at Bottom of Banner -->
                                <div class="absolute bottom-4 left-5 right-5 z-10">
                                    <h2 class="text-lg sm:text-2xl font-black text-white tracking-tight leading-snug drop-shadow-md" x-text="selectedProgramDetail.title"></h2>
                                </div>
                            </div>

                            <!-- Modal Body: Scrollable Details -->
                            <div class="p-6 sm:p-8 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                                
                                <!-- Progress KPI Cards -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-emerald-500/30 dark:border-emerald-500/20 text-center space-y-0.5 shadow-xs">
                                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Terkumpul</div>
                                        <div class="text-sm sm:text-base font-black text-[#0D5B3A] dark:text-emerald-400 truncate" x-text="formatRupiah(selectedProgramDetail.collected)"></div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-stone-200 dark:border-emerald-500/20 text-center space-y-0.5 shadow-xs">
                                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Target Anggaran</div>
                                        <div class="text-sm sm:text-base font-black text-stone-800 dark:text-stone-200 truncate" x-text="formatRupiah(selectedProgramDetail.target)"></div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-amber-500/30 dark:border-amber-500/20 text-center space-y-0.5 shadow-xs">
                                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Capaian</div>
                                        <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400" x-text="selectedProgramDetail.percent + '%'"></div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-stone-200 dark:border-emerald-500/20 text-center space-y-0.5 shadow-xs">
                                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Donatur</div>
                                        <div class="text-sm sm:text-base font-black text-[#0D6E42] dark:text-emerald-300" x-text="selectedProgramDetail.donorsCount + ' Umat'"></div>
                                    </div>
                                </div>

                                <!-- Progress Track -->
                                <div class="space-y-1.5 p-4 rounded-2xl bg-white dark:bg-[#0c2218] border border-stone-200/80 dark:border-emerald-500/20 shadow-xs">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-stone-600 dark:text-stone-300">Kemajuan Penyaluran Dāna</span>
                                        <span class="font-extrabold text-[#0D5B3A] dark:text-emerald-400" x-text="selectedProgramDetail.percent + '% tercapai'"></span>
                                    </div>
                                    <div class="w-full bg-stone-200/80 dark:bg-emerald-950/70 h-3 rounded-full overflow-hidden p-0.5 border border-stone-300/40 dark:border-emerald-800/40">
                                        <div class="bg-gradient-to-r from-emerald-500 via-emerald-400 to-amber-400 h-full rounded-full transition-all duration-700" :style="'width: ' + Math.min(selectedProgramDetail.percent, 100) + '%'"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-stone-500 dark:text-stone-400 pt-0.5">
                                        <span>Status: <strong class="text-stone-700 dark:text-stone-200" x-text="selectedProgramDetail.isOpen ? 'Program Terbuka' : 'Target Telah Terpenuhi'"></strong></span>
                                        <span class="font-semibold text-amber-700 dark:text-amber-400" x-text="selectedProgramDetail.daysLeft"></span>
                                    </div>
                                </div>

                                <!-- Deskripsi Lengkap Program (Formatted HTML / Text) -->
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-[#0D5B3A] dark:text-emerald-400 border-b border-stone-200/80 dark:border-emerald-500/20 pb-2">
                                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Tentang & Latar Belakang Program</span>
                                    </div>

                                    <div 
                                        class="text-stone-700 dark:text-stone-300 text-xs sm:text-sm leading-relaxed space-y-3 bg-white/70 dark:bg-[#0c2218]/70 p-5 rounded-2xl border border-stone-200/70 dark:border-emerald-500/15"
                                        x-html="selectedProgramDetail.content"
                                    ></div>
                                </div>

                                <!-- Bhikkhu Pembina / Penanggung Jawab Program Card -->
                                <template x-if="selectedProgramDetail.pembina && selectedProgramDetail.pembina.length > 0">
                                    <div class="p-3.5 sm:p-4 rounded-2xl bg-amber-500/10 dark:bg-amber-500/5 border border-amber-500/25 space-y-2">
                                        <div class="flex items-center justify-between border-b border-amber-500/20 pb-1.5">
                                            <span class="text-[9.5px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-900 dark:text-amber-300">
                                                Bhikkhu Pembina / Penanggung Jawab
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <template x-for="(m, i) in selectedProgramDetail.pembina" :key="i">
                                                <div class="flex items-center gap-2.5 p-2 rounded-xl bg-white/70 dark:bg-emerald-950/40 border border-amber-500/15">
                                                    <img :src="m.photo_url || '{{ asset('images/bhikkhu-sangha.jpg') }}'" :alt="m.name" class="w-9 h-9 rounded-full object-cover border border-amber-400 ring-1 ring-amber-300/30 shrink-0" />
                                                    <div class="min-w-0 flex-1">
                                                        <div class="text-xs font-black text-[#143D2D] dark:text-[#E8F3EE] truncate" x-text="m.name"></div>
                                                        <div class="text-[10.5px] text-[#0D6E42] dark:text-emerald-400 font-bold truncate" x-text="m.title || 'Bhikkhu Pembina'"></div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Admin / Panitia Pelaksana Program Card -->
                                <template x-if="selectedProgramDetail.admins && selectedProgramDetail.admins.length > 0">
                                    <div class="p-3.5 sm:p-4 rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/5 border border-emerald-500/25 space-y-2">
                                        <div class="flex items-center justify-between border-b border-emerald-500/20 pb-1.5">
                                            <span class="text-[9.5px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/20 text-[#0D5B3A] dark:text-emerald-300">
                                                Admin / Panitia Pelaksana
                                            </span>
                                            <span class="text-[10px] text-stone-500 dark:text-stone-400 font-bold">
                                                Penanggung Jawab Program
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                            <template x-for="(admin, i) in selectedProgramDetail.admins" :key="i">
                                                <div class="p-2 rounded-xl bg-white/70 dark:bg-emerald-950/40 border border-emerald-500/15 space-y-0.5">
                                                    <span class="text-[9px] uppercase font-bold text-[#0D6E42] dark:text-emerald-400 block truncate" x-text="admin.role || 'Pengurus'"></span>
                                                    <div class="text-xs font-black text-stone-900 dark:text-stone-100 truncate" x-text="admin.name"></div>
                                                    <template x-if="admin.phone">
                                                        <div class="text-[10px] text-stone-500 font-mono truncate" x-text="admin.phone"></div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <!-- Box Transparansi & Anumodana -->
                                <div class="p-4 rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/5 border border-emerald-500/20 flex items-start gap-3">
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <div class="space-y-1 text-xs text-stone-600 dark:text-stone-300">
                                        <span class="font-bold text-stone-900 dark:text-stone-100 block">Transparansi & Piagam Anumodana</span>
                                        <p class="text-[11px] leading-relaxed">
                                            Seluruh dana disalurkan langsung ke rekening resmi yayasan yang diawasi oleh Bhikkhu Sangha. Setiap donasi terverifikasi berhak menerima Piagam Anumodana digital resmi.
                                        </p>
                                    </div>
                                </div>

                            </div>

                            <!-- Modal Action Footer -->
                            <div class="p-4 sm:p-5 bg-stone-100 dark:bg-[#06140e] border-t border-stone-200 dark:border-emerald-900/50 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                                <div class="text-xs text-stone-500 dark:text-stone-400 text-center sm:text-left">
                                    Penyaluran resmi Vihara Sāmaggi Gāma
                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-2.5 w-full sm:w-auto">
                                    <!-- Button Lihat Daftar Donatur -->
                                    <button 
                                        @click="programDetailModal = false; openItemDonorsModal({id: selectedProgramDetail.id, title: selectedProgramDetail.title, categoryName: selectedProgramDetail.category, collected: selectedProgramDetail.collected, target: selectedProgramDetail.target, percent: selectedProgramDetail.percent, donorsCount: selectedProgramDetail.donorsCount, status: selectedProgramDetail.status, pembina: selectedProgramDetail.pembina, admins: selectedProgramDetail.admins})"
                                        type="button" 
                                        class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-white dark:bg-emerald-950/70 hover:bg-stone-100 dark:hover:bg-emerald-900 text-[#0D5B3A] dark:text-emerald-300 border border-emerald-500/30 text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5 shadow-2xs"
                                    >
                                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <span>Daftar Donatur (<span x-text="selectedProgramDetail.donorsCount"></span>)</span>
                                    </button>

                                    <!-- Button Salurkan Dana (If Open) -->
                                    <template x-if="selectedProgramDetail.isOpen">
                                        <button 
                                            @click="programDetailModal = false; openDanaModal(selectedProgramDetail.title, selectedProgramDetail.category, selectedProgramDetail.pembina, selectedProgramDetail.admins)"
                                            type="button" 
                                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 bg-[#0D5B3A] hover:bg-[#09472D] dark:bg-emerald-700 dark:hover:bg-emerald-600 text-white font-extrabold py-2.5 px-5 rounded-xl text-xs sm:text-sm shadow-md hover:shadow-lg transition-all cursor-pointer transform hover:-translate-y-0.5"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-300 shrink-0"><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                                            <span>Salurkan Dāna Sekarang</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- =========================================================================
             MODAL 1: DAFTAR DONATUR PER PROGRAM
             ========================================================================= -->
        <template x-teleport="body">
            <div 
                x-show="donorModal" 
                x-cloak 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/85 backdrop-blur-2xl flex items-center justify-center p-3 sm:p-6 overflow-y-auto"
                @keydown.escape.window="donorModal = false"
            >
                <div 
                    @click.away="donorModal = false" 
                    class="relative max-w-4xl w-full bg-[#FAF5ED] dark:bg-[#071710] rounded-[2rem] overflow-hidden border border-amber-500/40 dark:border-emerald-500/30 shadow-2xl max-h-[92vh] flex flex-col my-auto"
                >
                    <template x-if="selectedDonorCampaign">
                        <div class="flex flex-col h-full overflow-hidden">
                            
                            <!-- Top Header Banner -->
                            <div class="relative bg-gradient-to-r from-[#0a2318] via-[#143D2D] to-[#0a2318] text-white p-6 sm:p-7 shrink-0 border-b border-amber-500/30">
                                <button 
                                    @click="donorModal = false" 
                                    type="button" 
                                    class="absolute top-5 right-5 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer border border-white/20 shadow-sm"
                                    aria-label="Tutup Modal Donatur"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>

                                <div class="space-y-2 pr-12">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-3 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-[11px] font-black uppercase tracking-wider border border-amber-500/40" x-text="selectedDonorCampaign.categoryName"></span>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider border" :class="selectedDonorCampaign.status === 'open' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-stone-800/60 text-amber-300 border-amber-500/30'">
                                            <span class="w-2 h-2 rounded-full" :class="selectedDonorCampaign.status === 'open' ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                                            <span x-text="selectedDonorCampaign.status === 'open' ? 'Program Terbuka' : 'Target Telah Terpenuhi'"></span>
                                        </span>
                                    </div>

                                    <h2 class="text-lg sm:text-2xl font-serif font-black tracking-tight text-[#FAF5ED]" x-text="selectedDonorCampaign.title"></h2>
                                    
                                    <!-- Compact Progress Bar in Header -->
                                    <div class="pt-2 space-y-1">
                                        <div class="flex items-center justify-between text-xs text-stone-300">
                                            <span>Progres Partisipasi</span>
                                            <span class="text-amber-300 font-extrabold" x-text="formatRupiah(selectedDonorCampaign.collected) + ' (' + selectedDonorCampaign.percent + '%)'"></span>
                                        </div>
                                        <div class="w-full bg-black/40 h-2 rounded-full overflow-hidden p-0.5 border border-white/20">
                                            <div class="bg-gradient-to-r from-emerald-400 via-amber-400 to-yellow-300 h-full rounded-full transition-all duration-700" :style="'width: ' + Math.min(selectedDonorCampaign.percent, 100) + '%'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Body -->
                            <div class="p-5 sm:p-7 space-y-5 overflow-y-auto grow">
                                
                                <!-- 4 KPI Stat Cards -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-stone-200 dark:border-emerald-500/20 text-center space-y-0.5 shadow-xs">
                                        <div class="text-[10.5px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Total Donatur</div>
                                        <div class="text-base sm:text-lg font-black text-[#0D6E42] dark:text-emerald-300" x-text="selectedDonorCampaign.donorsCount + ' Umat'"></div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-amber-500/30 dark:border-amber-500/20 text-center space-y-0.5 shadow-xs">
                                        <div class="text-[10.5px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Terkumpul</div>
                                        <div class="text-sm sm:text-base font-black text-[#0D5B3A] dark:text-emerald-400 truncate" x-text="formatRupiah(selectedDonorCampaign.collected)"></div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-stone-200 dark:border-emerald-500/20 text-center space-y-0.5 shadow-xs">
                                        <div class="text-[10.5px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Target Anggaran</div>
                                        <div class="text-sm sm:text-base font-black text-stone-800 dark:text-stone-200 truncate" x-text="formatRupiah(selectedDonorCampaign.target)"></div>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-amber-500/30 dark:border-amber-500/20 text-center space-y-0.5 shadow-xs">
                                        <div class="text-[10.5px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Capaian</div>
                                        <div class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400" x-text="selectedDonorCampaign.percent + '%'"></div>
                                    </div>
                                </div>

                                <!-- Search Bar -->
                                <div class="relative w-full">
                                    <input 
                                        type="text" 
                                        x-model="itemDonorSearch" 
                                        placeholder="Cari nama donatur atau nomor referensi..." 
                                        class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-white dark:bg-[#0c2218] border border-stone-300/80 dark:border-emerald-500/30 text-xs text-stone-800 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-amber-500 shadow-inner"
                                    />
                                    <svg class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>

                                <!-- Table Container with Horizontal Scroll -->
                                <div class="rounded-2xl border border-stone-200 dark:border-emerald-950 overflow-hidden bg-white dark:bg-[#0c2218] shadow-xs">
                                    <div class="overflow-x-auto custom-scrollbar">
                                        <table class="w-full text-left text-xs min-w-[540px]">
                                            <thead class="bg-stone-50 dark:bg-[#06140e] border-b border-stone-200 dark:border-emerald-950 text-stone-500 font-bold uppercase tracking-wider text-[10.5px]">
                                                <tr>
                                                    <th class="py-3 px-4 text-center w-12 shrink-0">No</th>
                                                    <th class="py-3 px-4 min-w-[160px]">Nama Donatur</th>
                                                    <th class="py-3 px-4 whitespace-nowrap">Jumlah Dāna</th>
                                                    <th class="py-3 px-4 text-right whitespace-nowrap">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                                                <template x-for="(d, idx) in getCampaignDonors(selectedDonorCampaign.id)" :key="d.id">
                                                    <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/30 transition-colors">
                                                        <td class="py-3 px-4 text-center text-stone-400 whitespace-nowrap" x-text="idx + 1"></td>
                                                        <td class="py-3 px-4">
                                                            <div class="font-bold text-stone-900 dark:text-stone-100 text-xs" x-text="d.name"></div>
                                                            <div class="text-[10px] text-stone-400 font-mono" x-text="d.ref"></div>
                                                        </td>
                                                        <td class="py-3 px-4 font-black text-amber-600 dark:text-amber-400 whitespace-nowrap tabular-nums" x-text="d.amount"></td>
                                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                                            <template x-if="d.hasCertificate">
                                                                <button 
                                                                    @click="openCertificateModal(d)"
                                                                    type="button" 
                                                                    class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-800 dark:text-amber-300 text-[11px] font-bold border border-amber-500/30 cursor-pointer transition-all hover:scale-105"
                                                                >
                                                                    Piagam
                                                                </button>
                                                            </template>
                                                            <template x-if="!d.hasCertificate">
                                                                <span class="text-[11px] text-stone-400 font-medium italic">-</span>
                                                            </template>
                                                        </td>
                                                    </tr>
                                                </template>

                                                <!-- Empty State when No Donors Verified -->
                                                <template x-if="getCampaignDonors(selectedDonorCampaign.id).length === 0">
                                                    <tr>
                                                        <td colspan="5" class="py-12 px-4 text-center space-y-2.5">
                                                            <div class="w-12 h-12 rounded-full bg-emerald-500/10 dark:bg-emerald-400/10 text-[#0D6E42] dark:text-emerald-300 flex items-center justify-center mx-auto shadow-inner">
                                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            </div>
                                                            <div class="space-y-1 max-w-xs mx-auto">
                                                                <div class="text-xs sm:text-sm font-black text-stone-700 dark:text-stone-300">
                                                                    Belum Ada Catatan Donatur Terverifikasi
                                                                </div>
                                                                <p class="text-[11px] text-stone-500 dark:text-stone-400 leading-tight">
                                                                    Jadilah yang pertama menyalurkan dāna kebajikan untuk program ini.
                                                                </p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <!-- Modal Action Footer -->
                            <div class="p-4 sm:p-5 bg-stone-100 dark:bg-[#06140e] border-t border-stone-200 dark:border-emerald-900/50 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                                <div class="text-xs text-stone-500 dark:text-stone-400 text-center sm:text-left">
                                    Dana disalurkan 100% untuk peruntukan program kebajikan.
                                </div>

                                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                                    <template x-if="selectedDonorCampaign.status === 'open'">
                                        <button 
                                            @click="donorModal = false; openDanaModal(selectedDonorCampaign.title, selectedDonorCampaign.categoryName, selectedDonorCampaign.pembina, selectedDonorCampaign.admins)"
                                            type="button" 
                                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#0D5B3A] hover:bg-[#09472D] dark:bg-emerald-700 dark:hover:bg-emerald-600 text-white font-bold py-2.5 px-5 rounded-xl text-xs sm:text-sm shadow-sm transition-all cursor-pointer"
                                        >
                                            <svg class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                            <span>Berdana untuk Program Ini</span>
                                        </button>
                                    </template>
                                    
                                    <button 
                                        @click="donorModal = false"
                                        type="button" 
                                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-stone-200 dark:bg-emerald-950 hover:bg-stone-300 dark:hover:bg-emerald-900 text-stone-700 dark:text-stone-300 text-xs font-bold transition-all cursor-pointer"
                                    >
                                        Tutup
                                    </button>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- =========================================================================
             MODAL 2: PIAGAM ANUMODANA OFFICIAL ARTBOARD PREVIEW (ONLY NAME & NOMINAL OVERLAY)
             ========================================================================= -->
        <template x-teleport="body">
            <div 
                x-show="certificateModal" 
                x-cloak 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[110] w-screen h-screen min-h-screen bg-black/90 backdrop-blur-2xl flex items-center justify-center p-2 sm:p-4 md:p-6 overflow-y-auto"
                @keydown.escape.window="certificateModal = false"
            >
                <div 
                    @click.away="certificateModal = false" 
                    class="relative max-w-4xl w-full bg-[#1A1814] rounded-3xl overflow-hidden border border-amber-500/40 shadow-2xl p-4 sm:p-6 space-y-4 my-auto"
                >
                    <template x-if="selectedCertificate">
                        <div class="space-y-4">
                            
                            <!-- Top Modal Header -->
                            <div class="flex items-center justify-between px-2 text-white">
                                <div class="space-y-0.5">
                                    <h4 class="font-serif font-black text-amber-400 text-sm sm:text-base" x-text="selectedCertificate.templateName"></h4>
                                    <p class="text-[11px] text-stone-400" x-text="'No. Registrasi: ' + selectedCertificate.certNo"></p>
                                </div>
                                <button 
                                    @click="certificateModal = false" 
                                    type="button" 
                                    class="p-1.5 rounded-full bg-stone-800 hover:bg-stone-700 text-stone-300 transition-colors cursor-pointer"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <!-- ==============================================================
                                 OFFICIAL PIAGAM ARTBOARD (EXACT FIT: ONLY NAMA & NOMINAL WRITTEN)
                                 ============================================================== -->
                            <div 
                                id="printable-certificate" 
                                class="relative w-full aspect-[1024/734] rounded-2xl overflow-hidden shadow-2xl border border-stone-200 dark:border-stone-800 bg-white select-none"
                            >
                                <!-- 1. Background Certificate Image from DB Template -->
                                <img 
                                    :src="selectedCertificate.templateBg" 
                                    alt="Piagam Maha Anumodana" 
                                    class="absolute inset-0 w-full h-full object-contain pointer-events-none"
                                />

                                <!-- 2. Nama Donatur (Positioned directly in the blank space under 'Kepada') -->
                                <div 
                                    class="absolute left-1/2 w-[80%] text-center pointer-events-none"
                                    style="top: 38%; transform: translate(-50%, -50%);"
                                >
                                    <span 
                                        class="font-serif font-bold text-[#0f172a] tracking-wide uppercase drop-shadow-xs block leading-tight break-words"
                                        :style="selectedCertificate.name && selectedCertificate.name.length > 50 ? 'font-size: clamp(9px, 1.4vw, 15px);' : (selectedCertificate.name && selectedCertificate.name.length > 25 ? 'font-size: clamp(11px, 1.8vw, 19px);' : 'font-size: clamp(12px, 2.2vw, 24px);')"
                                        x-text="selectedCertificate.name"
                                    ></span>
                                </div>

                                <!-- 3. Nominal Donasi (Positioned directly in the blank space under 'Vihara Samaggi Gama sebesar :') -->
                                <div 
                                    class="absolute left-1/2 w-[68%] text-center pointer-events-none"
                                    style="top: 59%; transform: translate(-50%, -50%);"
                                >
                                    <span 
                                        class="font-black text-[#0284c7] tracking-tight block truncate"
                                        style="font-size: clamp(16px, 3.1vw, 34px); line-height: 1.2;"
                                        x-text="selectedCertificate.amount"
                                    ></span>
                                </div>
                            </div>

                            <!-- Certificate Action Toolbar -->
                            <div class="pt-2 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="text-stone-400 text-[11px]">
                                    Ref: <strong class="text-amber-300" x-text="selectedCertificate.ref"></strong>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    
                                    <!-- Unduh Gambar Otomatis (Canvas Render) -->
                                    <button 
                                        @click="downloadCertificateImage()" 
                                        :disabled="isDownloadingImg"
                                        type="button" 
                                        class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-xs cursor-pointer flex items-center gap-1.5 transition-all transform hover:-translate-y-0.5"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span x-text="isDownloadingImg ? 'Menyiapkan Gambar...' : 'Unduh Gambar Piagam (PNG)'"></span>
                                    </button>

                                    <!-- Bagikan ke WhatsApp -->
                                    <a 
                                        :href="'https://wa.me/?text=' + encodeURIComponent('Namo Buddhaya, berikut adalah Piagam Maha Anumodana Dāna atas nama ' + selectedCertificate.name + ' sebesar ' + selectedCertificate.amount + ' (No. Registrasi: ' + selectedCertificate.certNo + ').')" 
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#25D366] hover:bg-[#1EBE5B] text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        <span>WhatsApp</span>
                                    </a>

                                    <button 
                                        @click="certificateModal = false" 
                                        type="button" 
                                        class="px-3.5 py-2 rounded-xl bg-stone-800 hover:bg-stone-700 text-stone-300 text-xs font-bold transition-all cursor-pointer"
                                    >
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Interactive Modal: Detail Rekening & QRIS Donasi -->
        <template x-teleport="body">
            <div 
                x-show="danaModal" 
                x-cloak 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
                @keydown.escape.window="danaModal = false"
            >
                <div 
                    @click.away="danaModal = false" 
                    class="relative max-w-lg w-full bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-[2.5rem] overflow-hidden border border-amber-500/30 dark:border-emerald-500/30 shadow-2xl flex flex-col max-h-[90vh] my-auto"
                >
                    <!-- Close Button -->
                    <button 
                        @click="danaModal = false" 
                        type="button" 
                        class="absolute top-5 right-5 z-30 p-2.5 rounded-full bg-stone-200/80 dark:bg-emerald-950/80 hover:bg-stone-300 dark:hover:bg-emerald-900 text-stone-700 dark:text-stone-300 transition-colors cursor-pointer border border-stone-300/40 dark:border-emerald-500/20 shadow-xs"
                        aria-label="Tutup Modal Donasi"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <!-- Scrollable Modal Content -->
                    <div class="p-6 sm:p-8 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                        
                        <!-- Modal Header -->
                        <div class="space-y-1.5 pr-10">
                            <span class="px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-[#0D6E42] dark:text-emerald-300 text-xs font-extrabold" x-text="selectedCategory"></span>
                            <h3 class="text-xl sm:text-2xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-snug" x-text="selectedCampaign"></h3>
                            <p class="text-xs text-stone-500 dark:text-stone-400">
                                Pilih metode penyaluran dana kebajikan yang paling nyaman bagi Anda:
                            </p>
                        </div>

                        <!-- Payment Method Switcher Tabs -->
                        <div class="p-1 rounded-2xl bg-stone-200/70 dark:bg-emerald-950/70 border border-stone-300/50 dark:border-emerald-500/20 grid grid-cols-2 gap-1">
                            <button 
                                @click="paymentTab = 'bank'"
                                type="button" 
                                :class="paymentTab === 'bank' ? 'bg-[#0D5B3A] text-white shadow-md' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white'"
                                class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                <span>Transfer Bank</span>
                            </button>

                            <button 
                                @click="paymentTab = 'qris'"
                                type="button" 
                                :class="paymentTab === 'qris' ? 'bg-[#0D5B3A] text-white shadow-md' : 'text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-white'"
                                class="py-2.5 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer relative"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                <span>QRIS</span>
                            </button>
                        </div>

                        <!-- TAB PANEL 1: TRANSFER BANK (INTEGRATED WITH DATABASE) -->
                        <div x-show="paymentTab === 'bank'" class="space-y-3">
                            @foreach($bankAccounts as $index => $bank)
                                <div 
                                    @click="selectedBankName = '{{ $bank['name'] }}'"
                                    :class="selectedBankName === '{{ $bank['name'] }}' ? 'border-[#0D5B3A] dark:border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/40 ring-2 ring-emerald-500/20' : 'border-stone-200/80 dark:border-emerald-500/20 bg-white dark:bg-[#071710]'"
                                    class="p-4 rounded-2xl border shadow-xs flex items-center justify-between gap-4 transition-all cursor-pointer hover:border-emerald-600/50"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded {{ $index === 0 ? 'bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300' : ($index === 1 ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-300') }} text-[10.5px] font-black">{{ $bank['name'] }}</span>
                                            <span class="text-[11px] text-stone-500 dark:text-stone-400">a.n. {{ $bank['holder'] }}</span>
                                        </div>
                                        <div class="text-base sm:text-lg font-black text-[#143D2D] dark:text-[#E8F3EE]">
                                            {{ $bank['account'] }}
                                        </div>
                                    </div>
                                    <button 
                                        @click.stop="copyAccount('{{ $bank['cleanAccount'] }}', '{{ $bank['id'] }}'); selectedBankName = '{{ $bank['name'] }}'" 
                                        type="button" 
                                        class="shrink-0 px-3.5 py-2 rounded-xl bg-stone-100 dark:bg-emerald-950/70 hover:bg-[#0D5B3A] hover:text-white dark:hover:bg-emerald-600 text-[#0D5B3A] dark:text-emerald-300 text-xs font-bold transition-all border border-emerald-500/20 flex items-center gap-1.5 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <span x-text="copiedBankId === '{{ $bank['id'] }}' ? 'Tersalin' : 'Salin'"></span>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <!-- TAB PANEL 2: QRIS / E-WALLET INSTANT SCAN -->
                        <div x-show="paymentTab === 'qris'" class="space-y-4">
                            
                            <div class="p-5 rounded-3xl bg-white dark:bg-[#071710] border border-stone-200/80 dark:border-emerald-500/20 shadow-md text-center space-y-4">
                                
                                <div class="flex items-center justify-between px-2 pb-2 border-b border-stone-200 dark:border-emerald-900/40 text-xs">
                                    <span class="font-black text-rose-600 dark:text-rose-400 tracking-wider">QRIS • STANDAR NASIONAL</span>
                                    <span class="font-bold text-stone-500 dark:text-stone-400 text-[11px]">GPN</span>
                                </div>

                                <div class="space-y-1">
                                    <h4 class="text-sm sm:text-base font-black text-[#143D2D] dark:text-[#E8F3EE]">
                                        VIHARA SĀMAGGI GĀMA
                                    </h4>
                                </div>

                                <!-- QRIS Barcode Image Frame -->
                                <div class="max-w-[240px] mx-auto p-3 rounded-2xl bg-white border-2 border-dashed border-emerald-600/40 shadow-inner group relative">
                                    <img 
                                        src="{{ asset($qrisImage) }}" 
                                        alt="QRIS Vihara Samaggi Gama" 
                                        class="w-full h-auto rounded-xl object-contain shadow-xs"
                                    />
                                </div>

                                <div class="pt-1 flex flex-wrap items-center justify-center gap-2">
                                    <a 
                                        href="{{ asset($qrisImage) }}" 
                                        download="QRIS_Vihara_Samaggi_Gama.jpg"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-stone-100 dark:bg-emerald-950/70 hover:bg-[#0D5B3A] hover:text-white dark:hover:bg-emerald-600 text-[#0D5B3A] dark:text-emerald-300 text-xs font-bold transition-all border border-emerald-500/20 cursor-pointer shadow-xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span>Unduh Gambar QRIS</span>
                                    </a>
                                </div>

                                <div class="pt-3 border-t border-stone-200 dark:border-emerald-900/40 space-y-1.5">
                                    <div class="text-[11px] font-bold text-stone-500 dark:text-stone-400">
                                        Mendukung Seluruh Aplikasi Perbankan & E-Wallet:
                                    </div>
                                    <div class="flex flex-wrap items-center justify-center gap-1.5 text-[10px] font-bold text-stone-600 dark:text-stone-300">
                                        <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-emerald-950/50">BCA Mobile</span>
                                        <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-emerald-950/50">Livin' Mandiri</span>
                                        <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-emerald-950/50">BRImo</span>
                                        <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-emerald-950/50">GoPay</span>
                                        <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-emerald-950/50">OVO</span>
                                        <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-emerald-950/50">DANA</span>
                                        <span class="px-2 py-0.5 rounded bg-stone-100 dark:bg-emerald-950/50">ShopeePay</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Mengetahui Dewan Bhikkhu Sangha Endorsement Card (Vertical Stack) -->
                        <div x-show="(selectedProgramPembina && selectedProgramPembina.length > 0) || {{ $sanghaMembers->isNotEmpty() ? 'true' : 'false' }}" class="p-3.5 sm:p-4 rounded-2xl bg-amber-500/10 dark:bg-amber-500/5 border border-amber-500/25 dark:border-amber-500/20 space-y-2.5 shadow-xs">
                            <div class="flex items-center justify-between border-b border-amber-500/20 pb-2">
                                <span class="text-[9.5px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-900 dark:text-amber-300">
                                    Mengetahui
                                </span>
                            </div>

                            <template x-if="selectedProgramPembina && selectedProgramPembina.length > 0">
                                <div class="space-y-2">
                                    <template x-for="(member, idx) in selectedProgramPembina" :key="idx">
                                        <div class="flex items-center gap-3 p-2 rounded-xl bg-white/70 dark:bg-emerald-950/40 border border-amber-500/15 dark:border-emerald-500/20 shadow-2xs">
                                            <img 
                                                :src="member.photo_url || '{{ asset('images/bhikkhu-sangha.jpg') }}'" 
                                                :alt="member.name" 
                                                class="w-11 h-11 sm:w-12 sm:h-12 rounded-full object-cover border-2 border-amber-500/60 ring-2 ring-amber-400/25 shadow-sm shrink-0" 
                                            />
                                            <div class="space-y-0.5 min-w-0 flex-1">
                                                <h4 class="text-xs sm:text-sm font-black text-[#143D2D] dark:text-[#E8F3EE] truncate" x-text="member.name"></h4>
                                                <p class="text-[11px] text-[#0D6E42] dark:text-emerald-400 font-bold truncate" x-text="member.title || 'Bhikkhu Pembina Vihara'"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!selectedProgramPembina || selectedProgramPembina.length === 0">
                                <div class="space-y-2">
                                    @foreach($sanghaMembers as $member)
                                        <div class="flex items-center gap-3 p-2 rounded-xl bg-white/70 dark:bg-emerald-950/40 border border-amber-500/15 dark:border-emerald-500/20 shadow-2xs">
                                            <img 
                                                src="{{ $member->photo_url }}" 
                                                alt="{{ $member->name }}" 
                                                class="w-11 h-11 sm:w-12 sm:h-12 rounded-full object-cover border-2 border-amber-500/60 ring-2 ring-amber-400/25 shadow-sm shrink-0" 
                                            />
                                            <div class="space-y-0.5 min-w-0 flex-1">
                                                <h4 class="text-xs sm:text-sm font-black text-[#143D2D] dark:text-[#E8F3EE] truncate">
                                                    {{ $member->name }}
                                                </h4>
                                                <p class="text-[11px] text-[#0D6E42] dark:text-emerald-400 font-bold truncate">
                                                    {{ $member->title ?: 'Bhikkhu Pembina Vihara' }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </template>

                            <p class="text-[10.5px] text-stone-500 dark:text-stone-400 leading-tight px-0.5">
                                Penyaluran dāna kebajikan dinaungi & dibina oleh Bhikkhu Sangha Vihara Sāmaggi Gāma.
                            </p>
                        </div>

                        <!-- Panitia Pelaksana & Konfirmasi Dāna Card -->
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/5 border border-emerald-500/25 dark:border-emerald-500/20 space-y-2.5 shadow-xs">
                            <div class="flex items-center justify-between border-b border-emerald-500/20 pb-2">
                                <span class="text-[9.5px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/20 text-[#0D5B3A] dark:text-emerald-300">
                                    Konfirmasi Penyaluran Dāna
                                </span>
                                <span class="text-[10.5px] text-[#0D5B3A] dark:text-emerald-300 font-extrabold">
                                    Panitia Pelaksana
                                </span>
                            </div>

                            <div class="space-y-2">
                                <template x-for="(admin, idx) in (selectedProgramAdmins && selectedProgramAdmins.length > 0 ? selectedProgramAdmins : defaultAdmins)" :key="idx">
                                    <div class="flex items-center justify-between gap-3 p-2.5 rounded-xl bg-white/80 dark:bg-emerald-950/40 border border-emerald-500/15 dark:border-emerald-500/20 shadow-2xs">
                                        <div class="space-y-0.5 min-w-0 flex-1">
                                            <span class="text-[9.5px] uppercase font-extrabold text-[#0D6E42] dark:text-emerald-400 block truncate" x-text="admin.role || 'Pengurus'"></span>
                                            <h4 class="text-xs sm:text-sm font-black text-[#143D2D] dark:text-[#E8F3EE] truncate" x-text="admin.name"></h4>
                                            <template x-if="admin.phone">
                                                <p class="text-[10px] text-stone-500 dark:text-stone-400 font-mono truncate" x-text="admin.phone"></p>
                                            </template>
                                        </div>

                                        <a 
                                            :href="getAdminWhatsAppUrl(admin)"
                                            target="_blank" 
                                            rel="noopener noreferrer" 
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#25D366] hover:bg-[#1EBE5B] text-white text-xs font-bold shadow-xs hover:shadow-md transition-all shrink-0 cursor-pointer transform hover:-translate-y-0.5 active:translate-y-0"
                                            title="Konfirmasi via WhatsApp"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0"><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9"></path><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1"></path></svg>
                                            <span>Konfirmasi</span>
                                        </a>
                                    </div>
                                </template>
                            </div>

                            <p class="text-[10.5px] text-stone-500 dark:text-stone-400 leading-tight px-0.5">
                                Kirimkan bukti transfer ke salah satu panitia di atas untuk verifikasi donasi Anda.
                            </p>
                        </div>

                        <p class="text-[10px] text-center text-stone-400 dark:text-stone-500 italic pt-1 pb-1">
                            Sabbe Sattā Bhavantu Sukhitattā — Semoga kebajikan ini melimpahkan kedamaian.
                        </p>

                    </div>
                </div>
            </div>
        </template>

    </section>

    <!-- ==========================================
         SECTION 4: GALERI SUASANA & MOMEN KEBERKAHAN (DYNAMIC FROM DB)
         ========================================== -->
    <section 
        id="galeri" 
        aria-labelledby="gallery-heading" 
        x-data="{ 
            activeTab: 'all', 
            previewModal: false, 
            activeImg: '', 
            activeTitle: '', 
            activeCat: '', 
            activeDesc: '',
            openPreview(img, title, cat, desc) {
                this.activeImg = img;
                this.activeTitle = title;
                this.activeCat = cat;
                this.activeDesc = desc;
                this.previewModal = true;
            }
        }" 
        class="py-20 sm:py-28 px-6 sm:px-10 lg:px-16 max-w-[1440px] mx-auto relative"
    >
        <div class="absolute top-1/3 left-1/2 -translate-x-1/2 w-full max-w-[800px] h-[350px] bg-gradient-to-r from-amber-500/10 via-emerald-600/10 to-amber-600/10 blur-3xl pointer-events-none rounded-full"></div>

        <!-- Section Header -->
        <header class="text-center max-w-3xl mx-auto space-y-4 mb-12 sm:mb-16 relative z-10 reveal-on-scroll">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-sm font-bold uppercase tracking-wider transition-transform hover:scale-105 duration-200">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Dokumentasi & Suasana Vihara</span>
            </div>
            <h2 id="gallery-heading" class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-tight">
                Galeri Suasana & Momen Keberkahan
            </h2>
            <p class="text-stone-600 dark:text-stone-300 text-sm sm:text-base leading-relaxed">
                Potret keindahan arsitektur sakral, ketenangan latihan meditasi, dan kehangatan puja bakti umat di Vihara Sāmaggi Gāma.
            </p>

            <!-- Interactive Category Filter Tabs -->
            <div class="pt-4 flex flex-wrap items-center justify-center gap-2 sm:gap-2.5">
                <button 
                    @click="activeTab = 'all'" 
                    type="button" 
                    :class="activeTab === 'all' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md border-emerald-700 dark:border-emerald-500 scale-105' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                    class="px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border transition-all duration-200 cursor-pointer hover:-translate-y-0.5"
                >
                    Semua Momen ({{ $galleryAlbums->count() }})
                </button>
                @foreach ($galleryCategories as $cat)
                    <button 
                        @click="activeTab = '{{ Str::slug($cat) }}'" 
                        type="button" 
                        :class="activeTab === '{{ Str::slug($cat) }}' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md border-emerald-700 dark:border-emerald-500 scale-105' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                        class="px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border transition-all duration-200 cursor-pointer hover:-translate-y-0.5"
                    >
                        {{ $cat }}
                    </button>
                @endforeach
            </div>
        </header>

        <!-- Dynamic Luxury Gallery Grid with Full Image Visibility -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 relative z-10">
            @foreach ($galleryAlbums as $alb)
                @php
                    $catSlug = Str::slug($alb->category);
                    $coverUrl = $alb->cover_image ? asset($alb->cover_image) : asset('images/gallery-altar.jpg');
                @endphp
                <article 
                    x-show="activeTab === 'all' || activeTab === '{{ $catSlug }}'" 
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="group relative rounded-[2rem] bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 hover:border-amber-500/50 dark:hover:border-emerald-400/50 shadow-md hover:shadow-2xl transition-all duration-500 cursor-pointer overflow-hidden flex flex-col justify-between card-shine hover:-translate-y-1.5 reveal-on-scroll"
                    @click="openPreview('{{ $coverUrl }}', '{{ addslashes($alb->title) }}', '{{ addslashes($alb->category) }}', '{{ addslashes($alb->description ?? '') }}')"
                >
                    <!-- 1. FULL PHOTO CONTAINER (Clean, uncluttered, 100% visible) -->
                    <div class="relative aspect-[16/10] overflow-hidden bg-stone-900">
                        <img 
                            src="{{ $coverUrl }}" 
                            alt="{{ $alb->title }}" 
                            class="w-full h-full object-cover group-hover:scale-108 transition-transform duration-700 ease-out"
                            loading="lazy"
                            decoding="async"
                        />
                        <!-- Subtle soft gradient on hover only -->
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300 pointer-events-none"></div>

                        <!-- Top Floating Category Pill -->
                        <div class="absolute top-3.5 left-3.5 z-10">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#FAF5ED]/95 dark:bg-[#081610]/95 backdrop-blur-md text-[#0D6E42] dark:text-emerald-300 text-[11px] font-bold border border-emerald-500/20 shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>{{ $alb->category }}</span>
                            </span>
                        </div>

                        <!-- Hover Magnifier Icon -->
                        <div class="absolute top-3.5 right-3.5 z-10 w-9 h-9 rounded-full bg-black/40 backdrop-blur-md border border-white/20 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-75 group-hover:scale-100 shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                        </div>
                    </div>

                    <!-- 2. CARD CONTENT (Placed neatly beneath the photo) -->
                    <div class="p-6 space-y-3 flex flex-col justify-between grow">
                        <div class="space-y-2">
                            <!-- Location & Date Pill -->
                            <div class="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-400 font-semibold">
                                <span class="flex items-center gap-1.5 font-bold">
                                    <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>{{ $alb->location ?: 'Vihara Sāmaggi Gāma' }}</span>
                                </span>
                                @if ($alb->event_date)
                                    <span>•</span>
                                    <span>{{ $alb->event_date->translatedFormat('d M Y') }}</span>
                                @endif
                            </div>

                            <!-- Title -->
                            <h3 class="font-extrabold text-base sm:text-lg tracking-tight text-[#143D2D] dark:text-[#E8F3EE] group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors line-clamp-1 leading-snug">
                                {{ $alb->title }}
                            </h3>

                            <!-- Description -->
                            @if ($alb->description)
                                <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 line-clamp-2 leading-relaxed">
                                    {{ $alb->description }}
                                </p>
                            @endif
                        </div>

                        <!-- Card Footer CTA -->
                        <div class="pt-3 border-t border-stone-200/80 dark:border-emerald-900/40 flex items-center justify-between text-xs font-bold text-[#0D6E42] dark:text-emerald-400 group-hover:translate-x-1 transition-transform">
                            <span>Lihat Foto Resolusi Penuh</span>
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- View All Gallery Button -->
        <div class="text-center pt-8 reveal-on-scroll">
            <a 
                href="{{ route('galeri') }}" 
                class="inline-flex items-center gap-2 px-7 py-3 rounded-full bg-stone-200/80 dark:bg-emerald-950/70 hover:bg-[#0D5B3A] hover:text-white dark:hover:bg-emerald-600 text-[#0D5B3A] dark:text-emerald-300 text-xs sm:text-sm font-bold border border-emerald-600/30 shadow-sm transition-all duration-300 transform hover:-translate-y-0.5"
            >
                <span>Lihat Semua Galeri</span>
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>

        <!-- Lightbox Preview Modal for Gallery -->
        <template x-teleport="body">
            <div 
                x-show="previewModal" 
                x-cloak 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/90 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
                @keydown.escape.window="previewModal = false"
            >
                <div 
                    @click.away="previewModal = false" 
                    class="relative max-w-4xl w-full bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-3xl overflow-hidden border border-amber-500/30 dark:border-emerald-500/30 shadow-2xl flex flex-col max-h-[90vh] my-auto"
                >
                    <!-- Close Button -->
                    <button 
                        @click="previewModal = false" 
                        type="button" 
                        class="absolute top-4 right-4 z-20 p-2.5 rounded-full bg-black/50 hover:bg-black/80 text-white transition-colors cursor-pointer border border-white/20 shadow-md"
                        aria-label="Tutup Preview"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    <!-- Modal Image -->
                    <div class="relative bg-black/95 flex items-center justify-center overflow-hidden p-2 sm:p-4 min-h-[300px] max-h-[75vh]">
                        <img :src="activeImg" :alt="activeTitle" class="w-auto h-auto max-w-full max-h-[70vh] object-contain rounded-xl shadow-2xl" />
                    </div>

                    <!-- Modal Info Bar -->
                    <div class="p-6 bg-[#FAF5ED] dark:bg-[#081610] border-t border-stone-200 dark:border-emerald-900/40 space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-[#0D6E42] dark:text-emerald-300 text-xs font-bold" x-text="activeCat"></span>
                        </div>
                        <h3 class="text-lg sm:text-xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE]" x-text="activeTitle"></h3>
                        <p class="text-xs sm:text-sm text-stone-600 dark:text-stone-300 leading-relaxed" x-text="activeDesc"></p>
                    </div>
                </div>
            </div>
        </template>
    </section>

    <!-- ==========================================
         SECTION 7: BERITA & KAJIAN DHAMMA (DYNAMIC FROM DB)
         ========================================== -->
    <section id="berita" aria-labelledby="berita-heading" class="max-w-[1440px] mx-auto px-6 sm:px-10 lg:px-16 space-y-12 reveal-on-scroll pb-15">
        
        <!-- Section Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-stone-300/60 dark:border-emerald-500/20">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Kabar & Artikel</span>
                </div>
                <h2 id="berita-heading" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                    Berita & Kajian Dhamma
                </h2>
                <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm">
                    Ikuti liputan kegiatan sosial, artikel mutiara Dhamma, dan pengumuman resmi yayasan.
                </p>
            </div>

            <!-- View All News Hub Link -->
            <a 
                href="{{ route('berita') }}" 
                class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-[#0D6E42] dark:text-emerald-400 hover:text-amber-600 dark:hover:text-amber-300 transition-colors group self-start md:self-end"
            >
                <span>Lihat Semua Berita</span>
                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        <!-- News Layout: 1 Hero Featured + 3 Grid Cards -->
        <div class="space-y-8">
            
            <!-- Hero Featured Article -->
            @if ($featuredArticle)
                @php
                    $featCover = $featuredArticle->cover_image ? asset($featuredArticle->cover_image) : asset('images/news-baksos.jpg');
                    $featDate = $featuredArticle->published_at ? $featuredArticle->published_at->translatedFormat('d F Y') : $featuredArticle->created_at->translatedFormat('d F Y');
                @endphp
                <article class="bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-[2.5rem] overflow-hidden border border-amber-500/30 dark:border-emerald-500/25 shadow-xl group card-shine">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-0 items-center">
                        
                        <!-- Left Photo Thumbnail -->
                        <div class="lg:col-span-6 relative aspect-[16/10] sm:aspect-[16/9] lg:aspect-auto lg:h-[380px] overflow-hidden bg-stone-200 dark:bg-stone-900">
                            <img 
                                src="{{ $featCover }}" 
                                alt="{{ $featuredArticle->title }}" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out"
                                loading="lazy"
                                decoding="async"
                            />
                            <div class="absolute top-4 left-4 z-10 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-amber-500 text-stone-950 font-extrabold text-xs shadow-md uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span>Berita Utama</span>
                            </div>
                        </div>

                        <!-- Right Article Details -->
                        <div class="lg:col-span-6 p-6 sm:p-10 flex flex-col justify-between space-y-5">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-3 text-xs text-stone-500 dark:text-stone-400">
                                    <span class="px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-[#0D6E42] dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-800/40">
                                        {{ $featuredArticle->category }}
                                    </span>
                                    <span>•</span>
                                    <span>{{ $featDate }}</span>
                                    <span>•</span>
                                    <span>{{ $featuredArticle->author_name ?: 'Humas Sekretariat' }}</span>
                                </div>

                                <h3 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-snug group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors">
                                    <a href="{{ route('berita.detail', ['slug' => $featuredArticle->slug]) }}">
                                        {{ $featuredArticle->title }}
                                    </a>
                                </h3>

                                <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm leading-relaxed">
                                    {{ $featuredArticle->excerpt ?: Str::limit(strip_tags($featuredArticle->content), 180) }}
                                </p>
                            </div>

                            <div class="pt-4 border-t border-stone-200 dark:border-emerald-900/40 flex items-center justify-between">
                                <a href="{{ route('berita.detail', ['slug' => $featuredArticle->slug]) }}" class="inline-flex items-center gap-2 bg-[#0D5B3A] hover:bg-[#09472D] dark:bg-emerald-700 dark:hover:bg-emerald-600 text-white font-semibold px-5 py-2.5 rounded-full text-xs sm:text-sm shadow-sm transition-all duration-200 transform hover:-translate-y-0.5">
                                    <span>Baca Selengkapnya</span>
                                    <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @endif

            <!-- 2. THREE EDITORIAL NEWS / DHAMMA INSIGHT CARDS -->
            @if ($latestArticles->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    @foreach ($latestArticles as $art)
                        @php
                            $artCover = $art->cover_image ? asset($art->cover_image) : asset('images/gallery-altar.jpg');
                            $artDate = $art->published_at ? $art->published_at->translatedFormat('d F Y') : $art->created_at->translatedFormat('d F Y');
                        @endphp
                        <article class="bg-[#FAF5ED] dark:bg-[#0b1c15] rounded-[2rem] border border-stone-300/60 dark:border-emerald-500/20 hover:border-emerald-700/40 dark:hover:border-emerald-400/40 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between group card-shine hover:-translate-y-1.5 reveal-on-scroll">
                            <div class="space-y-4">
                                <div class="relative aspect-[16/10] overflow-hidden bg-stone-200 dark:bg-stone-900">
                                    <img 
                                        src="{{ $artCover }}" 
                                        alt="{{ $art->title }}" 
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                    <div class="absolute top-3 left-3">
                                        <span class="px-3 py-1 rounded-full bg-[#FAF5ED]/95 dark:bg-[#081610]/95 backdrop-blur-md text-[#0D6E42] dark:text-emerald-300 text-[11px] font-bold border border-emerald-500/20 shadow-sm">
                                            {{ $art->category }}
                                        </span>
                                    </div>
                                </div>

                                <div class="p-6 pt-0 space-y-2.5">
                                    <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                                        <span>{{ $artDate }}</span>
                                        <span>•</span>
                                        <span>{{ $art->author_name ?: 'Vihara' }}</span>
                                    </div>

                                    <h3 class="text-lg font-bold text-[#143D2D] dark:text-[#E8F3EE] leading-snug group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors line-clamp-2">
                                        <a href="{{ route('berita.detail', ['slug' => $art->slug]) }}">
                                            {{ $art->title }}
                                        </a>
                                    </h3>

                                    <p class="text-stone-600 dark:text-stone-300 text-xs sm:text-sm leading-relaxed line-clamp-3">
                                        {{ $art->excerpt ?: Str::limit(strip_tags($art->content), 100) }}
                                    </p>
                                </div>
                            </div>

                            <div class="p-6 pt-0">
                                <div class="pt-4 border-t border-stone-200 dark:border-emerald-900/40 flex items-center justify-between text-xs">
                                    <a href="{{ route('berita.detail', ['slug' => $art->slug]) }}" class="font-bold text-[#0D5B3A] dark:text-emerald-300 hover:text-amber-600 dark:hover:text-amber-300 transition-colors flex items-center gap-1 w-full justify-between">
                                        <span>Selengkapnya</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <!-- View All News Callout Button -->
            <div class="text-center pt-6 reveal-on-scroll">
                <a 
                    href="{{ route('berita') }}" 
                    class="inline-flex items-center gap-2.5 px-8 py-4 rounded-full bg-[#0D5B3A] hover:bg-[#09472D] dark:bg-emerald-600 dark:hover:bg-emerald-500 text-white font-extrabold text-sm sm:text-base shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 group"
                >
                    <span>Lihat Semua Berita ({{ $totalArticlesCount }} Artikel)</span>
                    <svg class="w-4 h-4 text-amber-300 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Alpine Donation Controller Script -->
    <script>
        window.homeDonasiController = function(donorsList) {
            return {
                danaTab: 'all',
                visibleProgramsCount: 6,
                danaModal: false,
                donorModal: false,
                certificateModal: false,
                programDetailModal: false,
                selectedCampaign: '',
                selectedCategory: '',
                selectedDonorCampaign: null,
                selectedCertificate: null,
                selectedProgramDetail: null,
                selectedProgramPembina: null,
                selectedProgramAdmins: @js(\App\Models\DonationProgram::getDefaultAdmins()),
                defaultAdmins: @js(\App\Models\DonationProgram::getDefaultAdmins()),
                itemDonorSearch: '',
                copiedCertRef: false,
                isDownloadingImg: false,
                paymentTab: 'bank',
                selectedBankName: @js($bankAccounts[0]['name'] ?? 'Bank Central Asia (BCA)'),
                copiedBankId: null,
                copiedBCA: false,
                copiedMandiri: false,
                copiedBRI: false,
                copiedNMID: false,
                donorsList: donorsList || [],
                activeItemTierFilter: 'all',
                copiedDonorRefId: null,

                loadMorePrograms() {
                    this.visibleProgramsCount += 6;
                },

                openProgramDetailModal(program) {
                    this.selectedProgramDetail = program;
                    this.programDetailModal = true;
                },

                openDanaModal(title, category, pembina = null, admins = null) {
                    this.selectedCampaign = title;
                    this.selectedCategory = category;
                    this.selectedProgramPembina = (pembina && pembina.length > 0) ? pembina : null;
                    this.selectedProgramAdmins = (admins && admins.length > 0) ? admins : this.defaultAdmins;
                    this.paymentTab = 'bank';
                    this.danaModal = true;
                    this.copiedBankId = null;
                    this.copiedBCA = false;
                    this.copiedMandiri = false;
                    this.copiedBRI = false;
                    this.copiedNMID = false;
                },

                openItemDonorsModal(campaign) {
                    this.selectedDonorCampaign = campaign;
                    this.itemDonorSearch = '';
                    this.activeItemTierFilter = 'all';
                    this.donorModal = true;
                },

                openCertificateModal(donor) {
                    if (!donor || !donor.hasCertificate) return;
                    this.selectedCertificate = donor;
                    this.certificateModal = true;
                    this.copiedCertRef = false;
                },

                printCertificate() {
                    window.print();
                },

                downloadCertificateImage() {
                    const cert = this.selectedCertificate;
                    if (!cert) return;
                    this.isDownloadingImg = true;

                    const canvas = document.createElement('canvas');
                    // Official 2x Retina Canvas Resolution (2048 x 1468)
                    canvas.width = 2048;
                    canvas.height = 1468;
                    const ctx = canvas.getContext('2d');

                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.src = cert.templateBg;

                    img.onload = () => {
                        // 1. Draw the official certificate template background
                        ctx.drawImage(img, 0, 0, 2048, 1468);

                        // 2. Write Nama Donatur (centered under 'Kepada' at Y = 558, auto-scaled to prevent truncation)
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        const donorName = (cert.name || '').toUpperCase();
                        let fontSize = 40;
                        ctx.font = `bold ${fontSize}px "Plus Jakarta Sans", "Georgia", serif`;
                        const maxTextWidth = 1600;
                        while (ctx.measureText(donorName).width > maxTextWidth && fontSize > 16) {
                            fontSize -= 2;
                            ctx.font = `bold ${fontSize}px "Plus Jakarta Sans", "Georgia", serif`;
                        }
                        ctx.fillStyle = '#0f172a';
                        ctx.fillText(donorName, 1024, 558, maxTextWidth);

                        // 3. Write Nominal Donasi (centered under 'sebesar :' at Y = 866)
                        ctx.font = 'bold 64px "Plus Jakarta Sans", "Arial", sans-serif';
                        ctx.fillStyle = '#0284c7';
                        ctx.fillText(cert.amount, 1024, 866);

                        // 4. Download file
                        const link = document.createElement('a');
                        link.download = 'Piagam_Anumodana_' + cert.name.replace(/[^a-zA-Z0-9]/g, '_') + '.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                        this.isDownloadingImg = false;
                    };

                    img.onerror = () => {
                        this.isDownloadingImg = false;
                        window.print();
                    };
                },

                getCampaignDonors(campaignId) {
                    if (!campaignId) return [];
                    return this.donorsList.filter(d => {
                        const matchCamp = d.campaignId === campaignId;
                        const matchSearch = this.itemDonorSearch === '' || 
                            d.name.toLowerCase().includes(this.itemDonorSearch.toLowerCase()) || 
                            d.ref.toLowerCase().includes(this.itemDonorSearch.toLowerCase());
                        
                        let matchTier = true;
                        if (this.activeItemTierFilter === 'maha') matchTier = d.rawAmount >= 10000000;
                        else if (this.activeItemTierFilter === 'utama') matchTier = d.rawAmount >= 5000000 && d.rawAmount < 10000000;
                        else if (this.activeItemTierFilter === 'danapati') matchTier = d.rawAmount >= 2000000 && d.rawAmount < 5000000;
                        else if (this.activeItemTierFilter === 'mitra') matchTier = d.rawAmount < 2000000;

                        return matchCamp && matchSearch && matchTier;
                    });
                },

                getDonorTier(amount) {
                    if (amount >= 10000000) {
                        return { label: 'Mahā Dānapati', tag: 'maha', badgeClass: 'bg-gradient-to-r from-amber-500/20 via-yellow-500/20 to-amber-600/30 text-amber-900 dark:text-amber-200 border-amber-500/50 shadow-xs' };
                    }
                    if (amount >= 5000000) {
                        return { label: 'Utama Dānapati', tag: 'utama', badgeClass: 'bg-gradient-to-r from-emerald-500/15 to-teal-500/20 text-emerald-900 dark:text-emerald-300 border-emerald-500/40' };
                    }
                    if (amount >= 2000000) {
                        return { label: 'Dānapati', tag: 'danapati', badgeClass: 'bg-teal-500/15 text-teal-900 dark:text-teal-300 border-teal-500/30' };
                    }
                    return { label: 'Kalyānamitta', tag: 'mitra', badgeClass: 'bg-stone-500/15 text-stone-700 dark:text-stone-300 border-stone-300 dark:border-stone-700' };
                },

                getInitials(name) {
                    if (!name) return 'HD';
                    if (name.includes('Anonim') || name.includes('Hamba')) return 'HD';
                    const clean = name.replace(/^(Keluarga|Kel\.|Bpk\.|Ibu|Upasaka|Upasika|Perkumpulan|Komunitas|Alm\.|Almh\.)\s+/i, '');
                    const parts = clean.trim().split(/\s+/);
                    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
                    return clean.slice(0, 2).toUpperCase();
                },

                copyDonorRef(ref) {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(ref);
                    }
                    this.copiedDonorRefId = ref;
                    setTimeout(() => this.copiedDonorRefId = null, 2500);
                },

                formatRupiah(num) {
                    return 'Rp ' + Number(num).toLocaleString('id-ID');
                },

                copyAccount(text, type) {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(text);
                    }
                    this.copiedBankId = type;
                    if (type === 'bca') this.copiedBCA = true;
                    if (type === 'mandiri') this.copiedMandiri = true;
                    if (type === 'bri') this.copiedBRI = true;
                    if (type === 'nmid') this.copiedNMID = true;
                    if (type === 'cert') this.copiedCertRef = true;
                    setTimeout(() => {
                        this.copiedBankId = null;
                        this.copiedBCA = false;
                        this.copiedMandiri = false;
                        this.copiedBRI = false;
                        this.copiedNMID = false;
                        this.copiedCertRef = false;
                    }, 2500);
                },

                getAdminWhatsAppUrl(admin) {
                    let method = this.paymentTab === 'bank' 
                        ? ('Transfer ' + (this.selectedBankName || 'Bank')) 
                        : 'QRIS / E-Wallet';
                    let targetName = admin && admin.name ? admin.name : 'Pengurus';
                    let targetRole = admin && admin.role ? admin.role : 'Pengurus';
                    let text = 'Namo Buddhaya ' + targetRole + ' ' + targetName + ',\n\nSaya telah menyalurkan dana kebajikan untuk program:\n* ' + (this.selectedCampaign || 'Program Donasi') + ' *\n\nMetode Penyaluran: ' + method + '\n\nMohon konfirmasi dan verifikasi bukti transfer terlampir. Anumodana.';
                    let targetPhone = '{{ $foundationWhatsApp }}';
                    if (admin && admin.phone) {
                        let clean = admin.phone.replace(/[^0-9]/g, '');
                        if (clean.startsWith('0')) clean = '62' + clean.slice(1);
                        if (clean.length >= 9) targetPhone = clean;
                    }
                    return 'https://wa.me/' + targetPhone + '?text=' + encodeURIComponent(text);
                },

                getWhatsAppConfirmationUrl() {
                    let adminList = (this.selectedProgramAdmins && this.selectedProgramAdmins.length > 0) ? this.selectedProgramAdmins : this.defaultAdmins;
                    let targetAdmin = (adminList && adminList.length > 0) ? (adminList.find(a => a.phone && a.phone.trim() !== '') || adminList[0]) : null;
                    return this.getAdminWhatsAppUrl(targetAdmin);
                }
            };
        };
    </script>

</main>