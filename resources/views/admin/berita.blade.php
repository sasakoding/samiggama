<?php

use App\Models\Article;
use App\Services\ImageUploadService;
use Livewire\Component;

new class extends Component
{
    public string $searchQuery = '';
    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        if (session()->has('feedbackMessage')) {
            $this->feedbackMessage = session('feedbackMessage');
        }
    }

    public function toggleStatus(int $id): void
    {
        $art = Article::find($id);
        if ($art) {
            $art->status = ($art->status === 'published') ? 'draft' : 'published';
            $art->save();
            $this->feedbackMessage = "Status artikel '{$art->title}' diubah menjadi " . ($art->status === 'published' ? 'Terbit' : 'Draf') . ".";
        }
    }

    public function deleteArticle(int $id): void
    {
        $art = Article::find($id);
        if ($art) {
            $title = $art->title;
            ImageUploadService::deleteOldImage($art->cover_image);
            $art->delete();
            $this->feedbackMessage = "Artikel '{$title}' berhasil dihapus.";
        }
    }

    public function render()
    {
        $query = Article::latest();

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(author_name) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(category) LIKE ?', [$search]);
            });
        }

        $articles = $query->get();

        return $this->view([
            'articles' => $articles,
        ])->title('Kelola Berita & Kajian Dhamma')->layout('layouts::admin');
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
                    Kajian Dhamma & Berita
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Kelola naskah ceramah Dhamma, berita aktivitas vihara, dan laporan transparansi berkala.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a 
                href="{{ route('admin.berita.create') }}"
                wire:navigate
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tulis Berita Baru</span>
            </a>
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
         2. TABEL ARTIKEL
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Table Toolbar -->
        <div class="p-5 border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-stone-900 dark:text-stone-100">
                    Daftar Publikasi Artikel
                </h2>
                <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                    Materi literasi Dhamma dan berita kegiatan yang dapat diakses publik.
                </p>
            </div>

            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    wire:model.live.debounce.250ms="searchQuery" 
                    type="text" 
                    placeholder="Cari judul, penulis, kategori..." 
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-[#0D5B3A] focus:ring-1 focus:ring-[#0D5B3A] transition-all shadow-2xs"
                />
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-5">Artikel & Kategori</th>
                        <th scope="col" class="py-3.5 px-4">Penulis / Narasumber</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Dibaca</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                        <th scope="col" class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                    @forelse ($articles as $art)
                        <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/20 transition-colors">
                            
                            <!-- Judul & Kategori -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <img 
                                        src="{{ asset($art->cover_image ?: 'images/news-baksos.jpg') }}" 
                                        alt="{{ $art->title }}" 
                                        class="w-12 h-10 rounded-lg object-cover border border-stone-200 dark:border-emerald-900/60 shrink-0" 
                                    />
                                    <div class="flex flex-col min-w-0 max-w-md">
                                        <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm truncate">
                                            {{ $art->title }}
                                        </span>
                                        <span class="text-[11px] text-stone-400 mt-0.5">
                                            {{ $art->category }} • {{ $art->created_at->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Penulis -->
                            <td class="py-3.5 px-4 font-bold text-stone-800 dark:text-stone-200">
                                {{ $art->author_name }}
                            </td>

                            <!-- Views -->
                            <td class="py-3.5 px-4 text-center font-bold text-stone-600 dark:text-stone-300">
                                {{ number_format($art->views_count) }}
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                <button 
                                    wire:click="toggleStatus({{ $art->id }})"
                                    type="button" 
                                    class="cursor-pointer transition-transform hover:scale-105"
                                    title="Klik untuk mengubah status"
                                >
                                    @if ($art->status === 'published')
                                        <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Terbit</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-stone-500 font-bold text-[11px] bg-stone-100 dark:bg-stone-800 px-2.5 py-1 rounded-full border border-stone-300 dark:border-stone-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                            <span>Draf</span>
                                        </span>
                                    @endif
                                </button>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    
                                    <!-- Lihat Tampilan Publik -->
                                    <a 
                                        href="{{ route('berita.detail', ['slug' => $art->slug]) }}" 
                                        target="_blank"
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-emerald-500/20 cursor-pointer shadow-2xs"
                                        title="Lihat Tampilan Publik"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>

                                    <!-- Edit Dedicated Page -->
                                    <a 
                                        href="{{ route('admin.berita.edit', $art->id) }}" 
                                        wire:navigate
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-emerald-500/20 cursor-pointer shadow-2xs"
                                        title="Edit Artikel di Halaman Editor"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    <!-- Hapus -->
                                    <button 
                                        wire:click="deleteArticle({{ $art->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus artikel ini?" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 border border-rose-500/20 cursor-pointer shadow-2xs"
                                        title="Hapus Artikel"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-stone-400">
                                Belum ada artikel yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>
