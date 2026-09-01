<?php

use App\Models\Setting;
use App\Services\ImageUploadService;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $about_badge = '';
    public string $about_title = '';
    public string $about_content = '';
    public $about_image = null;
    public ?string $existing_about_image = null;
    public string $about_image_badge = '';
    public string $about_image_quote = '';

    // 3 Nilai Luhur / Pilar
    public string $about_pillar1_title = '';
    public string $about_pillar1_desc = '';
    public string $about_pillar2_title = '';
    public string $about_pillar2_desc = '';
    public string $about_pillar3_title = '';
    public string $about_pillar3_desc = '';

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $this->about_badge = Setting::get('about_badge', 'Tentang Vihara Sāmaggi Gāma');
        $this->about_title = Setting::get('about_title', 'Oase Ketenangan untuk Membina Batin & Menjalin Kerukunan');
        
        $defaultContent = '<p>Nama <strong class="text-[#143D2D] dark:text-emerald-300 font-semibold">Sāmaggi Gāma</strong> berakar dari bahasa Pali yang bermakna <em>"Desa Kerukunan dan Persatuan"</em>. Vihara ini didirikan sebagai wadah spiritual yang terbuka bagi seluruh umat dan masyarakat untuk belajar, mempraktikkan ajaran Buddha, dan menumbuhkan cinta kasih universal.</p><p>Di tengah hiruk pikuk kehidupan modern, kami hadir menyediakan lingkungan yang asri dan sejuk bagi siapa saja yang ingin melatih kesadaran penuh (<em>mindfulness</em>), memperdalam pemahaman Dhamma, serta mempererat tali persaudaraan dalam kebajikan.</p>';
        $this->about_content = Setting::get('about_content', $defaultContent);

        $this->existing_about_image = Setting::get('about_image', 'images/gallery-dhammasala.jpg');
        $this->about_image_badge = Setting::get('about_image_badge', 'Dhammasala Utama');
        $this->about_image_quote = Setting::get('about_image_quote', '“Dalam keheningan batin, terbit kebijaksanaan luhur.”');

        $this->about_pillar1_title = Setting::get('about_pillar1_title', 'Sīla (Kemoralan)');
        $this->about_pillar1_desc = Setting::get('about_pillar1_desc', 'Fondasi perilaku luhur demi ketenteraman diri dan sesama.');

        $this->about_pillar2_title = Setting::get('about_pillar2_title', 'Samādhi (Meditasi)');
        $this->about_pillar2_desc = Setting::get('about_pillar2_desc', 'Pemusatan pikiran & kejernihan kesadaran batin.');

        $this->about_pillar3_title = Setting::get('about_pillar3_title', 'Paññā (Kebijaksanaan)');
        $this->about_pillar3_desc = Setting::get('about_pillar3_desc', 'Pemahaman hakikat kehidupan dalam terang Dhamma.');

        if (session()->has('feedbackMessage')) {
            $this->feedbackMessage = session('feedbackMessage');
        }
    }

    public function removeImage(): void
    {
        $this->about_image = null;
        $this->existing_about_image = null;
    }

    public function save(): void
    {
        $this->validate([
            'about_title' => 'required|string|max:255',
            'about_badge' => 'required|string|max:100',
            'about_content' => 'required|string',
            'about_image' => 'nullable|image|max:10240',
            'about_image_badge' => 'nullable|string|max:100',
            'about_image_quote' => 'nullable|string|max:255',
            'about_pillar1_title' => 'nullable|string|max:100',
            'about_pillar1_desc' => 'nullable|string|max:255',
            'about_pillar2_title' => 'nullable|string|max:100',
            'about_pillar2_desc' => 'nullable|string|max:255',
            'about_pillar3_title' => 'nullable|string|max:100',
            'about_pillar3_desc' => 'nullable|string|max:255',
        ], [
            'about_title.required' => 'Judul Tentang Kami wajib diisi.',
            'about_badge.required' => 'Label badge Tentang Kami wajib diisi.',
            'about_content.required' => 'Isi konten narasi Tentang Kami tidak boleh kosong.',
            'about_image.max' => 'Ukuran gambar maksimal 10MB.',
        ]);

        $imagePath = $this->existing_about_image;
        if ($this->about_image) {
            $imagePath = ImageUploadService::uploadAndCompress($this->about_image, 'uploads/settings');
            ImageUploadService::cleanLivewireTmp();
            $this->existing_about_image = $imagePath;
            $this->about_image = null;
        }

        Setting::set('about_badge', $this->about_badge);
        Setting::set('about_title', $this->about_title);
        Setting::set('about_content', $this->about_content);
        Setting::set('about_image', $imagePath);
        Setting::set('about_image_badge', $this->about_image_badge);
        Setting::set('about_image_quote', $this->about_image_quote);

        Setting::set('about_pillar1_title', $this->about_pillar1_title);
        Setting::set('about_pillar1_desc', $this->about_pillar1_desc);
        Setting::set('about_pillar2_title', $this->about_pillar2_title);
        Setting::set('about_pillar2_desc', $this->about_pillar2_desc);
        Setting::set('about_pillar3_title', $this->about_pillar3_title);
        Setting::set('about_pillar3_desc', $this->about_pillar3_desc);

        $this->feedbackMessage = 'Informasi Halaman Tentang Kami berhasil disimpan dan diperbarui.';
    }

    public function render()
    {
        return $this->view()->title('Kelola Tentang Kami')->layout('layouts::admin');
    }
};
?>

