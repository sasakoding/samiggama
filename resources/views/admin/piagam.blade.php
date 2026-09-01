<?php

use App\Models\CertificateTemplate;
use App\Models\IssuedCertificate;
use App\Services\ImageUploadService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $searchQuery = '';
    public string $activeCategoryTab = 'all'; // 'all', 'umum', 'alm'
    public bool $uploadModalOpen = false;
    public bool $previewModalOpen = false;
    public ?CertificateTemplate $selectedTemplate = null;
    public ?string $feedbackMessage = null;

    // Form inputs (Create / Edit)
    public bool $isEditing = false;
    public ?int $editingTemplateId = null;
    public string $formName = '';
    public string $formCategory = 'umum'; // 'umum' (Piagam Donatur / Anumodana), 'alm' (Piagam Pelimpahan Jasa / Pattidana)
    public string $formOrientation = 'landscape';
    public string $formStatus = 'aktif';
    public $formBackground = null;
    public ?string $existingBackgroundImage = null;

    public function setCategoryTab(string $tab): void
    {
        $this->activeCategoryTab = $tab;
    }

    public function openUploadModal(): void
    {
        $this->reset(['formName', 'formBackground', 'editingTemplateId', 'existingBackgroundImage']);
        $this->isEditing = false;
        $this->formCategory = ($this->activeCategoryTab !== 'all') ? $this->activeCategoryTab : 'umum';
        $this->formOrientation = 'landscape';
        $this->formStatus = 'aktif';
        $this->uploadModalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $tpl = CertificateTemplate::find($id);
        if (!$tpl) {
            return;
        }

        $this->isEditing = true;
        $this->editingTemplateId = $tpl->id;
        $this->formName = $tpl->name;
        $this->formCategory = $tpl->category ?? 'umum';
        $this->formOrientation = $tpl->orientation ?? 'landscape';
        $this->formStatus = $tpl->status ?? 'aktif';
        $this->existingBackgroundImage = $tpl->background_image;
        $this->formBackground = null;
        $this->uploadModalOpen = true;
    }

    public function openPreview(int $id): void
    {
        $this->selectedTemplate = CertificateTemplate::with('issuedCertificates')->find($id);
        if ($this->selectedTemplate) {
            $this->previewModalOpen = true;
        }
    }

    public function saveTemplate(): void
    {
        $this->validate([
            'formName' => 'required|string|max:255',
            'formCategory' => 'required|in:umum,alm',
            'formOrientation' => 'required|in:landscape,portrait',
            'formBackground' => 'nullable|image|max:10240',
        ], [
            'formName.required' => 'Nama template piagam wajib diisi.',
            'formCategory.required' => 'Pilihan jenis piagam wajib ditentukan.',
            'formBackground.image' => 'Berkas latar belakang harus berupa gambar (JPG, PNG, WebP).',
            'formBackground.max' => 'Ukuran berkas latar belakang maksimal 10MB.',
        ]);

        if ($this->isEditing && $this->editingTemplateId) {
            $tpl = CertificateTemplate::findOrFail($this->editingTemplateId);

            $bgPath = $tpl->background_image;
            if ($this->formBackground) {
                ImageUploadService::deleteOldImage($tpl->background_image);
                $bgPath = ImageUploadService::uploadOriginal($this->formBackground, 'uploads/certificates');
            }

            $slug = Str::slug($this->formName);
            if (CertificateTemplate::where('slug', $slug)->where('id', '!=', $tpl->id)->exists()) {
                $slug .= '-' . time();
            }

            $tpl->update([
                'name' => $this->formName,
                'category' => $this->formCategory,
                'slug' => $slug,
                'orientation' => $this->formOrientation,
                'background_image' => $bgPath,
                'status' => $this->formStatus,
            ]);

            $this->uploadModalOpen = false;
            $categoryLabel = $this->formCategory === 'alm' ? 'Pelimpahan Jasa (Alm.)' : 'Piagam Donatur (Umum)';
            $this->feedbackMessage = "Template piagam {$categoryLabel} '{$this->formName}' berhasil diperbarui.";
        } else {
            $bgPath = 'images/piagam-maha-anumodana.png';
            if ($this->formBackground) {
                $bgPath = ImageUploadService::uploadOriginal($this->formBackground, 'uploads/certificates');
            }

            $slug = Str::slug($this->formName);
            if (CertificateTemplate::where('slug', $slug)->exists()) {
                $slug .= '-' . time();
            }

            CertificateTemplate::create([
                'name' => $this->formName,
                'category' => $this->formCategory,
                'slug' => $slug,
                'orientation' => $this->formOrientation,
                'background_image' => $bgPath,
                'min_amount' => 0,
                'status' => $this->formStatus,
            ]);

            $this->uploadModalOpen = false;
            $categoryLabel = $this->formCategory === 'alm' ? 'Pelimpahan Jasa (Alm.)' : 'Piagam Donatur (Umum)';
            $this->feedbackMessage = "Template piagam {$categoryLabel} '{$this->formName}' berhasil disimpan.";
        }
    }

    public function toggleStatus(int $id): void
    {
        $tpl = CertificateTemplate::find($id);
        if ($tpl) {
            $tpl->status = ($tpl->status === 'aktif') ? 'nonaktif' : 'aktif';
            $tpl->save();
            $this->feedbackMessage = "Status template piagam '{$tpl->name}' diubah menjadi {$tpl->status}.";
        }
    }

    public function deleteTemplate(int $id): void
    {
        $tpl = CertificateTemplate::find($id);
        if ($tpl) {
            $name = $tpl->name;
            ImageUploadService::deleteOldImage($tpl->background_image);
            $tpl->issuedCertificates()->delete();
            $tpl->delete();
            $this->feedbackMessage = "Template piagam '{$name}' berhasil dihapus.";
        }
    }

    public function render()
    {
        $query = CertificateTemplate::withCount('issuedCertificates')->latest();

        if ($this->activeCategoryTab !== 'all') {
            $query->where('category', $this->activeCategoryTab);
        }

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->whereRaw('LOWER(name) LIKE ?', [$search]);
        }

        $templates = $query->get();
        $totalAll = CertificateTemplate::count();
        $totalUmum = CertificateTemplate::where('category', 'umum')->count();
        $totalAlm = CertificateTemplate::where('category', 'alm')->count();
        $totalIssued = IssuedCertificate::count();

        return $this->view([
            'templates' => $templates,
            'totalAll' => $totalAll,
            'totalUmum' => $totalUmum,
            'totalAlm' => $totalAlm,
            'totalIssued' => $totalIssued,
        ])->title('Desain & Template Piagam Anumodana')->layout('layouts::admin');
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
                    Desain Piagam Anumodana & Pattidāna
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Kelola template sertifikat penghargaan dāna untuk donatur umum dan pelimpahan jasa bagi mendiang (Alm./Almh.).
            </p>
        </div>

        <!-- Action CTA -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button 
                wire:click="openUploadModal"
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Desain Baru</span>
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
         2. CATEGORY TABS & SEARCH CONTROLS
         ========================================================================= -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-2 rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs">
        
        <!-- Category Tab Pills -->
        <div class="flex flex-wrap items-center gap-1.5">
            <button 
                wire:click="setCategoryTab('all')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $activeCategoryTab === 'all' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'bg-stone-100 dark:bg-emerald-950/60 text-stone-600 dark:text-stone-300 hover:bg-stone-200' }}"
            >
                Semua Desain ({{ $totalAll }})
            </button>

            <button 
                wire:click="setCategoryTab('umum')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 {{ $activeCategoryTab === 'umum' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-100 border border-emerald-500/20' }}"
            >
                <svg class="w-3.5 h-3.5 {{ $activeCategoryTab === 'umum' ? 'text-amber-300' : 'text-emerald-600 dark:text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                <span>Piagam Donatur / Umum</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeCategoryTab === 'umum' ? 'bg-emerald-800 text-emerald-100' : 'bg-emerald-200 dark:bg-emerald-900 text-emerald-900 dark:text-emerald-200' }}">{{ $totalUmum }}</span>
            </button>

            <button 
                wire:click="setCategoryTab('alm')" 
                type="button" 
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 {{ $activeCategoryTab === 'alm' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 hover:bg-amber-100 border border-amber-500/20' }}"
            >
                <svg class="w-3.5 h-3.5 {{ $activeCategoryTab === 'alm' ? 'text-amber-200' : 'text-amber-600 dark:text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                <span>Pelimpahan Jasa (Alm.)</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $activeCategoryTab === 'alm' ? 'bg-amber-800 text-amber-100' : 'bg-amber-200 dark:bg-amber-900 text-amber-900 dark:text-amber-200' }}">{{ $totalAlm }}</span>
            </button>
        </div>

        <!-- Search Input -->
        <div class="relative w-full sm:w-64">
            <input 
                wire:model.live.debounce.300ms="searchQuery" 
                type="text" 
                placeholder="Cari nama template..." 
                class="w-full pl-9 pr-4 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-500/20 text-xs text-stone-800 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-amber-500"
            />
            <svg class="w-4 h-4 text-stone-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- =========================================================================
         3. CARD GRID TEMPLATE PIAGAM
         ========================================================================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($templates as $tpl)
            @php
                $isAlm = ($tpl->category === 'alm');
            @endphp
            <div class="rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden flex flex-col justify-between group hover:border-[#0D5B3A]/40 transition-all">
                
                <!-- Preview Visual Artboard -->
                <div 
                    wire:click="openPreview({{ $tpl->id }})" 
                    class="relative aspect-[16/11] p-4 bg-gradient-to-br from-stone-900 via-stone-950 to-stone-900 cursor-pointer overflow-hidden flex flex-col justify-between border-b border-stone-100 dark:border-emerald-950"
                >
                    <div class="absolute inset-0 opacity-20 bg-cover bg-center" style="background-image: url('{{ asset($tpl->background_image) }}')"></div>
                    
                    <!-- Top Badges -->
                    <div class="relative z-10 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1">
                            <span class="px-2 py-0.5 rounded-md bg-black/60 backdrop-blur-xs text-stone-300 border border-white/10 text-[9.5px] font-bold uppercase">
                                {{ $tpl->orientation }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[9.5px] font-bold {{ $tpl->status === 'aktif' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-stone-500/20 text-stone-300' }}">
                                {{ ucfirst($tpl->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Inner Artboard Typography Simulation -->
                    <div class="relative z-10 text-center space-y-1 my-auto px-2">
                        <div class="text-[8.5px] uppercase tracking-widest text-amber-400/90 font-bold">Vihara Sāmaggi Gāma</div>
                        <div class="text-sm sm:text-base font-black text-white tracking-wide line-clamp-1 drop-shadow-md">{{ $tpl->name }}</div>
                        @if ($isAlm)
                            <div class="text-[9.5px] text-amber-200/80 font-medium">Khusus Persembahan Pattidāna Bagi Alm./Almh.</div>
                        @else
                            <div class="text-[9.5px] text-emerald-200/80 font-medium">Piagam Anumodana Dānapati Resmi</div>
                        @endif
                    </div>

                    <div class="relative z-10 flex justify-between text-[10px] text-stone-400 border-t border-white/10 pt-2">
                        <span>{{ $tpl->issued_certificates_count }} Piagam Terbit</span>
                        <span class="text-amber-300 font-semibold group-hover:underline">Klik untuk Pratinjau HD</span>
                    </div>
                </div>

                <!-- Card Body & Action Buttons -->
                <div class="p-4 sm:p-5 flex flex-col gap-3">
                    <div class="space-y-0.5">
                        <h3 class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm">
                            {{ $tpl->name }}
                        </h3>
                        <div class="flex items-center gap-1.5 text-[11px]">
                            @if ($isAlm)
                                <span class="inline-flex items-center gap-1 text-amber-700 dark:text-amber-400 font-semibold">
                                    <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                                    <span>Untuk Alm. / Mendiang</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-emerald-700 dark:text-emerald-400 font-semibold">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                    <span>Untuk Donatur / Umum</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Actions (Pratinjau, Edit, Toggle, Hapus) -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <!-- 2. Edit Template -->
                        <button 
                            wire:click="openEditModal({{ $tpl->id }})" 
                            type="button" 
                            class="w-8 h-8 rounded-xl flex items-center justify-center bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 text-amber-700 dark:text-amber-300 border border-amber-500/20 cursor-pointer shadow-2xs"
                            title="Edit Template Desain"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>

                        <!-- 3. Toggle Status -->
                        <button 
                            wire:click="toggleStatus({{ $tpl->id }})" 
                            type="button" 
                            class="w-8 h-8 rounded-xl flex items-center justify-center border shadow-2xs cursor-pointer {{ $tpl->status === 'aktif' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : 'bg-stone-100 dark:bg-stone-800 text-stone-500 border-stone-300 dark:border-stone-700' }}"
                            title="Ubah Status Aktif/Nonaktif"
                        >
                            @if ($tpl->status === 'aktif')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @endif
                        </button>

                        <!-- 4. Hapus Template -->
                        <button 
                            wire:click="deleteTemplate({{ $tpl->id }})" 
                            wire:confirm="Apakah Anda yakin ingin menghapus template piagam ini?" 
                            type="button" 
                            class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 border border-rose-500/20 cursor-pointer shadow-2xs"
                            title="Hapus Template"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full py-16 text-center space-y-2 bg-white dark:bg-[#0b1f17] rounded-3xl border border-stone-200/90 dark:border-white/[0.08]">
                <div class="w-12 h-12 rounded-full bg-stone-100 dark:bg-emerald-950/60 flex items-center justify-center mx-auto text-stone-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="font-bold text-stone-700 dark:text-stone-300 text-sm">Belum ada template piagam</div>
                <p class="text-xs text-stone-400">Klik tombol "+ Upload Desain Baru" untuk menambahkan template.</p>
            </div>
        @endforelse
    </div>

    <!-- =========================================================================
         4. MODAL UPLOAD / EDIT TEMPLATE (TELEPORTED)
         ========================================================================= -->
    <template x-teleport="body">
        <div 
            x-show="$wire.uploadModalOpen" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            @keydown.escape.window="$wire.uploadModalOpen = false"
        >
            <div 
                @click.away="$wire.uploadModalOpen = false" 
                class="relative max-w-xl w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
            >
                <!-- Header -->
                <div class="p-6 bg-gradient-to-r from-[#0B2117] to-[#0F3325] text-white flex items-center justify-between border-b border-emerald-900">
                    <div class="space-y-0.5">
                        <h3 class="text-lg font-extrabold text-[#FAF5ED]">
                            {{ $isEditing ? 'Edit Desain Piagam' : 'Tambah Desain Piagam Baru' }}
                        </h3>
                        <p class="text-xs text-stone-300">
                            {{ $isEditing ? 'Perbarui informasi atau ganti berkas latar belakang template.' : 'Pilih peruntukan piagam dan unggah file latar belakang beresolusi tinggi.' }}
                        </p>
                    </div>
                    <button 
                        @click="$wire.uploadModalOpen = false" 
                        type="button" 
                        class="p-2 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="saveTemplate" class="p-6 space-y-4 overflow-y-auto text-xs">
                    
                    <!-- PILIHAN OPSI KATEGORI / PERUNTUKAN PIAGAM -->
                    <div class="space-y-2">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Peruntukan / Jenis Piagam <span class="text-amber-500">*</span>
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Option 1: Piagam Donatur (Umum) -->
                            <label 
                                class="relative p-3.5 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between space-y-2 {{ $formCategory === 'umum' ? 'bg-emerald-50/70 dark:bg-emerald-950/50 border-[#0D5B3A] dark:border-emerald-400 shadow-xs' : 'bg-stone-50 dark:bg-[#071710] border-stone-200 dark:border-emerald-500/20 hover:border-stone-300' }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg {{ $formCategory === 'umum' ? 'bg-emerald-600 text-white' : 'bg-stone-200 dark:bg-emerald-950 text-stone-600 dark:text-stone-300' }} flex items-center justify-center font-bold text-xs">
                                            <svg class="w-4 h-4 {{ $formCategory === 'umum' ? 'text-amber-300' : 'text-stone-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-stone-900 dark:text-stone-100 text-xs">Piagam Donatur</div>
                                            <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-semibold">Umum / Hidup</div>
                                        </div>
                                    </div>
                                    <input 
                                        type="radio" 
                                        wire:model.live="formCategory" 
                                        value="umum" 
                                        class="mt-1 text-[#0D5B3A] focus:ring-emerald-500"
                                    />
                                </div>
                                <p class="text-[10.5px] text-stone-500 dark:text-stone-400 leading-snug">
                                    Untuk donatur perseorangan, keluarga, atau umum yang berdana kebajikan.
                                </p>
                            </label>

                            <!-- Option 2: Pelimpahan Jasa (Alm.) -->
                            <label 
                                class="relative p-3.5 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between space-y-2 {{ $formCategory === 'alm' ? 'bg-amber-50/70 dark:bg-amber-950/50 border-amber-600 dark:border-amber-400 shadow-xs' : 'bg-stone-50 dark:bg-[#071710] border-stone-200 dark:border-emerald-500/20 hover:border-stone-300' }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg {{ $formCategory === 'alm' ? 'bg-amber-600 text-white' : 'bg-stone-200 dark:bg-emerald-950 text-stone-600 dark:text-stone-300' }} flex items-center justify-center font-bold text-xs">
                                            <svg class="w-4 h-4 {{ $formCategory === 'alm' ? 'text-amber-200' : 'text-stone-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-stone-900 dark:text-stone-100 text-xs">Pelimpahan Jasa</div>
                                            <div class="text-[10px] text-amber-700 dark:text-amber-400 font-semibold">Pattidāna / Alm.</div>
                                        </div>
                                    </div>
                                    <input 
                                        type="radio" 
                                        wire:model.live="formCategory" 
                                        value="alm" 
                                        class="mt-1 text-amber-600 focus:ring-amber-500"
                                    />
                                </div>
                                <p class="text-[10.5px] text-stone-500 dark:text-stone-400 leading-snug">
                                    Khusus dedikasi pelimpahan jasa bagi mendiang/leluhur tercinta (Alm./Almh.).
                                </p>
                            </label>
                        </div>
                    </div>

                    <!-- Nama Template -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nama Template Piagam <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formName" 
                            type="text" 
                            required 
                            placeholder="{{ $formCategory === 'alm' ? 'cth: Piagam Pattidāna Pelimpahan Jasa Alm.' : 'cth: Piagam Maha Anumodana Dānapati' }}"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>

                    <!-- Format Orientasi & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Format Orientasi
                            </label>
                            <select 
                                wire:model="formOrientation" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            >
                                <option value="landscape">Landscape (Mendatar)</option>
                                <option value="portrait">Portrait (Tegak)</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Status Template
                            </label>
                            <select 
                                wire:model="formStatus" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            >
                                <option value="aktif">Aktif (Dapat Diterbitkan)</option>
                                <option value="nonaktif">Nonaktif (Diarsipkan)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Background File Upload -->
                    <div class="space-y-2">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            File Gambar Background {{ $isEditing ? '(Opsional - Ganti Jika Ingin)' : '(Resolusi Tinggi / HD)' }}
                        </label>

                        @if ($isEditing && $existingBackgroundImage)
                            <div class="p-3 rounded-2xl bg-stone-100 dark:bg-[#071710] border border-stone-200 dark:border-emerald-500/20 flex items-center gap-3">
                                <img src="{{ asset($existingBackgroundImage) }}" alt="Preview Background" class="w-16 h-12 object-cover rounded-lg border border-amber-500/30 shadow-xs" />
                                <div class="text-[11px] text-stone-600 dark:text-stone-300 space-y-0.5">
                                    <div class="font-bold text-[#0D6E42] dark:text-emerald-300">Berkas Background Saat Ini</div>
                                    <div class="text-[10px] text-stone-400">Pilih berkas baru di bawah ini hanya jika ingin mengganti gambar latar belakang.</div>
                                </div>
                            </div>
                        @endif

                        <input 
                            wire:model="formBackground" 
                            type="file" 
                            accept="image/*"
                            class="w-full text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 dark:file:bg-emerald-950 file:text-stone-700 dark:file:text-stone-300 hover:file:bg-stone-200 cursor-pointer"
                        />
                        <div class="text-[11px] text-stone-500 dark:text-stone-400">
                            Format JPG / PNG / WebP resolusi tinggi. Berkas disimpan dalam kualitas asli tanpa penurunan resolusi.
                        </div>
                        <div wire:loading wire:target="formBackground" class="text-amber-500 text-[11px] font-semibold">
                            Mengunggah berkas template HD...
                        </div>
                    </div>

                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex items-center justify-end gap-2.5">
                        <button 
                            @click="$wire.uploadModalOpen = false" 
                            type="button" 
                            class="py-2.5 px-4 rounded-xl bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-700 dark:text-stone-300 font-bold text-xs transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer"
                        >
                            {{ $isEditing ? 'Simpan Perubahan' : 'Simpan Template' }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

    <!-- =========================================================================
         5. MODAL PREVIEW HD (TELEPORTED)
         ========================================================================= -->
    @if ($selectedTemplate)
        <template x-teleport="body">
            <div 
                x-show="$wire.previewModalOpen" 
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/85 backdrop-blur-xl flex items-center justify-center p-4 sm:p-8 overflow-y-auto"
                @keydown.escape.window="$wire.previewModalOpen = false"
            >
                <div 
                    @click.away="$wire.previewModalOpen = false" 
                    class="relative max-w-4xl w-full bg-white dark:bg-[#081710] rounded-3xl overflow-hidden border border-emerald-500/30 shadow-2xl flex flex-col my-auto"
                >
                    <div class="p-4 bg-[#0B2117] text-white flex items-center justify-between border-b border-emerald-900/60">
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-sm text-[#FAF5ED]">{{ $selectedTemplate->name }}</span>
                            @if ($selectedTemplate->category === 'alm')
                                <span class="px-2 py-0.5 rounded-full bg-amber-500/30 text-amber-300 border border-amber-500/40 text-[10px] font-bold">Pelimpahan Jasa (Alm.)</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 text-[10px] font-bold">Piagam Donatur (Umum)</span>
                            @endif
                        </div>
                        <button 
                            @click="$wire.previewModalOpen = false" 
                            type="button" 
                            class="p-1.5 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="aspect-[1024/734] bg-stone-950 flex items-center justify-center p-4 sm:p-8 overflow-hidden relative">
                        <img src="{{ asset($selectedTemplate->background_image) }}" alt="{{ $selectedTemplate->name }}" class="w-full h-full object-contain rounded-xl shadow-2xl border border-amber-500/40" />
                        
                        <!-- Sample Dynamic Overlay for Admin Preview -->
                        <div class="absolute left-1/2 w-[70%] text-center pointer-events-none" style="top: 38%; transform: translate(-50%, -50%);">
                            <span class="font-serif font-black text-[#0f172a] tracking-wide uppercase drop-shadow-xs block text-xs sm:text-base md:text-xl">
                                @if ($selectedTemplate->category === 'alm')
                                    [ALM. NAMA MENDIANG / LELUHUR TERCINTA]
                                @else
                                    [NAMA LENGKAP DONATUR / KELUARGA]
                                @endif
                            </span>
                        </div>
                        <div class="absolute left-1/2 w-[70%] text-center pointer-events-none" style="top: 59%; transform: translate(-50%, -50%);">
                            <span class="font-black text-[#0284c7] tracking-tight block text-xs sm:text-lg md:text-2xl">
                                Rp 10.000.000
                            </span>
                            @if ($selectedTemplate->category === 'alm')
                                <span class="text-[10px] sm:text-xs text-stone-600 font-serif italic block mt-0.5">
                                    Disalurkan oleh: Keluarga Besar Tanujaya
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-4 bg-white dark:bg-[#0b1f17] border-t border-stone-200 dark:border-emerald-950 flex items-center justify-between">
                        <span class="text-xs text-stone-500 dark:text-stone-400">
                            Jenis: <strong>{{ $selectedTemplate->category_label }}</strong> • Format: {{ ucfirst($selectedTemplate->orientation) }} • Resolusi Asli Lossless HD
                        </span>
                        <button 
                            @click="$wire.previewModalOpen = false" 
                            type="button" 
                            class="py-2 px-4 rounded-xl bg-stone-100 dark:bg-emerald-950 hover:bg-stone-200 text-stone-700 dark:text-stone-300 font-bold text-xs cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif

</div>
