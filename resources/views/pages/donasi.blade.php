<?php

use App\Models\CertificateTemplate;
use App\Models\Donation;
use App\Models\DonationProgram;
use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        $donationPrograms = DonationProgram::with(['donations' => function($q) {
            $q->where('status', 'verified');
        }])->latest()->get();
        $donations = Donation::with('donationProgram.certificateTemplate', 'issuedCertificate.certificateTemplate')->where('status', 'verified')->latest()->get();
        
        $activeTemplates = CertificateTemplate::where('status', 'aktif')->orderByDesc('min_amount')->get();
        $defaultTemplate = $activeTemplates->first() ?? CertificateTemplate::first();

        $campaigns = $donationPrograms->map(function ($p) {
            return [
                'id' => $p->id,
                'title' => $p->title,
                'category' => Str::slug($p->category),
                'categoryName' => $p->category,
                'target' => (int) $p->target_amount,
                'collected' => (int) $p->collected_amount,
                'percent' => (float) $p->progress_percentage,
                'donorsCount' => (int) $p->donors_count,
                'daysLeft' => $p->days_left_text,
                'status' => $p->status === 'aktif' ? 'open' : 'closed',
                'statusLabel' => $p->status === 'aktif' ? 'Dibuka' : 'Ditutup',
                'img' => $p->cover_image ?: 'images/gallery-altar.jpg',
                'desc' => $p->content ?? '',
            ];
        });

        $donorsList = $donations->map(function ($d) use ($activeTemplates, $defaultTemplate) {
            $isAlm = preg_match('/\b(alm|almh|mendiang|pattidana|leluhur)\b/i', $d->donor_name . ' ' . ($d->donor_message ?? ''));

            // Match template
            $matchedTemplate = $d->issuedCertificate?->certificateTemplate;
            if (!$matchedTemplate && $d->donationProgram?->certificateTemplate) {
                $matchedTemplate = $d->donationProgram->certificateTemplate;
            }
            if (!$matchedTemplate) {
                if ($isAlm) {
                    $matchedTemplate = $activeTemplates->firstWhere('category', 'alm') 
                        ?? $activeTemplates->firstWhere('category', 'umum') 
                        ?? $defaultTemplate;
                } else {
                    $matchedTemplate = $activeTemplates->firstWhere('category', 'umum') 
                        ?? $defaultTemplate;
                }
            }

            $bgImage = $matchedTemplate?->background_image ? asset($matchedTemplate->background_image) : asset('images/piagam-maha-anumodana.png');
            $tplName = $matchedTemplate?->name ?? ($isAlm ? 'Piagam Pelimpahan Jasa Pattidāna' : 'Piagam Maha Anumodana');

            return [
                'id' => $d->id,
                'campaignId' => $d->donation_program_id,
                'name' => $d->donor_name,
                'isAlm' => (bool) $isAlm,
                'program' => $d->donationProgram?->title ?? 'Dāna Umum Operasional Vihara',
                'amount' => 'Rp ' . number_format($d->amount, 0, ',', '.'),
                'rawAmount' => (int) $d->amount,
                'date' => $d->created_at->translatedFormat('d F Y'),
                'ref' => $d->invoice_number,
                'certNo' => $d->issuedCertificate?->certificate_number ?? ('PA/' . $d->created_at->format('Y/m') . '/' . str_pad($d->id, 4, '0', STR_PAD_LEFT)),
                'status' => 'Tervalidasi',
                'templateBg' => $bgImage,
                'templateName' => $tplName,
            ];
        });

        // Settings for bank accounts & foundation (Dynamic from DB)
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

        $qrisMerchantId = Setting::get('qris_nmid') ?: Setting::get('qris_merchant_id', 'ID1020038891024');
        $qrisImage = Setting::get('qris_image', 'images/qris-vihara.jpg');
        $phone = Setting::get('foundation_phone', '+62 811-2345-6789');
        $cleanPhone = \App\Models\AdminContact::getRandomActivePhone($phone);

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

        $totalDana = Donation::where('status', 'verified')->sum('amount');
        $totalDonatur = Donation::where('status', 'verified')->count();
        $totalProgramAktif = DonationProgram::where('status', 'aktif')->count();
        $totalProgramSelesai = DonationProgram::where('status', 'selesai')->count();

        return $this->view([
            'donationPrograms' => $donationPrograms,
            'campaigns' => $campaigns,
            'donorsList' => $donorsList,
            'bankAccounts' => $bankAccounts,
            'bankName1' => $bankName1,
            'bankAccount1' => $bankAccount1,
            'bankHolder1' => $bankHolder1,
            'bankName2' => $bankName2,
            'bankAccount2' => $bankAccount2,
            'bankHolder2' => $bankHolder2,
            'qrisMerchantId' => $qrisMerchantId,
            'qrisImage' => $qrisImage,
            'cleanPhone' => $cleanPhone ?: '6281123456789',
            'sanghaMembers' => $sanghaMembers,
            'totalDana' => $totalDana,
            'totalDonatur' => $totalDonatur,
            'totalProgramAktif' => $totalProgramAktif,
            'totalProgramSelesai' => $totalProgramSelesai,
        ]);
    }
};
?>