<div 
    x-data="{
        editorMode: 'visual',
        isDragging: false,
        about_title: @entangle('about_title'),
        about_badge: @entangle('about_badge'),
        about_image_badge: @entangle('about_image_badge'),
        about_image_quote: @entangle('about_image_quote'),

        init() {
            this.$nextTick(() => {
                const el = document.getElementById('wysiwyg-about-content');
                if (el && this.$wire.about_content) {
                    el.innerHTML = this.$wire.about_content;
                }
            });
        },

        updateContentFromDiv() {
            const el = document.getElementById('wysiwyg-about-content');
            if (el) {
                this.$wire.set('about_content', el.innerHTML, false);
            }
        },

        syncToDiv() {
            const el = document.getElementById('wysiwyg-about-content');
            if (el) {
                el.innerHTML = this.$wire.about_content || '';
            }
        },

        savedSelection: null,

        saveSelection() {
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                const editor = document.getElementById('wysiwyg-about-content');
                if (editor && editor.contains(range.commonAncestorContainer)) {
                    this.savedSelection = range.cloneRange();
                }
            }
        },

        restoreSelection() {
            const editor = document.getElementById('wysiwyg-about-content');
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

        removeFormat() {
            this.restoreSelection();
            document.execCommand('removeFormat', false, null);
            document.execCommand('unlink', false, null);
            const sel = window.getSelection();
            if (sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                const container = range.commonAncestorContainer;
                const parent = container.nodeType === 3 ? container.parentNode : container;
                if (parent && parent.id !== 'wysiwyg-about-content') {
                    parent.removeAttribute('style');
                    parent.removeAttribute('class');
                }
            }
            this.saveSelection();
            this.updateContentFromDiv();
        },

        insertLink() {
            this.restoreSelection();
            const url = prompt('Masukkan tautan URL (contoh: https://...):');
            if (url) {
                document.execCommand('createLink', false, url);
                this.saveSelection();
                this.updateContentFromDiv();
            }
        },

        countWords() {
            const text = (this.$wire.about_content || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            return text.length > 0 ? text.split(' ').length : 0;
        },

        countChars() {
            return (this.$wire.about_content || '').replace(/<[^>]*>/g, '').length;
        },

        handleDrop(e) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById('about-image-upload');
                if (input) {
                    input.files = files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }
    }"
    class="space-y-6 font-sans"
>

    <!-- =========================================================================
         1. TOP HEADER & ACTION CTA
         ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Kelola Profil Tentang Kami
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Atur judul sambutan, narasi sejarah & visi misi, gambar dhammasala, serta 3 nilai pilar kebaikan.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button 
                wire:click="save" 
                type="button" 
                class="inline-flex items-center gap-2 py-2.5 px-5 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-md border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>Simpan Perubahan</span>
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
         2. MAIN TWO-COLUMN FORM LAYOUT
         ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: TITLE & VISUAL CONTENT EDITOR (8 COLS) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Card: Judul & Sub-judul -->
            <div class="p-6 rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs space-y-4">
                
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">
                        Label Badge Atas <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        wire:model="about_badge" 
                        type="text" 
                        placeholder="cth: Tentang Vihara Sāmaggi Gāma"
                        class="w-full px-4 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-semibold"
                    />
                    @error('about_badge') <span class="text-rose-500 text-[11px] font-bold">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">
                        Judul Utama Tentang Kami <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        wire:model="about_title" 
                        type="text" 
                        placeholder="cth: Oase Ketenangan untuk Membina Batin & Menjalin Kerukunan"
                        class="w-full px-4 py-3 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-sm sm:text-base focus:outline-none focus:border-amber-500 font-black tracking-tight"
                    />
                    @error('about_title') <span class="text-rose-500 text-[11px] font-bold">{{ $message }}</span> @enderror
                </div>

            </div>

            <!-- Card: Rich Visual WYSIWYG Editor -->
            <div class="rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
                
                <!-- Editor Top Header & Mode Switcher -->
                <div class="p-3 sm:p-4 bg-stone-100/70 dark:bg-[#071710] border-b border-stone-200 dark:border-emerald-950 flex flex-wrap items-center justify-between gap-3 text-xs">
                    
                    <div class="flex items-center gap-1 bg-stone-200/80 dark:bg-emerald-950/70 p-1 rounded-xl">
                        <button 
                            @click="editorMode = 'visual'; $nextTick(() => syncToDiv())" 
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
                            <span>Pratinjau Publik</span>
                        </button>
                    </div>

                    <div class="text-[11px] text-stone-400 font-medium">
                        <span x-text="countWords() + ' Kata'"></span> •
                        <span x-text="countChars() + ' Karakter'"></span>
                    </div>
                </div>

                <!-- Formatting Toolbar in Visual Mode -->
                <div x-show="editorMode === 'visual'" class="p-2.5 bg-stone-50 dark:bg-[#081b13] border-b border-stone-200 dark:border-emerald-950/80 flex flex-wrap items-center gap-1 text-xs">
                    
                    <select 
                        @mousedown="saveSelection()"
                        @focus="saveSelection()"
                        @change="formatBlock($event.target.value); $event.target.value = ''"
                        class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#0b1f17] border border-stone-200 dark:border-emerald-900 text-xs font-bold text-stone-800 dark:text-stone-200 focus:outline-none cursor-pointer"
                        title="Tingkat Judul"
                    >
                        <option value="">Gaya Teks...</option>
                        <option value="p">Paragraf Standar</option>
                        <option value="h2">Heading 2 (Judul Bagian)</option>
                        <option value="h3">Heading 3 (Sub-Judul)</option>
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
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h13M7 12h13M7 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                    </button>

                    <div class="h-5 w-px bg-stone-300 dark:bg-emerald-900/60 mx-1"></div>

                    <!-- Insert Link -->
                    <button 
                        @mousedown.prevent=""
                        @click="insertLink()" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Sisipkan Tautan (Link)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </button>

                    <!-- Clear Format -->
                    <button 
                        @mousedown.prevent=""
                        @click="removeFormat()" 
                        type="button" 
                        class="p-2 rounded-lg hover:bg-stone-200 dark:hover:bg-emerald-900/60 text-stone-700 dark:text-stone-200 cursor-pointer"
                        title="Bersihkan Format"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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

                <!-- 1. WYSIWYG Editable Visual Body -->
                <div 
                    wire:ignore
                    x-show="editorMode === 'visual'" 
                    class="p-6 sm:p-8 min-h-[350px] max-h-[600px] overflow-y-auto bg-white dark:bg-[#0b1f17] focus:outline-none leading-relaxed text-stone-800 dark:text-stone-100 text-sm sm:text-base prose dark:prose-invert max-w-none"
                    id="wysiwyg-about-content"
                    contenteditable="true"
                    @keyup="saveSelection(); updateContentFromDiv()"
                    @mouseup="saveSelection()"
                    @selectstart="saveSelection()"
                    @focus="saveSelection()"
                    @blur="saveSelection(); updateContentFromDiv()"
                >{!! $about_content !!}</div>

                <!-- 2. HTML Raw Code View -->
                <div x-show="editorMode === 'html'" x-cloak class="p-4 bg-[#1e1e1e]">
                    <textarea 
                        wire:model="about_content" 
                        rows="14" 
                        class="w-full h-full min-h-[350px] bg-transparent text-emerald-400 text-xs sm:text-sm focus:outline-none resize-y leading-relaxed font-mono"
                        placeholder="<p>Tulis kode HTML narasi Tentang Kami...</p>"
                        @input="syncToDiv()"
                    ></textarea>
                </div>

                <!-- 3. Live Preview Mode -->
                <div x-show="editorMode === 'preview'" x-cloak class="p-6 sm:p-8 min-h-[350px] bg-[#FAF5ED] dark:bg-[#071710] space-y-4">
                    <div class="space-y-2 border-b border-stone-200 dark:border-emerald-950 pb-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950 text-[#0D6E42] dark:text-emerald-300 font-bold text-xs" x-text="about_badge"></span>
                        <h2 class="text-xl sm:text-2xl font-black text-[#143D2D] dark:text-[#E8F3EE]" x-text="about_title"></h2>
                    </div>
                    <div class="prose dark:prose-invert max-w-none text-stone-700 dark:text-stone-300 text-sm sm:text-base leading-relaxed" x-html="$wire.about_content"></div>
                </div>

            </div>

            <!-- Card: 3 Nilai Luhur / Pilar Vihara -->
            <div class="p-6 rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs space-y-4">
                <div class="space-y-0.5 border-b border-stone-100 dark:border-emerald-950 pb-3">
                    <h3 class="text-sm font-extrabold text-stone-900 dark:text-stone-100">
                        3 Nilai Luhur & Pilar Vihara
                    </h3>
                    <p class="text-xs text-stone-500 dark:text-stone-400">
                        Kartu ringkas prinsip spiritual yang tampil di bawah narasi Tentang Kami.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    
                    <!-- Pilar 1 -->
                    <div class="p-4 rounded-2xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950 space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-[#0D6E42] dark:text-emerald-300 font-bold flex items-center justify-center text-xs">1</span>
                            <label class="font-bold text-stone-700 dark:text-stone-300">Pilar Pertama</label>
                        </div>
                        <input 
                            wire:model="about_pillar1_title" 
                            type="text" 
                            placeholder="cth: Sīla (Kemoralan)"
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 font-bold text-xs focus:outline-none"
                        />
                        <textarea 
                            wire:model="about_pillar1_desc" 
                            rows="2"
                            placeholder="Deskripsi singkat..."
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none"
                        ></textarea>
                    </div>

                    <!-- Pilar 2 -->
                    <div class="p-4 rounded-2xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950 space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 font-bold flex items-center justify-center text-xs">2</span>
                            <label class="font-bold text-stone-700 dark:text-stone-300">Pilar Kedua</label>
                        </div>
                        <input 
                            wire:model="about_pillar2_title" 
                            type="text" 
                            placeholder="cth: Samādhi (Meditasi)"
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 font-bold text-xs focus:outline-none"
                        />
                        <textarea 
                            wire:model="about_pillar2_desc" 
                            rows="2"
                            placeholder="Deskripsi singkat..."
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none"
                        ></textarea>
                    </div>

                    <!-- Pilar 3 -->
                    <div class="p-4 rounded-2xl bg-stone-50 dark:bg-[#071710] border border-stone-200 dark:border-emerald-950 space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-[#0D6E42] dark:text-emerald-300 font-bold flex items-center justify-center text-xs">3</span>
                            <label class="font-bold text-stone-700 dark:text-stone-300">Pilar Ketiga</label>
                        </div>
                        <input 
                            wire:model="about_pillar3_title" 
                            type="text" 
                            placeholder="cth: Paññā (Kebijaksanaan)"
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 font-bold text-xs focus:outline-none"
                        />
                        <textarea 
                            wire:model="about_pillar3_desc" 
                            rows="2"
                            placeholder="Deskripsi singkat..."
                            class="w-full px-3 py-2 rounded-xl bg-white dark:bg-[#0b1f17] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none"
                        ></textarea>
                    </div>

                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: IMAGE UPLOAD & VISUAL SETTINGS (4 COLS) -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Card: Gambar Utama Dhammasala / Profil -->
            <div class="p-6 rounded-3xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs space-y-4">
                
                <div class="space-y-0.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">
                        Foto Profil Tentang Kami <span class="text-amber-500">*</span>
                    </h3>
                    <p class="text-[11px] text-stone-400">Ditampilkan di samping narasi pada halaman utama.</p>
                </div>

                <!-- Image Preview -->
                @if ($about_image)
                    <div class="relative aspect-[4/5] rounded-2xl overflow-hidden border-2 border-emerald-500 shadow-md group">
                        <img src="{{ $about_image->temporaryUrl() }}" alt="Preview Foto Tentang Kami" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button 
                                wire:click="removeImage" 
                                type="button" 
                                class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-all cursor-pointer flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Hapus Foto</span>
                            </button>
                        </div>
                    </div>
                @elseif ($existing_about_image)
                    <div class="relative aspect-[4/5] rounded-2xl overflow-hidden border border-stone-200 dark:border-emerald-950 shadow-md group">
                        <img src="{{ asset($existing_about_image) }}" alt="Foto Tentang Kami Saat Ini" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button 
                                wire:click="removeImage" 
                                type="button" 
                                class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-all cursor-pointer flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Ganti Foto</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Drag & Drop Dropzone -->
                <div 
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="isDragging = false; handleDrop($event)"
                    :class="isDragging ? 'border-[#0D5B3A] bg-emerald-50/50 dark:bg-emerald-950/40 scale-[1.02]' : 'border-stone-300 dark:border-emerald-950/80 bg-stone-50/70 dark:bg-[#071710] hover:border-emerald-500'"
                    class="relative border-2 border-dashed rounded-2xl p-6 text-center space-y-3 transition-all duration-200 cursor-pointer"
                >
                    <input 
                        id="about-image-upload"
                        wire:model="about_image" 
                        type="file" 
                        accept="image/png,image/jpeg,image/jpg,image/webp" 
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                    />

                    <div class="w-12 h-12 rounded-2xl bg-[#0D5B3A]/10 text-[#0D5B3A] dark:text-emerald-300 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>

                    <div class="space-y-1">
                        <p class="text-xs font-extrabold text-stone-800 dark:text-stone-200">
                            Tarik & Jatuhkan Foto di Sini
                        </p>
                        <p class="text-[11px] text-stone-400">
                            atau <span class="text-[#0D5B3A] dark:text-emerald-400 font-bold underline">klik untuk memilih berkas</span>
                        </p>
                    </div>

                    <p class="text-[10px] text-stone-400">
                        JPG, PNG, WebP (Maks. 10MB) • Kompresi Otomatis
                    </p>

                    <div wire:loading wire:target="about_image" class="text-amber-500 text-xs font-bold pt-1">
                        Mengunggah & memproses foto...
                    </div>
                </div>

                <!-- Label & Quote Floating Overlay pada Gambar -->
                <div class="pt-3 border-t border-stone-100 dark:border-emerald-950 space-y-3 text-xs">
                    
                    <div class="space-y-1">
                        <label class="block font-bold text-stone-700 dark:text-stone-300">
                            Badge Mengambang di Foto
                        </label>
                        <input 
                            wire:model="about_image_badge" 
                            type="text" 
                            placeholder="cth: Dhammasala Utama"
                            class="w-full px-3.5 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-stone-700 dark:text-stone-300">
                            Kutipan Renungan di Bawah Foto
                        </label>
                        <input 
                            wire:model="about_image_quote" 
                            type="text" 
                            placeholder="cth: “Dalam keheningan batin, terbit kebijaksanaan luhur.”"
                            class="w-full px-3.5 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none italic"
                        />
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
