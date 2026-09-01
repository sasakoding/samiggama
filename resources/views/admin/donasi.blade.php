<?php

use App\Models\DonationProgram;
use App\Services\ImageUploadService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $activeStatusTab = 'semua'; // 'semua', 'aktif', 'selesai'
    public string $searchQuery = '';
    public bool $modalOpen = false;
    public ?int $editingId = null;
    public ?string $feedbackMessage = null;

    // Form inputs
    public string $formTitle = '';
    public string $formCategory = 'Pembangunan & Sarana';
    public string $formTarget = '';
    public string $formStartDate = '';
    public string $formEndDate = '';
    public string $formContent = '';
    public string $formStatus = 'aktif';
    public $formCoverImage = null;
    public ?string $existingCoverImage = null;

    public function setTab(string $tab): void
    {
        $this->activeStatusTab = $tab;
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'editingId',
            'formTitle',
            'formTarget',
            'formStartDate',
            'formEndDate',
            'formContent',
            'formCoverImage',
            'existingCoverImage',
        ]);
        $this->formCategory = 'Pembangunan & Sarana';
        $this->formStatus = 'aktif';
        $this->formStartDate = date('Y-m-d');
        $this->modalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $program = DonationProgram::find($id);
        if (!$program) {
            return;
        }

        $this->editingId = $program->id;
        $this->formTitle = $program->title;
        $this->formCategory = $program->category;
        $this->formTarget = (string) $program->target_amount;
        $this->formStartDate = $program->start_date ? $program->start_date->format('Y-m-d') : '';
        $this->formEndDate = $program->end_date ? $program->end_date->format('Y-m-d') : '';
        $this->formContent = $program->content ?? '';
        $this->formStatus = $program->status;
        $this->existingCoverImage = $program->cover_image;
        $this->formCoverImage = null;

        $this->modalOpen = true;
    }

    public function saveProgram(): void
    {
        $this->validate([
            'formTitle' => 'required|string|max:255',
            'formCategory' => 'required|string',
            'formTarget' => 'required|numeric|min:1000',
            'formCoverImage' => 'nullable|image|max:10240', // max 10MB
        ]);

        $coverImagePath = $this->existingCoverImage;
        if ($this->formCoverImage) {
            $coverImagePath = ImageUploadService::uploadAndCompress($this->formCoverImage, 'uploads/programs');
            ImageUploadService::cleanLivewireTmp();
        }

        $targetAmount = (int) $this->formTarget;
        $slug = Str::slug($this->formTitle);

        if ($this->editingId) {
            $program = DonationProgram::find($this->editingId);
            if ($program) {
                // Ensure slug uniqueness
                if ($program->slug !== $slug && DonationProgram::where('slug', $slug)->exists()) {
                    $slug .= '-' . time();
                }

                $program->update([
                    'title' => $this->formTitle,
                    'slug' => $slug,
                    'category' => $this->formCategory,
                    'target_amount' => $targetAmount,
                    'start_date' => $this->formStartDate ?: null,
                    'end_date' => $this->formEndDate ?: null,
                    'content' => $this->formContent,
                    'status' => $this->formStatus,
                    'cover_image' => $coverImagePath,
                ]);

                $this->feedbackMessage = "Program dāna '{$this->formTitle}' berhasil diperbarui.";
            }
        } else {
            if (DonationProgram::where('slug', $slug)->exists()) {
                $slug .= '-' . time();
            }

            DonationProgram::create([
                'title' => $this->formTitle,
                'slug' => $slug,
                'category' => $this->formCategory,
                'target_amount' => $targetAmount,
                'start_date' => $this->formStartDate ?: null,
                'end_date' => $this->formEndDate ?: null,
                'content' => $this->formContent,
                'status' => $this->formStatus,
                'cover_image' => $coverImagePath ?: 'images/gallery-altar.jpg',
            ]);

            $this->feedbackMessage = "Program dāna baru '{$this->formTitle}' berhasil dipublikasikan.";
        }

        $this->modalOpen = false;
    }

    public function toggleStatus(int $id): void
    {
        $program = DonationProgram::find($id);
        if ($program) {
            $program->status = ($program->status === 'aktif') ? 'selesai' : 'aktif';
            $program->save();
            $this->feedbackMessage = "Status program '{$program->title}' diubah menjadi {$program->status}.";
        }
    }

    public function deleteProgram(int $id): void
    {
        $program = DonationProgram::find($id);
        if ($program) {
            $title = $program->title;
            ImageUploadService::deleteOldImage($program->cover_image);
            $program->delete();
            $this->feedbackMessage = "Program '{$title}' berhasil dihapus.";
        }
    }

    public function render()
    {
        $query = DonationProgram::query()->latest();

        if ($this->activeStatusTab !== 'semua') {
            $query->where('status', $this->activeStatusTab);
        }

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(category) LIKE ?', [$search]);
            });
        }

        $programs = $query->get();

        $countSemua = DonationProgram::count();
        $countAktif = DonationProgram::where('status', 'aktif')->count();
        $countSelesai = DonationProgram::where('status', 'selesai')->count();

        return $this->view([
            'programs' => $programs,
            'countSemua' => $countSemua,
            'countAktif' => $countAktif,
            'countSelesai' => $countSelesai,
        ])->title('Kelola Program Donasi')->layout('layouts::admin');
    }
};
?>

