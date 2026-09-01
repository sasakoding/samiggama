<?php

use App\Models\Article;
use App\Models\Donation;
use App\Models\DonationProgram;
use App\Models\Expense;
use App\Models\Schedule;
use Livewire\Component;

new class extends Component
{
    public string $activeTab = 'semua';
    public ?string $feedbackMessage = null;

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function verifyTransaction(int $id): void
    {
        $donation = Donation::find($id);
        if ($donation) {
            $donation->update(['status' => 'verified']);
            $this->feedbackMessage = "Donasi {$donation->invoice_number} atas nama {$donation->donor_name} berhasil diverifikasi.";
        }
    }

    public function render()
    {
        // 1. KPI Financial & General Metrics
        $totalDana = Donation::where('status', 'verified')->sum('amount');
        $totalDonatur = Donation::where('status', 'verified')->count();
        $totalPengeluaran = Expense::sum('amount');
        $saldoKas = $totalDana - $totalPengeluaran;
        $pendingDonationsCount = Donation::where('status', 'pending')->count();
        $programAktif = DonationProgram::where('status', 'aktif')->count();
        $agendaAktif = Schedule::where('status', 'aktif')->count();

        // 2. Recent Transactions Query
        $transQuery = Donation::with('donationProgram')->latest();
        if ($this->activeTab !== 'semua') {
            $transQuery->where('status', $this->activeTab);
        }
        $transactions = $transQuery->take(6)->get();

        // 3. Recent Expenses Query
        $recentExpenses = Expense::latest()->take(6)->get();

        // 4. Upcoming Schedules
        $upcomingSchedules = Schedule::where('status', 'aktif')
            ->where('category', '!=', 'Hari Libur Nasional')
            ->orderBy('event_date')
            ->take(3)
            ->get();

        // 5. Visitor Analytics
        $visitorAnalytics = \App\Models\VisitorLog::getAdminDashboardStats();

        return $this->view([
            'totalDana' => $totalDana,
            'totalDonatur' => $totalDonatur,
            'totalPengeluaran' => $totalPengeluaran,
            'saldoKas' => $saldoKas,
            'pendingDonationsCount' => $pendingDonationsCount,
            'programAktif' => $programAktif,
            'agendaAktif' => $agendaAktif,
            'transactions' => $transactions,
            'recentExpenses' => $recentExpenses,
            'upcomingSchedules' => $upcomingSchedules,
            'visitorAnalytics' => $visitorAnalytics,
        ])->title('Dashboard')->layout('layouts::admin');
    }
};
?>

