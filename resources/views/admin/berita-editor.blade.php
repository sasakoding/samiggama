<?php

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Services\ImageUploadService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?int $articleId = null;
    public bool $isEdit = false;

    // Database fields
    public string $title = '';
    public string $slug = '';
    public string $category = 'Kajian Dhamma';
    public string $author_name = '';
    public string $excerpt = '';
    public string $content = '';
    public string $status = 'published';
    public ?string $published_at = null;
    public $coverImage = null;
    public ?string $existingCoverImage = null;
    public int $views_count = 0;

    public function mount(?int $id = null): void
    {
        ArticleCategory::seedDefaultsIfEmpty();

        if ($id) {
            $art = Article::findOrFail($id);
            $this->articleId = $art->id;
            $this->isEdit = true;
            $this->title = $art->title;
            $this->slug = $art->slug;
            $this->category = $art->category;
            $this->author_name = $art->author_name ?? '';
            $this->excerpt = $art->excerpt ?? '';
            $this->content = $art->content ?? '';
            $this->status = $art->status;
            $this->published_at = $art->published_at ? $art->published_at->format('Y-m-d\TH:i') : null;
            $this->existingCoverImage = $art->cover_image;
            $this->views_count = $art->views_count;
        } else {
            $firstCategory = ArticleCategory::where('status', 'aktif')->first();
            $this->category = $firstCategory ? $firstCategory->name : 'Kajian Dhamma';
            $this->author_name = auth()->user()?->name ?? 'Bhante Saddhaviro Thera';
            $this->published_at = now()->format('Y-m-d\TH:i');
        }
    }

    public function removeCover(): void
    {
        $this->coverImage = null;
        $this->existingCoverImage = null;
    }

    public function save(string $targetStatus = null): void
    {
        if ($targetStatus) {
            $this->status = $targetStatus;
        }

        $this->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'author_name' => 'required|string|max:255',
            'content' => 'required|string',
            'coverImage' => 'nullable|image|max:10240',
        ], [
            'title.required' => 'Judul artikel wajib diisi.',
            'category.required' => 'Pilih kategori artikel.',
            'author_name.required' => 'Nama penulis / narasumber wajib diisi.',
            'content.required' => 'Isi konten lengkap artikel tidak boleh kosong.',
            'coverImage.max' => 'Ukuran gambar sampul maksimal 10MB.',
        ]);

        // Auto-generate excerpt from content (maksimal 100 kata)
        $cleanContentText = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($this->content))));
        $this->excerpt = Str::words($cleanContentText, 100, '...');

        $coverImagePath = $this->existingCoverImage;
        if ($this->coverImage) {
            $coverImagePath = ImageUploadService::uploadAndCompress($this->coverImage, 'uploads/articles');
            ImageUploadService::cleanLivewireTmp();
        }

        $cleanSlug = Str::slug($this->slug ?: $this->title);
        if (empty($cleanSlug)) {
            $cleanSlug = 'artikel-' . time();
        }

        $publishDate = $this->published_at ? Carbon::parse($this->published_at) : now();

        if ($this->isEdit && $this->articleId) {
            $art = Article::findOrFail($this->articleId);

            if ($art->slug !== $cleanSlug && Article::where('slug', $cleanSlug)->where('id', '!=', $art->id)->exists()) {
                $cleanSlug .= '-' . time();
            }

            $art->update([
                'title' => $this->title,
                'slug' => $cleanSlug,
                'category' => $this->category,
                'author_name' => $this->author_name,
                'excerpt' => $this->excerpt,
                'content' => $this->content,
                'status' => $this->status,
                'published_at' => $publishDate,
                'cover_image' => $coverImagePath,
            ]);

            session()->flash('feedbackMessage', "Artikel '{$this->title}' berhasil diperbarui.");
            $this->redirect(route('admin.berita'), navigate: true);
        } else {
            if (Article::where('slug', $cleanSlug)->exists()) {
                $cleanSlug .= '-' . time();
            }

            Article::create([
                'title' => $this->title,
                'slug' => $cleanSlug,
                'category' => $this->category,
                'author_name' => $this->author_name,
                'user_id' => auth()->id(),
                'excerpt' => $this->excerpt,
                'content' => $this->content,
                'status' => $this->status,
                'published_at' => $publishDate,
                'cover_image' => $coverImagePath,
                'views_count' => 0,
            ]);

            session()->flash('feedbackMessage', "Artikel '{$this->title}' berhasil diterbitkan.");
            $this->redirect(route('admin.berita'), navigate: true);
        }
    }

    public function render()
    {
        $categories = ArticleCategory::where('status', 'aktif')->orderBy('name')->get();

        return $this->view([
            'categories' => $categories,
        ])->title($this->isEdit ? 'Edit Berita' : 'Tulis Berita Baru')->layout('layouts::admin');
    }
};
?>


