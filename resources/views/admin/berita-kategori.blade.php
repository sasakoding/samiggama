<?php

use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $searchQuery = '';
    public bool $modalOpen = false;
    public ?int $editingId = null;
    public ?string $feedbackMessage = null;

    // Form inputs
    public string $formName = '';
    public string $formSlug = '';
    public string $formDescription = '';
    public string $formBadgeColor = 'emerald';
    public string $formStatus = 'aktif';

    public function mount(): void
    {
        ArticleCategory::seedDefaultsIfEmpty();

        if (session()->has('feedbackMessage')) {
            $this->feedbackMessage = session('feedbackMessage');
        }
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'editingId',
            'formName',
            'formSlug',
            'formDescription',
        ]);
        $this->formBadgeColor = 'emerald';
        $this->formStatus = 'aktif';
        $this->modalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $category = ArticleCategory::find($id);
        if (!$category) {
            return;
        }

        $this->editingId = $category->id;
        $this->formName = $category->name;
        $this->formSlug = $category->slug;
        $this->formDescription = $category->description ?? '';
        $this->formBadgeColor = $category->badge_color ?? 'emerald';
        $this->formStatus = $category->status;

        $this->modalOpen = true;
    }

    public function toggleStatus(int $id): void
    {
        $category = ArticleCategory::find($id);
        if ($category) {
            $category->status = ($category->status === 'aktif') ? 'nonaktif' : 'aktif';
            $category->save();
            $this->feedbackMessage = "Status kategori '{$category->name}' diubah menjadi " . ($category->status === 'aktif' ? 'Aktif' : 'Nonaktif') . ".";
        }
    }

    public function saveCategory(): void
    {
        $this->validate([
            'formName' => 'required|string|max:100|unique:article_categories,name,' . $this->editingId,
            'formDescription' => 'nullable|string|max:500',
            'formBadgeColor' => 'required|in:emerald,amber,blue,purple,rose',
            'formStatus' => 'required|in:aktif,nonaktif',
        ], [
            'formName.required' => 'Nama kategori wajib diisi.',
            'formName.unique' => 'Nama kategori sudah digunakan.',
            'formBadgeColor.required' => 'Pilih warna tema badge kategori.',
        ]);

        $slug = Str::slug($this->formSlug ?: $this->formName);

        if ($this->editingId) {
            $category = ArticleCategory::findOrFail($this->editingId);
            $oldName = $category->name;

            if ($category->slug !== $slug && ArticleCategory::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug .= '-' . time();
            }

            $category->update([
                'name' => $this->formName,
                'slug' => $slug,
                'description' => $this->formDescription,
                'badge_color' => $this->formBadgeColor,
                'status' => $this->formStatus,
            ]);

            // Synchronize existing articles with updated category name
            if ($oldName !== $this->formName) {
                Article::where('category', $oldName)->update(['category' => $this->formName]);
            }

            $this->feedbackMessage = "Kategori '{$this->formName}' berhasil diperbarui.";
        } else {
            if (ArticleCategory::where('slug', $slug)->exists()) {
                $slug .= '-' . time();
            }

            ArticleCategory::create([
                'name' => $this->formName,
                'slug' => $slug,
                'description' => $this->formDescription,
                'badge_color' => $this->formBadgeColor,
                'status' => $this->formStatus,
            ]);

            $this->feedbackMessage = "Kategori baru '{$this->formName}' berhasil ditambahkan.";
        }

        $this->modalOpen = false;
    }

    public function deleteCategory(int $id): void
    {
        $category = ArticleCategory::find($id);
        if (!$category) {
            return;
        }

        $articlesCount = $category->articles()->count();
        $name = $category->name;

        if ($articlesCount > 0) {
            // Reassign articles to default category 'Kajian Dhamma' or leave unassigned
            $fallback = ArticleCategory::where('id', '!=', $id)->first();
            $fallbackName = $fallback ? $fallback->name : 'Kajian Dhamma';
            Article::where('category', $name)->update(['category' => $fallbackName]);
        }

        $category->delete();
        $this->feedbackMessage = "Kategori '{$name}' berhasil dihapus.";
    }

    public function render()
    {
        $query = ArticleCategory::withCount('articles')->latest();

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(description) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(slug) LIKE ?', [$search]);
            });
        }

        $categories = $query->get();
        $totalCategories = ArticleCategory::count();
        $activeCategories = ArticleCategory::where('status', 'aktif')->count();
        $totalArticlesInDb = Article::count();

        return $this->view([
            'categories' => $categories,
            'totalCategories' => $totalCategories,
            'activeCategories' => $activeCategories,
            'totalArticlesInDb' => $totalArticlesInDb,
        ])->title('Kelola Kategori Berita & Kajian Dhamma')->layout('layouts::admin');
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
                    Kelola Kategori Berita
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Atur klasifikasi topik berita, kajian Dhamma, dan warta kegiatan vihara secara dinamis.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button 
                wire:click="openCreateModal" 
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Kategori</span>
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
         3. CATEGORIES TABLE & SEARCH
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Search Bar -->
        <div class="p-4 sm:p-5 border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative w-full sm:w-80">
                <input 
                    wire:model.live.debounce.250ms="searchQuery" 
                    type="text" 
                    placeholder="Cari nama kategori atau deskripsi..." 
                    class="w-full pl-9 pr-4 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-amber-500"
                />
                <svg class="w-4 h-4 text-stone-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="text-xs font-bold text-stone-500 dark:text-stone-400">
                Menampilkan <span class="text-stone-800 dark:text-stone-200">{{ $categories->count() }}</span> Kategori
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600 dark:text-stone-300">
                <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-5">Nama Kategori & Tema</th>
                        <th scope="col" class="py-3.5 px-4">Slug URL</th>
                        <th scope="col" class="py-3.5 px-4">Deskripsi</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Jumlah Berita</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                        <th scope="col" class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                    @forelse ($categories as $cat)
                        <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/30 transition-colors">
                            
                            <!-- Nama Kategori & Badge -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full 
                                        {{ $cat->badge_color === 'amber' ? 'bg-amber-500' : '' }}
                                        {{ $cat->badge_color === 'blue' ? 'bg-blue-500' : '' }}
                                        {{ $cat->badge_color === 'purple' ? 'bg-purple-500' : '' }}
                                        {{ $cat->badge_color === 'rose' ? 'bg-rose-500' : '' }}
                                        {{ $cat->badge_color === 'emerald' ? 'bg-emerald-500' : '' }}
                                    "></span>
                                    <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm">
                                        {{ $cat->name }}
                                    </span>
                                </div>
                            </td>

                            <!-- Slug -->
                            <td class="py-3.5 px-4 font-mono text-[11px] text-stone-500 dark:text-stone-400">
                                {{ $cat->slug }}
                            </td>

                            <!-- Deskripsi -->
                            <td class="py-3.5 px-4 max-w-xs truncate text-[11.5px] text-stone-500 dark:text-stone-400" title="{{ $cat->description }}">
                                {{ $cat->description ?: '-' }}
                            </td>

                            <!-- Jumlah Berita -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-stone-100 dark:bg-emerald-950 text-stone-800 dark:text-emerald-200 font-bold text-xs tabular-nums border border-stone-200 dark:border-emerald-500/20">
                                    {{ $cat->articles_count }} Berita
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                <button 
                                    wire:click="toggleStatus({{ $cat->id }})" 
                                    type="button" 
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-bold cursor-pointer transition-colors
                                        {{ $cat->status === 'aktif' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/25' : 'bg-stone-200 dark:bg-stone-800 text-stone-600 dark:text-stone-400 hover:bg-stone-300' }}"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full {{ $cat->status === 'aktif' ? 'bg-emerald-500' : 'bg-stone-400' }}"></span>
                                    <span>{{ $cat->status === 'aktif' ? 'Aktif' : 'Nonaktif' }}</span>
                                </button>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button 
                                        wire:click="openEditModal({{ $cat->id }})" 
                                        type="button" 
                                        class="w-7 h-7 rounded-lg bg-stone-100 dark:bg-emerald-950/60 hover:bg-[#0D5B3A] hover:text-white dark:hover:bg-emerald-600 text-stone-600 dark:text-stone-300 flex items-center justify-center cursor-pointer border border-stone-200 dark:border-emerald-500/20 shadow-2xs transition-all"
                                        title="Edit Kategori"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>

                                    <button 
                                        wire:click="deleteCategory({{ $cat->id }})" 
                                        wire:confirm="Yakin ingin menghapus kategori '{{ $cat->name }}'? Seluruh artikel di bawah kategori ini akan dialihkan secara aman."
                                        type="button" 
                                        class="w-7 h-7 rounded-lg bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-600 hover:text-white text-rose-600 dark:text-rose-400 flex items-center justify-center cursor-pointer border border-rose-200 dark:border-rose-500/20 shadow-2xs transition-all"
                                        title="Hapus Kategori"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center space-y-2">
                                <div class="w-12 h-12 rounded-full bg-stone-100 dark:bg-[#071710] flex items-center justify-center mx-auto text-stone-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                </div>
                                <p class="font-bold text-stone-600 dark:text-stone-300">Belum ada data kategori yang cocok</p>
                                <p class="text-xs text-stone-400">Gunakan kata kunci pencarian lain atau tambahkan kategori baru.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- =========================================================================
         4. MODAL FORM: TAMBAH / EDIT KATEGORI
         ========================================================================= -->
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            
            <div 
                x-data 
                @click.away="$wire.set('modalOpen', false)"
                class="w-full max-w-lg rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200 dark:border-emerald-500/30 shadow-2xl overflow-hidden font-sans space-y-5 p-6 sm:p-7"
            >
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-stone-200 dark:border-emerald-950 pb-4">
                    <div class="space-y-0.5">
                        <h3 class="text-base sm:text-lg font-extrabold text-stone-900 dark:text-stone-100">
                            {{ $editingId ? 'Edit Kategori Berita' : 'Tambah Kategori Berita Baru' }}
                        </h3>
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            {{ $editingId ? 'Perbarui informasi dan label kategori berita.' : 'Buat kategori topik baru untuk artikel & warta.' }}
                        </p>
                    </div>
                    <button 
                        @click="$wire.set('modalOpen', false)" 
                        type="button" 
                        class="p-1.5 rounded-xl text-stone-400 hover:text-stone-700 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-emerald-950 cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form Inputs -->
                <div class="space-y-4 text-xs">
                    
                    <!-- Nama Kategori -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-stone-700 dark:text-stone-300">
                            Nama Kategori <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            wire:model="formName" 
                            type="text" 
                            placeholder="cth: Kajian Dhamma, Berita Vihara, Kegiatan Sosial"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-semibold"
                        />
                        @error('formName') <span class="text-rose-500 text-[11px] font-bold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Slug URL (Optional) -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-stone-700 dark:text-stone-300">
                            Slug URL (Opsional / Otomatis)
                        </label>
                        <input 
                            wire:model="formSlug" 
                            type="text" 
                            placeholder="cth: kajian-dhamma"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-mono"
                        />
                    </div>

                    <!-- Deskripsi -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-stone-700 dark:text-stone-300">
                            Deskripsi Singkat Topik
                        </label>
                        <textarea 
                            wire:model="formDescription" 
                            rows="2"
                            placeholder="Penjelasan ringkas isi berita dalam kategori ini..."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        ></textarea>
                        @error('formDescription') <span class="text-rose-500 text-[11px] font-bold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Grid: Warna Badge & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        <!-- Warna Tema Badge -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-stone-700 dark:text-stone-300">
                                Warna Tema Badge
                            </label>
                            <select 
                                wire:model="formBadgeColor" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-bold"
                            >
                                <option value="emerald">Hijau Zamrud (Emerald)</option>
                                <option value="amber">Emas / Kuning (Amber)</option>
                                <option value="blue">Biru Langit (Blue)</option>
                                <option value="purple">Ungu Transparansi (Purple)</option>
                                <option value="rose">Merah Muda (Rose)</option>
                            </select>
                        </div>

                        <!-- Status Visibilitas -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-stone-700 dark:text-stone-300">
                                Status Visibilitas
                            </label>
                            <select 
                                wire:model="formStatus" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-bold"
                            >
                                <option value="aktif">Aktif (Tampil Publik)</option>
                                <option value="nonaktif">Nonaktif (Disembunyikan)</option>
                            </select>
                        </div>

                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="pt-4 border-t border-stone-200 dark:border-emerald-950 flex items-center justify-end gap-2.5">
                    <button 
                        @click="$wire.set('modalOpen', false)" 
                        type="button" 
                        class="px-4 py-2.5 rounded-xl bg-stone-100 dark:bg-emerald-950 hover:bg-stone-200 dark:hover:bg-emerald-900 text-stone-700 dark:text-stone-300 text-xs font-bold transition-all cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        wire:click="saveCategory" 
                        type="button" 
                        class="px-5 py-2.5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white text-xs font-bold shadow-xs border border-amber-400/30 transition-all cursor-pointer"
                    >
                        {{ $editingId ? 'Simpan Perubahan' : 'Tambah Kategori' }}
                    </button>
                </div>

            </div>

        </div>
    @endif

</div>
