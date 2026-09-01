<?php

use App\Models\Officer;
use App\Services\ImageUploadService;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $searchQuery = '';
    public bool $modalOpen = false;
    public ?int $editingId = null;
    public ?string $feedbackMessage = null;

    // Form inputs
    public string $formName = '';
    public string $formTitle = '';
    public string $formDivision = 'Pengurus Harian';
    public int $formSortOrder = 1;
    public string $formStatus = 'aktif';
    public $formPhoto = null;
    public ?string $existingPhoto = null;

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'formName', 'formTitle', 'formPhoto', 'existingPhoto']);
        $this->formDivision = 'Pengurus Harian';
        $this->formSortOrder = Officer::count() + 1;
        $this->formStatus = 'aktif';
        $this->modalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $officer = Officer::find($id);
        if (!$officer) {
            return;
        }

        $this->editingId = $officer->id;
        $this->formName = $officer->name;
        $this->formTitle = $officer->title;
        $this->formDivision = $officer->division;
        $this->formSortOrder = $officer->sort_order;
        $this->formStatus = $officer->status;
        $this->existingPhoto = $officer->photo;
        $this->formPhoto = null;

        $this->modalOpen = true;
    }

    public function saveOfficer(): void
    {
        $this->validate([
            'formName' => 'required|string|max:255',
            'formTitle' => 'required|string|max:255',
            'formDivision' => 'required|string',
            'formSortOrder' => 'required|integer',
            'formPhoto' => 'nullable|image|max:10240',
        ]);

        $photoPath = $this->existingPhoto;
        if ($this->formPhoto) {
            $photoPath = ImageUploadService::uploadAndCompress($this->formPhoto, 'uploads/officers');
            ImageUploadService::cleanLivewireTmp();
        }

        if ($this->editingId) {
            $officer = Officer::find($this->editingId);
            if ($officer) {
                $officer->update([
                    'name' => $this->formName,
                    'title' => $this->formTitle,
                    'division' => $this->formDivision,
                    'sort_order' => $this->formSortOrder,
                    'status' => $this->formStatus,
                    'photo' => $photoPath,
                ]);
                $this->feedbackMessage = "Data pengurus '{$this->formName}' berhasil diperbarui.";
            }
        } else {
            Officer::create([
                'name' => $this->formName,
                'title' => $this->formTitle,
                'division' => $this->formDivision,
                'sort_order' => $this->formSortOrder,
                'status' => $this->formStatus,
                'photo' => $photoPath ?: 'images/bhante-dhammiko.jpg',
            ]);
            $this->feedbackMessage = "Pengurus baru '{$this->formName}' berhasil ditambahkan.";
        }

        $this->modalOpen = false;
    }

    public function deleteOfficer(int $id): void
    {
        $officer = Officer::find($id);
        if ($officer) {
            $name = $officer->name;
            ImageUploadService::deleteOldImage($officer->photo);
            $officer->delete();
            $this->feedbackMessage = "Data pengurus '{$name}' berhasil dihapus.";
        }
    }

    public function render()
    {
        $query = Officer::orderBy('sort_order');

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(title) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(division) LIKE ?', [$search]);
            });
        }

        $officers = $query->get();

        return $this->view([
            'officers' => $officers,
        ])->title('Kelola Pengurus Yayasan')->layout('layouts::admin');
    }
};
?>

