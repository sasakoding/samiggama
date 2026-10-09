<?php

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Services\ImageUploadService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $searchQuery = '';
    public bool $modalOpen = false;
    public bool $previewModalOpen = false;
    public ?GalleryAlbum $selectedAlbum = null;
    public ?int $editingId = null;
    public ?string $feedbackMessage = null;

    // Form inputs
    public string $formTitle = '';
    public string $formCategory = 'Puja Bakti';
    public string $formEventDate = '';
    public string $formLocation = 'Dhammasala Utama';
    public string $formDescription = '';
    public $formCoverImage = null;
    public ?string $existingCoverImage = null;
    public array $formPhotos = [];
    public ?GalleryAlbum $editingAlbum = null;

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset([
            'editingId',
            'editingAlbum',
            'formTitle',
            'formDescription',
            'formCoverImage',
            'existingCoverImage',
            'formPhotos',
        ]);
        $this->formCategory = 'Puja Bakti';
        $this->formEventDate = date('Y-m-d');
        $this->formLocation = 'Dhammasala Utama';
        $this->modalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $album = GalleryAlbum::with('photos')->find($id);
        if (!$album) {
            return;
        }

        $this->editingId = $album->id;
        $this->editingAlbum = $album;
        $this->formTitle = $album->title;
        $this->formCategory = $album->category;
        $this->formEventDate = $album->event_date ? $album->event_date->format('Y-m-d') : '';
        $this->formLocation = $album->location ?? '';
        $this->formDescription = $album->description ?? '';
        $this->existingCoverImage = $album->cover_image;
        $this->formCoverImage = null;
        $this->formPhotos = [];

        $this->modalOpen = true;
    }

    public function openPreview(int $id): void
    {
        $this->selectedAlbum = GalleryAlbum::with('photos')->find($id);
        if ($this->selectedAlbum) {
            $this->previewModalOpen = true;
        }
    }

    public function saveAlbum(): void
    {
        $this->validate([
            'formTitle' => 'required|string|max:255',
            'formCategory' => 'required|string',
            'formCoverImage' => 'nullable|image|max:10240',
            'formPhotos' => 'nullable|array',
            'formPhotos.*' => 'image|max:10240',
        ], [
            'formTitle.required' => 'Nama kegiatan / judul album wajib diisi.',
            'formCoverImage.image' => 'File foto sampul harus berupa gambar (JPG, PNG, WebP).',
            'formCoverImage.max' => 'Ukuran foto sampul maksimal 10MB.',
            'formPhotos.*.image' => 'Semua file foto tambahan harus berupa gambar valid.',
            'formPhotos.*.max' => 'Ukuran foto tambahan maksimal 10MB per file.',
        ]);

        $coverImagePath = $this->existingCoverImage;
        if ($this->formCoverImage) {
            $newCover = ImageUploadService::uploadAndCompress($this->formCoverImage, 'uploads/gallery');
            if ($newCover) {
                if ($this->existingCoverImage && $this->existingCoverImage !== $newCover) {
                    ImageUploadService::deleteOldImage($this->existingCoverImage);
                }
                $coverImagePath = $newCover;
            }
        }

        $slug = Str::slug($this->formTitle) ?: 'album-' . time();

        if ($this->editingId) {
            $album = GalleryAlbum::find($this->editingId);
            if ($album) {
                if (GalleryAlbum::where('slug', $slug)->where('id', '!=', $album->id)->exists()) {
                    $slug .= '-' . time();
                }

                $album->update([
                    'title' => $this->formTitle,
                    'slug' => $slug,
                    'category' => $this->formCategory,
                    'event_date' => $this->formEventDate ?: null,
                    'location' => $this->formLocation ?: null,
                    'description' => $this->formDescription ?: null,
                    'cover_image' => $coverImagePath,
                ]);

                // Save additional photos if uploaded
                if (!empty($this->formPhotos)) {
                    foreach ($this->formPhotos as $photoFile) {
                        $photoPath = ImageUploadService::uploadAndCompress($photoFile, 'uploads/gallery');
                        if ($photoPath) {
                            GalleryPhoto::create([
                                'gallery_album_id' => $album->id,
                                'file_path' => $photoPath,
                                'image_path' => $photoPath,
                            ]);
                        }
                    }
                }

                $this->feedbackMessage = "Album dokumentasi '{$this->formTitle}' berhasil diperbarui.";
            }
        } else {
            if (GalleryAlbum::where('slug', $slug)->exists()) {
                $slug .= '-' . time();
            }

            $album = GalleryAlbum::create([
                'title' => $this->formTitle,
                'slug' => $slug,
                'category' => $this->formCategory,
                'event_date' => $this->formEventDate ?: null,
                'location' => $this->formLocation ?: null,
                'description' => $this->formDescription ?: null,
                'cover_image' => $coverImagePath ?: 'images/gallery-altar.jpg',
            ]);

            // Save additional photos
            if (!empty($this->formPhotos)) {
                foreach ($this->formPhotos as $photoFile) {
                    $photoPath = ImageUploadService::uploadAndCompress($photoFile, 'uploads/gallery');
                    if ($photoPath) {
                        GalleryPhoto::create([
                            'gallery_album_id' => $album->id,
                            'file_path' => $photoPath,
                            'image_path' => $photoPath,
                        ]);
                    }
                }
            }

            $this->feedbackMessage = "Album kegiatan baru '{$this->formTitle}' berhasil dibuat.";
        }

        ImageUploadService::cleanLivewireTmp();
        $this->reset(['formCoverImage', 'formPhotos', 'editingId', 'editingAlbum', 'existingCoverImage']);
        $this->modalOpen = false;
    }

    public function deleteAlbum(int $id): void
    {
        $album = GalleryAlbum::with('photos')->find($id);
        if ($album) {
            $title = $album->title;
            if ($album->cover_image) {
                ImageUploadService::deleteOldImage($album->cover_image);
            }
            if ($album->photos) {
                foreach ($album->photos as $p) {
                    $path = $p->file_path ?: $p->image_path;
                    if ($path) {
                        ImageUploadService::deleteOldImage($path);
                    }
                    $p->delete();
                }
            }
            $album->delete();
            $this->feedbackMessage = "Album '{$title}' berhasil dihapus.";
            if ($this->selectedAlbum && $this->selectedAlbum->id === $id) {
                $this->previewModalOpen = false;
                $this->selectedAlbum = null;
            }
        }
    }

    public function deletePhoto(int $id): void
    {
        $photo = GalleryPhoto::find($id);
        if ($photo) {
            $path = $photo->file_path ?: $photo->image_path;
            if ($path) {
                ImageUploadService::deleteOldImage($path);
            }
            $photo->delete();
            if ($this->selectedAlbum) {
                $this->selectedAlbum->refresh();
            }
            if ($this->editingAlbum) {
                $this->editingAlbum->refresh();
            }
            $this->feedbackMessage = "Foto berhasil dihapus dari album.";
        }
    }

    public function render()
    {
        $query = GalleryAlbum::withCount('photos')->latest();

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(location) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(category) LIKE ?', [$search]);
            });
        }

        $albums = $query->get();

        return $this->view([
            'albums' => $albums,
        ])->title('Galeri Foto & Dokumentasi')->layout('layouts::admin');
    }
};
?>