<div class="space-y-6 font-sans">

    @if (auth()->user()?->isBendahara())
        <!-- =========================================================================
             A. DASHBOARD KHUSUS BENDAHARA (FOKUS KEUANGAN & KAS)
             ========================================================================= -->
        <!-- 1. Header Bendahara -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-stone-200/80 dark:border-white/[0.08]">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                    <span>Panel Pengurus</span>
                    <span>/</span>
                    <span class="text-amber-600 dark:text-amber-400 font-bold">Keuangan & Kas Vihara</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100 flex items-center gap-3">
                    <span>Dashboard Keuangan</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/25 font-bold">
                        Bendahara
                    </span>
                </h1>
                <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-medium">
                    Monitoring penerimaan dāna umat, validasi transaksi, dan pencatatan arus keluar kas vihara.
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex items-center gap-2.5 shrink-0">
                <a 
                    href="{{ route('admin.pengeluaran') }}" 
                    class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Catat Pengeluaran</span>
                </a>
                <a 
                    href="{{ route('admin.transaksi') }}" 
                    class="px-4 py-2.5 rounded-xl bg-[#0D5B3A] hover:bg-emerald-800 text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Verifikasi Transaksi</span>
                </a>
            </div>
        </div>

        <!-- Feedback Banner -->
        @if ($feedbackMessage)
            <div 
                x-data="{ show: true }" 
                x-show="show"
                x-transition
                class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-500/30 text-emerald-900 dark:text-emerald-200 text-xs font-semibold flex items-center justify-between shadow-xs"
            >
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    <span>{{ $feedbackMessage }}</span>
                </div>
                <button @click="show = false" type="button" class="text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 cursor-pointer p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <!-- 2. 4 Financial KPI Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            
            <!-- Total Dāna Masuk -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex flex-col justify-between space-y-2 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Total Dāna Masuk</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/15 text-[#0D6E42] dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-[#0D6E42] dark:text-emerald-400 tracking-tight">
                    Rp {{ number_format($totalDana, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-stone-400 font-medium">
                    {{ number_format($totalDonatur) }} transaksi terverifikasi
                </p>
            </div>

            <!-- Total Pengeluaran Kas -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex flex-col justify-between space-y-2 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Total Pengeluaran</span>
                    <div class="w-7 h-7 rounded-lg bg-rose-500/15 text-rose-700 dark:text-rose-400 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-rose-700 dark:text-rose-400 tracking-tight">
                    Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-stone-400 font-medium">
                    Akumulasi belanja operasional
                </p>
            </div>

            <!-- Saldo Bersih Kas Vihara -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-900/15 via-amber-500/10 to-transparent dark:from-emerald-950/40 dark:via-amber-950/20 bg-white dark:bg-[#0b1f17] border border-amber-500/30 dark:border-amber-400/25 shadow-xs flex flex-col justify-between space-y-2 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-300">Saldo Kas Bersih</span>
                    <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-700 dark:text-amber-300 flex items-center justify-center font-bold text-xs">
                        Rp
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-stone-900 dark:text-amber-200 tracking-tight">
                    Rp {{ number_format($saldoKas, 0, ',', '.') }}
                </div>
                <p class="text-[11px] text-stone-500 dark:text-stone-400 font-medium">
                    Net kas tersedia saat ini
                </p>
            </div>

            <!-- Pending Verifikasi -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#0b1f17] border {{ $pendingDonationsCount > 0 ? 'border-amber-500/40 bg-amber-50/20 dark:bg-amber-950/10' : 'border-stone-200/90 dark:border-white/[0.08]' }} shadow-xs flex flex-col justify-between space-y-2 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Menunggu Verifikasi</span>
                    <div class="w-7 h-7 rounded-lg {{ $pendingDonationsCount > 0 ? 'bg-amber-500/20 text-amber-700 dark:text-amber-300 animate-pulse' : 'bg-stone-100 dark:bg-emerald-950/60 text-stone-400' }} flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black {{ $pendingDonationsCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-stone-900 dark:text-stone-100' }} tracking-tight">
                    {{ $pendingDonationsCount }} <span class="text-sm font-semibold text-stone-500">Transaksi</span>
                </div>
                <a href="{{ route('admin.transaksi', ['tab' => 'pending']) }}" class="text-[11px] font-bold text-amber-600 dark:text-amber-400 hover:underline">
                    Lihat antrean verifikasi →
                </a>
            </div>

        </div>

        <!-- 3. 2-Column Split: Transaksi Dāna vs Pengeluaran Kas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Kolom Kiri (2 Kolom): Transaksi Dāna Terkini -->
            <div class="lg:col-span-2 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="p-5 border-b border-stone-200/80 dark:border-emerald-950 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-extrabold text-stone-900 dark:text-stone-100">
                                Transaksi Dāna Masuk Terkini
                            </h2>
                            <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                                Mutasi penerimaan donasi terbaru dari umat & para donatur.
                            </p>
                        </div>
                        <a href="{{ route('admin.transaksi') }}" class="text-xs font-bold text-[#0D5B3A] dark:text-emerald-400 hover:underline">
                            Kelola Semua Transaksi →
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                                <tr>
                                    <th scope="col" class="py-3 px-5">Donatur</th>
                                    <th scope="col" class="py-3 px-4">Program Dāna</th>
                                    <th scope="col" class="py-3 px-4 text-right">Nominal</th>
                                    <th scope="col" class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                                @forelse ($transactions as $tx)
                                    <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/20 transition-colors">
                                        <td class="py-3.5 px-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-300 font-bold text-xs flex items-center justify-center shrink-0 border border-amber-500/20">
                                                    {{ strtoupper(substr($tx->donor_name, 0, 2)) }}
                                                </div>
                                                <div class="flex flex-col min-w-0">
                                                    <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs truncate max-w-[140px]">
                                                        {{ $tx->donor_name }}
                                                    </span>
                                                    <span class="text-[10px] text-stone-400">
                                                        {{ $tx->invoice_number }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="py-3.5 px-4">
                                            <span class="text-xs text-stone-700 dark:text-stone-300 font-medium truncate block max-w-[160px]">
                                                {{ $tx->donationProgram?->title ?? 'Dāna Umum Vihara' }}
                                            </span>
                                        </td>

                                        <td class="py-3.5 px-4 text-right font-extrabold text-stone-900 dark:text-stone-100 tabular-nums">
                                            Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                        </td>

                                        <td class="py-3.5 px-4 text-center">
                                            @if ($tx->status === 'verified')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">
                                                    Verified
                                                </span>
                                            @else
                                                <button 
                                                    wire:click="verifyTransaction({{ $tx->id }})" 
                                                    type="button" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-stone-950 font-bold text-[10px] shadow-xs cursor-pointer transition-colors"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Validasi</span>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-stone-400">
                                            Belum ada data transaksi donasi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4 bg-stone-50/70 dark:bg-[#071710] border-t border-stone-200/80 dark:border-emerald-950 flex items-center justify-between text-xs">
                    <span class="text-stone-500 dark:text-stone-400">Pencatatan mutasi otomatis dari rekening dan QRIS</span>
                    <a href="{{ route('admin.transaksi') }}" class="font-bold text-[#0D5B3A] dark:text-emerald-400 hover:underline">
                        Buka Buku Transaksi →
                    </a>
                </div>
            </div>

            <!-- Kolom Kanan (1 Kolom): Rincian Pengeluaran Kas Terkini -->
            <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="p-5 border-b border-stone-200/80 dark:border-emerald-950 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-extrabold text-stone-900 dark:text-stone-100">
                                Pengeluaran Kas Terkini
                            </h2>
                            <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                                Catatan belanja & operasional.
                            </p>
                        </div>
                        <a href="{{ route('admin.pengeluaran') }}" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
                            Kelola
                        </a>
                    </div>

                    <div class="p-4 space-y-3">
                        @forelse ($recentExpenses as $exp)
                            <div class="p-3 rounded-xl bg-stone-50/80 dark:bg-[#071710] border border-stone-200/80 dark:border-emerald-950 flex items-center justify-between gap-3">
                                <div class="space-y-0.5 min-w-0">
                                    <h3 class="text-xs font-bold text-stone-900 dark:text-stone-100 line-clamp-1">
                                        {{ $exp->title }}
                                    </h3>
                                    <span class="text-[10px] text-stone-400">
                                        {{ $exp->expense_date ? \Carbon\Carbon::parse($exp->expense_date)->translatedFormat('d M Y') : 'Pengeluaran Kas' }}
                                    </span>
                                </div>
                                <span class="font-black text-rose-700 dark:text-rose-400 text-xs shrink-0 whitespace-nowrap">
                                    - Rp {{ number_format($exp->amount, 0, ',', '.') }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-6 text-stone-400 text-xs">
                                Belum ada catatan pengeluaran kas.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="p-4 bg-stone-50/70 dark:bg-[#071710] border-t border-stone-200/80 dark:border-emerald-950">
                    <a href="{{ route('admin.pengeluaran') }}" class="w-full py-2 px-3 rounded-xl bg-stone-100 dark:bg-[#091f16] hover:bg-stone-200 dark:hover:bg-emerald-900/50 text-stone-700 dark:text-stone-200 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Catatan Pengeluaran</span>
                    </a>
                </div>
            </div>

        </div>

    @else
        <!-- =========================================================================
             B. DASHBOARD ADMINISTRATOR (RINGKASAN EKSEKUTIF SEMUA FITUR)
             ========================================================================= -->
        <!-- 1. TOP GREETING -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-stone-200/80 dark:border-white/[0.08]">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Ringkasan Eksekutif
                </h1>
                <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-medium mt-0.5">
                    Pantau penerimaan dāna umat, agenda kebaktian, dan operasional Vihara Sāmaggi Gāma.
                </p>
            </div>
        </div>

        <!-- Feedback Banner -->
        @if ($feedbackMessage)
            <div 
                x-data="{ show: true }" 
                x-show="show"
                x-transition
                class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-500/30 text-emerald-900 dark:text-emerald-200 text-xs font-semibold flex items-center justify-between shadow-xs"
            >
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    <span>{{ $feedbackMessage }}</span>
                </div>
                <button @click="show = false" type="button" class="text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 cursor-pointer p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <!-- 2. 4 STATISTIK KPI CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            
            <!-- Total Dāna Terhimpun -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex flex-col justify-between space-y-3 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Total Dāna Terhimpun</span>
                </div>
                <div class="text-xl font-extrabold tracking-tight dark:text-stone-100 text-amber-600">
                    Rp {{ number_format($totalDana, 0, ',', '.') }}
                </div>
                <div class="absolute right-0 -bottom-5 -mr-5">
                    <svg class="size-30 text-stone-300 dark:opacity-7 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <!-- Total Donatur -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex flex-col justify-between space-y-3 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Donatur Terverifikasi</span>
                </div>
                <div class="text-xl font-extrabold tracking-tight dark:text-stone-100 text-stone-900">
                    {{ number_format($totalDonatur) }} Umat
                </div>
                <div class="absolute right-0 -bottom-5 -mr-5">
                    <svg class="size-30 text-stone-300 dark:opacity-7 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>

            <!-- Program Dāna Aktif -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex flex-col justify-between space-y-3 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Program Donasi Aktif</span>
                </div>
                <div class="text-xl font-extrabold tracking-tight dark:text-stone-100 text-stone-900">
                    {{ $programAktif }} Kampanye
                </div>
                <div class="absolute right-0 -bottom-5 -mr-5">
                    <svg class="size-30 text-stone-300 dark:opacity-7 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
            </div>

            <!-- Agenda Puja & Kegiatan -->
            <div class="p-5 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex flex-col justify-between space-y-3 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Jadwal Kegiatan Aktif</span>
                </div>
                <div class="text-xl font-extrabold tracking-tight dark:text-stone-100 text-stone-900">
                    {{ $agendaAktif }} Kegiatan
                </div>
                <div class="absolute right-0 -bottom-5 -mr-5">
                    <svg class="size-30 text-stone-300 dark:opacity-7 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>

        </div>

        <!-- 3. 2-COLUMN SPLIT: RECENT DONATIONS & UPCOMING SCHEDULES -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Kolom Kiri (2 Kolom): Transaksi Dāna Terkini -->
            <div class="lg:col-span-2 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="p-5 border-b border-stone-200/80 dark:border-emerald-950 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-extrabold text-stone-900 dark:text-stone-100">
                                Transaksi Dāna Terkini
                            </h2>
                            <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                                Daftar perolehan dāna masuk terbaru dari donatur.
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                                <tr>
                                    <th scope="col" class="py-3 px-5">Donatur</th>
                                    <th scope="col" class="py-3 px-4">Program Dāna</th>
                                    <th scope="col" class="py-3 px-4 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                                @forelse ($transactions as $tx)
                                    <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/20 transition-colors">
                                        <td class="py-3.5 px-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-300 font-bold text-xs flex items-center justify-center shrink-0 border border-amber-500/20">
                                                    {{ strtoupper(substr($tx->donor_name, 0, 2)) }}
                                                </div>
                                                <div class="flex flex-col min-w-0">
                                                    <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs truncate max-w-[140px]">
                                                        {{ $tx->donor_name }}
                                                    </span>
                                                    <span class="text-[10px] text-stone-400">
                                                        {{ $tx->invoice_number }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="py-3.5 px-4">
                                            <span class="text-xs text-stone-700 dark:text-stone-300 font-medium truncate block max-w-[180px]">
                                                {{ $tx->donationProgram?->title ?? 'Dāna Umum Vihara' }}
                                            </span>
                                        </td>

                                        <td class="py-3.5 px-4 text-right font-extrabold text-stone-900 dark:text-stone-100 tabular-nums">
                                            Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-8 text-center text-stone-400">
                                            Belum ada data transaksi donasi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4 bg-stone-50/70 dark:bg-[#071710] border-t border-stone-200/80 dark:border-emerald-950 flex items-center justify-between text-xs">
                    <span class="text-stone-500 dark:text-stone-400">Terintegrasi otomatis dengan mutasi rekening & QRIS</span>
                    <a href="{{ route('admin.transaksi') }}" class="font-bold text-[#0D5B3A] dark:text-emerald-400 hover:underline">
                        Lihat Semua Transaksi →
                    </a>
                </div>
            </div>

            <!-- Kolom Kanan (1 Kolom): Agenda Kegiatan Terdekat -->
            <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="p-5 border-b border-stone-200/80 dark:border-emerald-950 flex items-center justify-between">
                        <h2 class="text-base font-extrabold text-stone-900 dark:text-stone-100">
                            Jadwal & Agenda Terdekat
                        </h2>
                    </div>

                    <div class="p-5 space-y-4">
                        @forelse ($upcomingSchedules as $sch)
                            <div class="p-3.5 rounded-xl bg-stone-50/80 dark:bg-[#071710] border border-stone-200/80 dark:border-emerald-950 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                        {{ $sch->category }}
                                    </span>
                                    <span class="text-[11px] text-stone-400 font-medium">
                                        {{ $sch->event_date?->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                                <h3 class="text-xs font-extrabold text-stone-900 dark:text-stone-100 line-clamp-1">
                                    {{ $sch->title }}
                                </h3>
                                <div class="flex items-center gap-3 text-[11px] text-stone-500 dark:text-stone-400">
                                    <span>{{ substr($sch->start_time, 0, 5) }}</span>
                                    <span>•</span>
                                    <span>{{ $sch->location }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-stone-400 text-xs">
                                Belum ada jadwal kegiatan terdaftar.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="p-4 bg-stone-50/70 dark:bg-[#071710] border-t border-stone-200/80 dark:border-emerald-950">
                    <a href="{{ route('admin.agenda') }}" class="w-full py-2 px-3 rounded-xl bg-stone-100 dark:bg-[#091f16] hover:bg-stone-200 dark:hover:bg-emerald-900/50 text-stone-700 dark:text-stone-200 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>Tambah Jadwal Baru</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- 4. STATISTIK & TRAFIK PENGUNJUNG WEBSITE (REAL-TIME INTERNAL TRACKER) -->
        <div class="space-y-4 pt-2">
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-1 border-b border-stone-200/80 dark:border-white/[0.08]">
                <div>
                    <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-emerald-700 dark:text-emerald-400 font-bold uppercase tracking-wider text-[10px]">Real-time Tracker</span>
                    </div>
                    <h2 class="text-lg font-extrabold text-stone-900 dark:text-stone-100 tracking-tight flex items-center gap-2">
                        <span>Trafik & Statistik Pengunjung</span>
                    </h2>
                </div>
                <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                    <span>Perangkat:</span>
                    <span class="px-2 py-0.5 rounded-md bg-stone-100 dark:bg-white/[0.06] text-stone-700 dark:text-stone-300 font-bold text-[10.5px]">📱 Mobile: {{ $visitorAnalytics['mobile_percent'] }}%</span>
                    <span class="px-2 py-0.5 rounded-md bg-stone-100 dark:bg-white/[0.06] text-stone-700 dark:text-stone-300 font-bold text-[10.5px]">💻 Desktop: {{ $visitorAnalytics['desktop_percent'] }}%</span>
                </div>
            </div>

            <!-- 4 Visitor KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                
                <!-- Hari Ini -->
                <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-emerald-500/30 dark:border-emerald-500/20 shadow-xs flex flex-col justify-between space-y-1 relative overflow-hidden">
                    <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Hari Ini</span>
                    <div class="text-xl sm:text-2xl font-black text-[#0D6E42] dark:text-emerald-400 tracking-tight">
                        {{ number_format($visitorAnalytics['today_hits']) }} <span class="text-xs font-bold text-stone-400">Hits</span>
                    </div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 font-medium">
                        {{ number_format($visitorAnalytics['today_unique']) }} Pengunjung Unik
                    </div>
                </div>

                <!-- Kemarin -->
                <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex flex-col justify-between space-y-1 relative overflow-hidden">
                    <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Kemarin</span>
                    <div class="text-xl sm:text-2xl font-black text-stone-800 dark:text-stone-100 tracking-tight">
                        {{ number_format($visitorAnalytics['yesterday_hits']) }} <span class="text-xs font-bold text-stone-400">Hits</span>
                    </div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 font-medium">
                        Kunjungan 24 Jam Lalu
                    </div>
                </div>

                <!-- Bulan Ini -->
                <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex flex-col justify-between space-y-1 relative overflow-hidden">
                    <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-amber-700 dark:text-amber-400">Bulan Ini</span>
                    <div class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-300 tracking-tight">
                        {{ number_format($visitorAnalytics['month_hits']) }} <span class="text-xs font-bold text-stone-400">Hits</span>
                    </div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 font-medium">
                        Total Bulan {{ \Carbon\Carbon::now()->translatedFormat('F') }}
                    </div>
                </div>

                <!-- Akumulasi Total -->
                <div class="p-4 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs flex flex-col justify-between space-y-1 relative overflow-hidden">
                    <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-stone-500 dark:text-stone-400">Total Akumulasi</span>
                    <div class="text-xl sm:text-2xl font-black text-stone-900 dark:text-stone-100 tracking-tight">
                        {{ number_format($visitorAnalytics['total_hits']) }} <span class="text-xs font-bold text-stone-400">Hits</span>
                    </div>
                    <div class="text-[11px] text-stone-500 dark:text-stone-400 font-medium">
                        {{ number_format($visitorAnalytics['total_unique']) }} Total Unik
                    </div>
                </div>

            </div>

            <!-- 2-Column Split: 7-Day Chart & Top Visited Pages -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                
                <!-- Left: 7-Day Bar Chart Visual (Col 1-7) -->
                @php
                    $maxHits = 1;
                    foreach ($visitorAnalytics['daily_stats'] as $day) {
                        if ($day['hits'] > $maxHits) $maxHits = $day['hits'];
                    }
                @endphp
                <div class="lg:col-span-7 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] p-5 shadow-xs flex flex-col justify-between space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-stone-900 dark:text-stone-100">
                                Tren Kunjungan 7 Hari Terakhir
                            </h3>
                            <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">
                                Perbandingan tayangan halaman (Hits) dan pengunjung unik harian.
                            </p>
                        </div>
                    </div>

                    <!-- 7-Day Visual Columns -->
                    <div class="grid grid-cols-7 gap-2 sm:gap-3 items-end h-40 pt-4 pb-2 border-b border-stone-100 dark:border-white/[0.06]">
                        @foreach ($visitorAnalytics['daily_stats'] as $day)
                            @php
                                $heightPercent = max(8, round(($day['hits'] / $maxHits) * 100));
                            @endphp
                            <div class="flex flex-col items-center justify-end h-full gap-1.5 group relative">
                                <!-- Tooltip on Hover -->
                                <div class="opacity-0 group-hover:opacity-100 pointer-events-none absolute -top-10 bg-stone-900 text-white text-[10px] py-1 px-2 rounded-lg transition-opacity duration-150 whitespace-nowrap z-20 shadow-lg border border-stone-700">
                                    {{ $day['day_name'] }}: {{ $day['hits'] }} hits ({{ $day['unique'] }} unik)
                                </div>

                                <div class="w-full flex items-end justify-center gap-1 h-full">
                                    <!-- Bar Hits -->
                                    <div 
                                        class="w-full max-w-[18px] bg-gradient-to-t from-[#0D5B3A] to-emerald-400 rounded-t-md transition-all duration-500 group-hover:brightness-110" 
                                        style="height: {{ $heightPercent }}%;"
                                    ></div>
                                </div>
                                <span class="text-[10.5px] font-bold text-stone-500 dark:text-stone-400">
                                    {{ $day['short_day'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 pt-1">
                        <span>Data diperbarui otomatis dari setiap kunjungan publik</span>
                    </div>
                </div>

                <!-- Right: Top 5 Visited Pages (Col 8-12) -->
                <div class="lg:col-span-5 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] p-5 shadow-xs flex flex-col justify-between space-y-3">
                    <div>
                        <h3 class="text-sm font-extrabold text-stone-900 dark:text-stone-100">
                            Halaman Paling Sering Dikunjungi
                        </h3>
                        <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-0.5">
                            5 Halaman publik dengan lalu lintas tertinggi.
                        </p>
                    </div>

                    <div class="divide-y divide-stone-100 dark:divide-white/[0.06] flex-1">
                        @forelse ($visitorAnalytics['top_pages'] as $page)
                            <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-5 h-5 rounded-md bg-stone-100 dark:bg-white/[0.06] text-stone-600 dark:text-stone-300 font-mono font-bold text-[10px] flex items-center justify-center shrink-0">
                                        {{ $loop->iteration }}
                                    </span>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-stone-900 dark:text-stone-100 truncate text-[11.5px]">
                                            {{ $page['page_name'] ?? 'Halaman Publik' }}
                                        </h4>
                                        <span class="text-[10px] text-stone-400 font-mono truncate block">
                                            {{ $page['url'] ?? '/' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-black text-[#0D6E42] dark:text-emerald-400 text-xs tabular-nums">
                                        {{ number_format($page['total_views'] ?? 0) }} <span class="text-[9.5px] font-normal text-stone-400">views</span>
                                    </div>
                                    <span class="text-[10px] text-stone-400 block">
                                        {{ number_format($page['unique_visitors'] ?? 0) }} unik
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-stone-400">
                                Belum ada data halaman dikunjungi.
                            </div>
                        @endforelse
                    </div>

                    <div class="pt-2 border-t border-stone-100 dark:border-white/[0.06] text-[11px] text-stone-400 flex items-center justify-between">
                        <span>Pencatatan real-time</span>
                        <a href="{{ route('home') }}" target="_blank" class="text-[#0D6E42] dark:text-emerald-400 font-bold hover:underline">
                            Buka Web Publik ↗
                        </a>
                    </div>
                </div>

            </div>

        </div>
    @endif

</div>