<div class="space-y-6 font-sans">

    <!-- =========================================================================
         1. TOP HEADER & CONTROLS
         ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Struktur Pengurus Yayasan
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Bagan hierarki dewan pembina, pengurus harian, dan seksi bidang Vihara Sāmaggi Gāma.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button 
                wire:click="openCreateModal"
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span>+ Pengurus Baru</span>
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
         2. TABEL DATA PENGURUS
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Table Toolbar -->
        <div class="p-5 border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-stone-900 dark:text-stone-100">
                    Daftar Struktur Kepengurusan
                </h2>
                <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                    Data personel dewan pembina spiritual dan badan pengurus yayasan.
                </p>
            </div>

            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    wire:model.live.debounce.250ms="searchQuery" 
                    type="text" 
                    placeholder="Cari nama, jabatan..." 
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-[#0D5B3A] focus:ring-1 focus:ring-[#0D5B3A] transition-all shadow-2xs"
                />
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-5">Nama & Jabatan</th>
                        <th scope="col" class="py-3.5 px-4">Divisi / Badan</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Urutan</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                        <th scope="col" class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                    @forelse ($officers as $off)
                        <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/20 transition-colors">
                            
                            <!-- Nama & Jabatan -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-300 font-bold text-xs flex items-center justify-center shrink-0 border border-amber-500/20">
                                        {{ strtoupper(substr($off->name, 0, 2)) }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm truncate">
                                            {{ $off->name }}
                                        </span>
                                        <span class="text-[11px] text-stone-400 mt-0.5">
                                            {{ $off->title }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Divisi -->
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-xs text-stone-800 dark:text-stone-200">
                                    {{ $off->division }}
                                </span>
                            </td>

                            <!-- Urutan -->
                            <td class="py-3.5 px-4 text-center font-bold text-stone-600 dark:text-stone-300">
                                #{{ $off->sort_order }}
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if ($off->status === 'aktif')
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-300 font-bold text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Aktif</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-stone-500 font-bold text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                        <span>Nonaktif</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Edit -->
                                    <button 
                                        wire:click="openEditModal({{ $off->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-emerald-500/20 cursor-pointer shadow-2xs"
                                        title="Edit Data Pengurus"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- Hapus -->
                                    <button 
                                        wire:click="deleteOfficer({{ $off->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus data pengurus ini?" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 border border-rose-500/20 cursor-pointer shadow-2xs"
                                        title="Hapus Pengurus"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-stone-400">
                                Belum ada data pengurus.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- =========================================================================
         3. MODAL TAMBAH / EDIT PENGURUS (TELEPORTED)
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
                            {{ $editingId ? 'Edit Data Pengurus' : 'Tambah Pengurus Baru' }}
                        </h3>
                        <p class="text-xs text-stone-300">Isi data lengkap pejabat pengurus yayasan.</p>
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
                <form wire:submit.prevent="saveOfficer" class="p-6 space-y-4 overflow-y-auto text-xs">
                    
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nama Lengkap & Gelar <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formName" 
                            type="text" 
                            required 
                            placeholder="cth: Hendra Wijaya, S.E."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Jabatan Resmi <span class="text-amber-500">*</span>
                            </label>
                            <input 
                                wire:model="formTitle" 
                                type="text" 
                                required 
                                placeholder="cth: Ketua Umum Yayasan"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Divisi / Badan
                            </label>
                            <select 
                                wire:model="formDivision" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            >
                                <option value="Dewan Pembina">Dewan Pembina</option>
                                <option value="Pengurus Harian">Pengurus Harian</option>
                                <option value="Seksi Bidang">Seksi Bidang</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Urutan Tampil (Sort Order)
                            </label>
                            <input 
                                wire:model="formSortOrder" 
                                type="number" 
                                min="1"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Status Keaktifan
                            </label>
                            <select 
                                wire:model="formStatus" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            >
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Foto Profil (Kompresi Otomatis)
                        </label>
                        <input 
                            wire:model="formPhoto" 
                            type="file" 
                            accept="image/*"
                            class="w-full text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 dark:file:bg-emerald-950 file:text-stone-700 dark:file:text-stone-300 hover:file:bg-stone-200 cursor-pointer"
                        />
                        <div wire:loading wire:target="formPhoto" class="text-amber-500 text-[11px] font-semibold">
                            Mengunggah dan mengompresi foto...
                        </div>
                    </div>

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
                            {{ $editingId ? 'Simpan Perubahan' : 'Simpan Pengurus' }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

</div>