<main id="main-content" class="min-h-screen bg-[#F0E9DF] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 font-sans selection:bg-emerald-200 selection:text-emerald-950">

    <!-- ==========================================
         HERO & PROGRAM OVERVIEW SECTION
         ========================================== -->
    <header class="relative pt-32 pb-16 px-6 sm:px-10 lg:px-16 overflow-hidden border-b border-stone-200/80 dark:border-emerald-950/60">
        
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
                <span class="text-[#0D6E42] dark:text-emerald-300 font-bold">Program Donasi & Dāna</span>
            </nav>

            <!-- Eyebrow Pill Badge -->
            <div class="flex flex-wrap items-center justify-center gap-2.5">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Dāna Paramita — Ladang Kebajikan Luhur</span>
                </div>
                
            </div>

            <!-- Page Title -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-tight">
                Program Donasi, Dāna Paramita & <br class="hidden sm:inline" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0D5B3A] via-[#8C6B1F] to-[#0D5B3A] dark:from-emerald-300 dark:via-amber-300 dark:to-emerald-300">
                    Penyaluran Kasih Umat
                </span>
            </h1>

            <!-- Narrative Paragraph -->
            <p class="max-w-3xl mx-auto text-stone-600 dark:text-stone-300 text-sm sm:text-base lg:text-lg leading-relaxed">
                Salurkan kebajikan Anda untuk mendukung kelestarian Buddha Sasana, pembangunan sarana ibadah, pengadaan jubah & Sangha Dāna, serta aksi kepedulian sosial kemanusiaan yang dikelola secara transparan dan akuntabel.
            </p>
        </div>
    </header>

    <!-- Main Content Container with Clean Alpine.js Component -->
    <div 
        x-data="donasiPageController({{ Js::from($campaigns) }}, {{ Js::from($donorsList) }})"
        x-init="
            if (window.location.hash === '#donatur') {
                activeView = 'donatur';
            }
        "
        class="max-w-[1440px] mx-auto px-6 sm:px-10 lg:px-16 py-12 sm:py-20 space-y-16"
    >

        <!-- ==========================================
             SECTION 2: MAIN VIEW TOGGLE (PROGRAM VS DONATUR)
             ========================================== -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-3 p-2 rounded-3xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-amber-500/25 dark:border-emerald-500/20 shadow-md max-w-2xl mx-auto">
            <button 
                @click="activeView = 'program'" 
                type="button" 
                :class="activeView === 'program' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md font-extrabold' : 'text-stone-700 dark:text-stone-300 hover:text-[#0D5B3A] dark:hover:text-emerald-300 font-semibold'"
                class="w-full md:w-auto flex-1 py-3 px-4 rounded-2xl text-xs sm:text-sm transition-all duration-200 cursor-pointer flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>Program Dāna ({{ $campaigns->count() }})</span>
            </button>

            <button 
                @click="activeView = 'donatur'" 
                id="donatur"
                type="button" 
                :class="activeView === 'donatur' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md font-extrabold' : 'text-stone-700 dark:text-stone-300 hover:text-[#0D5B3A] dark:hover:text-emerald-300 font-semibold'"
                class="w-full md:w-auto flex-1 py-3 px-4 rounded-2xl text-xs sm:text-sm transition-all duration-200 cursor-pointer flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Donatur ({{ $donorsList->count() }})</span>
            </button>
        </div>

        <!-- ==========================================
             VIEW 1: DAFTAR PROGRAM DONASI
             ========================================== -->
        <div x-show="activeView === 'program'" class="space-y-12">
            
            <!-- Filter Bar & Search -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-stone-300/60 dark:border-emerald-500/20">
                <div class="space-y-1">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                        Pilihan Program Penyaluran Dana
                    </h2>
                    <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                        Klik tombol <strong>"Berdana Sekarang"</strong> untuk melihat nomor rekening dan konfirmasi bukti transfer resmi.
                    </p>
                </div>

                <!-- Live Search -->
                <div class="relative w-full md:w-80">
                    <input 
                        type="text" 
                        x-model="searchQuery" 
                        placeholder="Cari nama program donasi..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/60 dark:border-emerald-500/20 text-xs text-stone-800 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-emerald-600 dark:focus:border-emerald-400 transition-colors shadow-inner"
                    />
                    <svg class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Filter Status Tabs -->
            <div class="flex flex-wrap items-center gap-2">
                <button 
                    @click="danaTab = 'all'" 
                    type="button" 
                    :class="danaTab === 'all' ? 'bg-[#0D5B3A] dark:bg-emerald-600 text-white shadow-md' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 cursor-pointer"
                >
                    Semua Program ({{ $campaigns->count() }})
                </button>
                <button 
                    @click="danaTab = 'open'" 
                    type="button" 
                    :class="danaTab === 'open' ? 'bg-emerald-600 dark:bg-emerald-600 text-white shadow-md' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                    class="px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 cursor-pointer flex items-center gap-1.5"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sedang Dibuka ({{ $totalProgramAktif }})</span>
                </button>
                @if ($totalProgramSelesai > 0)
                    <button 
                        @click="danaTab = 'closed'" 
                        type="button" 
                        :class="danaTab === 'closed' ? 'bg-stone-700 dark:bg-stone-700 text-white shadow-md' : 'bg-[#FAF5ED] dark:bg-[#0b1c15] text-stone-700 dark:text-stone-300 border border-stone-300/60 dark:border-emerald-500/20 hover:bg-stone-200/60 dark:hover:bg-emerald-950/60'"
                        class="px-4 py-2 rounded-full text-xs font-bold transition-all duration-200 cursor-pointer flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Telah Ditutup ({{ $totalProgramSelesai }})</span>
                    </button>
                @endif
            </div>

            <!-- Campaign Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <x-items.donasi-items :data="$donationPrograms" :sanghaMembers="$sanghaMembers" />
            </div>
        </div>

        <!-- ==========================================
             VIEW 2: DAFTAR NAMA DONATUR (DONOR RECOGNITION FEED)
             ========================================== -->
        <div x-show="activeView === 'donatur'" class="space-y-8">
            
            <!-- Donors Feed Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-stone-300/60 dark:border-emerald-500/20">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-[#0D6E42] dark:text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>Catatan Kebajikan & Anumodana Umat</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#143D2D] dark:text-[#E8F3EE] tracking-tight">
                        Daftar Nama Donatur & Partisipasi Dana
                    </h2>
                    <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                        Pencatatan transparan seluruh dana kebajikan yang masuk melalui rekening resmi yayasan lengkap dengan Sertifikat Anumodana.
                    </p>
                </div>

                <!-- Donor Search Bar -->
                <div class="w-full md:w-80">
                    <div class="relative w-full">
                        <input 
                            type="text" 
                            x-model="donorSearch" 
                            placeholder="Cari nama donatur / nomor invoice..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-[#FAF5ED] dark:bg-[#0b1c15] border border-stone-300/80 dark:border-emerald-500/30 text-xs text-stone-800 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-amber-500 dark:focus:border-amber-400 transition-colors shadow-inner"
                        />
                        <svg class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Table of Donors -->
            <div class="rounded-3xl overflow-hidden bg-[#FAF5ED] dark:bg-[#0b1c15] border border-amber-500/30 dark:border-emerald-500/25 shadow-lg">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-stone-200/80 dark:bg-[#06140e] text-[#143D2D] dark:text-emerald-200 font-extrabold border-b border-stone-300/70 dark:border-emerald-900/50">
                            <tr>
                                <th class="py-4 px-5 text-center w-12 ">No.</th>
                                <th class="py-4 px-5">Nama Donatur</th>
                                <th class="py-4 px-5">Program Dana</th>
                                <th class="py-4 px-5">Jumlah Dāna</th>
                                <th class="py-4 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200/60 dark:divide-emerald-900/30 text-stone-700 dark:text-stone-300">
                            <template x-for="(donor, idx) in donorsList.filter(d => matchesDonor(d))" :key="donor.id">
                                <tr class="hover:bg-amber-500/5 dark:hover:bg-emerald-400/5 transition-colors group">
                                    <td class="py-4 px-5 text-center  font-bold text-stone-400" x-text="idx + 1"></td>
                                    <td class="py-4 px-5">
                                        <div class="flex items-center gap-3 min-w-80">
                                            <div class="w-9 h-9 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center font-black text-xs text-amber-800 dark:text-amber-300 shrink-0" x-text="getInitials(donor.name)"></div>
                                            <div>
                                                <div class="font-bold text-sm text-[#143D2D] dark:text-[#E8F3EE] group-hover:text-[#0D6E42] dark:group-hover:text-emerald-400 transition-colors" x-text="donor.name"></div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="py-4 px-5 max-w-xs">
                                        <span class="block" x-text="donor.program"></span>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class=" font-black text-sm text-[#0D5B3A] dark:text-emerald-400 block min-w-35" x-text="donor.amount"></span>
                                    </td>
                                    <td class="py-4 px-5 text-right">
                                        <button 
                                            @click="openCertificateModal(donor)"
                                            type="button" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-900 dark:text-amber-300 border border-amber-500/40 text-xs font-bold transition-all cursor-pointer shadow-2xs hover:scale-105"
                                        >
                                            <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>Piagam</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                                            <span class="text-[10px] text-amber-900 dark:text-amber-300 font-extrabold" x-text="selectedProgramDetail.pembina.length > 1 ? 'Dewan Bhikkhu Sangha' : 'Bhikkhu Pembina'"></span>
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
                                        @click="programDetailModal = false; openItemDonorsModal({id: selectedProgramDetail.id, title: selectedProgramDetail.title, categoryName: selectedProgramDetail.category, collected: selectedProgramDetail.collected, target: selectedProgramDetail.target, percent: selectedProgramDetail.percent, donorsCount: selectedProgramDetail.donorsCount, status: selectedProgramDetail.status})"
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
                            
                            <!-- Header -->
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
                                    
                                    <!-- Progress Bar -->
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
                                                            <button 
                                                                @click="openCertificateModal(d)"
                                                                type="button" 
                                                                class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-800 dark:text-amber-300 text-[11px] font-bold border border-amber-500/30 cursor-pointer transition-all hover:scale-105"
                                                            >
                                                                Piagam
                                                            </button>
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
                                    <p class="text-[11px] text-stone-400 " x-text="'No. Registrasi: ' + selectedCertificate.certNo"></p>
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
                                        class=" font-black text-[#0284c7] tracking-tight block truncate"
                                        style="font-size: clamp(16px, 3.1vw, 34px); line-height: 1.2;"
                                        x-text="selectedCertificate.amount"
                                    ></span>
                                </div>
                            </div>

                            <!-- Certificate Action Toolbar -->
                            <div class="pt-2 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="text-stone-400  text-[11px]">
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

        <!-- ==========================================
             MODAL 3: DETAIL REKENING & QRIS (FROM DATABASE SETTINGS)
             ========================================== -->
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

                        <!-- TAB PANEL 1: TRANSFER BANK (FROM DATABASE SETTINGS) -->
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
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-stone-100 dark:bg-emerald-950/60 text-[11px]  font-bold text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-emerald-500/20">
                                        <span>NMID: {{ $qrisMerchantId }}</span>
                                        <button 
                                            @click="copyAccount('{{ $qrisMerchantId }}', 'nmid')"
                                            type="button" 
                                            class="text-[#0D6E42] dark:text-emerald-400 hover:underline cursor-pointer font-bold"
                                            title="Salin NMID"
                                        >
                                            <span x-text="copiedNMID ? 'Tersalin!' : 'Salin'"></span>
                                        </button>
                                    </div>
                                </div>

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
                            </div>
                        </div>

                        <!-- Mengetahui Dewan Bhikkhu Sangha Endorsement Card (Vertical Stack) -->
                        <div x-show="(selectedProgramPembina && selectedProgramPembina.length > 0) || {{ $sanghaMembers->isNotEmpty() ? 'true' : 'false' }}" class="p-3.5 sm:p-4 rounded-2xl bg-amber-500/10 dark:bg-amber-500/5 border border-amber-500/25 dark:border-amber-500/20 space-y-2.5 shadow-xs">
                            <div class="flex items-center justify-between border-b border-amber-500/20 pb-2">
                                <span class="text-[9.5px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-900 dark:text-amber-300">
                                    Mengetahui
                                </span>
                                <span class="text-[10.5px] text-amber-900 dark:text-amber-300 font-extrabold" x-text="selectedProgramPembina && selectedProgramPembina.length > 0 ? (selectedProgramPembina.length > 1 ? 'Dewan Bhikkhu Sangha' : 'Bhikkhu Pembina') : '{{ $sanghaMembers->count() > 1 ? 'Dewan Bhikkhu Sangha' : 'Bhikkhu Pembina' }}'">
                                    {{ $sanghaMembers->count() > 1 ? 'Dewan Bhikkhu Sangha' : 'Bhikkhu Pembina' }}
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

    </div>

    <!-- Alpine Controller Script -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('donasiPageController', (campaigns, donorsList) => ({
                activeView: 'program',
                danaTab: 'all',
                searchQuery: '',
                donorSearch: '',
                danaModal: false,
                programDetailModal: false,
                selectedCampaign: '',
                selectedCategory: '',
                paymentTab: 'bank',
                selectedBankName: @js($bankAccounts[0]['name'] ?? 'Bank Central Asia (BCA)'),
                copiedBankId: null,
                copiedBCA: false,
                copiedMandiri: false,
                copiedNMID: false,
                donorModal: false,
                certificateModal: false,
                selectedDonorCampaign: null,
                selectedCertificate: null,
                selectedProgramDetail: null,
                selectedProgramPembina: null,
                selectedProgramAdmins: @js(\App\Models\DonationProgram::getDefaultAdmins()),
                defaultAdmins: @js(\App\Models\DonationProgram::getDefaultAdmins()),
                itemDonorSearch: '',
                copiedCertRef: false,
                isDownloadingImg: false,
                campaigns: campaigns,
                donorsList: donorsList,
                visibleProgramsCount: 999,
                activeItemTierFilter: 'all',
                copiedDonorRefId: null,

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
                    this.copiedNMID = false;
                },

                openItemDonorsModal(campaign) {
                    this.selectedDonorCampaign = campaign;
                    this.itemDonorSearch = '';
                    this.activeItemTierFilter = 'all';
                    this.donorModal = true;
                },

                openCertificateModal(donor) {
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

                copyAccount(text, type) {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(text);
                    }
                    this.copiedBankId = type;
                    if (type === 'bca') this.copiedBCA = true;
                    if (type === 'mandiri') this.copiedMandiri = true;
                    if (type === 'nmid') this.copiedNMID = true;
                    if (type === 'cert') this.copiedCertRef = true;
                    setTimeout(() => {
                        this.copiedBankId = null;
                        this.copiedBCA = false;
                        this.copiedMandiri = false;
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
                    let targetPhone = '{{ $cleanPhone }}';
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
                },

                formatRupiah(num) {
                    return 'Rp ' + Number(num).toLocaleString('id-ID');
                },

                matchesCampaign(item) {
                    const matchTab = (this.danaTab === 'all') || 
                        (this.danaTab === 'open' && item.status === 'open') ||
                        (this.danaTab === 'closed' && item.status === 'closed') ||
                        (item.category === this.danaTab);
                    const matchQuery = this.searchQuery === '' || 
                        item.title.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                        item.desc.toLowerCase().includes(this.searchQuery.toLowerCase());
                    return matchTab && matchQuery;
                },

                matchesDonor(donor) {
                    if (this.donorSearch === '') return true;
                    return donor.name.toLowerCase().includes(this.donorSearch.toLowerCase()) ||
                        donor.program.toLowerCase().includes(this.donorSearch.toLowerCase()) ||
                        donor.ref.toLowerCase().includes(this.donorSearch.toLowerCase());
                }
            }));
        });
    </script>

    <!-- Print Styles for Anumodana Certificate -->
    <style>
        @media print {
            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body * {
                visibility: hidden !important;
            }
            #printable-certificate, #printable-certificate * {
                visibility: visible !important;
            }
            #printable-certificate {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: #ffffff !important;
            }
        }
    </style>
</main>