<div class="space-y-6 font-sans">

    <!-- =========================================================================
         1. TOP HEADER & ACTION CTA
         ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Galeri Foto & Dokumentasi
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Kelola arsip visual kebaktian, pembangunan vihara, dan kegiatan sosial kemasyarakatan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button 
                wire:click="openCreateModal"
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>+ Album Foto Baru</span>
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
         2. CARD GRID ALBUM DOKUMENTASI
         ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($albums as $alb)
            <div class="rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden flex flex-col justify-between group hover:border-[#0D5B3A]/40 transition-all">
                
                <!-- Album Cover -->
                <div 
                    wire:click="openPreview({{ $alb->id }})" 
                    class="relative aspect-[16/10] overflow-hidden cursor-pointer bg-stone-900"
                >
                    <img 
                        src="{{ asset($alb->cover_image ?: 'images/gallery-altar.jpg') }}" 
                        alt="{{ $alb->title }}" 
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                    
                    <div class="absolute top-3 left-3">
                        <span class="px-2.5 py-1 rounded-md bg-black/60 backdrop-blur-xs text-amber-300 border border-amber-400/30 text-[10px] font-extrabold uppercase">
                            {{ $alb->category }}
                        </span>
                    </div>

                    <div class="absolute bottom-3 left-3 right-3 text-white">
                        <span class="text-[10px] text-stone-300 block font-medium">
                            {{ $alb->event_date?->translatedFormat('d M Y') }} • {{ $alb->location }}
                        </span>
                        <h3 class="font-extrabold text-sm line-clamp-1 text-[#FAF5ED]">
                            {{ $alb->title }}
                        </h3>
                    </div>
                </div>

                <!-- Footer / Action Buttons -->
                <div class="p-4 sm:p-5 flex items-center justify-between gap-3 border-t border-stone-100 dark:border-emerald-950">
                    <span class="text-xs text-stone-500 dark:text-stone-400">
                        {{ $alb->photos_count }} Foto Arsip
                    </span>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <!-- Preview -->
                        <button 
                            wire:click="openPreview({{ $alb->id }})" 
                            type="button" 
                            class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-emerald-500/20 cursor-pointer shadow-2xs"
                            title="Lihat Foto"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>

                        <!-- Edit -->
                        <button 
                            wire:click="openEditModal({{ $alb->id }})" 
                            type="button" 
                            class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-emerald-500/20 cursor-pointer shadow-2xs"
                            title="Edit Album"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>

                        <!-- Delete -->
                        <button 
                            wire:click="deleteAlbum({{ $alb->id }})" 
                            wire:confirm="Apakah Anda yakin ingin menghapus album ini beserta seluruh fotonya?" 
                            type="button" 
                            class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 border border-rose-500/20 cursor-pointer shadow-2xs"
                            title="Hapus Album"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full py-12 text-center text-stone-400 text-xs">
                Belum ada album foto yang terdaftar.
            </div>
        @endforelse
    </div>

    <!-- =========================================================================
         3. MODAL TAMBAH / EDIT ALBUM (TELEPORTED)
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
                            {{ $editingId ? 'Edit Album Foto' : 'Buat Album Foto Baru' }}
                        </h3>
                        <p class="text-xs text-stone-300">Unggah foto kegiatan dengan kompresi otomatis.</p>
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
                <form wire:submit.prevent="saveAlbum" class="p-6 space-y-4 overflow-y-auto text-xs">
                    
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nama Kegiatan / Judul Album <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formTitle" 
                            type="text" 
                            required 
                            placeholder="cth: Pindapata Akbar 12 Bhikkhu Sangha"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10"
                        />
                        @error('formTitle')
                            <p class="text-[11px] text-rose-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Kategori
                            </label>
                            <select 
                                wire:model="formCategory" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            >
                                <option value="Puja Bakti">Puja Bakti</option>
                                <option value="Sarana Vihara">Sarana & Dhammasala</option>
                                <option value="Bakti Sosial">Bakti Sosial</option>
                                <option value="Sekolah Minggu">Sekolah Minggu</option>
                                <option value="Rapat">Rapat</option>
                                <option value="Kunjungan">Kunjungan</option>
                                <option value="Hari Raya">Hari Raya</option>
                                <option value="Kegiatan Lainnya">Kegiatan Lainnya</option>
                            </select>
                            @error('formCategory')
                                <p class="text-[11px] text-rose-500 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Tanggal Kegiatan
                            </label>
                            <input 
                                wire:model="formEventDate" 
                                type="date" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                            @error('formEventDate')
                                <p class="text-[11px] text-rose-500 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Lokasi
                        </label>
                        <input 
                            wire:model="formLocation" 
                            type="text" 
                            placeholder="cth: Pelataran Utama Vihara Sāmaggi Gāma"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        />
                        @error('formLocation')
                            <p class="text-[11px] text-rose-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Foto Sampul Utama -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Foto Sampul Utama (Kompresi Otomatis)
                        </label>
                        <input 
                            wire:model="formCoverImage" 
                            type="file" 
                            accept="image/*"
                            class="w-full text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 dark:file:bg-emerald-950 file:text-stone-700 dark:file:text-stone-300 hover:file:bg-stone-200 cursor-pointer"
                        />
                        @error('formCoverImage')
                            <p class="text-[11px] text-rose-500 font-semibold">{{ $message }}</p>
                        @enderror

                        @if ($formCoverImage)
                            <div class="mt-2 flex items-center gap-3 p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-500/30">
                                <span class="text-xs text-emerald-700 dark:text-emerald-300 font-bold">Foto sampul baru siap diunggah</span>
                            </div>
                        @elseif ($existingCoverImage)
                            <div class="mt-2 flex items-center gap-3 p-2.5 rounded-xl bg-stone-50 dark:bg-emerald-950/20 border border-stone-200 dark:border-emerald-500/20">
                                <img src="{{ asset($existingCoverImage) }}" class="w-14 h-10 object-cover rounded-lg border border-stone-300 dark:border-emerald-800" />
                                <span class="text-[11px] text-stone-500 dark:text-stone-400">Sampul saat ini</span>
                            </div>
                        @endif
                    </div>

                    <!-- Foto Tambahan -->
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Foto Tambahan (Multi-Upload)
                        </label>
                        <input 
                            wire:model="formPhotos" 
                            type="file" 
                            multiple
                            accept="image/*"
                            class="w-full text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 dark:file:bg-emerald-950 file:text-stone-700 dark:file:text-stone-300 hover:file:bg-stone-200 cursor-pointer"
                        />
                        @error('formPhotos')
                            <p class="text-[11px] text-rose-500 font-semibold">{{ $message }}</p>
                        @enderror
                        @error('formPhotos.*')
                            <p class="text-[11px] text-rose-500 font-semibold">{{ $message }}</p>
                        @enderror

                        @if (!empty($formPhotos))
                            <div class="mt-1.5 p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-500/30 text-[11px] text-emerald-700 dark:text-emerald-300 font-semibold">
                                ✓ {{ count($formPhotos) }} foto tambahan baru dipilih siap diunggah & dikompresi.
                            </div>
                        @endif
                    </div>

                    <!-- Foto Tersimpan di Album Ini (Edit Mode) -->
                    @if ($editingId && $editingAlbum && $editingAlbum->photos->isNotEmpty())
                        <div class="space-y-1.5 pt-2 border-t border-stone-200 dark:border-emerald-950">
                            <div class="flex items-center justify-between">
                                <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                    Foto Arsip Album Ini ({{ $editingAlbum->photos->count() }})
                                </label>
                                <span class="text-[10px] text-stone-400">Arahkan kursor & klik sampah untuk hapus</span>
                            </div>
                            <div class="grid grid-cols-4 sm:grid-cols-5 gap-2 max-h-36 overflow-y-auto p-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950">
                                @foreach ($editingAlbum->photos as $ep)
                                    <div class="relative group aspect-square rounded-lg overflow-hidden border border-stone-200 dark:border-emerald-900/60 shadow-2xs">
                                        <img src="{{ asset($ep->image_path) }}" class="w-full h-full object-cover" />
                                        <button 
                                            wire:click="deletePhoto({{ $ep->id }})" 
                                            wire:confirm="Hapus foto ini dari album?"
                                            type="button" 
                                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-rose-400 hover:text-rose-300 cursor-pointer"
                                            title="Hapus foto ini"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Deskripsi Dokumentasi
                        </label>
                        <textarea 
                            wire:model="formDescription" 
                            rows="2"
                            placeholder="Catatan ringkas mengenai dokumentasi foto kegiatan..."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        ></textarea>
                        @error('formDescription')
                            <p class="text-[11px] text-rose-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Uploading Progress Indicator -->
                    <div wire:loading wire:target="formCoverImage, formPhotos" class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-500/30 text-amber-800 dark:text-amber-200 text-[11px] font-semibold flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4 text-amber-600" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>Sedang mengunggah foto ke browser cache... mohon tunggu sebentar.</span>
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
                            wire:loading.attr="disabled"
                            wire:target="saveAlbum, formCoverImage, formPhotos"
                            class="inline-flex items-center gap-2 py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="saveAlbum, formCoverImage, formPhotos">
                                {{ $editingId ? 'Simpan Perubahan' : 'Simpan Album' }}
                            </span>
                            <span wire:loading wire:target="saveAlbum, formCoverImage, formPhotos" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Menyimpan & Kompresi...</span>
                            </span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

    <!-- =========================================================================
         4. MODAL PREVIEW FOTO ALBUM (TELEPORTED)
         ========================================================================= -->
    @if ($selectedAlbum)
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
                    class="relative max-w-4xl w-full bg-white dark:bg-[#081710] rounded-3xl overflow-hidden border border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[90vh]"
                >
                    <div class="p-4 bg-[#0B2117] text-white flex items-center justify-between border-b border-emerald-900/60">
                        <div>
                            <span class="text-[10px] text-amber-300 font-bold uppercase">{{ $selectedAlbum->category }}</span>
                            <h3 class="font-extrabold text-sm text-[#FAF5ED]">{{ $selectedAlbum->title }}</h3>
                        </div>
                        <button 
                            @click="$wire.previewModalOpen = false" 
                            type="button" 
                            class="p-1.5 rounded-full text-stone-400 hover:text-white bg-black/30 hover:bg-black/50 transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto space-y-4">
                        <img src="{{ asset($selectedAlbum->cover_image ?: 'images/gallery-altar.jpg') }}" alt="{{ $selectedAlbum->title }}" class="w-full aspect-[16/9] object-cover rounded-2xl shadow-lg border border-stone-200 dark:border-emerald-900/50" />

                        @if ($selectedAlbum->photos->count() > 0)
                            <div class="space-y-2">
                                <h4 class="font-bold text-xs text-stone-700 dark:text-stone-300">Foto Lainnya dalam Album:</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    @foreach ($selectedAlbum->photos as $p)
                                        <div class="relative group rounded-xl overflow-hidden aspect-square border border-stone-200 dark:border-emerald-900/50">
                                            <img src="{{ asset($p->image_path) }}" class="w-full h-full object-cover" />
                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                <button 
                                                    wire:click="deletePhoto({{ $p->id }})" 
                                                    wire:confirm="Hapus foto ini dari album?" 
                                                    type="button" 
                                                    class="p-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white shadow-md transition-all cursor-pointer" 
                                                    title="Hapus Foto"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </template>
    @endif

</div>