<div class="space-y-6 font-sans">

    <!-- =========================================================================
         1. TOP HEADER & SEARCH CONTROLS
         ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Program Dāna Paramita
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Kelola kampanye penggalangan dāna vihara, pembangunan sarana, dan kebajikan sosial.
            </p>
        </div>

        <!-- Filter Tabs & Create Button -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button 
                wire:click="openCreateModal"
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Program Baru</span>
            </button>
        </div>
    </div>

    <!-- Feedback Banner -->
    @if ($feedbackMessage)
        <div 
            x-data="{ show: true }" 
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
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

    <!-- =========================================================================
         2. DAFTAR PROGRAM DĀNA (DATA TABLE & CARDS)
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Table Header Toolbar -->
        <div class="p-5 border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Segmented Status Tabs -->
            <div class="p-1 rounded-xl bg-stone-100 dark:bg-[#06150e] border border-stone-200 dark:border-emerald-900/40 text-xs font-bold flex items-center gap-1">
                <button 
                    @click="$wire.setTab('semua')" 
                    type="button" 
                    class="px-3 py-1.5 rounded-lg transition-all cursor-pointer {{ $activeStatusTab === 'semua' ? 'bg-white dark:bg-emerald-900/70 text-[#0D5B3A] dark:text-white shadow-xs font-extrabold' : 'text-stone-500 hover:text-stone-800 dark:hover:text-stone-200' }}"
                >
                    Semua ({{ $countSemua }})
                </button>
                <button 
                    @click="$wire.setTab('aktif')" 
                    type="button" 
                    class="px-3 py-1.5 rounded-lg transition-all cursor-pointer {{ $activeStatusTab === 'aktif' ? 'bg-white dark:bg-emerald-900/70 text-[#0D5B3A] dark:text-white shadow-xs font-extrabold' : 'text-stone-500 hover:text-stone-800 dark:hover:text-stone-200' }}"
                >
                    Aktif ({{ $countAktif }})
                </button>
                <button 
                    @click="$wire.setTab('selesai')" 
                    type="button" 
                    class="px-3 py-1.5 rounded-lg transition-all cursor-pointer {{ $activeStatusTab === 'selesai' ? 'bg-white dark:bg-emerald-900/70 text-[#0D5B3A] dark:text-white shadow-xs font-extrabold' : 'text-stone-500 hover:text-stone-800 dark:hover:text-stone-200' }}"
                >
                    Selesai ({{ $countSelesai }})
                </button>
            </div>

            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    wire:model.live.debounce.250ms="searchQuery" 
                    type="text" 
                    placeholder="Cari program dāna..." 
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-[#0D5B3A] focus:ring-1 focus:ring-[#0D5B3A] transition-all shadow-2xs"
                />
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-5">Program & Kategori</th>
                        <th scope="col" class="py-3.5 px-4">Donatur</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                        <th scope="col" class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                    @forelse ($programs as $prog)
                        <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/20 transition-colors">
                            
                            <!-- Program Title & Category -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <img 
                                        src="{{ asset($prog->cover_image ?: 'images/gallery-altar.jpg') }}" 
                                        alt="{{ $prog->title }}" 
                                        class="w-12 h-10 rounded-lg object-cover border border-stone-200 dark:border-emerald-900/60 shrink-0" 
                                    />
                                    <div class="flex flex-col min-w-0 max-w-sm">
                                        <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm truncate">
                                            {{ $prog->title }}
                                        </span>
                                        <span class="text-[11px] text-stone-400 mt-0.5">
                                            {{ $prog->category }}
                                        </span>

                                        <div class="space-y-1 mt-2.5">
                                            <div class="flex justify-between text-[10.5px] font-bold text-stone-600 dark:text-stone-300">
                                                <span>{{ $prog->progress_percentage }}%</span>
                                            </div>
                                            <div class="w-full h-1.5 rounded-full bg-stone-100 dark:bg-stone-800 overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-amber-500 to-emerald-600 rounded-full" style="width: {{ $prog->progress_percentage }}%"></div>
                                            </div>
                                        </div>

                                        <div class="flex justify-between mt-1">
                                            <span class="font-extrabold text-amber-600 dark:text-amber-400 text-xs">
                                                Rp {{ number_format($prog->collected_amount, 0, ',', '.') }}
                                            </span>
                                            <span class="text-[11px] text-stone-400">
                                                dari Rp {{ number_format($prog->target_amount, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Donors Count -->
                            <td class="py-3.5 px-4">
                                <span class="font-extrabold text-stone-800 dark:text-stone-200 tabular-nums text-xs">
                                    {{ $prog->donors_count }} Donatur
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-4 text-center">
                                @if ($prog->status === 'aktif')
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-300 font-bold text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Aktif</span>
                                    </span>
                                @elseif ($prog->status === 'selesai')
                                    <span class="inline-flex items-center gap-1.5 text-stone-600 dark:text-stone-400 font-bold text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                        <span>Selesai</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-amber-700 dark:text-amber-300 font-bold text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Draft</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Action Buttons -->
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Edit -->
                                    <button 
                                        wire:click="openEditModal({{ $prog->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-600 dark:text-stone-300 transition-all cursor-pointer border border-stone-200/80 dark:border-emerald-500/20 shadow-2xs"
                                        title="Edit Program"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- Toggle Status -->
                                    <button 
                                        wire:click="toggleStatus({{ $prog->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer border shadow-2xs {{ $prog->status === 'aktif' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-500/30' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' }}"
                                        title="{{ $prog->status === 'aktif' ? 'Tandai Selesai' : 'Aktifkan Kembali' }}"
                                    >
                                        @if ($prog->status === 'aktif')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </button>

                                    <!-- Delete -->
                                    <button 
                                        wire:click="deleteProgram({{ $prog->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus program dāna ini?" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 transition-all cursor-pointer border border-rose-500/20 shadow-2xs"
                                        title="Hapus Program"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-stone-400">
                                Belum ada program dāna yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- =========================================================================
         3. MODAL TAMBAH / EDIT PROGRAM (TELEPORTED)
         ========================================================================= -->
    <template x-teleport="body">
        <div 
            x-show="$wire.modalOpen" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            @keydown.escape.window="$wire.modalOpen = false"
        >
            <div 
                @click.away="$wire.modalOpen = false" 
                class="relative max-w-xl w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
            >
                <!-- Header -->
                <div class="p-6 bg-gradient-to-r from-[#0B2117] to-[#0F3325] text-white flex items-center justify-between border-b border-emerald-900">
                    <div class="space-y-0.5">
                        <h3 class="text-lg font-extrabold text-[#FAF5ED]">
                            {{ $editingId ? 'Edit Program Dāna' : 'Buat Program Dāna Baru' }}
                        </h3>
                        <p class="text-xs text-stone-300">Isi formulir rincian target dan kampanye kebajikan.</p>
                    </div>
                    <button 
                        @click="$wire.modalOpen = false" 
                        type="button" 
                        class="p-2 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="saveProgram" class="p-6 space-y-4 overflow-y-auto text-xs">
                    
                    <!-- Judul Program -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Judul Program Dāna <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formTitle" 
                            type="text" 
                            required 
                            placeholder="cth: Dāna Renovasi Kuti Bhikkhu Sangha"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"
                        />
                    </div>

                    <!-- Kategori & Target Anggaran -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Kategori Program
                            </label>
                            <select 
                                wire:model="formCategory" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            >
                                <option value="Pembangunan & Sarana">Pembangunan & Sarana</option>
                                <option value="Operasional & Rutin">Operasional & Rutin</option>
                                <option value="Pendidikan & Generasi Muda">Pendidikan & Generasi Muda</option>
                                <option value="Bakti Sosial & Kemanusiaan">Bakti Sosial & Kemanusiaan</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Target Anggaran (Rp) <span class="text-amber-500">*</span>
                            </label>
                            <input 
                                wire:model="formTarget" 
                                type="number" 
                                min="1000"
                                step="1000"
                                required 
                                placeholder="cth: 50000000"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                            @if ($formTarget && is_numeric($formTarget))
                                <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                                    Format: Rp {{ number_format((int)$formTarget, 0, ',', '.') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Periode Mulai & Selesai -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Tanggal Mulai
                            </label>
                            <input 
                                wire:model="formStartDate" 
                                type="date" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Tanggal Berakhir
                            </label>
                            <input 
                                wire:model="formEndDate" 
                                type="date" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>
                    </div>

                    <!-- Cover Image Upload -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Foto Sampul Kampanye (Otomatis Kompresi)
                        </label>
                        <input 
                            wire:model="formCoverImage" 
                            type="file" 
                            accept="image/*"
                            class="w-full text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 dark:file:bg-emerald-950 file:text-stone-700 dark:file:text-stone-300 hover:file:bg-stone-200 cursor-pointer"
                        />
                        <div wire:loading wire:target="formCoverImage" class="text-amber-500 text-[11px] font-semibold">
                            Memproses & mengunggah gambar...
                        </div>
                    </div>

                    <!-- Rincian Penjelasan -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Rincian Program & Penjelasan
                        </label>
                        <textarea 
                            wire:model="formContent" 
                            rows="3"
                            placeholder="Jelaskan tujuan penggalangan dāna ini kepada umat..."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        ></textarea>
                    </div>

                    <!-- Status -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Status Kampanye
                        </label>
                        <div class="flex items-center gap-4 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="formStatus" type="radio" value="aktif" class="text-[#0D5B3A] focus:ring-emerald-500" />
                                <span class="font-bold text-stone-800 dark:text-stone-200">Aktif Terbuka</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="formStatus" type="radio" value="selesai" class="text-[#0D5B3A] focus:ring-emerald-500" />
                                <span class="font-bold text-stone-600 dark:text-stone-400">Tandai Selesai</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="formStatus" type="radio" value="draft" class="text-[#0D5B3A] focus:ring-emerald-500" />
                                <span class="font-bold text-stone-600 dark:text-stone-400">Simpan Draft</span>
                            </label>
                        </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex items-center justify-end gap-2.5">
                        <button 
                            @click="$wire.modalOpen = false" 
                            type="button" 
                            class="py-2.5 px-4 rounded-xl bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-700 dark:text-stone-300 font-bold text-xs transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer"
                        >
                            {{ $editingId ? 'Simpan Perubahan' : 'Publikasikan Program' }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

</div>
