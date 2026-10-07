<?php

use App\Models\Donation;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.admin')] class extends Component
{
    use WithPagination;

    public string $searchQuery = '';

    // Modal state
    public bool $isModalOpen = false;
    public bool $isEditing = false;
    public ?int $editId = null;

    // Form fields (Uraian & Nominal)
    public string $formTitle = '';
    public string $formAmount = '';

    public function updatingSearchQuery(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->isEditing = false;
        $this->editId = null;
        $this->formTitle = '';
        $this->formAmount = '';
        $this->isModalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $expense = Expense::findOrFail($id);
        $this->isEditing = true;
        $this->editId = $expense->id;
        $this->formTitle = $expense->title;
        $this->formAmount = (string) (int) $expense->amount;
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    public function saveExpense(): void
    {
        $cleanAmount = (float) preg_replace('/[^0-9.]/', '', $this->formAmount);

        $this->validate([
            'formTitle' => 'required|string',
            'formAmount' => 'required|numeric|min:1',
        ], [
            'formTitle.required' => 'Uraian pengeluaran wajib diisi.',
            'formAmount.required' => 'Nominal pengeluaran wajib diisi.',
            'formAmount.min' => 'Nominal pengeluaran minimal Rp 1.',
        ]);

        if ($this->isEditing && $this->editId) {
            $expense = Expense::findOrFail($this->editId);
            $expense->update([
                'title' => $this->formTitle,
                'amount' => $cleanAmount,
            ]);

            session()->flash('feedbackMessage', 'Catatan pengeluaran "' . $expense->title . '" berhasil diperbarui.');
        } else {
            $expense = Expense::create([
                'title' => $this->formTitle,
                'amount' => $cleanAmount,
                'expense_date' => now(),
                'category' => 'Umum',
                'created_by' => Auth::id(),
            ]);

            session()->flash('feedbackMessage', 'Catatan pengeluaran baru "' . $expense->title . '" berhasil ditambahkan.');
        }

        $this->closeModal();
    }

    public function deleteExpense(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $title = $expense->title;
        $expense->delete();

        session()->flash('feedbackMessage', 'Catatan pengeluaran "' . $title . '" telah berhasil dihapus.');
    }

    public function render()
    {
        $query = Expense::query();

        if ($this->searchQuery) {
            $q = '%' . trim($this->searchQuery) . '%';
            $query->where('title', 'like', $q);
        }

        $expenses = $query->orderByDesc('id')->paginate(15);

        // Financial Metrics
        $totalAllExpenses = Expense::sum('amount');
        $totalExpensesCount = Expense::count();
        $totalDonationsCollected = Donation::where('status', 'verified')->sum('amount');

        return $this->view([
            'expenses' => $expenses,
            'totalAllExpenses' => $totalAllExpenses,
            'totalExpensesCount' => $totalExpensesCount,
            'totalDonationsCollected' => $totalDonationsCollected,
        ])->title('Pengeluaran Dāna — Panel Admin');
    }
};

?>

<div class="space-y-8">
    
    <!-- =========================================================================
         1. TOP HEADER & BREADCRUMB
         ========================================================================= -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-xs text-stone-500 dark:text-stone-400">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-600 transition-colors">Admin</a>
                <span>/</span>
                <span class="text-stone-800 dark:text-stone-200 font-semibold">Keuangan & Kas</span>
                <span>/</span>
                <span class="text-emerald-700 dark:text-emerald-400 font-bold">Pengeluaran Dāna</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 dark:text-stone-100 tracking-tight flex items-center gap-3">
                <span>Catatan Pengeluaran Dāna</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/25 font-bold">
                    Kas Vihara
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                Kelola pencatatan uraian dan nominal pengeluaran kas vihara.
            </p>
        </div>

        <!-- Action Button -->
        <button 
            wire:click="openCreateModal" 
            type="button" 
            class="px-5 py-2.5 rounded-xl bg-[#0D5B3A] hover:bg-emerald-800 text-white font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer shrink-0"
        >
            <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Catat Pengeluaran Baru</span>
        </button>
    </div>

    <!-- Flash Feedback Message -->
    @if (session()->has('feedbackMessage'))
        <div 
            x-data="{ show: true }" 
            x-show="show" 
            x-transition 
            class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300 flex items-center justify-between text-xs sm:text-sm font-medium shadow-xs"
        >
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('feedbackMessage') }}</span>
            </div>
            <button @click="show = false" type="button" class="text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 cursor-pointer p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <!-- =========================================================================
         2. FINANCIAL SUMMARY METRIC CARDS
         ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        
        <!-- Metric 1: Akumulasi Pengeluaran -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total Akumulasi Belanja</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/15 text-rose-700 dark:text-rose-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-black text-rose-700 dark:text-rose-400">
                Rp {{ number_format($totalAllExpenses, 0, ',', '.') }}
            </div>
            <p class="text-[10.5px] text-stone-400">{{ $totalExpensesCount }} kali transaksi tercatat</p>
        </div>

        <!-- Metric 2: Total Donasi Masuk -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">Total Dāna Masuk</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/15 text-[#0D6E42] dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                </div>
            </div>
            <div class="text-xl sm:text-2xl font-black text-[#0D6E42] dark:text-emerald-400">
                Rp {{ number_format($totalDonationsCollected, 0, ',', '.') }}
            </div>
            <p class="text-[10.5px] text-stone-400">Donasi umat berstatus tervalidasi</p>
        </div>
    </div>

    <!-- =========================================================================
         3. TABEL DATA PENGELUARAN & TOOLBAR SEARCH
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Table Toolbar -->
        <div class="p-4 sm:p-5 border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            
            <div class="text-xs font-bold text-stone-700 dark:text-stone-300">
                Daftar Pengeluaran Kas
            </div>

            <!-- Search Input -->
            <div class="relative w-full sm:w-80">
                <input 
                    wire:model.live.debounce.300ms="searchQuery" 
                    type="text" 
                    placeholder="Cari uraian belanja..." 
                    class="w-full pl-9 pr-4 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-emerald-500"
                />
                <svg class="w-4 h-4 text-stone-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

        </div>

        <!-- Table Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-100/75 dark:bg-[#071710] text-stone-600 dark:text-stone-400 uppercase tracking-wider font-bold text-[10px] border-b border-stone-200/80 dark:border-emerald-950">
                    <tr>
                        <th class="py-3 px-4 text-center w-14">No</th>
                        <th class="py-3 px-4">Uraian Pengeluaran</th>
                        <th class="py-3 px-4 text-right">Nominal (Rp)</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200/70 dark:divide-emerald-950/60">
                    @forelse ($expenses as $item)
                        <tr class="hover:bg-stone-50/80 dark:hover:bg-emerald-950/30 transition-colors">
                            
                            <!-- Nomor -->
                            <td class="py-3.5 px-4 text-center text-stone-400 dark:text-stone-500 font-semibold text-xs">
                                {{ ($expenses->currentPage() - 1) * $expenses->perPage() + $loop->iteration }}
                            </td>

                            <!-- Uraian -->
                            <td class="py-3.5 px-4 max-w-md">
                                <div class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm leading-snug whitespace-pre-line">
                                    {{ $item->title }}
                                </div>
                            </td>

                            <!-- Nominal -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <span class="font-black text-rose-700 dark:text-rose-400 text-xs sm:text-sm">
                                    - Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </span>
                            </td>

                            <!-- Aksi (Edit & Hapus) -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Edit -->
                                    <button 
                                        wire:click="openEditModal({{ $item->id }})" 
                                        type="button" 
                                        class="w-7 h-7 rounded-lg flex items-center justify-center bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 text-amber-700 dark:text-amber-300 border border-amber-500/20 cursor-pointer shadow-2xs transition-colors"
                                        title="Edit Pengeluaran"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        wire:click="deleteExpense({{ $item->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus catatan pengeluaran '{{ $item->title }}' sebesar Rp {{ number_format($item->amount, 0, ',', '.') }}?" 
                                        type="button" 
                                        class="w-7 h-7 rounded-lg flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 border border-rose-500/20 cursor-pointer shadow-2xs transition-colors"
                                        title="Hapus Pengeluaran"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-16 text-center text-stone-400">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <div class="w-12 h-12 rounded-full bg-stone-100 dark:bg-emerald-950/50 flex items-center justify-center mx-auto text-stone-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                    </div>
                                    <div class="font-bold text-stone-700 dark:text-stone-300 text-sm">Tidak ada catatan pengeluaran</div>
                                    <p class="text-xs text-stone-400">Klik "+ Catat Pengeluaran Baru" untuk menambahkan transaksi.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-5 py-4 bg-stone-50/80 dark:bg-[#071710] border-t border-stone-200/80 dark:border-emerald-950/70">
            {{ $expenses->onEachSide(1)->links('components.custom-pagination') }}
        </div>

    </div>

    <!-- =========================================================================
         4. MODAL TAMBAH & EDIT PENGELUARAN (TELEPORTED)
         ========================================================================= -->
    <template x-teleport="body">
        <div 
            x-show="$wire.isModalOpen" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            @keydown.escape.window="$wire.closeModal"
        >
            <div 
                @click.away="$wire.closeModal" 
                class="relative max-w-lg w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
            >
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-[#0B2117] to-[#0F3325] text-white flex items-center justify-between border-b border-emerald-900">
                    <div class="space-y-0.5">
                        <h3 class="text-lg font-extrabold text-[#FAF5ED]">
                            {{ $isEditing ? 'Edit Catatan Pengeluaran' : 'Catat Pengeluaran Baru' }}
                        </h3>
                        <p class="text-xs text-stone-300">
                            {{ $isEditing ? 'Perbarui uraian atau nominal pengeluaran kas.' : 'Masukkan uraian dan nominal pengeluaran kas.' }}
                        </p>
                    </div>
                    <button 
                        @click="$wire.closeModal" 
                        type="button" 
                        class="p-2 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Body -->
                <form wire:submit.prevent="saveExpense" class="p-6 space-y-4 overflow-y-auto text-xs custom-scrollbar">
                    
                    <!-- 1. Uraian Pengeluaran (Textarea) -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Uraian Pengeluaran <span class="text-amber-500">*</span>
                        </label>
                        <textarea 
                            wire:model="formTitle" 
                            rows="4" 
                            placeholder="Tuliskan uraian keperluan pengeluaran belanja kas vihara..." 
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-emerald-500 resize-none font-semibold"
                        ></textarea>
                        @error('formTitle') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                    </div>

                    <!-- 2. Nominal Pengeluaran (Rp) -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nominal Pengeluaran (Rp) <span class="text-amber-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 font-bold text-stone-400">Rp</span>
                            <input 
                                wire:model="formAmount" 
                                type="text" 
                                placeholder="500000" 
                                class="w-full pl-11 pr-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-emerald-500 font-bold"
                            />
                        </div>
                        @error('formAmount') <span class="text-rose-500 text-[11px] block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-stone-200/80 dark:border-emerald-950 flex items-center justify-end gap-3">
                        <button 
                            @click="$wire.closeModal" 
                            type="button" 
                            class="px-4 py-2 rounded-xl text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-emerald-950 font-bold transition-colors cursor-pointer"
                        >
                            Batal
                        </button>

                        <button 
                            type="submit" 
                            class="px-6 py-2.5 rounded-xl bg-[#0D5B3A] hover:bg-emerald-800 text-white font-bold shadow-md hover:shadow-lg transition-all duration-200 cursor-pointer flex items-center gap-2"
                        >
                            <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Pengeluaran' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

</div>