<div 
    x-data="{
        editorMode: 'visual',
        customSlugOpen: false,
        isDragging: false,
        title: @js($title),
        slug: @js($slug),
        category: @js($category),
        author_name: @js($author_name),
        excerpt: @js($excerpt),
        published_at: @js($published_at),
        status: @js($status),
        customSlug: {{ $isEdit ? 'true' : 'false' }},

        init() {
            this.$nextTick(() => {
                const el = document.getElementById('wysiwyg-content');
                if (el && this.$wire.content) {
                    el.innerHTML = this.$wire.content;
                }
            });
        },

        generateSlug(text) {
            return (text || '')
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        },

        cleanFormatting() {
            const editor = document.getElementById('wysiwyg-content');
            if (!editor) return;

            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
                // Text selection cleaning
                document.execCommand('removeFormat', false, null);
                document.execCommand('hiliteColor', false, 'transparent');
                document.execCommand('backColor', false, 'transparent');

                const range = sel.getRangeAt(0);
                const container = range.commonAncestorContainer;
                
                // Clean parent wrappers
                let parent = container.nodeType === 1 ? container : container.parentElement;
                while (parent && parent !== editor) {
                    parent.style.backgroundColor = '';
                    parent.style.background = '';
                    parent.style.color = '';
                    parent.style.fontFamily = '';
                    parent.style.fontSize = '';
                    parent.removeAttribute('bgcolor');
                    parent = parent.parentElement;
                }

                // Clean child elements in selection
                const targetNode = container.nodeType === 1 ? container : container.parentElement;
                if (targetNode) {
                    targetNode.querySelectorAll('*').forEach(el => {
                        el.style.backgroundColor = '';
                        el.style.background = '';
                        el.style.color = '';
                        el.style.fontFamily = '';
                        el.style.fontSize = '';
                        el.removeAttribute('bgcolor');
                    });
                }
            } else {
                // Whole editor background & formatting clean
                editor.querySelectorAll('*').forEach(el => {
                    el.style.backgroundColor = '';
                    el.style.background = '';
                    el.style.color = '';
                    el.style.fontFamily = '';
                    el.style.fontSize = '';
                    el.removeAttribute('bgcolor');
                    if (el.tagName === 'SPAN' && (!el.getAttribute('style') || el.getAttribute('style').trim() === '')) {
                        el.replaceWith(...el.childNodes);
                    }
                });
                editor.style.backgroundColor = '';
                editor.style.background = '';
            }

            this.updateContentFromDiv();
        },

        clearBackground() {
            const editor = document.getElementById('wysiwyg-content');
            if (!editor) return;

            document.execCommand('hiliteColor', false, 'transparent');
            document.execCommand('backColor', false, 'transparent');

            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
                const range = sel.getRangeAt(0);
                let parent = range.commonAncestorContainer.nodeType === 1 ? range.commonAncestorContainer : range.commonAncestorContainer.parentElement;
                while (parent && parent !== editor) {
                    parent.style.backgroundColor = '';
                    parent.style.background = '';
                    parent.removeAttribute('bgcolor');
                    parent = parent.parentElement;
                }
            } else {
                editor.querySelectorAll('*').forEach(el => {
                    el.style.backgroundColor = '';
                    el.style.background = '';
                    el.removeAttribute('bgcolor');
                });
            }

            this.updateContentFromDiv();
        },

        handlePaste(event) {
            const clipboardData = event.clipboardData || window.clipboardData;
            if (!clipboardData) return;

            const html = clipboardData.getData('text/html');
            if (html) {
                event.preventDefault();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Strip intrusive background styles, colors, and fonts while keeping semantic structure
                doc.body.querySelectorAll('*').forEach(el => {
                    el.style.backgroundColor = '';
                    el.style.background = '';
                    el.style.color = '';
                    el.style.fontFamily = '';
                    el.style.fontSize = '';
                    el.removeAttribute('bgcolor');
                    el.removeAttribute('class');
                    if (el.tagName === 'SPAN' && (!el.getAttribute('style') || el.getAttribute('style').trim() === '')) {
                        el.replaceWith(...el.childNodes);
                    }
                });

                document.execCommand('insertHTML', false, doc.body.innerHTML);
                this.updateContentFromDiv();
            }
        },

        savedSelection: null,

        saveSelection() {
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                const editor = document.getElementById('wysiwyg-content');
                if (editor && editor.contains(range.commonAncestorContainer)) {
                    this.savedSelection = range.cloneRange();
                }
            }
        },

        restoreSelection() {
            const editor = document.getElementById('wysiwyg-content');
            if (editor) {
                editor.focus();
                if (this.savedSelection) {
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(this.savedSelection);
                }
            }
        },

        execCmd(command, value = null) {
            this.restoreSelection();
            try {
                document.execCommand(command, false, value);
            } catch(e) {}
            this.saveSelection();
            this.updateContentFromDiv();
        },

        formatBlock(tag) {
            if (!tag) return;
            this.restoreSelection();
            
            let ok = false;
            try {
                ok = document.execCommand('formatBlock', false, '<' + tag + '>');
            } catch(e) {}
            if (!ok) {
                try {
                    document.execCommand('formatBlock', false, tag);
                } catch(e) {}
            }
            
            this.saveSelection();
            this.updateContentFromDiv();
        },

        insertLink() {
            this.restoreSelection();
            const url = prompt('Masukkan tautan URL (contoh: https://...):', 'https://');
            if (url && url !== 'https://') {
                document.execCommand('createLink', false, url);
                this.saveSelection();
                this.updateContentFromDiv();
            }
        },

        insertPaliQuote() {
            this.restoreSelection();
            const quoteHtml = '<blockquote class=\'p-4 my-4 rounded-2xl bg-amber-500/10 border-l-4 border-amber-600 font-serif italic text-stone-800 dark:text-amber-200\'><p class=\'font-bold\'>“Namo Tassa Bhagavato Arahato Sammāsambuddhassa...”</p><cite class=\'block text-xs text-stone-500 dark:text-stone-400 not-italic mt-1\'>— Sutta Pitaka / Dhammapada</cite></blockquote><p></p>';
            document.execCommand('insertHTML', false, quoteHtml);
            this.saveSelection();
            this.updateContentFromDiv();
        },

        updateContentFromDiv() {
            const el = document.getElementById('wysiwyg-content');
            if (el) {
                this.$wire.content = el.innerHTML;
            }
        },

        syncToDiv() {
            const el = document.getElementById('wysiwyg-content');
            if (el) {
                el.innerHTML = this.$wire.content || '';
            }
        },

        countWords() {
            const text = (this.$wire.content || '').replace(/<[^>]*>/g, ' ').trim();
            return text ? text.split(/\s+/).filter(Boolean).length : 0;
        },

        countChars() {
            const text = (this.$wire.content || '').replace(/<[^>]*>/g, '').trim();
            return text.length;
        },

        handleDrop(event) {
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.$wire.upload('coverImage', files[0], () => {}, () => {});
            }
        },

        syncAndSave(targetStatus = null) {
            this.updateContentFromDiv();
            if (targetStatus) {
                this.status = targetStatus;
            }
            this.$wire.title = this.title;
            this.$wire.slug = this.slug;
            this.$wire.status = this.status;
            this.$wire.category = this.category;
            this.$wire.author_name = this.author_name;
            this.$wire.excerpt = this.excerpt;
            this.$wire.published_at = this.published_at;
            this.$wire.save(this.status);
        }
    }" 
    class="space-y-6 font-sans pb-16 w-full max-w-7xl mx-auto"
