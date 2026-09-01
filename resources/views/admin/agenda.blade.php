<?php

use App\Models\Schedule;
use App\Services\ImageUploadService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads, WithPagination;

    public string $searchQuery = '';
    public string $categoryFilter = 'all';
    public bool $modalOpen = false;
    public ?int $editingId = null;
    public ?string $feedbackMessage = null;

    // Form inputs
    public string $formTitle = '';
    public string $formCategory = '';
    public string $formEventDate = '';
    public string $formStartTime = '';
    public string $formEndTime = '';
    public string $formScheduleType = '';
    public string $formLocation = '';
    public string $formLeader = '';
    public string $formDescription = '';
    public string $formStatus = 'aktif';
    public $formCoverImage = null;
    public ?string $existingCoverImage = null;
    public array $formActivities = [];

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function setCategoryFilter(string $category): void
    {
        $this->categoryFilter = $category;
        $this->resetPage();
    }

    public function addActivity(): void
    {
        $this->formActivities[] = [
            'date' => $this->formEventDate ?: date('Y-m-d'),
            'time' => '',
            'activity' => '',
            'leader_1' => '',
            'leader_2' => '',
            'speaker' => '',
            'topic' => '',
        ];
    }

    public function removeActivity(int $index): void
    {
        unset($this->formActivities[$index]);
        $this->formActivities = array_values($this->formActivities);
    }

    public function duplicateActivity(int $index): void
    {
        if (isset($this->formActivities[$index])) {
            $clone = $this->formActivities[$index];
            $this->formActivities[] = $clone;
        }
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'editingId',
            'formTitle',
            'formEventDate',
            'formLeader',
            'formDescription',
            'formCoverImage',
            'existingCoverImage',
            'formActivities',
        ]);
        $this->formCategory = '';
        $this->formEventDate = '';
        $this->formStartTime = '';
        $this->formEndTime = '';
        $this->formScheduleType = '';
        $this->formLocation = '';
        $this->formStatus = 'aktif';
        $this->modalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $sch = Schedule::find($id);
        if (!$sch) {
            return;
        }

        $this->editingId = $sch->id;
        $this->formTitle = $sch->title;
        $this->formCategory = $sch->category;
        $this->formEventDate = $sch->event_date ? $sch->event_date->format('Y-m-d') : '';
        $this->formStartTime = substr($sch->start_time, 0, 5);
        $this->formEndTime = $sch->end_time ? substr($sch->end_time, 0, 5) : '';
        $this->formScheduleType = $sch->schedule_type;
        $this->formLocation = $sch->location;
        $this->formLeader = $sch->leader ?? '';
        $this->formDescription = $sch->description ?? '';
        $this->formStatus = $sch->status;
        $this->existingCoverImage = $sch->cover_image;
        $this->formCoverImage = null;
        $this->formActivities = is_array($sch->activities) ? $sch->activities : (is_string($sch->activities) ? (json_decode($sch->activities, true) ?: []) : []);

        $this->modalOpen = true;
    }

    public function saveSchedule(): void
    {
        $this->validate([
            'formTitle' => 'required|string|max:255',
            'formCategory' => 'required|string',
            'formEventDate' => 'required|date',
            'formStartTime' => 'required',
            'formCoverImage' => 'nullable|image|max:10240',
        ]);

        $coverImagePath = $this->existingCoverImage;
        if ($this->formCoverImage) {
            $coverImagePath = ImageUploadService::uploadAndCompress($this->formCoverImage, 'uploads/schedules');
            ImageUploadService::cleanLivewireTmp();
        }

        $slug = Str::slug($this->formTitle);

        // Sanitize activities array
        $cleanedActivities = [];
        foreach ($this->formActivities as $act) {
            if (!empty(trim($act['activity'] ?? '')) || !empty(trim($act['speaker'] ?? '')) || !empty(trim($act['topic'] ?? ''))) {
                $cleanedActivities[] = [
                    'date' => trim($act['date'] ?? '') ?: $this->formEventDate,
                    'time' => trim($act['time'] ?? ''),
                    'activity' => trim($act['activity'] ?? ''),
                    'leader_1' => trim($act['leader_1'] ?? ''),
                    'leader_2' => trim($act['leader_2'] ?? ''),
                    'speaker' => trim($act['speaker'] ?? ''),
                    'topic' => trim($act['topic'] ?? ''),
                ];
            }
        }

        if ($this->editingId) {
            $sch = Schedule::find($this->editingId);
            if ($sch) {
                if ($sch->slug !== $slug && Schedule::where('slug', $slug)->exists()) {
                    $slug .= '-' . time();
                }

                $sch->update([
                    'title' => $this->formTitle,
                    'slug' => $slug,
                    'category' => $this->formCategory,
                    'event_date' => $this->formEventDate,
                    'start_time' => $this->formStartTime,
                    'end_time' => $this->formEndTime ?: null,
                    'schedule_type' => $this->formScheduleType,
                    'location' => $this->formLocation,
                    'leader' => $this->formLeader ?: null,
                    'description' => $this->formDescription ?: null,
                    'activities' => count($cleanedActivities) > 0 ? $cleanedActivities : null,
                    'status' => $this->formStatus,
                    'cover_image' => $coverImagePath,
                ]);

                $this->feedbackMessage = "Jadwal kegiatan '{$this->formTitle}' berhasil diperbarui.";
            }
        } else {
            if (Schedule::where('slug', $slug)->exists()) {
                $slug .= '-' . time();
            }

            Schedule::create([
                'title' => $this->formTitle,
                'slug' => $slug,
                'category' => $this->formCategory,
                'event_date' => $this->formEventDate,
                'start_time' => $this->formStartTime,
                'end_time' => $this->formEndTime ?: null,
                'schedule_type' => $this->formScheduleType,
                'location' => $this->formLocation,
                'leader' => $this->formLeader ?: null,
                'description' => $this->formDescription ?: null,
                'activities' => count($cleanedActivities) > 0 ? $cleanedActivities : null,
                'status' => $this->formStatus,
                'cover_image' => $coverImagePath ?: 'images/kegiatan-chanting.jpg',
            ]);

            $this->feedbackMessage = "Jadwal kegiatan baru '{$this->formTitle}' berhasil ditambahkan.";
        }

        $this->modalOpen = false;
    }

    public function deleteSchedule(int $id): void
    {
        $sch = Schedule::find($id);
        if ($sch) {
            $title = $sch->title;
            ImageUploadService::deleteOldImage($sch->cover_image);
            $sch->delete();
            $this->feedbackMessage = "Jadwal kegiatan '{$title}' berhasil dihapus.";
        }
    }

    public function render()
    {
        $query = Schedule::orderBy('event_date', 'desc');

        if ($this->categoryFilter !== 'all') {
            if ($this->categoryFilter === 'Hari Raya Buddhis') {
                $query->whereIn('category', ['Hari Raya Buddhis', 'Hari Raya']);
            } else {
                $query->where('category', $this->categoryFilter);
            }
        }

        if (!empty($this->searchQuery)) {
            $search = '%' . strtolower($this->searchQuery) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(leader) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(location) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(category) LIKE ?', [$search]);
            });
        }

        $schedules = $query->paginate(15);

        $categoryCounts = [
            'all' => Schedule::count(),
            'Hari Raya Buddhis' => Schedule::whereIn('category', ['Hari Raya Buddhis', 'Hari Raya'])->count(),
            'Hari Uposatha' => Schedule::where('category', 'Hari Uposatha')->count(),
            'Hari Libur Nasional' => Schedule::where('category', 'Hari Libur Nasional')->count(),
            'Puja Bakti' => Schedule::where('category', 'Puja Bakti')->count(),
            'Meditasi & Retreat' => Schedule::where('category', 'Meditasi & Retreat')->count(),
        ];

        return $this->view([
            'schedules' => $schedules,
            'categoryCounts' => $categoryCounts,
        ])->title('Jadwal Kegiatan & Kalender Libur')->layout('layouts::admin');
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
                    Agenda, Hari Libur & Event Khusus
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400 font-normal">
                Kelola perayaan hari suci Buddhis, hari uposatha purnama/tilem, hari libur nasional, seminar, dan event vihara.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button 
                wire:click="openCreateModal"
                type="button" 
                class="inline-flex items-center gap-1.5 py-2.5 px-4 rounded-xl bg-[#0D5B3A] hover:bg-[#0F6B44] text-white font-bold text-xs shadow-xs border border-amber-400/30 transition-all cursor-pointer transform hover:-translate-y-0.5"
            >
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Agenda Baru</span>
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

    <!-- Category Filter Tabs -->
    <div class="flex flex-wrap items-center gap-2 pb-1">
        <button 
            wire:click="setCategoryFilter('all')" 
            type="button" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-all cursor-pointer {{ $categoryFilter === 'all' ? 'bg-[#0D5B3A] text-white shadow-xs' : 'bg-white dark:bg-[#071710] text-stone-600 dark:text-stone-300 hover:bg-stone-100 border border-stone-200/80 dark:border-emerald-950' }}"
        >
            <span>Semua Agenda & Libur</span>
            <span class="ml-1 text-[10px] px-1.5 py-0.2 rounded-full {{ $categoryFilter === 'all' ? 'bg-black/20 text-white' : 'bg-stone-100 dark:bg-emerald-950 text-stone-500' }}">{{ $categoryCounts['all'] }}</span>
        </button>

        <button 
            wire:click="setCategoryFilter('Hari Raya Buddhis')" 
            type="button" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center gap-1.5 {{ $categoryFilter === 'Hari Raya Buddhis' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white dark:bg-[#071710] text-amber-700 dark:text-amber-300 hover:bg-amber-50 border border-stone-200/80 dark:border-emerald-950' }}"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span>Hari Raya Buddhis</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $categoryFilter === 'Hari Raya Buddhis' ? 'bg-black/20 text-white' : 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300' }}">{{ $categoryCounts['Hari Raya Buddhis'] }}</span>
        </button>

        <button 
            wire:click="setCategoryFilter('Hari Uposatha')" 
            type="button" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center gap-1.5 {{ $categoryFilter === 'Hari Uposatha' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white dark:bg-[#071710] text-purple-700 dark:text-purple-300 hover:bg-purple-50 border border-stone-200/80 dark:border-emerald-950' }}"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <span>Hari Uposatha</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $categoryFilter === 'Hari Uposatha' ? 'bg-black/20 text-white' : 'bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300' }}">{{ $categoryCounts['Hari Uposatha'] }}</span>
        </button>

        <button 
            wire:click="setCategoryFilter('Hari Libur Nasional')" 
            type="button" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center gap-1.5 {{ $categoryFilter === 'Hari Libur Nasional' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white dark:bg-[#071710] text-rose-700 dark:text-rose-300 hover:bg-rose-50 border border-stone-200/80 dark:border-emerald-950' }}"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
            <span>Hari Libur Nasional</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $categoryFilter === 'Hari Libur Nasional' ? 'bg-black/20 text-white' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300' }}">{{ $categoryCounts['Hari Libur Nasional'] }}</span>
        </button>

        <button 
            wire:click="setCategoryFilter('Puja Bakti')" 
            type="button" 
            class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-all cursor-pointer flex items-center gap-1.5 {{ $categoryFilter === 'Puja Bakti' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-white dark:bg-[#071710] text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 border border-stone-200/80 dark:border-emerald-950' }}"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Puja Bakti & Event</span>
            <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $categoryFilter === 'Puja Bakti' ? 'bg-black/20 text-white' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' }}">{{ $categoryCounts['Puja Bakti'] }}</span>
        </button>
    </div>

    <!-- =========================================================================
         2. TABEL DATA JADWAL KEGIATAN
         ========================================================================= -->
    <div class="rounded-2xl bg-white dark:bg-[#0b1f17] border border-stone-200/90 dark:border-white/[0.08] shadow-xs overflow-hidden">
        
        <!-- Table Toolbar -->
        <div class="p-5 border-b border-stone-200/80 dark:border-emerald-950 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-stone-900 dark:text-stone-100">
                    Kalender Agenda, Hari Raya & Libur Nasional
                </h2>
                <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                    Daftar kegiatan, perayaan hari suci Buddhis, dan hari libur resmi.
                </p>
            </div>

            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    wire:model.live.debounce.250ms="searchQuery" 
                    type="text" 
                    placeholder="Cari agenda, hari raya, atau lokasi..." 
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-xs text-stone-900 dark:text-stone-100 placeholder-stone-400 focus:outline-none focus:border-[#0D5B3A] focus:ring-1 focus:ring-[#0D5B3A] transition-all shadow-2xs"
                />
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-stone-50/80 dark:bg-[#071710] border-b border-stone-200/80 dark:border-emerald-950 text-[11px] font-bold text-stone-500 dark:text-stone-400 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-4 text-center w-12">No</th>
                        <th scope="col" class="py-3.5 px-5">Agenda & Waktu</th>
                        <th scope="col" class="py-3.5 px-4">Kategori & Lokasi</th>
                        <th scope="col" class="py-3.5 px-4">Pembimbing Dhamma</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                        <th scope="col" class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-emerald-950/60">
                    @forelse ($schedules as $index => $sch)
                        <tr class="hover:bg-stone-50/60 dark:hover:bg-emerald-950/20 transition-colors">
                            
                            <!-- No -->
                            <td class="py-3.5 px-4 text-center font-bold text-stone-400 dark:text-stone-500">
                                {{ $schedules->firstItem() + $index }}
                            </td>

                            <!-- Agenda & Waktu -->
                            <td class="py-3.5 px-5">
                                <div class="flex flex-col min-w-0 max-w-sm">
                                    <span class="font-extrabold text-stone-900 dark:text-stone-100 text-xs sm:text-sm truncate">
                                        {{ $sch->title }}
                                    </span>
                                    <div class="flex flex-wrap items-center gap-2 mt-0.5">
                                        <span class="text-[11px] text-stone-400">
                                            {{ $sch->event_date?->translatedFormat('d M Y') }} • {{ substr($sch->start_time, 0, 5) }}{{ $sch->end_time ? ' - ' . substr($sch->end_time, 0, 5) : '' }}
                                        </span>
                                        @if (!empty($sch->activities) && is_countable($sch->activities) && count($sch->activities) > 0)
                                            <span class="px-2 py-0.2 rounded-full bg-emerald-100 dark:bg-emerald-950 text-[#0D6E42] dark:text-emerald-300 text-[10px] font-bold border border-emerald-500/20">
                                                📅 {{ count($sch->activities) }} Jadwal Sesi
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori & Lokasi -->
                            <td class="py-3.5 px-4">
                                <div class="flex flex-col items-start gap-1">
                                    @php
                                        $catColors = [
                                            'Hari Raya Buddhis' => 'text-amber-800 dark:text-amber-300',
                                            'Hari Raya' => 'text-amber-800 dark:text-amber-300',
                                            'Hari Uposatha' => 'text-purple-800 dark:text-purple-300',
                                            'Hari Libur Nasional' => 'text-rose-800 dark:text-rose-300',
                                            'Puja Bakti' => 'text-emerald-800 dark:text-emerald-300',
                                            'Sekolah Minggu Buddhis' => 'text-sky-800 dark:text-sky-300',
                                            'Meditasi & Retreat' => 'text-teal-800 dark:text-teal-300',
                                            'Bakti Sosial' => 'text-orange-800 dark:text-orange-300',
                                        ];
                                        $badgeStyle = $catColors[$sch->category] ?? 'text-stone-700 dark:text-stone-300';
                                    @endphp
                                    <span class="inline-flex items-center rounded-md text-[10.5px] font-extrabold {{ $badgeStyle }}">
                                        {{ $sch->category }}
                                    </span>
                                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-medium">
                                        {{ $sch->location }}
                                    </span>
                                </div>
                            </td>

                            <!-- Pembimbing -->
                            <td class="py-3.5 px-4 font-bold text-stone-800 dark:text-stone-200">
                                {{ $sch->leader ?: 'Bhikkhu Sangha' }}
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if ($sch->status === 'aktif')
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-300 font-bold text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Aktif</span>
                                    </span>
                                @elseif ($sch->status === 'selesai')
                                    <span class="inline-flex items-center gap-1.5 text-stone-500 font-bold text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>
                                        <span>Selesai</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-rose-600 font-bold text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Dibatalkan</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Edit -->
                                    <button 
                                        wire:click="openEditModal({{ $sch->id }})" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-stone-100 dark:bg-emerald-950/60 hover:bg-stone-200 text-stone-600 dark:text-stone-300 border border-stone-200 dark:border-emerald-500/20 cursor-pointer shadow-2xs"
                                        title="Edit Jadwal"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <!-- Hapus -->
                                    <button 
                                        wire:click="deleteSchedule({{ $sch->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus jadwal agenda ini?" 
                                        type="button" 
                                        class="w-8 h-8 rounded-xl flex items-center justify-center bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 dark:text-rose-400 border border-rose-500/20 cursor-pointer shadow-2xs"
                                        title="Hapus Jadwal"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-stone-400">
                                Belum ada jadwal kegiatan yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($schedules->hasPages() || $schedules->total() > 0)
            <div class="px-5 py-4 bg-stone-50/80 dark:bg-[#071710] border-t border-stone-200/80 dark:border-emerald-950/70">
                {{ $schedules->onEachSide(1)->links('components.custom-pagination') }}
            </div>
        @endif

    </div>

    <!-- =========================================================================
         3. MODAL TAMBAH / EDIT AGENDA (TELEPORTED)
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
            class="fixed inset-0 z-[100] w-screen h-screen min-h-screen bg-black/80 backdrop-blur-xl flex items-center justify-center p-3 sm:p-6 overflow-y-auto"
            @keydown.escape.window="$wire.modalOpen = false"
        >
            <div 
                @click.away="$wire.modalOpen = false" 
                class="relative max-w-3xl w-full bg-white dark:bg-[#0b1f17] rounded-3xl overflow-hidden border border-stone-300/80 dark:border-emerald-500/30 shadow-2xl flex flex-col my-auto max-h-[92vh]"
            >
                <!-- Header -->
                <div class="p-6 bg-gradient-to-r from-[#0B2117] to-[#0F3325] text-white flex items-center justify-between border-b border-emerald-900 shrink-0">
                    <div class="space-y-0.5">
                        <h3 class="text-lg font-extrabold text-[#FAF5ED]">
                            {{ $editingId ? 'Edit Jadwal / Hari Libur' : 'Tambah Agenda / Hari Libur Baru' }}
                        </h3>
                        <p class="text-xs text-stone-300">Isi formulir rincian kegiatan, perayaan hari suci, atau hari libur nasional.</p>
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
                <form wire:submit.prevent="saveSchedule" class="p-6 space-y-4 overflow-y-auto text-xs custom-scrollbar">
                    
                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Nama / Judul Kegiatan atau Hari Libur <span class="text-amber-500">*</span>
                        </label>
                        <input 
                            wire:model="formTitle" 
                            type="text" 
                            required 
                            placeholder="cth: Hari Raya Trisuci Waisak 2570 BE / Tahun Baru Imlek"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 font-bold"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Kategori Acara / Libur <span class="text-amber-500">*</span>
                            </label>
                            <select 
                                wire:model="formCategory" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-semibold"
                            >
                                <option value="Hari Raya Buddhis">Hari Raya Buddhis</option>
                                <option value="Hari Uposatha">Hari Uposatha (Purnama / Tilem)</option>
                                <option value="Hari Libur Nasional">Hari Libur Nasional & Cuti Bersama</option>
                                <option value="Puja Bakti">Puja Bakti & Event Vihara</option>
                                <option value="Sekolah Minggu Buddhis">Sekolah Minggu & Pemuda (SMB)</option>
                                <option value="Meditasi & Retreat">Meditasi & Retreat</option>
                                <option value="Bakti Sosial">Bakti Sosial & Donor Darah</option>
                                <option value="Lainnya">Kegiatan Lainnya</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Tanggal Pelaksanaan Utama <span class="text-amber-500">*</span>
                            </label>
                            <input 
                                wire:model="formEventDate" 
                                type="date" 
                                required 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500 font-bold"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Jam Mulai <span class="text-amber-500">*</span>
                            </label>
                            <input 
                                wire:model="formStartTime" 
                                type="time" 
                                required 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Jam Selesai
                            </label>
                            <input 
                                wire:model="formEndTime" 
                                type="time" 
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Lokasi Ruangan
                            </label>
                            <input 
                                wire:model="formLocation" 
                                type="text" 
                                placeholder="cth: Dhammasala Utama Lt. 2"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Pembimbing / Pengisi Utama
                            </label>
                            <input 
                                wire:model="formLeader" 
                                type="text" 
                                placeholder="cth: Bhante Saddhaviro Thera"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Foto Sampul / Poster
                        </label>
                        <input 
                            wire:model="formCoverImage" 
                            type="file" 
                            accept="image/*"
                            class="w-full text-xs text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-stone-100 dark:file:bg-emerald-950 file:text-stone-700 dark:file:text-stone-300 hover:file:bg-stone-200 cursor-pointer"
                        />
                        <div wire:loading wire:target="formCoverImage" class="text-amber-500 text-[11px] font-semibold">
                            Mengunggah dan mengompresi gambar...
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Deskripsi Acara
                        </label>
                        <textarea 
                            wire:model="formDescription" 
                            rows="2"
                            placeholder="Penjelasan ringkas tata kebaktian atau materi yang dibahas..."
                            class="w-full px-3.5 py-2.5 rounded-xl bg-stone-50 dark:bg-[#071710] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs focus:outline-none focus:border-amber-500"
                        ></textarea>
                    </div>

                    <!-- =========================================================================
                         SECTION: RANGKAIAN JADWAL KEGIATAN BERTANGGAL (JSON ACTIVITIES)
                         ========================================================================= -->
                    <div class="pt-4 border-t border-stone-200 dark:border-emerald-950/80 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <span class="block font-extrabold uppercase tracking-wider text-stone-900 dark:text-stone-100 text-xs flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>Rangkaian Jadwal Kegiatan Bertanggal (Opsional)</span>
                                </span>
                                <p class="text-[11px] text-stone-500 dark:text-stone-400">
                                    Tambahkan sesi kegiatan spesifik (seperti jadwal SPD, Pekan Kathina, atau Waisak) yang memiliki tanggal, petugas, dan penceramah.
                                </p>
                            </div>
                            <button 
                                wire:click="addActivity" 
                                type="button" 
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#0D5B3A] hover:bg-[#09472D] text-white font-bold text-xs shadow-xs transition-all cursor-pointer shrink-0 self-start sm:self-auto"
                            >
                                <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Jadwal Sesi</span>
                            </button>
                        </div>

                        @if (count($formActivities) > 0)
                            <div class="space-y-3 max-h-72 overflow-y-auto custom-scrollbar p-1">
                                @foreach ($formActivities as $index => $act)
                                    <div class="p-3.5 rounded-2xl bg-stone-50/90 dark:bg-[#071710] border border-stone-200 dark:border-emerald-500/20 space-y-2.5 shadow-2xs relative">
                                        <div class="flex items-center justify-between gap-2 border-b border-stone-200/70 dark:border-emerald-950/60 pb-2">
                                            <span class="inline-flex items-center gap-1.5 font-black text-[11px] text-[#0D6E42] dark:text-emerald-300">
                                                <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 flex items-center justify-center text-[10px] border border-emerald-500/30 font-bold">{{ $index + 1 }}</span>
                                                <span>Jadwal Kegiatan #{{ $index + 1 }}</span>
                                            </span>
                                            <div class="flex items-center gap-1.5">
                                                <button 
                                                    wire:click="duplicateActivity({{ $index }})" 
                                                    type="button" 
                                                    class="px-2 py-1 rounded-lg bg-stone-200/70 dark:bg-emerald-950 text-stone-700 dark:text-stone-300 hover:bg-stone-300 text-[10.5px] font-bold transition-colors cursor-pointer"
                                                    title="Duplikat baris ini"
                                                >
                                                    Duplikat
                                                </button>
                                                <button 
                                                    wire:click="removeActivity({{ $index }})" 
                                                    type="button" 
                                                    class="px-2 py-1 rounded-lg bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-200 text-[10.5px] font-bold transition-colors cursor-pointer"
                                                    title="Hapus baris ini"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                            <div class="space-y-1">
                                                <label class="block text-[10px] font-bold text-stone-600 dark:text-stone-400 uppercase">Tanggal</label>
                                                <input 
                                                    wire:model="formActivities.{{ $index }}.date" 
                                                    type="date" 
                                                    class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#0c2218] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs font-semibold"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label class="block text-[10px] font-bold text-stone-600 dark:text-stone-400 uppercase">Waktu / Jam</label>
                                                <input 
                                                    wire:model="formActivities.{{ $index }}.time" 
                                                    type="text" 
                                                    placeholder="cth: 08:30 - 10:30"
                                                    class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#0c2218] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label class="block text-[10px] font-bold text-stone-600 dark:text-stone-400 uppercase">Nama Kegiatan</label>
                                                <input 
                                                    wire:model="formActivities.{{ $index }}.activity" 
                                                    type="text" 
                                                    placeholder="cth: Kebaktian Minggu I SPD"
                                                    class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#0c2218] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs font-bold"
                                                />
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5">
                                            <div class="space-y-1">
                                                <label class="block text-[10px] font-bold text-stone-600 dark:text-stone-400 uppercase">Pemimpin 1</label>
                                                <input 
                                                    wire:model="formActivities.{{ $index }}.leader_1" 
                                                    type="text" 
                                                    placeholder="cth: Upasaka Tan"
                                                    class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#0c2218] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label class="block text-[10px] font-bold text-stone-600 dark:text-stone-400 uppercase">Pemimpin 2</label>
                                                <input 
                                                    wire:model="formActivities.{{ $index }}.leader_2" 
                                                    type="text" 
                                                    placeholder="cth: Upasika Lin"
                                                    class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#0c2218] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label class="block text-[10px] font-bold text-stone-600 dark:text-stone-400 uppercase">Penceramah</label>
                                                <input 
                                                    wire:model="formActivities.{{ $index }}.speaker" 
                                                    type="text" 
                                                    placeholder="cth: Bhante Uttamo"
                                                    class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#0c2218] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs font-bold text-[#0D6E42] dark:text-emerald-400"
                                                />
                                            </div>
                                            <div class="space-y-1">
                                                <label class="block text-[10px] font-bold text-stone-600 dark:text-stone-400 uppercase">Topik Dhamma</label>
                                                <input 
                                                    wire:model="formActivities.{{ $index }}.topic" 
                                                    type="text" 
                                                    placeholder="cth: Menemukan Batin Damai"
                                                    class="w-full px-2.5 py-1.5 rounded-lg bg-white dark:bg-[#0c2218] border border-stone-300 dark:border-emerald-500/25 text-stone-900 dark:text-stone-100 text-xs"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 rounded-2xl bg-stone-50 dark:bg-[#071710] border border-dashed border-stone-300 dark:border-emerald-950 text-center space-y-1">
                                <p class="text-stone-500 dark:text-stone-400 text-xs font-medium">Belum ada rincian jadwal kegiatan bertanggal ditambahkan.</p>
                                <p class="text-stone-400 dark:text-stone-500 text-[11px]">Jika acara ini berupa rangkaian kegiatan multi-tanggal/sesi (seperti SPD atau Waisak), klik tombol <strong>+ Tambah Jadwal Sesi</strong> di atas.</p>
                            </div>
                        @endif
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
                            {{ $editingId ? 'Simpan Perubahan' : 'Simpan Jadwal' }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </template>

</div>
