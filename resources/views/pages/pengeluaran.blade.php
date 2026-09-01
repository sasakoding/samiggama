<?php

use App\Models\Donation;
use App\Models\Expense;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $searchQuery = '';

    public function updatingSearchQuery(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Expense::query();

        if ($this->searchQuery) {
            $q = '%' . trim($this->searchQuery) . '%';
            $query->where('title', 'like', $q);
        }

        $expenses = $query->orderByDesc('id')->paginate(15);

        // Financial Transparency Totals
        $totalAllExpenses = Expense::sum('amount');
        $totalExpensesCount = Expense::count();
        $totalDonationsCollected = Donation::where('status', 'verified')->sum('amount');

        return $this->view([
            'expenses' => $expenses,
            'totalAllExpenses' => $totalAllExpenses,
            'totalExpensesCount' => $totalExpensesCount,
            'totalDonationsCollected' => $totalDonationsCollected,
        ])->title('Laporan & Rincian Pengeluaran Dāna — Vihara Sāmaggi Gāma');
    }
};

?>

<main id="main-content" class="min-h-screen bg-[#F0E9DF] dark:bg-[#07130E] text-stone-800 dark:text-stone-100 font-sans selection:bg-emerald-200 selection:text-emerald-950">

    <!-- =========================================================================
         SECTION 1: HERO HEADER
         ========================================================================= -->
    <header class="w-full bg-[#FAF5ED] dark:bg-[#091711] border-b border-emerald-950/10 dark:border-emerald-500/15 relative overflow-hidden pt-12 sm:pt-16 pb-16 sm:pb-20 px-6 sm:px-12 lg:px-16">
        
        <!-- Background Topography Accent Lines -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-15 dark:opacity-10 stroke-emerald-900 dark:stroke-emerald-400 fill-none" viewBox="0 0 1440 600" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0,150 C320,300 420,-50 720,150 C1020,350 1120,50 1440,200 L1440,600 L0,600 Z" stroke-width="1.5" />
            <path d="M0,250 C320,400 420,50 720,250 C1020,450 1120,150 1440,300" stroke-width="1" stroke-dasharray="4 8" />
        </svg>

        <div class="max-w-4xl mx-auto text-center relative z-10 space-y-4">
            
            <!-- Breadcrumb / Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-900/5 dark:bg-emerald-400/10 border border-emerald-800/15 dark:border-emerald-400/20 text-[#0D6E42] dark:text-emerald-300 text-xs sm:text-sm font-bold uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Transparansi & Akuntabilitas Kas Vihara</span>
            </div>

            <!-- Main Title -->
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-[#143D2D] dark:text-[#E8F3EE] tracking-tight leading-tight font-sans">
                Laporan & Rincian <br class="hidden sm:inline" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0D5B3A] via-[#8C6B1F] to-[#0D5B3A] dark:from-emerald-300 dark:via-amber-300 dark:to-emerald-300">
                    Pengeluaran Dāna Umat
                </span>
            </h1>

            <!-- Narrative Paragraph -->
            <p class="max-w-2xl mx-auto text-stone-600 dark:text-stone-300 text-sm sm:text-base leading-relaxed">
                Wujud pertanggungjawaban terbuka atas seluruh dāna kebajikan yang disalurkan untuk pemeliharaan sarana vihara, puja bakti, pelayanan Bhikkhu Sangha, dan aksi kemanusiaan.
            </p>

            <!-- Navigation pill between Donasi and Pengeluaran -->
            <div class="pt-4 flex items-center justify-center gap-3">
                <a 
                    href="{{ url('/donasi') }}" 
                    class="px-5 py-2 rounded-full bg-white dark:bg-[#0b1f17] text-stone-700 dark:text-stone-300 hover:text-[#0D6E42] dark:hover:text-emerald-300 border border-stone-300/80 dark:border-emerald-500/20 text-xs font-bold transition-all shadow-xs flex items-center gap-2"
                >
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                    <span>Daftar Program & Donatur Masuk</span>
                </a>
                <a 
                    href="{{ url('/donasi#donasi-rekening') }}" 
                    class="px-5 py-2 rounded-full bg-[#0D5B3A] hover:bg-emerald-800 text-white text-xs font-bold transition-all shadow-md flex items-center gap-2"
                >
                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Berdana Sekarang</span>
                </a>
            </div>

        </div>
    </header>

    <!-- Main Container -->
    <div class="max-w-[1440px] mx-auto px-6 sm:px-10 lg:px-16 py-10 sm:py-16 space-y-10">

        <!-- =========================================================================
             SECTION 2: FINANCIAL SUMMARY STAT CARDS
             ========================================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">  

            <!-- Metric 1: Total Pengeluaran -->
            <div class="p-6 rounded-3xl bg-white/80 dark:bg-[#091711]/80 backdrop-blur-md border border-rose-500/25 dark:border-rose-500/20 shadow-sm space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-rose-800 dark:text-rose-300 uppercase tracking-wider">Total Penyaluran Dāna</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-500/15 text-rose-700 dark:text-rose-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-black text-rose-700 dark:text-rose-400 tracking-tight">
                    Rp {{ number_format($totalAllExpenses, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-stone-500 dark:text-stone-400 font-medium">
                    {{ $totalExpensesCount }} rincian transaksi belanja disalurkan
                </p>
            </div>

            <!-- Metric 2: Keterbukaan & Integritas -->
            <div class="p-6 rounded-3xl bg-white/80 dark:bg-[#091711]/80 backdrop-blur-md border border-stone-200/80 dark:border-emerald-950/60 shadow-sm space-y-2 flex flex-col justify-between">
                <div class="space-y-1">
                    <span class="text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Standar Akuntabilitas</span>
                    <div class="text-base sm:text-lg font-extrabold text-stone-900 dark:text-stone-100 leading-snug">
                        Terbuka & Akuntabel
                    </div>
                </div>
                <p class="text-[11px] text-stone-500 dark:text-stone-400 leading-relaxed">
                    Setiap pengeluaran kas diawasi oleh pengurus dan dewan pembina yayasan demi kelestarian Buddha Sasana.
                </p>
            </div>

        </div>

        <!-- =========================================================================
             SECTION 3: DAFTAR RINCIAN PENGELUARAN & SEARCH
             ========================================================================= -->
        <div class="rounded-3xl bg-[#FAF5ED] dark:bg-[#091711] border border-amber-500/20 dark:border-emerald-500/20 shadow-md overflow-hidden">
            
            <!-- Toolbar -->
            <div class="p-5 sm:p-6 border-b border-stone-300/60 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                
                <div class="text-xs font-bold text-stone-700 dark:text-stone-300">
                    Daftar Pengeluaran Kas
                </div>

                <!-- Search Box -->
                <div class="relative w-full sm:w-80">
                    <input 
                        wire:model.live.debounce.300ms="searchQuery" 
                        type="text" 
                        placeholder="Cari uraian belanja..." 
                        class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-white dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-emerald-500 shadow-2xs"
                    />
                    <svg class="w-4 h-4 text-stone-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

            </div>

            <!-- Expenses Table List -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-stone-200/60 dark:bg-[#071710] text-stone-600 dark:text-stone-400 uppercase tracking-wider font-bold text-[10.5px] border-b border-stone-300/60 dark:border-emerald-950">
                        <tr>
                            <th class="py-3.5 px-4 text-center w-14">No</th>
                            <th class="py-3.5 px-5">Uraian Pengeluaran</th>
                            <th class="py-3.5 px-5 text-right">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200/70 dark:divide-emerald-950/60">
                        @forelse ($expenses as $exp)
                            <tr class="hover:bg-white/60 dark:hover:bg-emerald-950/30 transition-colors">
                               
                                <!-- Nomor -->
                                <td class="py-4 px-4 text-center text-stone-400 dark:text-stone-500 font-semibold text-xs">
                                    {{ ($expenses->currentPage() - 1) * $expenses->perPage() + $loop->iteration }}
                                </td>

                                <!-- Uraian -->
                                <td class="py-4 px-5 max-w-md">
                                    <div class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm leading-snug whitespace-pre-line">
                                        {{ $exp->title }}
                                    </div>
                                </td>

                                <!-- Nominal (Rp) -->
                                <td class="py-4 px-5 whitespace-nowrap text-right">
                                    <span class="font-black text-rose-700 dark:text-rose-400 text-xs sm:text-sm">
                                        - Rp {{ number_format($exp->amount, 0, ',', '.') }}
                                    </span>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-16 text-center text-stone-400">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-full bg-stone-100 dark:bg-emerald-950/50 flex items-center justify-center mx-auto text-stone-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                        </div>
                                        <div class="font-bold text-stone-700 dark:text-stone-300 text-sm">Tidak ada catatan pengeluaran</div>
                                        <p class="text-xs text-stone-400">Belum ada transaksi pengeluaran yang dicatat.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($expenses->hasPages())
                <div class="p-4 sm:p-5 border-t border-stone-300/60 dark:border-emerald-950">
                    {{ $expenses->links() }}
                </div>
            @endif

        </div>

    </div>

</main>