>

    <!-- =========================================================================
         1. TOP HEADER & BREADCRUMBS
         ========================================================================= -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-stone-200/80 dark:border-emerald-950">
        
        <div class="space-y-1.5">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-semibold text-stone-500 dark:text-stone-400">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-[#0D5B3A] dark:hover:text-emerald-400 transition-colors">Dashboard</a>
                <span>/</span>
                <a href="{{ route('admin.berita') }}" wire:navigate class="hover:text-[#0D5B3A] dark:hover:text-emerald-400 transition-colors">Kajian & Berita</a>
                <span>/</span>
                <span class="text-[#0D5B3A] dark:text-emerald-400 font-bold">
                    {{ $isEdit ? 'Edit Berita' : 'Tulis Berita Baru' }}
                </span>
            </nav>

            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    {{ $isEdit ? 'Edit Naskah Berita' : 'Tulis Berita Baru' }}
                </h1>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a 
                href="{{ route('admin.berita') }}" 
                wire:navigate
                class="py-2.5 px-4 rounded-xl bg-stone-100 dark:bg-emerald-950/70 hover:bg-stone-200 text-stone-700 dark:text-stone-300 font-bold text-xs transition-colors cursor-pointer"
            >
                Kembali
            </a>

            <button 
                @click="syncAndSave('draft')"
                type="button" 
                class="py-2.5 px-4 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-900 dark:text-amber-300 border border-amber-500/30 font-bold text-xs transition-all cursor-pointer flex items-center gap-1.5"
            >
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <span>Draf</span>
            </button>

            <button 
                @click="syncAndSave('published')"
                type="button" 
                class="py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer flex items-center gap-1.5 transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <span>{{ $isEdit ? 'Perbarui' : 'Terbitkan' }}</span>
            </button>
        </div>

    </div>

    <!-- Error Summary Alerts -->
    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-500/30 text-rose-800 dark:text-rose-200 text-xs space-y-1 shadow-xs">
            <div class="font-extrabold flex items-center gap-1.5">
                <svg class="w-4 h-4 text-rose-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>Terdapat beberapa isian yang perlu diperiksa:</span>
            </div>
            <ul class="list-disc list-inside pl-2 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- =========================================================================
         2. MAIN FORM GRID: LEFT (EDITOR) & RIGHT (SIDEBAR METADATA)
         ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">

        <!-- =========================================================
             LEFT COLUMN: MAIN WRITING AREA (8 COLS)
             ========================================================= -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Title Input -->
            <div class="p-6 rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs space-y-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                        Judul Artikel / Kajian <span class="text-amber-500">*</span>
                    </label>
                    <input 
                        x-model="title" 
                        @input="if (!customSlug) slug = generateSlug(title)"
                        type="text" 
                        required
                        placeholder="Ketik judul artikel berita yang memikat di sini..." 
                        class="w-full text-xl sm:text-2xl font-black text-stone-900 dark:text-stone-100 placeholder-stone-400 bg-transparent border-0 border-b-2 border-stone-200 dark:border-emerald-950 focus:border-[#0D5B3A] dark:focus:border-emerald-500 focus:ring-0 px-0 py-2 transition-all outline-none"
                    />
                </div>

                <!-- Slug Preview & Custom Edit -->
                <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                    <span class="text-stone-400">Tautan Permanen:</span>
                    <span class="text-[#0D5B3A] dark:text-emerald-400 font-bold bg-stone-100 dark:bg-emerald-950/70 px-2.5 py-1 rounded-lg border border-stone-200 dark:border-emerald-900/60 break-all">
                        {{ route('berita') }}/<strong x-text="slug || 'judul-artikel'"></strong>
                    </span>
                    <button 
                        @click="customSlugOpen = !customSlugOpen; customSlug = true" 
                        type="button" 
                        class="text-xs text-amber-600 dark:text-amber-400 hover:underline font-bold cursor-pointer"
                    >
                        <span x-text="customSlugOpen ? 'Tutup Kustom Slug' : 'Ubah Slug'"></span>
                    </button>
                </div>

                <div x-show="customSlugOpen" x-cloak class="pt-2">
                    <input 
                        x-model="slug" 
                        type="text" 
                        placeholder="kustom-slug-url" 
                        class="w-full px-3 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/30 text-xs text-stone-900 dark:text-stone-100 focus:outline-none focus:border-amber-500"
                    />
                    <p class="text-[11px] text-stone-400 mt-1">Gunakan huruf kecil dan tanda hubung (-). Huruf unik akan dipertahankan.</p>
                </div>
            </div>

            <!-- =========================================================
                 BLOGGER-STYLE RICH CONTENT EDITOR
                 ========================================================= -->
            <div class="rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-sm overflow-hidden flex flex-col">
                
                <!-- Editor Topbar Tabs & Mode Selector -->
                <div class="px-5 py-3.5 bg-stone-100/90 dark:bg-[#071710] border-b border-stone-200 dark:border-emerald-950 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-1.5 p-1 rounded-xl bg-stone-200/70 dark:bg-emerald-950 text-xs">
                        <button 
                            @click="editorMode = 'visual'" 
                            type="button" 
                            :class="editorMode === 'visual' ? 'bg-white dark:bg-[#0b1f17] text-[#0D5B3A] dark:text-emerald-300 shadow-xs font-black' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 font-semibold'"
                            class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Editor Visual</span>
                        </button>

                        <button 
                            @click="editorMode = 'html'" 
                            type="button" 
                            :class="editorMode === 'html' ? 'bg-white dark:bg-[#0b1f17] text-[#0D5B3A] dark:text-emerald-300 shadow-xs font-black' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 font-semibold'"
                            class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            <span>Kode HTML</span>
                        </button>

                        <button 
                            @click="editorMode = 'preview'" 
                            type="button" 
                            :class="editorMode === 'preview' ? 'bg-white dark:bg-[#0b1f17] text-[#0D5B3A] dark:text-emerald-300 shadow-xs font-black' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 font-semibold'"
                            class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span>Pratinjau Hasil</span>
                        </button>
                    </div>

                    <div class="text-[11px] text-stone-400 font-medium">
                        <span x-text="countWords() + ' Kata'"></span> •
                        <span x-text="countChars() + ' Karakter'"></span>
                    </div>
                </div>

                <!-- Blogger-style Interactive Formatting Toolbar (Visible in Visual Mode) -->
                <div x-show="editorMode === 'visual'" class="p-2.5 bg-stone-50 dark:bg-[#081b13] border-b border-stone-200 dark:border-emerald-950/80 flex flex-wrap items-center gap-1 text-xs">
                    
                    <!-- Headings Dropdown -->
                    <select 
                        @mousedown="saveSelection()"
                        @focus="saveSelection()"
                        @change="formatBlock($event.target.value); $event.target.value = ''"
                        class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#0b1f17] border border-stone-200 dark:border-emerald-900 text-xs font-bold text-stone-800 dark:text-stone-200 focus:outline-none cursor-pointer"
                        title="Tingkat Judul"
                    >
                        <option value="">Gaya Teks...</option>
                        <option value="p">Paragraf Standar</option>
                        <option value="h2">Heading 2 (Judul Bab)</option>
                        <option value="h3">Heading 3 (Sub-Bab)</option>
                        <option value="blockquote">Kutipan Dhamma (Quote)</option>
                    </select>

                    <div class="h-5 w-px bg-stone-300 dark:bg-emerald-900/60 mx-1"></div>

                    <!-- Basic Text Formatting -->
                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('bold')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 font-black cursor-pointer"
                        title="Tebal (Ctrl+B)"
                    >
                        <b>B</b>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('italic')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 italic font-serif font-black cursor-pointer"
                        title="Miring (Ctrl+I)"
                    >
                        <i>I</i>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('underline')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 underline font-bold cursor-pointer"
                        title="Garis Bawah (Ctrl+U)"
                    >
                        <u>U</u>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('strikeThrough')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 line-through font-bold cursor-pointer"
                        title="Coret"
                    >
                        <s>S</s>
                    </button>

                    <div class="h-5 w-px bg-stone-300 dark:bg-emerald-900/60 mx-1"></div>

                    <!-- Lists -->
                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('insertUnorderedList')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Daftar Poin (Bullet List)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16M2 6h.01M2 12h.01M2 18h.01"/></svg>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('insertOrderedList')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Daftar Angka (Numbered List)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h13M7 12h13M7 18h13M3 6h1m-1 6h1m-1 6h1"/></svg>
                    </button>

                    <!-- Alignment -->
                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('justifyLeft')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Rata Kiri"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"/></svg>
                    </button>

                    <div class="h-5 w-px bg-stone-300 dark:bg-emerald-900/60 mx-1"></div>

                    <button 
                        @mousedown.prevent=""
                        @click="insertPaliQuote()" 
                        type="button" 
                        class="px-2.5 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-800 dark:text-amber-300 font-bold border border-amber-500/30 cursor-pointer flex items-center gap-1"
                        title="Sisipkan Kotak Kutipan Pali / Syair Dhamma"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        <span>Syair Pali</span>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('justifyCenter')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Rata Tengah"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M4 18h16"/></svg>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('justifyRight')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Rata Kanan"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M4 18h16"/></svg>
                    </button>

                    <div class="h-5 w-px bg-stone-300 dark:bg-emerald-900/60 mx-1"></div>

                    <!-- Links & Divider -->
                    <button 
                        @mousedown.prevent=""
                        @click="insertLink()" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Sisipkan Tautan URL"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('insertHorizontalRule')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Garis Pemisah (Horizontal Rule)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="clearBackground()" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-950/60 text-amber-700 dark:text-amber-300 cursor-pointer"
                        title="Hapus Warna Background / Sorotan Teks"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="cleanFormatting()" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-bold cursor-pointer flex items-center gap-1"
                        title="Bersihkan Semua Format (Hapus Background Copas, Warna, Font Asing)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span class="text-[10.5px] font-bold">Bersihkan Format</span>
                    </button>

                    <div class="h-5 w-px bg-stone-300 dark:bg-emerald-900/60 mx-1"></div>

                    <!-- Undo & Redo -->
                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('undo')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Urungkan (Ctrl+Z)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    </button>

                    <button 
                        @mousedown.prevent=""
                        @click="execCmd('redo')" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Ulangi (Ctrl+Y)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/></svg>
                    </button>
                </div>

                <!-- 1. WYSIWYG Editable Visual Body (wire:ignore to prevent Livewire from re-rendering or wiping content) -->
                <div 
                    wire:ignore
                    x-show="editorMode === 'visual'" 
                    class="p-6 sm:p-8 min-h-[420px] max-h-[750px] overflow-y-auto bg-white dark:bg-[#0b1f17] focus:outline-none leading-relaxed text-stone-800 dark:text-stone-100 text-sm sm:text-base prose dark:prose-invert max-w-none"
                    id="wysiwyg-content"
                    contenteditable="true"
                    @keyup="saveSelection(); updateContentFromDiv()"
                    @mouseup="saveSelection()"
                    @selectstart="saveSelection()"
                    @focus="saveSelection()"
                    @blur="saveSelection(); updateContentFromDiv()"
                    @paste="handlePaste($event)"
                >{!! $content !!}</div>

                <!-- 2. HTML Raw Code View -->
                <div x-show="editorMode === 'html'" x-cloak class="p-4 bg-[#1e1e1e]">
                    <textarea 
                        wire:model="content" 
                        rows="18" 
                        class="w-full h-full min-h-[420px] bg-transparent text-emerald-400 text-xs sm:text-sm focus:outline-none resize-y leading-relaxed font-sans"
                        placeholder="<p>Tulis kode HTML artikel di sini...</p>"
                        @input="syncToDiv()"
                    ></textarea>
                </div>

                <!-- 3. Live Preview Mode -->
                <div x-show="editorMode === 'preview'" x-cloak class="p-6 sm:p-10 min-h-[420px] bg-[#FAF5ED] dark:bg-[#071710] space-y-6">
                    <div class="space-y-2 border-b border-stone-200 dark:border-emerald-950 pb-4">
                        <span class="px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950 text-[#0D6E42] dark:text-emerald-300 font-bold text-xs" x-text="category"></span>
                        <h1 class="text-2xl sm:text-4xl font-serif font-black text-stone-900 dark:text-stone-100" x-text="title || 'Pratinjau Judul Artikel'"></h1>
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            Ditulis oleh: <strong x-text="author_name"></strong> • <span x-text="published_at ? published_at.substring(0, 10) : 'Hari ini'"></span>
                        </p>
                    </div>

                    @if ($coverImage)
                        <div class="aspect-video w-full rounded-2xl overflow-hidden shadow-md">
                            <img src="{{ $coverImage->temporaryUrl() }}" alt="Cover" class="w-full h-full object-cover" />
                        </div>
                    @elseif ($existingCoverImage)
                        <div class="aspect-video w-full rounded-2xl overflow-hidden shadow-md">
                            <img src="{{ asset($existingCoverImage) }}" alt="Cover" class="w-full h-full object-cover" />
                        </div>
                    @endif

                    <div class="prose dark:prose-invert max-w-none text-stone-800 dark:text-stone-200 text-sm sm:text-base leading-relaxed" x-html="$wire.content"></div>
                </div>

            </div>

        </div>

        <!-- =========================================================
             RIGHT COLUMN: SIDEBAR METADATA & THUMBNAIL (4 COLS)
             ========================================================= -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Card 1: Premium Drag & Drop Thumbnail Dropzone -->
            <div class="p-6 rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs space-y-4">
                <div class="space-y-0.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                        Gambar Sampul / Thumbnail <span class="text-amber-500">*</span>
                    </h3>
                    <p class="text-[11px] text-stone-400">Tampil sebagai banner utama dan preview sosial media.</p>
                </div>

                <!-- Live Preview of Uploaded or Existing Image -->
                @if ($coverImage)
                    <div class="relative aspect-video rounded-2xl overflow-hidden border-2 border-emerald-500 shadow-md group">
                        <img src="{{ $coverImage->temporaryUrl() }}" alt="Preview Cover" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button 
                                wire:click="removeCover" 
                                type="button" 
                                class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-all cursor-pointer flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Hapus Foto</span>
                            </button>
                        </div>
                    </div>
                @elseif ($existingCoverImage)
                    <div class="relative aspect-video rounded-2xl overflow-hidden border border-stone-200 dark:border-emerald-950 shadow-md group">
                        <img src="{{ asset($existingCoverImage) }}" alt="Existing Cover" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button 
                                wire:click="removeCover" 
                                type="button" 
                                class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-all cursor-pointer flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Ganti Foto</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Drag and Drop Dropzone Input -->
                <div 
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="isDragging = false; handleDrop($event)"
                    :class="isDragging ? 'border-[#0D5B3A] bg-emerald-50/50 dark:bg-emerald-950/40 scale-[1.02]' : 'border-stone-300 dark:border-emerald-950/80 bg-stone-50/70 dark:bg-[#071710] hover:border-emerald-500'"
                    class="relative border-2 border-dashed rounded-2xl p-6 text-center space-y-3 transition-all duration-200 cursor-pointer"
                >
                    <input 
                        wire:model="coverImage" 
                        type="file" 
                        accept="image/png,image/jpeg,image/jpg,image/webp" 
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                    />

                    <div class="w-12 h-12 rounded-2xl bg-[#0D5B3A]/10 text-[#0D5B3A] dark:text-emerald-300 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-extrabold text-stone-800 dark:text-stone-200">
                            Tarik & Jatuhkan Gambar di Sini
                        </p>
                        <p class="text-[11px] text-stone-400">
                            atau <span class="text-[#0D5B3A] dark:text-emerald-400 font-bold underline">klik untuk memilih berkas</span>
                        </p>
                    </div>

                    <p class="text-[10px] text-stone-400">
                        JPG, PNG, WebP (Maks. 10MB) • Kompresi WebP Otomatis
                    </p>

                    <div wire:loading wire:target="coverImage" class="text-amber-500 text-xs font-bold pt-1">
                        Mengunggah & memproses gambar...
                    </div>
                </div>
            </div>

            <!-- Card 2: Metadata & Kategori Berita -->
            <div class="p-6 rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs space-y-4">
                
                <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                    Pengaturan Publikasi
                </h3>

                <!-- Kategori -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">
                            Kategori Artikel <span class="text-amber-500">*</span>
                        </label>
                        <a 
                            href="{{ route('admin.berita.kategori') }}" 
                            target="_blank" 
                            class="text-[10.5px] text-[#0D6E42] dark:text-emerald-400 font-bold hover:underline"
                        >
                            + Kelola Kategori
                        </a>
                    </div>
                    <select 
                        x-model="category" 
                        class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-semibold"
                    >
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Penulis / Narasumber -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">
                        Nama Penulis / Narasumber <span class="text-amber-500">*</span>
                    </label>
                    <input 
                        x-model="author_name" 
                        type="text" 
                        required
                        placeholder="cth: Bhante Saddhaviro Thera"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                    />
                </div>

                <!-- Tanggal Terbit -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">
                        Jadwal / Waktu Terbit
                    </label>
                    <input 
                        x-model="published_at" 
                        type="datetime-local" 
                        class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                    />
                </div>

                <!-- Status Penerbitan (Pure Alpine buttons for instantaneous 0-lag switching) -->
                <div class="space-y-1.5 pt-2 border-t border-stone-100 dark:border-emerald-950">
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">
                        Status Visibilitas
                    </label>
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button 
                            @click="status = 'published'"
                            type="button" 
                            :class="status === 'published' ? 'border-[#0D5B3A] bg-emerald-500/10 text-[#0D5B3A] dark:text-emerald-300 shadow-xs' : 'border-stone-200 dark:border-emerald-950 text-stone-600 dark:text-stone-400 hover:border-emerald-500/40'"
                            class="p-3 rounded-2xl border-2 cursor-pointer text-center space-y-1 transition-all"
                        >
                            <div class="font-extrabold text-xs">Terbit Publik</div>
                            <div class="text-[10px] opacity-75">Bisa dibaca umat</div>
                        </button>

                        <button 
                            @click="status = 'draft'"
                            type="button" 
                            :class="status === 'draft' ? 'border-amber-500 bg-amber-500/10 text-amber-900 dark:text-amber-300 shadow-xs' : 'border-stone-200 dark:border-emerald-950 text-stone-600 dark:text-stone-400 hover:border-amber-500/40'"
                            class="p-3 rounded-2xl border-2 cursor-pointer text-center space-y-1 transition-all"
                        >
                            <div class="font-extrabold text-xs">Simpan Draf</div>
                            <div class="text-[10px] opacity-75">Hanya admin</div>
                        </button>
                    </div>
                </div>

                @if ($isEdit)
                    <div class="pt-3 border-t border-stone-100 dark:border-emerald-950 flex items-center justify-between text-xs text-stone-500">
                        <span>Total Pembaca:</span>
                        <strong class="text-stone-800 dark:text-stone-200">{{ number_format($views_count) }} Views</strong>
                    </div>
                @endif

            </div>

            <!-- Card 3: Tips Penulisan Dhamma -->
            <div class="p-5 rounded-3xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200 space-y-2.5">
                <div class="flex items-center gap-2 font-bold text-xs text-amber-800 dark:text-amber-300">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    <span>Panduan Penulisan Warta</span>
                </div>
                <ul class="text-[11.5px] space-y-1 text-stone-600 dark:text-stone-300 leading-relaxed list-disc list-inside">
                    <li>Gunakan tombol <strong>"Syair Pali"</strong> untuk menyisipkan ayat sutta berbingkai rapi.</li>
                    <li>Sertakan narasumber Bhikkhu Sangha atau penceramah Dhamma dengan gelar lengkap.</li>
                    <li>Thumbnail otomatis dikompres ke format WebP hemat kuota.</li>
                </ul>
            </div>

        </div>

    </div>

</div>
